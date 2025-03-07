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

// Initialize variables for search
$searchName = '';
$searchDate = '';
$searchMonth = '';  // New variable for month filter

// Check if the search form is submitted
if (isset($_POST['searchName'])) {
    $searchName = $_POST['searchName'];
}

if (isset($_POST['searchDate'])) {
    $searchDate = $_POST['searchDate'];
}

if (isset($_POST['searchMonth'])) {  // Capture selected month
    $searchMonth = $_POST['searchMonth'];
}

// Base query to select all orders along with size and price from inventory
$sql = "SELECT o.customer_name, o.order_date, o.product_name, o.quantity, i.size, i.price 
        FROM tb_orders o 
        JOIN tb_inventory i ON o.product_name = i.name";

// Array to hold WHERE conditions
$whereClauses = [];

// If search name is provided, add it to the WHERE clause
if (!empty($searchName)) {
    $whereClauses[] = "o.customer_name LIKE '%" . $conn->real_escape_string($searchName) . "%'";
}

// If search date is provided, add it to the WHERE clause
if (!empty($searchDate)) {
    $whereClauses[] = "DATE(o.order_date) = '" . $conn->real_escape_string($searchDate) . "'";
}

// If search month is provided, filter by the month and year
if (!empty($searchMonth)) {
    $whereClauses[] = "MONTH(o.order_date) = '" . $conn->real_escape_string($searchMonth) . "'";
}

// Apply WHERE conditions if any
if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(' AND ', $whereClauses);
}

// Sort by order date in descending order so the most recent order is first
$sql .= " ORDER BY o.order_date DESC, o.customer_name ASC";

// Execute the query
$result = $conn->query($sql);

if (!$result) {
    die("Error executing query: " . $conn->error);
}

// Create an array to hold the customer order data
$customerOrders = [];

// Group orders by customer
while ($row = $result->fetch_assoc()) {
    $customerOrders[$row['customer_name']][] = $row;
}

// Initialize totals
$totalOrders = 0;
$totalQuantity = 0;
$totalAmount = 0;
$monthlyTotals = [];  // Array to hold monthly totals

