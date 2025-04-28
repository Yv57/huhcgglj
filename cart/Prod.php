<?php
// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "assignment_db";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the selected product_id
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// Fetch product details
$product_query = "SELECT * FROM products WHERE product_id = ?";
$stmt = $conn->prepare($product_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product_result = $stmt->get_result();
$product = $product_result->fetch_assoc();

// Fetch product prices
$price_query = "SELECT price, uom FROM product_price WHERE product_id = ?";
$stmt = $conn->prepare($price_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$price_result = $stmt->get_result();
$prices = [];
while ($row = $price_result->fetch_assoc()) {
    $prices[$row['uom']] = $row['price'];
}

// Fetch product images from database
$image_query = "SELECT image FROM prod_details WHERE product_id = ?";
$stmt = $conn->prepare($image_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$image_result = $stmt->get_result();

$db_images = [];
while ($image_row = $image_result->fetch_assoc()) {
    $db_images[] = base64_encode($image_row['image']);
}

// Fetch additional images from folder
$product_name = $product['name'] ?? 'default';
$product_name_sanitized = preg_replace("/[^A-Za-z0-9]/", "_", $product_name);
$image_dir = "product_images/";
$image_files = glob($image_dir . $product_name_sanitized . "_*.{jpg,png,gif}", GLOB_BRACE);

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?></title>
    <style>
        body {
            background: linear-gradient(180deg, #F3F0EA, #F8EBDE, #E7C4B6, #E7C4B6, #F8EBDE, #F3F0EA);
            font-family: Arial, sans-serif;
            text-align: center;
            color: #A3857D;
            padding: 0;
            margin-top: 50px;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            background:#F3F0EA;
            backdrop-filter: blur(10px);
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: row;
        }
        .thumbnails {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-right: 20px;
        }
        .thumbnails img, .main-image {
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
            border: 2px solid transparent;
        }
        .thumbnails img {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }
        .thumbnails img:hover {
            border-color: #DEA2A0;
        }
        .main-image {
            width: 400px;
            height: 400px;
            object-fit: fill;
            margin-left: 40px;
        }
        .product-details {
            flex: 1;
            text-align: left;
            padding: 50px 20px;
        }
        h2 {
            color: #A3857D;
            text-shadow: 0px 0px 10px #E7C4B6;
            font-size: 30px;
        }
        .price {
            font-size: 25px;
            color: #DE4A4A;
            font-weight: bold;
        }
        #priceButtons {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
            transition: 0.3s;
        }
        .whole-box-btn {
            background: black;
            color: white;
        }
        .buy-1-btn {
            background: #E91E63;
            color: white;
        }
        .whole-box-btn:hover, .buy-1-btn:hover {
            background: #444;
        }
        .cart-btn {
            background: black;
            color: white;
            display: none;
            margin-top: 10px;
        }
        .cart-btn:hover {
            background: #444;
        }
        .quantity-btn {
            padding: 10px;
            font-size: 16px;
            cursor: pointer;
            margin: 0 5px;
        }
        .additional-images {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            margin: 0;
            padding: 0;
        }
        .additional-images img {
            width: 100%;
            max-width: 1000px;
            height: auto;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="thumbnails">
            <?php foreach ($db_images as $image): ?>
                <img src="data:image/jpeg;base64,<?php echo $image; ?>" 
                     onclick="changeImage('data:image/jpeg;base64,<?php echo $image; ?>')">
            <?php endforeach; ?>
        </div>

        <div>
            <img id="mainImage" class="main-image" 
                 src="<?php echo !empty($db_images) ? 'data:image/jpeg;base64,'.$db_images[0] : 'default.jpg'; ?>" 
                 alt="Product Image">
        </div>

        <div class="product-details">
            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
            <p class="description"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            <p id="selectedPrice" class="price" style="display: none;"></p>

            <div id="priceButtons">
                <?php foreach ($prices as $uom => $price): ?>
                    <button class="btn price-btn <?php echo ($uom === 'box') ? 'whole-box-btn' : 'buy-1-btn'; ?>" 
                    onclick="selectOption('<?php echo $uom; ?>', <?php echo $price; ?>)">
                    Buy <?php echo ucfirst($uom); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            
            <a href="#" class="btn cart-btn" id="addToCart">Add to Cart</a>
        </div>
    </div>

    <div class="additional-images">
        <?php foreach ($image_files as $image): ?>
            <img src="<?php echo htmlspecialchars($image); ?>">
        <?php endforeach; ?>
    </div>

    <script>
        function changeImage(imageSrc) {
            document.getElementById("mainImage").src = imageSrc;
        }

        function selectOption(option, price) {
            document.getElementById("selectedPrice").style.display = "block";
            document.getElementById("selectedPrice").innerText = `Price: RM ${price.toFixed(2)}`;
            document.getElementById("addToCart").style.display = "inline-block";
            document.getElementById("addToCart").href = `cart.php?product_id=<?php echo $product_id; ?>&product_price_id=${priceId}&quantity=1`;

        }
    </script>
</body>
</html>
