<?php
// Start the session
session_start();

// Include database connection file
include('db_connection.php');

// Check if the user is logged in and if they are an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    // Redirect to login page if not logged in or not an admin
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

    /* Sidebar styling */
    .sidebar {
        width: 260px;
        background: linear-gradient(145deg, #34495e, #2c3e50);
        color: #ecf0f1;
        padding: 30px 20px;
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
        z-index: 1000;
    }

    .sidebarHeader h2 {
        font-size: 1.8rem;
        font-weight: bold;
        text-align: center;
    }

    .sidebarNav ul {
        list-style: none;
        padding: 0;
    }

    .sidebarNav ul li {
        margin: 1.2rem 0;
    }

    .sidebarNav ul li a {
        text-decoration: none;
        color: #ecf0f1;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        padding: 0.8rem 1rem;
        border-radius: 8px;
        transition: background 0.3s ease;
    }

    .sidebarNav ul li a:hover {
        background-color: #2980b9;
    }

    .sidebarNav ul li a i {
        margin-right: 15px;
    }

    /* Mobile Sidebar */
    .sidebar-toggle {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        font-size: 24px;
        background: none;
        border: none;
        color: black;
        cursor: pointer;
        z-index: 1100;
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 220px;
            transform: translateX(-100%);
            position: fixed;
            transition: transform 0.3s ease;
        }

        .sidebar.open {
            transform: translateX(0);
        }

        .sidebar-toggle {
            display: block;
        }

        .mainContent {
            margin-left: 0;
            width: 100%;
        }
    }

    .mainContent {
    margin-left: 280px;
    padding: 30px;
    width: calc(100% - 280px);
    min-height: 100vh;
    transition: margin-left 0.3s ease;
}

@media (max-width: 768px) {
    .mainContent {
        width: 100%;
        margin-left: 0;
        padding: 20px;
        
    }
}

/* Container for Buttons */
.buttonContainer {
    display: flex;
    flex-direction: column;
    gap: 15px;
    max-width: 300px;
    margin-bottom: 20px;
}

/* Primary Button */
.button1 {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background-color: #27ae60; /* Green */
    color: #ffffff;
    padding: 12px 18px;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: bold;
    text-decoration: none;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

/* Ensuring icon spacing remains consistent */
.button1 i {
    margin-right: 8px;
    font-size: 1.2rem;
}

.button1:hover {
    background-color: #2ecc71; /* Lighter Green */
    transform: translateY(-2px);
}

/* Centering and Adjusting Button Position on Mobile View */
@media (max-width: 768px) {
    .button1 {
        display: flex;
        width: 100%;
        max-width: 280px; /* Limits button width */
        margin: 0 auto; /* Centers the button */
        transform: translateX(30px); /* Moves it slightly to the left */
    }
}

/* Category Button */
.categoryButton {
    width: 100%;
    background-color: #2980b9;
    color: #ffffff;
    padding: 15px;
    border-radius: 8px;
    cursor: pointer;
    text-align: center;
    font-size: 1.2rem;
    border: none;
    transition: background-color 0.3s ease, transform 0.3s ease;
    display: block;
}

.categoryButton:hover {
    background-color: #3498db;
    transform: scale(1.05);
}

/* Category Section */
.categoryContainer {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
}
/* Mobile View: Adjusts alignment & shifts slightly to the right */
@media (max-width: 414px) {
    .categoryContainer {
        grid-template-columns: 1fr;
        margin-left: auto;
        margin-right: auto;
        max-width: 95%; /* Makes sure content doesn’t stretch too much */
    }

    .categoryButton {
        max-width: 80%; /* Keeps button size proportional */
        margin: 0 auto; /* Centers the button */
        display: block;
        transform: translateX(5px); /* Slight right shift */
    }

    .categoryCard {
        display: block;
        transform: translateX(30px); /* Slight right shift */
    }
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

/* Logout Button */
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

</style>

<body>
<!-- Burger Menu -->
<div class="sidebar-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebarHeader">
        <h2>GSL25 IMS</h2>
    </div>
    <nav class="sidebarNav">
        <ul>
                <li><a href="dashboard.php"><i class="fa fa-home"></i> Home</a></li>
                <li><a href="inventory.php"><i class="fa fa-box"></i> Inventory</a></li>
                <li><a href="orders.php"><i class="fas fa-cash-register"></i> Point of Sale (POS)</a></li>
                <li><a href="reports.php"><i class="fa fa-chart-line"></i> Reports</a></li>
                <li><a href="settings.php"><i class="fa fa-cog"></i> Settings</a></li>
        </ul>
    </nav>
    <form action="logout.php" method="POST" class="logout-form">
    <button type="submit" class="logout-button">
        <i class="fas fa-sign-out-alt"></i> Logout
    </button>
</form>
</div>

<!-- Main Content -->
<div class="mainContent">
<div class="buttonContainer">
    <a href="pos.php" class="button1"><i class="fa fa-plus"></i> Add Supply</a>

        <!-- Steel Category -->
        <div class="categoryCard" id="steelCard">
                <button class="categoryButton" onclick="toggleDropdown('steelCard')">Steel</button>
                <div id="steelDropdown" class="categoryDropdown">
                    <?php
                    $sql = "SELECT DISTINCT category FROM tb_inventory WHERE main_category = 'CONSTRUCTION'";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $category = $row['category'];
                            echo "<a href='category.php?category=" . urlencode($category) . "' class='text-gray-800'>" . htmlspecialchars($category) . "</a>";
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
                            echo "<a href='category.php?category=" . urlencode($category) . "' class='text-gray-800'>" . htmlspecialchars($category) . "</a>";
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
    // Toggle the visibility of the Steel and Lumber dropdowns
    document.getElementById('steelButton').addEventListener('click', function() {
        var steelDropdown = document.getElementById('steelDropdown');
        var lumberDropdown = document.getElementById('lumberDropdown');
        
        // Toggle Steel dropdown visibility
        steelDropdown.classList.toggle('hidden');
        
        // Close Lumber dropdown if it's open
        if (!lumberDropdown.classList.contains('hidden')) {
            lumberDropdown.classList.add('hidden');
        }
    });

    document.getElementById('lumberButton').addEventListener('click', function() {
        var lumberDropdown = document.getElementById('lumberDropdown');
        var steelDropdown = document.getElementById('steelDropdown');
        
        // Toggle Lumber dropdown visibility
        lumberDropdown.classList.toggle('hidden');
        
        // Close Steel dropdown if it's open
        if (!steelDropdown.classList.contains('hidden')) {
            steelDropdown.classList.add('hidden');
        }
    });

        function clearSearch() {
            window.location.href = 'inventory.php';
        }
    </script>

 <script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
    }

    function toggleDropdown(id) {
        document.getElementById(id).classList.toggle('active');
    }
</script>

</body>
</html>
