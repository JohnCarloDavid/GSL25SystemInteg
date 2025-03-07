<?php
// Start the session
session_start();

// Include database connection file
include('db_connection.php');

// Check if the user is logged in and if they are an employee
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'employee') {
    // Redirect to login page if not logged in or not an employee
    header("Location: login.php");
    exit();
}

// Initialize the search term
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query to select all rows from the tb_inventory table and group by category
$sql = "SELECT category, GROUP_CONCAT(product_id, '::', name, '::', quantity SEPARATOR ';;') AS products, 
               SUM(quantity) AS total_quantity
        FROM tb_inventory";
if (!empty($search)) {
    $sql .= " WHERE name LIKE '%$search%' OR category LIKE '%$search%' OR product_id LIKE '%$search%'";
}
$sql .= " GROUP BY category";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - GSL25 Inventory Management System</title>
    <link rel="icon" href="img/GSL25_transparent 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            color: #2c3e50;
            background-color: #ecf0f1;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .mainContent {
            margin-left: 280px;
            padding: 30px;
            width: calc(100% - 280px);
            min-height: 100vh;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Header Styling */
        .mainHeader {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .mainHeader h1 {
            font-size: 2.5rem; /* Increased font size */
            font-weight: 700;
            color: #2980b9; /* A more vibrant color */
            margin: 0;
            text-align: center;
            letter-spacing: 1px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2980b9; /* Adding a bottom border */
        }

        /* Back Button Styling */
        .backButton {
            background-color: #2980b9;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 1rem;
            text-decoration: none;
            margin-bottom: 20px;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .backButton:hover {
            background-color: #3498db;
        }

        .categoryButton {
            width: 100%;
            background-color: #2980b9;
            color: #ffffff;
            padding: 15px;
            margin-top: 10px;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            font-size: 1.2rem;
            border: none;
            outline: none;
            transition: background-color 0.3s ease, transform 0.3s ease;
            display: block;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .categoryButton:hover {
            background-color: #3498db;
            transform: scale(1.05);
        }

        .categoryContainer {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .categoryCard {
            background-color: #ffffff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .categoryCard:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .low-stock {
            background-color: red;
            color: white;
        }

        .logout-button {
            background-color: #e74c3c;
            color: #ffffff;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
            margin-top: 10px;
        }

        .logout-button i {
            margin-right: 8px;
            font-size: 1.2rem;
        }

        .logout-button:hover {
            background-color: #c0392b;
        }

        .categoryDropdown {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 10px;
            padding: 10px;
            display: none;
        }

        .categoryDropdown a {
            padding: 8px 12px;
            color: #34495e;
            text-decoration: none;
            display: block;
            border-radius: 6px;
            margin: 5px 0;
        }

        .categoryDropdown a:hover {
            background-color: #f1f1f1;
        }

        .categoryCard.active .categoryDropdown {
            display: block;
        }

        @media (max-width: 768px) {
    .mainContent {
        margin-left: 0;
        padding: 15px;
        width: 100%;
    }

    .mainHeader h1 {
        font-size: 2rem;
        text-align: center;
        padding-bottom: 8px;
    }

    .backButton {
        width: 100%;
        text-align: center;
        padding: 8px;
        font-size: 0.9rem;
    }

    .categoryContainer {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .categoryCard {
        width: 100%;
        padding: 12px;
    }

    .categoryButton {
        font-size: 1rem;
        padding: 12px;
    }

    .categoryDropdown a {
        font-size: 0.9rem;
        padding: 10px;
    }

    .logout-button {
        font-size: 1rem;
        padding: 8px;
    }

    .fa-arrow-left {
        font-size: 14px;
    }
}

    </style>
</head>
<body>
    
    <div class="mainContent">
        <a href="employee_landing.php" class="backButton"><i class="fa fa-arrow-left"></i> Back to Employee Dashboard</a>
        
        <div class="mainHeader">
            <h1>Inventory</h1>
        </div>

        <div class="categoryContainer">
            <!-- Example Category Cards -->
            <div class="categoryCard" id="steelCard">
                <button class="categoryButton" onclick="toggleDropdown('steelCard')">Steel</button>
                <div id="steelDropdown" class="categoryDropdown">
                    <?php
                    // Display categories for employee user
                    $sql = "SELECT DISTINCT category FROM tb_inventory WHERE main_category = 'CONSTRUCTION'";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $category = $row['category'];
                            echo "<a href='employee-category.php?category=" . urlencode($category) . "' class='text-gray-800'>" . htmlspecialchars($category) . "</a>";
                        }
                    } else {
                        echo "<p>No products found in Steel.</p>";
                    }
                    ?>
                </div>
            </div>

            <!-- Lumber Category -->
            <div class="categoryCard" id="lumberCard">
                <button class="categoryButton" onclick="toggleDropdown('lumberCard')">Lumber</button>
                <div id="lumberDropdown" class="categoryDropdown">
                    <?php
                    $sql = "SELECT DISTINCT category FROM tb_inventory WHERE main_category = 'ELECTRICAL'";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $category = $row['category'];
                            echo "<a href='employee-category.php?category=" . urlencode($category) . "' class='text-gray-800'>" . htmlspecialchars($category) . "</a>";
                        }
                    } else {
                        echo "<p>No products found in Lumber.</p>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleDropdown(cardId) {
            const card = document.getElementById(cardId);
            card.classList.toggle("active");
        }
    </script>
</body>
</html>
