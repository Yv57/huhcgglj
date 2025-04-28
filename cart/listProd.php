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

// Fetch products with only one main image each
$query = "
    SELECT p.product_id, p.name, p.description, 
           (SELECT image FROM prod_details WHERE product_id = p.product_id AND is_main = 1 LIMIT 1) AS image 
    FROM products p";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product List</title>
    <style>
        body {
            background:linear-gradient(180deg, #F3F0EA, #F8EBDE ,  #E7C4B6, #A3857D);
            font-family: Arial, sans-serif;
            color: #A3857D;
            text-align: center;
            padding: 20px;
        }
        .container {
            max-width: 80%;
            margin: auto;
        }
        h2 {
            color: #A3857D;
            text-shadow: 0px 0px 10px #E7C4B6;
            font-size: 28px;
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .product-card {
            background: rgba(255, 255, 255, 0.3); /* transparen background */
            backdrop-filter: blur(10px); 
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }
        
        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: contain;
            border-radius: 10px;
        }
        .product-card h3 {
            margin: 10px 0;
            font-size: 18px;
            color: #A3857D; 
        }

        .product-card p {
            color: #5E5E5E; 
            font-size: 14px;
        }
        .price {
            font-size: 18px;
            color: #DEA2A0;
            font-weight: bold;
        }
        .view-details {
            display: block;
            background: #DEA2A0;
            color: #F3F0EA;
            padding: 10px;
            margin-top: 10px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
        }
        .view-details:hover {
            background: #E7C4B6;
            box-shadow: 0 0 15px #E7C4B6;
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h2> All Product </h2>
        <div class="product-grid">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="product-card">
                    <?php if ($row['image']): ?>
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($row['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                    <?php else: ?>
                        <img src="placeholder.jpg" alt="No Image">
                    <?php endif; ?>
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    <p><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                    <a href="prod.php?product_id=<?php echo $row['product_id']; ?>" class="view-details">View Details</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>