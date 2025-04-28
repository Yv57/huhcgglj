<?php
session_start();
$host = "localhost";
$username = "root";
$password = "";
$database = "assignment_db";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Assume user_id = 1 (Replace this with session-based login if implemented)
$user_id = 1;

// ✅ Get or Create Shopping Cart
$conn->begin_transaction();
$stmt = $conn->prepare("SELECT cart_id FROM shopping_cart WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows > 0) {
    $cart_id = $result->fetch_assoc()['cart_id'];
} else {
    $stmt = $conn->prepare("INSERT INTO shopping_cart (user_id) VALUES (?)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_id = $stmt->insert_id;
    $stmt->close();
}
$conn->commit();

// ✅ Handle Remove Item
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['remove_id'])) {
    $remove_id = intval($_POST['remove_id']);
    $stmt = $conn->prepare("DELETE FROM cart_detail WHERE cart_detail_id = ? LIMIT 1");
    $stmt->bind_param("i", $remove_id);
    $stmt->execute();
    $stmt->close();

    header("Location: cart.php"); // Prevent form resubmission
    exit();
}

// ✅ Handle Quantity Update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['cart_detail_id'], $_POST['change'])) {
    $cart_detail_id = intval($_POST['cart_detail_id']);
    $change = intval($_POST['change']); // +1 or -1

    // Fetch current quantity
    $stmt = $conn->prepare("SELECT quantity FROM cart_detail WHERE cart_detail_id = ? LIMIT 1");
    $stmt->bind_param("i", $cart_detail_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $new_quantity = max(1, $row['quantity'] + $change);

        $stmt = $conn->prepare("UPDATE cart_detail SET quantity = ? WHERE cart_detail_id = ?");
        $stmt->bind_param("ii", $new_quantity, $cart_detail_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: cart.php");
    exit();
}

// ✅ Handle Add to Cart
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['product_id'], $_GET['product_price_id'])) {
    $product_id = intval($_GET['product_id']);
    $product_price_id = intval($_GET['product_price_id']);
    $quantity = isset($_GET['quantity']) ? max(1, intval($_GET['quantity'])) : 1;

    // ✅ Get price_id and stock for the specific product-price combination
    $stmt = $conn->prepare("SELECT cart_detail_id, quantity 
                            FROM cart_detail 
                            WHERE cart_id = ? AND product_id = ? AND price_id = ? LIMIT 1");

    $stmt->bind_param("ii", $product_id, $product_price_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $price_id = $row['price_id'];
        $stock = $row['stock'];

        if ($quantity > $stock) {
            echo "<script>alert('Not enough stock available!');</script>";
        } else {
            // ✅ Check if product exists in cart
            $stmt = $conn->prepare("SELECT cart_detail_id, quantity FROM cart_detail WHERE cart_id = ? AND product_id = ? AND price_id = ? LIMIT 1");
            $stmt->bind_param("iii", $cart_id, $product_id, $price_id);
            $stmt->execute();
            $check_result = $stmt->get_result();
            $stmt->close();

            if ($check_result->num_rows > 0) {
                // ✅ If product exists, update quantity
                $row = $check_result->fetch_assoc();
                $new_quantity = min($stock, $row['quantity'] + $quantity);

                $stmt = $conn->prepare("UPDATE cart_detail SET quantity = ? WHERE cart_detail_id = ?");
                $stmt->bind_param("ii", $new_quantity, $row['cart_detail_id']);
            } else {
                // ✅ Otherwise, insert a new entry
                $stmt = $conn->prepare("INSERT INTO cart_detail (cart_id, product_id, price_id, quantity) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiii", $cart_id, $product_id, $price_id, $quantity);
            }
            $stmt->execute();
            $stmt->close();
        }
    }
}

// ✅ Get Cart Items
$stmt = $conn->prepare("SELECT cd.cart_detail_id, cd.price_id, p.name, pp.uom, cd.quantity, pp.price 
                        FROM cart_detail cd
                        JOIN products p ON cd.product_id = p.product_id
                        JOIN product_price pp ON cd.price_id = pp.price_id
                        WHERE cd.cart_id = ?");
$stmt->bind_param("i", $cart_id);
$stmt->execute();
$cart_items = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <style>
        body { font-family: Arial, sans-serif; background: #F8F8F8; text-align: center; margin: 20px; }
        .cart-container { max-width: 600px; margin: auto; background: white; padding: 20px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); border-radius: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: center; border: 1px solid #ddd; }
        .btn { padding: 10px 15px; margin-top: 10px; background: #28A745; color: white; border-radius: 5px; text-decoration: none; }
        .btn:hover { background: #218838; }
        .remove-btn { background: red; color: white; border: none; padding: 5px 10px; cursor: pointer; }
        .remove-btn:hover { background: darkred; }
        .qty-btn { padding: 5px 10px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>

<div class="cart-container">
    <h2>🛒 Your Shopping Cart</h2>

    <?php if ($cart_items->num_rows > 0): ?>
        <table>
            <tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th><th>Action</th></tr>
            <?php $total_price = 0; while ($row = $cart_items->fetch_assoc()): 
                $subtotal = $row['price'] * $row['quantity'];
                $total_price += $subtotal;
            ?>
                <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td>RM <?= number_format($row['price'], 2) ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="cart_detail_id" value="<?= $row['cart_detail_id'] ?>">
                            <button type="submit" name="change" value="-1" class="qty-btn">-</button>
                            <?= $row['quantity'] ?>
                            <button type="submit" name="change" value="1" class="qty-btn">+</button>
                        </form>
                    </td>
                    <td>RM <?= number_format($subtotal, 2) ?></td>
                    <td>
                        <form method="POST">
                            <button type="submit" class="remove-btn" name="remove_id" value="<?= $row['cart_detail_id'] ?>">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
        <h3>Total: RM <?= number_format($total_price, 2) ?></h3>
        <a href="checkout.php" class="btn">Proceed to Checkout</a>
    <?php else: ?>
        <p>Your cart is empty.</p>
    <?php endif; ?>
</div>

</body>
</html>

<?php $conn->close(); ?>