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
// Fetch Total Stock
$total_stock_query = "SELECT SUM(quantity) AS total_stock FROM tb_inventory";
$total_stock_result = $conn->query($total_stock_query);
$total_stock = ($total_stock_result->num_rows > 0) ? $total_stock_result->fetch_assoc()['total_stock'] : 0;

// Fetch Total Orders and Total Amount
$total_orders_query = "SELECT COUNT(*) AS total_orders, SUM(o.quantity * i.price) AS total_amount 
                       FROM tb_orders o 
                       JOIN tb_inventory i ON o.product_name = i.name";
$total_orders_result = $conn->query($total_orders_query);

if ($total_orders_result->num_rows > 0) {
    $orders_data = $total_orders_result->fetch_assoc();
    $total_orders = $orders_data['total_orders'];
    $total_amount = $orders_data['total_amount'];
} else {
    $total_orders = 0;
    $total_amount = 0.00;
}

// Fetch Total Categories
$total_categories_query = "SELECT COUNT(DISTINCT category) AS total_categories FROM tb_inventory";
$total_categories_result = $conn->query($total_categories_query);
$total_categories = ($total_categories_result->num_rows > 0) ? $total_categories_result->fetch_assoc()['total_categories'] : 0;

// Fetch Low Stock Items
$low_stock_query = "SELECT COUNT(*) AS low_stock_items FROM tb_inventory WHERE quantity < 15";
$low_stock_result = $conn->query($low_stock_query);
$low_stock_items = ($low_stock_result->num_rows > 0) ? $low_stock_result->fetch_assoc()['low_stock_items'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - GSL25 Inventory Management System</title>
    <link rel="icon" href="img/GSL25_transparent 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
/* Body and general styling */
body {
    font-family: 'Poppins', sans-serif;
    display: flex;
    margin: 0;
    color: #2c3e50;
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
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    transition: background 0.3s ease, transform 0.3s ease;
}

/* Collapsible Sidebar for Mobile */
@media (max-width: 768px) {
    .sidebar {
        width: 220px;
        transform: translateX(-100%);
        position: fixed;
        z-index: 1000;
    }

    .sidebar.open {
        transform: translateX(0);
    }
}

/* Sidebar styling */
.sidebar {
    width: 260px;
    background: linear-gradient(145deg, #34495e, #2c3e50);
    color: #ecf0f1;
    padding: 30px 20px;
    height: 100vh;
    position: fixed;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    transition: background 0.3s ease;
}

.sidebarHeader h2 {
    font-size: 1.8rem;
    font-weight: bold;
    margin-bottom: 1.5rem;
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

@media (max-width: 768px) {
    .sidebar-toggle {
        display: block;
    }
}

/* Burger Menu Button */
.sidebar-toggle {
    display: none;
    position: fixed;
    top: 15px;
    left: 15px;
    font-size: 24px;
    background: none;
    border: none;
    color:black;
    cursor: pointer;
    z-index: 1100;
}

/* Sidebar (default hidden on mobile) */
@media (max-width: 768px) {
    .sidebar-toggle {
        display: block;
    }

    .sidebar {
        width: 220px;
        transform: translateX(-100%);
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        background: linear-gradient(145deg, #34495e, #2c3e50);
        transition: transform 0.3s ease;
        z-index: 1000;
    }

    .sidebar.open {
        transform: translateX(0);
    }

    .mainContent {
        margin-left: 0;
        width: 100%;
        transition: margin-left 0.3s ease;
    }
}
/* Main content styling */
.mainContent {
    margin-left: 280px;
    padding: 30px;
    width: calc(100% - 280px);
    min-height: 100vh;
    transition: margin-left 0.3s ease;
}

/* Adjust for mobile when sidebar is hidden */
@media (max-width: 768px) {
    .mainContent {
        margin-left: 0;
        width: 100%;
    }
}

/* Dashboard Sections */
.dashboardSections {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

/* Ensure sections don't break on small screens */
@media (max-width: 600px) {
    .dashboardSections {
        grid-template-columns: 1fr;
    }
}

/* Quick Actions & Recent Activities */
.quickActions, .recentActivities {
    background: lightblue;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    transition: background 0.3s ease, color 0.3s ease;
    text-align: center;
}

/* Quick Actions Buttons */
.quickActions .buttonGroup {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
}

.quickActions .buttonGroup a {
    background: #3498db;
    padding: 10px 28px;
    border-radius: 12px;
    color: #ffffff;
    font-size: 1rem;
    text-decoration: none;
    text-align: center;
    transition: background 0.3s ease;
}

.quickActions .buttonGroup a:hover {
    background: #2985b3;
}

/* Chart Container */
.chart-container {
    width: 100%; 
    max-width: 1100px; 
    height: 350px; 
    margin: 20px auto; 
    padding: 20px;
    background: #ecf0f1; 
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column; 
    text-align: center;
}

/* Logout Button */
.logout-form {
    margin-top: auto;
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

/* Dashboard Sections */
.dashboardSections {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

/* Stat Card Styling */
.statCard {
    background: #ffffff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
    overflow: hidden;
}

.statCard:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
}

.statCard h3 {
    font-size: 1.2rem;
    color: #34495e;
    margin-bottom: 10px;
    font-weight: 600;
}

/* Icon Design */
.statCard i {
    font-size: 2.5rem;
    margin-bottom: 10px;
}

/* Color Coding for Cards */
.statCard.total-stock {
    border-left: 5px solid #2980b9;
}

.statCard.total-orders {
    border-left: 5px solid #27ae60;
}

.statCard.categories {
    border-left: 5px solid #f39c12;
}

.statCard.low-stock {
    border-left: 5px solid #e74c3c;
}

/* Responsive Grid */
@media (max-width: 768px) {
    .dashboardSections {
        grid-template-columns: 1fr;
    }
}

    </style>
</head>
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

</aside>
    <div class="mainContent">    
        <div class="quickActions">
            <h2>Quick Actions</h2>
            <div class="buttonGroup">
                <a href="add-product.php">Add Product</a>
                <a href="add-order.php">Orders</a>
                <a href="reports.php">Report</a>
            </div>
        </div>
        <div class="chart-container">
            <h3 style="text-align: center; margin-bottom: 15px;">
            Stacked Bar Graph</h3>
            <canvas id="myChart"></canvas>
        </div>
        <div class="dashboardSections">
    <div class="statCard total-stock">
        <i class="fas fa-boxes" style="color: #2980b9;"></i>
        <h3>Total Stock</h3>
        <p><?php echo $total_stock; ?></p>
    </div>

    <div class="statCard total-orders">
        <i class="fas fa-shopping-cart" style="color: #27ae60;"></i>
        <h3>Total Orders</h3>
        <p><?php echo $total_orders; ?></p>
    </div>

    <div class="statCard categories">
        <i class="fas fa-layer-group" style="color: #f39c12;"></i>
        <h3>Categories</h3>
        <p><?php echo $total_categories; ?></p>
    </div>

    <div class="statCard low-stock">
        <i class="fas fa-exclamation-triangle" style="color: #e74c3c;"></i>
        <h3>Low Stock Items</h3>
        <p><?php echo $low_stock_items; ?></p>
    </div>
</div>

        </div>
    <script>
        const ctx = document.getElementById('myChart').getContext('2d');
        const myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Total Stock', 'Total Orders', 'Categories', 'Low Stock',], 
                datasets: [{
                    label: 'Inventory Statistics',
                    data: [
                        <?php echo $total_stock; ?>,
                        <?php echo $total_orders; ?>,
                        <?php echo $total_categories; ?>,
                        <?php echo $low_stock_items; ?>,
                    ],
                    backgroundColor: [
                        'rgba(52, 152, 219, 0.6)', // Blue
                        'rgba(46, 204, 113, 0.6)', // Green
                        'rgba(155, 89, 182, 0.6)', // Purple
                        'rgba(231, 76, 60, 0.6)',  // Red
                    ],
                    borderColor: [
                        'rgba(52, 152, 219, 1)',
                        'rgba(46, 204, 113, 1)',
                        'rgba(155, 89, 182, 1)',
                        'rgba(231, 76, 60, 1)',
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

<script>
        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("open");
        }

        function toggleDropdown(id) {
            document.getElementById(id).classList.toggle("hidden");
        }
    </script>

</body>
</html>
