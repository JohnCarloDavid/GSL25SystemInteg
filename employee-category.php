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
// Initialize the category
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// Initialize the search term
$search = isset($_GET['search']) ? '%' . trim($_GET['search']) . '%' : '%';

// Query to select products for the given category and search term, including price and image URL
$sql = "SELECT i.product_id, i.name, i.price, i.size, i.quantity, i.image_url
        FROM tb_inventory i
        WHERE i.category = ? AND (i.product_id LIKE ? OR i.name LIKE ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('sss', $category, $search, $search);
$stmt->execute();
$result = $stmt->get_result();

// Query to calculate the total stock for the given category
$total_stock_sql = "SELECT SUM(quantity) AS total_stock 
                    FROM tb_inventory 
                    WHERE category = ? AND (product_id LIKE ? OR name LIKE ?)";
$total_stock_stmt = $conn->prepare($total_stock_sql);
$total_stock_stmt->bind_param('sss', $category, $search, $search);
$total_stock_stmt->execute();
$total_stock_result = $total_stock_stmt->get_result();
$total_stock = $total_stock_result->fetch_assoc()['total_stock'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category); ?> - GSL25 Inventory Management System</title>
    <link rel="icon" href="img/GSL25_transparent 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <style>
        .low-stock {
            color: red;
        }
        .product-image {
            width: 40px;        
            height: 40px;       
            object-fit: cover; 
            display: block;     
            margin: 0 auto;     
        }
        td {
            vertical-align: middle; 
        }
        /* Mobile fixes */
        @media (max-width: 640px) {
            .action-icons {
                display: flex;
                justify-content: center;
                gap: 8px;
            }
            .action-icons i {
                font-size: 1.2rem; /* Larger tap targets */
            }
        }
    </style>
</head>
<body class="bg-gray-100 p-2">
    <div class="container mx-auto px-2 md:px-4">
        <!-- Header -->
        <div class="text-center py-4">
            <h1 class="text-lg md:text-2xl font-bold"><?php echo htmlspecialchars($category); ?> Inventory</h1>
        </div>
        
        <div class="flex flex-wrap justify-between items-center mb-4">
            <a href="employee_inventory.php" class="bg-blue-500 text-white py-2 px-3 rounded hover:bg-blue-600 text-sm md:text-base">Back</a>
        </div>

        <!-- Search Bar -->
        <form method="GET" action="" class="mb-4">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
            <div class="flex items-center space-x-2">
                <input type="text" name="search" placeholder="Search by ID or name..." 
                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
                    class="flex-grow p-2 border border-gray-300 rounded-md text-sm md:text-base">
                <button type="submit" class="bg-blue-500 text-white py-2 px-3 md:px-4 rounded-md text-sm md:text-base">Search</button>
            </div>
        </form>

        <!-- Inventory Table (Mobile Responsive) -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-300 text-xs md:text-sm">
                <thead>
                    <tr class="bg-gray-200 text-xs md:text-sm">
                        <th class="border p-1 md:p-2">ID</th>
                        <th class="border p-1 md:p-2">Name</th>
                        <th class="border p-1 md:p-2">Price</th>
                        <th class="border p-1 md:p-2">Size</th>
                        <th class="border p-1 md:p-2">Qty</th>
                        <th class="border p-1 md:p-2">Image</th>
                        <th class="border p-1 md:p-2 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr data-id="<?php echo htmlspecialchars($row['product_id']); ?>" class="text-xs md:text-sm">
                            <td class="border p-1 md:p-2"><?php echo htmlspecialchars($row['product_id']); ?></td>
                            <td class="border p-1 md:p-2"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="border p-1 md:p-2"><?php echo htmlspecialchars($row['price']); ?></td>
                            <td class="border p-1 md:p-2"><?php echo htmlspecialchars($row['size']); ?></td>
                            <td class="border p-1 md:p-2 quantity-cell <?php echo $row['quantity'] < 15 ? 'low-stock' : ''; ?>">
                                <?php echo htmlspecialchars($row['quantity']); ?>
                            </td>
                            <td class="border p-1 md:p-2">
                                <?php if ($row['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="Product" class="product-image">
                                <?php else: ?>
                                    <span>No Image</span>
                                <?php endif; ?>
                            </td>
                            <td class="border p-1 md:p-2">
                                <div class="action-icons flex justify-center space-x-2">
                                    <i class="fas fa-edit text-blue-500 cursor-pointer" onclick="editProduct('<?php echo htmlspecialchars($row['product_id']); ?>')"></i>
                                    <i class="fas fa-trash text-red-500 cursor-pointer" onclick="deleteProduct('<?php echo htmlspecialchars($row['product_id']); ?>')"></i>
                                    <i class="fas fa-minus-circle text-orange-500 cursor-pointer" data-action="deduct" data-id="<?php echo htmlspecialchars($row['product_id']); ?>"></i>
                                    <i class="fas fa-plus-circle text-green-500 cursor-pointer" data-action="add" data-id="<?php echo htmlspecialchars($row['product_id']); ?>"></i>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Total Stock -->
        <div class="text-right mt-3 text-sm md:text-base">
            <span class="font-bold">Total Stock:</span> <?php echo $total_stock; ?>
        </div>
    </div>

<script>
// Handle click events for "add" and "deduct" actions
document.querySelectorAll('i[data-action]').forEach(icon => {
    icon.addEventListener('click', function () {
        const action = this.getAttribute('data-action');
        const productId = this.getAttribute('data-id');

        // Send the request to the backend
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'update_quantity.php', true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    const row = document.querySelector(`tr[data-id="${productId}"]`);
                    if (row) {
                        const quantityCell = row.querySelector('.quantity-cell');
                        if (quantityCell) {
                            quantityCell.textContent = response.new_quantity;

                            if (response.new_quantity < 15) {
                                quantityCell.classList.add('low-stock');
                            } else {
                                quantityCell.classList.remove('low-stock');
                            }
                        }

                        const totalStockFooter = document.querySelector('.text-right');
                        if (totalStockFooter) {
                            totalStockFooter.textContent = `Total Stock: ${response.total_stock}`;
                        }
                    }
                } else {
                    console.error(response.message || 'Failed to update quantity');
                }
            } else {
                console.error('Server error. Please try again later.');
            }
        };
        xhr.send(`product_id=${productId}&action=${action}`);
    });
});

function editProduct(productId) {
    window.location.href = `edit_product.php?product_id=${productId}`;
}

function deleteProduct(productId) {
    if (confirm('Are you sure you want to delete this product?')) {
        window.location.href = `delete_product.php?product_id=${productId}`;
    }
}
</script>
</body>
</html>
