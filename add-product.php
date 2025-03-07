<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    // Redirect to login page if not logged in or not an admin
    header("Location: login.php");
    exit();
}
if (isset($_POST['submit'])) {
    include('db_connection.php');

    // Capture the form data and convert to uppercase where needed
    $main_category = strtoupper($_POST['main_category']);
    $name = strtoupper($_POST['name']);
    $category = strtoupper($_POST['category']);
    $quantity = $_POST['quantity'];
    $size = $_POST['size'];
    $price = $_POST['price'];

    // Handle the uploaded image file
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["image"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $uploadOk = true;

   // Check if the user is logged in and if they are an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    // Redirect to login page if not logged in or not an admin
    header("Location: login.php");
    exit();
}

    // Validate the uploaded file type
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check === false) {
        $message = "<p class='message error'>File is not an image.</p>";
        $uploadOk = false;
    }

    // Check file extension
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        $message = "<p class='message error'>Sorry, only JPG, JPEG, PNG & GIF files are allowed.</p>";
        $uploadOk = false;
    }

    // Upload the file if validation passes
    if ($uploadOk && move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        // Prepare the SQL query to insert the data into the database
        $sql = "INSERT INTO tb_inventory (main_category, name, category, quantity, size, price, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssiss', $main_category, $name, $category, $quantity, $size, $price, $target_file);

        // Execute the query and handle success/error
        if ($stmt->execute()) {
            header("Location: inventory.php?message=success");
            exit;
        } else {
            $message = "<p class='message error'>Error: " . $stmt->error . "</p>";
        }

        $stmt->close();
    } else {
        $message = "<p class='message error'>Error uploading image.</p>";
    }

    // Close the database connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - GSL25 Inventory Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <link rel="icon" href="img/GSL25_transparent 2.png">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f9f9f9;
            margin: 0;
            padding: 0;
            color: #333;
            transition: background-color 0.3s, color 0.3s;
        }
    </style>
    <script>
        function toUpperCaseInput(event) {
            event.target.value = event.target.value.toUpperCase();
        }

        function updateCategoryOptions() {
            const mainCategory = document.getElementById('main_category').value;
            const categorySelect = document.getElementById('category');
            categorySelect.innerHTML = '';

            let options = [];

            if (mainCategory === 'CONSTRUCTION') {
                options = ['STEEL', 'GALVANIZED', 'ROOFING'];
            } else if (mainCategory === 'ELECTRICAL') {
                options = ['WIRING', 'LIGHTING', 'CABLE'];
            }

            options.forEach(option => {
                const optElement = document.createElement('option');
                optElement.value = option;
                optElement.textContent = option;
                categorySelect.appendChild(optElement);
            });
        }
    </script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md p-6 bg-white shadow-lg rounded-lg mx-4">
        <h1 class="text-xl font-bold mb-4 text-center">Add New Product</h1>

        <?php if (isset($message)) { echo "<div class='text-center p-2 mb-4 rounded-md text-white " . ($message ? 'bg-green-500' : 'bg-red-500') . "'>$message</div>"; } ?>

        <form action="add-product.php" method="post" enctype="multipart/form-data" class="space-y-4">
            <label class="block font-semibold text-sm">Main Category:</label>
            <select id="main_category" name="main_category" required onchange="updateCategoryOptions()" class="w-full p-3 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Select Main Category</option>
                <option value="CONSTRUCTION">CONSTRUCTION</option>
                <option value="ELECTRICAL">ELECTRICAL</option>
            </select>

            <label class="block font-semibold text-sm">Name:</label>
            <input type="text" id="name" name="name" required oninput="toUpperCaseInput(event)" class="w-full p-3 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">

            <label class="block font-semibold text-sm">Category:</label>
            <select id="category" name="category" required class="w-full p-3 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Select a Category</option>
            </select>

            <label class="block font-semibold text-sm">Size:</label>
            <input type="text" id="size" name="size" required class="w-full p-3 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">

            <label class="block font-semibold text-sm">Quantity:</label>
            <input type="number" id="quantity" name="quantity" required class="w-full p-3 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">

            <label class="block font-semibold text-sm">Price:</label>
            <input type="number" id="price" name="price" step="0.01" required class="w-full p-3 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">

            <label class="block font-semibold text-sm">Image:</label>
            <input type="file" id="image" name="image" accept="image/*" required class="w-full p-3 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">

            <div class="flex flex-col sm:flex-row sm:space-x-4 space-y-3 sm:space-y-0">
                <input type="submit" name="submit" value="Add Product" class="bg-blue-600 text-white px-4 py-3 rounded-md text-center w-full hover:bg-blue-700 cursor-pointer">
                <a href="inventory.php" class="bg-gray-500 text-white px-4 py-3 rounded-md text-center w-full hover:bg-gray-700">Back to Inventory</a>
            </div>
        </form>
    </div>
</body>
</html>
