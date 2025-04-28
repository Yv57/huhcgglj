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

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $stock = intval($_POST['stock']);
    $main_image_index = intval($_POST['main_image_index']); // Get main image index

    // Insert product details
    $stmt = $conn->prepare("INSERT INTO products (name, description, stock) VALUES (?, ?, ?)");
    
    if (!$stmt) {
        die("Error preparing product query: " . $conn->error); // Debugging line
    }

    $stmt->bind_param("ssi", $name, $description, $stock);

    if ($stmt->execute()) {
        $product_id = $stmt->insert_id; // Get last inserted product ID

        // Process and insert multiple images
        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] == 0) {
                    $imageData = file_get_contents($tmp_name); // Read image file
                    $is_main = ($key == $main_image_index) ? 1 : 0; // Set main image

                    // Insert image into database
                    $imgStmt = $conn->prepare("INSERT INTO prod_details (product_id, image, is_main) VALUES (?, ?, ?)");

                    if (!$imgStmt) {
                        die("Error preparing image query: " . $conn->error); // Debugging line
                    }

                    $imgStmt->bind_param("isi", $product_id, $imageData, $is_main);
                    $imgStmt->send_long_data(1, $imageData);
                    $imgStmt->execute();
                    $imgStmt->close();
                }
            }
        }

        echo "<p style='color: green;'>Product and images added successfully!</p>";
    } else {
        echo "<p style='color: red;'>Error: " . $stmt->error . "</p>";
    }

    $stmt->close();
}
$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
</head>
<body>
    <h2>Add Product</h2>
    <form action="addProd.php" method="post" enctype="multipart/form-data">
        <label>Name:</label><br>
        <input type="text" name="name" required><br><br>

        <label>Description:</label><br>
        <textarea name="description"></textarea><br><br>

        <label>Stock:</label><br>
        <input type="number" name="stock" required><br><br>

        <label>Images:</label><br>
        <input type="file" name="images[]" accept="image/*" multiple required><br><br>

        <label>Select Main Image:</label>
    <select name="main_image_index">
        <option value="0">First Image</option>
        <option value="1">Second Image</option>
        <option value="2">Third Image</option>
    </select><br>
    
        <input type="submit" value="Add Product">
    </form>
</body>
</html>