// Calculate totals and group by month
foreach ($customerOrders as $customerName => $orders) {
    foreach ($orders as $order) {
        $totalOrders++;
        $totalQuantity += $order['quantity'];
        $totalAmount += $order['quantity'] * $order['price'];

        // Extract month and year for monthly totals
        $orderMonthYear = date('Y-m', strtotime($order['order_date']));
        if (!isset($monthlyTotals[$orderMonthYear])) {
            $monthlyTotals[$orderMonthYear] = [
                'quantity' => 0,
                'amount' => 0,
                'orders' => 0
            ];
        }
        $monthlyTotals[$orderMonthYear]['quantity'] += $order['quantity'];
        $monthlyTotals[$orderMonthYear]['amount'] += $order['quantity'] * $order['price'];
        $monthlyTotals[$orderMonthYear]['orders']++;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - GSL25 Inventory Management System</title>
    <link rel="icon" href="img/GSL25_transparent 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
      /* General Styles */
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    color: #2c3e50;
    background-color: #f9f9f9;
    transition: background-color 0.3s ease;
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
/* Main Content Styling */
.mainContent {
    margin-left: 280px;
    padding: 30px;
    width: calc(100% - 280px);
    transition: margin-left 0.3s ease;
}

.mainHeader h1 {
    font-size: 2.5rem;
    margin-bottom: 1.5rem;
    text-align: center;
    color: #34495e;
}

/* Tablets & iPads (max-width: 1024px) */
@media (max-width: 1024px) {
    .mainContent {
        margin-left: 0; /* Remove sidebar margin */
        padding: 20px;
        width: 100%;
    }

    .totalsSection {
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
        padding: 20px;
    }

    .totalsSection div {
        flex: 1 1 45%; /* Make totals half-width on larger tablets */
        text-align: center;
    }

    .ordersTable th, .ordersTable td {
        font-size: 1rem;
        padding: 12px;
    }

    .orderDetails {
        flex-wrap: wrap;
        justify-content: center;
        text-align: center;
        gap: 15px;
    }

    .orderDetails h3, .orderDetails p {
        font-size: 1.3rem;
    }

    .orderDetails button {
        width: auto;
        padding: 10px 15px;
    }

    .searchSection input, 
    .searchSection select, 
    .searchSection button {
        font-size: 1rem;
        padding: 12px;
    }
}

/* Tablets (max-width: 768px) */
@media (max-width: 768px) {
    .mainContent {
        padding: 20px;
    }

    .mainHeader h1 {
        font-size: 2.2rem;
    }

    .totalsSection {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 12px;
        padding: 18px;
    }

    .orderDetails {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 10px;
    }

    .orderDetails h3, .orderDetails p {
        font-size: 1.2rem;
    }

    .orderDetails button {
        width: 100%;
    }

    .ordersTable {
        overflow-x: auto;
    }

    .ordersTable th, .ordersTable td {
        font-size: 0.95rem;
        padding: 10px;
    }

    .searchSection input,
    .searchSection select,
    .searchSection button {
        width: 100%;
        font-size: 1rem;
        padding: 12px;
    }

    button {
        width: 100%;
        padding: 12px;
        font-size: 1rem;
    }
}

/* Mobile (max-width: 480px) */
@media (max-width: 480px) {
    .mainContent {
        margin-left: 0;
        padding: 15px;
        width: 100%;
    }

    .mainHeader h1 {
        font-size: 2rem;
        margin-bottom: 1rem;
        text-align: center;
    }

    .totalsSection {
        flex-direction: column;
        gap: 10px;
        padding: 15px;
        text-align: center;
    }

    .orderDetails {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 10px;
    }

    .orderDetails h3,
    .orderDetails p {
        font-size: 1.2rem;
    }

    .orderDetails button {
        width: 100%;
        margin: 10px 0;
    }

    .ordersTable {
        display: block;
        overflow-x: auto;
        width: 100%;
    }

    .ordersTable th,
    .ordersTable td {
        font-size: 0.9rem;
        padding: 10px;
    }

    .searchSection {
        margin-top: 20px;
        padding: 10px;
    }

    .searchSection form {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }

    .searchSection input,
    .searchSection select,
    .searchSection button {
        width: 100%;
        padding: 12px;
        font-size: 1rem;
    }

    button {
        width: 100%;
        padding: 12px;
        font-size: 1rem;
    }

    .logout-button {
        width: 100%;
        padding: 12px;
    }

    table {
        width: 100%;
    }

    th, td {
        padding: 10px;
        font-size: 0.9rem;
    }
}

/* Printing Media Query */
@media print {
    @page {
        size: 80mm 80mm;
        margin: 0;
    }

    body {
        font-family: Arial, sans-serif;
        font-size: 10px;
    }

    .receipt-style {
        width: 80mm;
        margin: 0;
        padding: 5mm;
    }

    table {
        width: 100%;
    }

    th, td {
        padding: 5px;
        text-align: left;
        border: 1px solid #000;
    }

    th {
        background-color: #f1f1f1;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    button {
        display: none;
    }
}

@media (max-width: 480px) {
    .searchSection {
        margin-top: 20px; /* Moves the entire section lower */
    }

    .searchSection form {
        flex-direction: column;
        align-items: stretch;
    }

    .searchSection input,
    .searchSection select,
    .searchSection button {
        width: 100%;
        margin-bottom: 10px;
    }

    .searchSection button {
        padding: 12px;
        font-size: 1rem;
    }
}
#monthlyReportChart {
    width: 100% !important;
    height: auto !important;
    max-width: 900px; /* Adjust this as needed */
    max-height: 500px;
    margin: auto;
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


    <div class="mainContent">
    <header class="mainHeader"></header>

    <!-- Search Bar -->
    <section class="searchSection">
        <form method="POST" action="reports.php" class="flex items-center mb-4">
            <input type="text" name="searchName" placeholder="Search by customer name..." value="<?php echo htmlspecialchars($searchName); ?>" class="p-2 border rounded-lg mr-2">
            <input type="date" name="searchDate" value="<?php echo htmlspecialchars($searchDate); ?>" class="p-2 border rounded-lg mr-2">
            <select name="searchMonth" class="p-2 border rounded-lg mr-2">
                <option value="">Select Month</option>
                <?php for ($month = 1; $month <= 12; $month++) { ?>
                    <option value="<?php echo $month; ?>" <?php echo $searchMonth == $month ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $month, 10)); ?></option>
                <?php } ?>
            </select>
            <button type="submit" class="bg-blue-500 text-white p-2 rounded-lg">Search</button>
        </form>
    </section>

    <!-- Monthly Reports Section -->
    <section class="monthlyReportsSection">
        <h2>Monthly Reports</h2>
        <table class="w-full text-left mb-4">
            <thead>
                <tr class="bg-blue-500 text-white">
                    <th>Month</th>
                    <th>Orders</th>
                    <th>Total Quantity</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($monthlyTotals as $monthYear => $totals) { ?>
                    <tr class="bg-blue-100">
                        <td><?php echo date('F Y', strtotime($monthYear . '-01')); ?></td>
                        <td><?php echo $totals['orders']; ?></td>
                        <td><?php echo $totals['quantity']; ?></td>
                        <td><?php echo number_format($totals['amount'], 2); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <!-- Monthly Graph -->
        <div id="chartContainer" style="width: 100%; height: auto; text-align: center;">
            <canvas id="monthlyReportChart"></canvas>
        </div>

    </section>

    <section class="ordersSection p-4 bg-white shadow-md rounded-lg">
  <table class="ordersTable w-full text-left border-collapse">
    <thead>
      <tr class="bg-blue-600 text-white text-lg">
        <th class="py-3 px-4">Customer Name</th>
        <th class="py-3 px-4">Order Date</th>
        <th class="py-3 px-4 text-center">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($customerOrders as $customerName => $orders) { ?>
        <tr class="bg-blue-100 border-b hover:bg-blue-200 transition duration-200">
          <td class="py-3 px-4 font-medium text-gray-800"><?php echo htmlspecialchars($customerName); ?></td>
          <td class="py-3 px-4 text-gray-700"><?php echo date("F j, Y", strtotime($orders[0]['order_date'])); ?></td>
          <td class="py-3 px-4 text-center">
            <button onclick="toggleReport('<?php echo htmlspecialchars($customerName); ?>')" 
              class="bg-blue-600 hover:bg-blue-700 transition duration-200 text-white px-4 py-2 rounded-lg shadow-md">
              View Invoice
            </button>
          </td>
        </tr>

        <tr id="report-<?php echo htmlspecialchars($customerName); ?>" style="display:none;">
  <td colspan="3">
    <div id="invoice-<?php echo htmlspecialchars($customerName); ?>" class="receipt-style bg-white border border-gray-300 rounded-lg p-6 shadow-lg">
      <div class="text-center">
        <h2 class="text-2xl font-bold">GSL25 STEEL TRADING</h2>
        <p class="text-sm">San Nicholas II, Sasmuan Pampanga</p>
        <p class="text-sm">Phone: 09307832574</p>
        <hr class="my-3">
        <h3 class="font-semibold text-xl">Invoice for <?php echo htmlspecialchars($customerName); ?></h3>
      </div>

      <div class="mb-6">
        <p><strong>Date Sold:</strong> <?php echo date("F j, Y", strtotime($orders[0]['order_date'])); ?></p>
        <p><strong>Sold To:</strong> <?php echo htmlspecialchars($customerName); ?></p>
      </div>

      <table class="w-full border-collapse mb-6" style="border: 1px solid #ddd; border-spacing: 0;">
        <thead>
          <tr style="background-color: #f4f4f4; text-align: left; font-weight: bold;">
            <th style="border: 1px solid #ddd; padding: 8px;">Product Name</th>
            <th style="border: 1px solid #ddd; padding: 8px;">Size</th>
            <th style="border: 1px solid #ddd; padding: 8px;">Quantity</th>
            <th style="border: 1px solid #ddd; padding: 8px;">Price</th>
            <th style="border: 1px solid #ddd; padding: 8px;">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $totalAmount = 0;
          foreach ($orders as $order) {
            $amount = $order['quantity'] * $order['price'];
            $totalAmount += $amount;
          ?>
          <tr style="text-align: left;">
            <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($order['product_name']); ?></td>
            <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($order['size']); ?></td>
            <td style="border: 1px solid #ddd; padding: 8px;"><?php echo $order['quantity']; ?></td>
            <td style="border: 1px solid #ddd; padding: 8px;"><?php echo number_format($order['price'], 2); ?></td>
            <td style="border: 1px solid #ddd; padding: 8px;"><?php echo number_format($amount, 2); ?></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>

      <div class="text-right mt-6">
        <p class="text-lg font-semibold"><strong>Total Amount: ₱<?php echo number_format($totalAmount, 2); ?></strong></p>
      </div>

      <div class="text-center mt-6">
        <hr class="my-3">
        <p><strong>Thank you for your purchase!</strong></p>
        <p>No. Ref: <span id="reference-number"><?php echo rand(10000, 99999); ?></span></p>
        <p>Visit us again at GSL25 STEEL TRADING</p>
      </div>

      <div class="mt-6">
        <p style="text-align: right; font-size: 14px;">Seller's Signature: _____________________</p>
      </div>

      <button onclick="printInvoice('<?php echo htmlspecialchars($customerName); ?>')" class="bg-blue-500 text-white px-6 py-3 rounded-lg mt-6">Print Receipt</button>
    </div>
  </td>
</tr>

<?php } ?>
</tbody>
</table>
</section>

<script>
  function toggleReport(customerName) {
    var report = document.getElementById('report-' + customerName);
    report.style.display = (report.style.display === "none" || report.style.display === "") ? "table-row" : "none";
  }

  function printInvoice(customerName) {
    var printContent = document.getElementById('invoice-' + customerName);
    var printWindow = window.open('', '', 'height=600,width=800');
    
    printWindow.document.write('<html><head><title>Invoice</title>');
    
    // Include custom CSS for print styling
    printWindow.document.write('<style>');
    printWindow.document.write('body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.5; margin: 0; padding: 0; width: 4in; height: 6in; }');
    printWindow.document.write('.receipt-style { margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; width: 100%; height: 100%; box-sizing: border-box; }');
    printWindow.document.write('.text-center { text-align: center; }');
    printWindow.document.write('.text-right { text-align: right; }');
    printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 20px; }');
    printWindow.document.write('th, td { padding: 8px; border: 1px solid #ddd; text-align: left; font-size: 12px; }');
    printWindow.document.write('.text-lg { font-size: 14px; }');
    printWindow.document.write('.font-semibold { font-weight: 600; }');
    printWindow.document.write('.my-3 { margin-top: 1rem; margin-bottom: 1rem; }');
    printWindow.document.write('</style>');

    printWindow.document.write('</head><body>');
    printWindow.document.write(printContent.innerHTML);
    printWindow.document.write('</body></html>');
    
    printWindow.document.close();
    printWindow.print();
  }
</script>

    <!-- Script to render the graph -->
    <script>
        const monthlyTotals = <?php echo json_encode($monthlyTotals); ?>;
        const months = [];
        const orderCounts = [];
        const totalAmounts = [];

        // Prepare data for the chart
        for (const monthYear in monthlyTotals) {
            months.push(monthYear);
            orderCounts.push(monthlyTotals[monthYear].orders);
            totalAmounts.push(monthlyTotals[monthYear].amount);
        }

        const ctx = document.getElementById('monthlyReportChart').getContext('2d');
const monthlyReportChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'Total Orders',
            data: orderCounts,
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            fill: true
        }, {
            label: 'Total Amount',
            data: totalAmounts,
            borderColor: 'rgba(153, 102, 255, 1)',
            backgroundColor: 'rgba(153, 102, 255, 0.2)',
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false, // Allows resizing
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                mode: 'index',
                intersect: false,
            }
        },
        scales: {
            x: {
                title: {
                    display: true,
                    text: 'Month'
                }
            },
            y: {
                title: {
                    display: true,
                    text: 'Value'
                }
            }
        }
    }
});

// Adjust chart size dynamically
function adjustChartSize() {
    let chartContainer = document.getElementById('chartContainer');
    if (window.innerWidth > 1024) {
        chartContainer.style.width = "900px";
        chartContainer.style.height = "500px";
    } else {
        chartContainer.style.width = "100%";
        chartContainer.style.height = "auto";
    }
}

// Call function on load and resize
window.addEventListener('load', adjustChartSize);
window.addEventListener('resize', adjustChartSize);

    </script>

    <script>
        // Toggle report visibility
        function toggleReport(customerName) {
            const reportRow = document.getElementById('report-' + customerName);
            reportRow.style.display = reportRow.style.display === 'none' ? 'table-row' : 'none';
        }

        function clearSearch() {
            window.location.href = 'inventory.php';
        }
        
       
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        function toggleDropdown(id) {
            document.getElementById(id).classList.toggle('active');
        }
    </script>
</body>
</html>
