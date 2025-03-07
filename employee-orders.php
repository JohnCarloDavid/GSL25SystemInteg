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
// Initialize variables
$selected_date = '';
$searchQuery = ''; // Variable for the search query

// Check if a date filter has been applied
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['selected_date'])) {
    $selected_date = $_POST['selected_date'];
}

// Check if a search query has been entered
if (isset($_GET['search'])) {
    $searchQuery = $_GET['search'];
}

// Fetch orders from the database based on the selected date or search query
if ($selected_date && $searchQuery) {
    $sql = "SELECT o.*, i.size, i.price FROM tb_orders o 
            JOIN tb_inventory i ON o.product_name = i.name 
            WHERE o.order_date = ? AND o.customer_name LIKE ? 
            ORDER BY o.order_id DESC";
    $stmt = $conn->prepare($sql);
    $searchParam = "%" . $searchQuery . "%";
    $stmt->bind_param('ss', $selected_date, $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif ($selected_date) {
    $sql = "SELECT o.*, i.size, i.price FROM tb_orders o 
            JOIN tb_inventory i ON o.product_name = i.name 
            WHERE o.order_date = ? ORDER BY o.order_id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $selected_date);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif ($searchQuery) {
    $sql = "SELECT o.*, i.size, i.price FROM tb_orders o 
            JOIN tb_inventory i ON o.product_name = i.name 
            WHERE o.customer_name LIKE ? ORDER BY o.order_id DESC";
    $stmt = $conn->prepare($sql);
    $searchParam = "%" . $searchQuery . "%";
    $stmt->bind_param('s', $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Default query if no filters are applied
    $sql = "SELECT o.*, i.size, i.price FROM tb_orders o 
            JOIN tb_inventory i ON o.product_name = i.name 
            ORDER BY o.order_id DESC";
    $result = $conn->query($sql);
}

// Initialize variables for total amount and total quantity
$total_quantity = 0;
$customerTotalAmount = 0; 
$lastCustomerName = ""; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Orders - GSL25 Inventory Management System</title>
    <link rel="icon" href="img/GSL25_transparent 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            color: #2c3e50;
            background-color: #f8f9fa;
            padding-top: 60px; /* Prevent content from being hidden under the fixed header */
        }

        /* Fixed Header */
        .top-bar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 10px 15px;
            z-index: 1000;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        /* Ensure top bar buttons are scrollable if they overflow */
        .top-bar-container {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            gap: 10px;
            padding-bottom: 5px;
        }

        /* Search Form */
        .search-form {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 10px;
            width: 100%;
        }

        .search-input {
            max-width: 250px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        /* Table Styling */
        .ordersTable {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .ordersTable th, .ordersTable td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .ordersTable tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        /* Button Styling */
        .button {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 6px;
            transition: background 0.3s;
        }

        .edit-btn {
            background-color: #3498db;
            color: white;
        }

        .edit-btn:hover {
            background-color: #2980b9;
        }

        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        .clear-button, .search-button, .recently-deleted-btn, .back-button {
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 14px;
        }

        @media (max-width: 768px) {
    .top-bar { 
        display: flex; 
        flex-wrap: nowrap; 
        overflow-x: auto; 
        align-items: center; 
        justify-content: flex-start; 
        gap: 5px; 
        padding: 6px; 
        white-space: nowrap;
        scrollbar-width: none; 
        -ms-overflow-style: none;
        background: #fff; /* Maintain clean UI */
    }
    
    .top-bar::-webkit-scrollbar { display: none; } 

    .top-bar-container, .search-form { 
        display: flex; 
        flex-wrap: nowrap; 
        align-items: center; 
        gap: 5px; 
        min-width: max-content;
    }

    .search-input { 
        width: auto; 
        min-width: 100px; 
        flex: 1; 
        font-size: 14px; 
        padding: 6px;
    }


    /* Small buttons for better spacing */
    .clear-button, .search-button, .recently-deleted-btn, .back-button { 
        flex-shrink: 0; 
        padding: 6px; 
        font-size: 10px;
    }

    /* Table adjustments for mobile */
    .ordersTable { 
        font-size: 12px; 
        width: 100%;
    }

    .ordersSection { 
        overflow-x: auto; 
        padding: 5px;
    }
}
    </style>
</head>
<body>

<div class="top-bar">
    <form action="employee-orders.php" method="GET" class="search-form">
        <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search by customer name" class="search-input p-2 rounded border border-gray-300">
        <button type="submit" class="search-button bg-blue-500 text-white p-2 rounded hover:bg-blue-600 transition"><i class="fa fa-search"></i></button>
        <button type="button" class="clear-button bg-gray-300 p-2 rounded hover:bg-gray-400 transition" onclick="clearSearch()">Clear</button>
        <a href="employee-recently-delete.php" class="recently-deleted-btn bg-red-500 text-white p-2 rounded hover:bg-red-600 transition"><i class="fa fa-undo"></i> Recently Deleted</a>
        <a href="employee_landing.php" class="back-button text-blue-500 hover:text-blue-700 p-2">Back</a>
    </form>
</div>

<!-- Orders Section -->
<div class="mainContent">
    <section class="ordersSection mt-20">
        <div class="overflow-x-auto">
            <?php 
            $lastCustomerName = "";
            $customerTotalAmount = 0;

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) { 
                    $total_price = $row['quantity'] * $row['price'];

                    if ($row['customer_name'] != $lastCustomerName) {
                        if ($lastCustomerName != "") {
                            echo '<tr><td colspan="5"><strong>Total: ' . number_format($customerTotalAmount, 2) . '</strong></td></tr>';
                            echo '<tr><td colspan="5">&nbsp;</td></tr>'; 
                        }

                        $customerTotalAmount = 0;
                        $lastCustomerName = $row['customer_name'];

                        echo '<table class="ordersTable">';
                        echo '<thead>
                                <tr>
                                    <th colspan="5" class="customer-info bg-gray-200">
                                        <strong>Customer: ' . htmlspecialchars($row['customer_name']) . '</strong><br>
                                        <strong>Order Date: ' . htmlspecialchars($row['order_date']) . '</strong>
                                    </th>
                                </tr>
                                <tr class="bg-blue-500 text-white">
                                    <th>Product Name</th>
                                    <th>Size</th>
                                    <th>Quantity</th>
                                    <th>Total Price</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>';
                        echo '<tbody>';
                    }

                    $customerTotalAmount += $total_price;

                    echo '<tr>
                            <td>' . htmlspecialchars($row['product_name']) . '</td>
                            <td>' . htmlspecialchars($row['size']) . '</td>
                            <td>' . htmlspecialchars($row['quantity']) . '</td>
                            <td>' . number_format($total_price, 2) . '</td>
                            <td>
                                <a href="employee-edit-order.php?id=' . htmlspecialchars($row['order_id']) . '" class="button edit-btn"><i class="fa fa-edit"></i></a>
                                <a href="employee-delete-order.php?id=' . htmlspecialchars($row['order_id']) . '" class="button delete-btn" onclick="return confirm(\'Are you sure you want to delete this order?\');"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>';
                }

                echo '<tr><td colspan="5"><strong>Total: ' . number_format($customerTotalAmount, 2) . '</strong></td></tr>';
                echo '</tbody></table>';
            } else {
                echo '<p class="text-center text-gray-600">No orders found.</p>';
            }
            ?>
        </div>
    </section>
</div>

<script>
    function clearSearch() {
        document.querySelector('.search-input').value = '';
        document.querySelector('.search-form').submit();
    }
</script>

</body>
</html>
