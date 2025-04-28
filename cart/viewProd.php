<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "assignment_db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch products
$sql = "SELECT product_id, name, description, price, stock, image FROM products";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>Description</th><th>Price</th><th>Stock</th><th>Image</th></tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['product_id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['description'] . "</td>";
        echo "<td>" . $row['price'] . "</td>";
        echo "<td>" . $row['stock'] . "</td>";
        echo "<td><img src='data:image/jpeg;base64," . base64_encode($row['image']) . "' width='100'></td>";
        echo "</tr>";
    }

    echo "</table>";
} else {
    echo "No products found.";
}

$conn->close();
?>
