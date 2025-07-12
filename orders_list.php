<?php
// orders_list.php - View all orders
include 'db_connection.php';

// Fetch all orders with sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'order_id';
$direction = isset($_GET['dir']) ? $_GET['dir'] : 'DESC';

// Validate sort parameters to prevent SQL injection
$allowedSorts = ['order_id', 'person_name', 'tablet_name', 'quantity', 'order_date'];
$sort = in_array($sort, $allowedSorts) ? $sort : 'order_id';
$direction = ($direction === 'ASC') ? 'ASC' : 'DESC';

$sql = "SELECT * FROM orders ORDER BY $sort $direction";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order List - Pharmacy Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f9ff;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .orders-table th, .orders-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .orders-table th {
            background-color: #3498db;
            color: white;
            cursor: pointer;
        }
        .orders-table th:hover {
            background-color: #2980b9;
        }
        .orders-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .orders-table tr:hover {
            background-color: #e9f7fe;
        }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-btn:hover {
            background-color: #2980b9;
        }
        .sort-icon::after {
            content: " ↕";
            font-size: 12px;
        }
        .sort-asc::after {
            content: " ↑";
            font-size: 12px;
        }
        .sort-desc::after {
            content: " ↓";
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Order List</h1>
            <p>View all placed orders</p>
        </div>
        
        <table class="orders-table">
            <thead>
                <tr>
                    <th class="<?php echo $sort == 'order_id' ? ($direction == 'ASC' ? 'sort-asc' : 'sort-desc') : 'sort-icon'; ?>">
                        <a href="?sort=order_id&dir=<?php echo $sort == 'order_id' && $direction == 'ASC' ? 'DESC' : 'ASC'; ?>" style="color: white; text-decoration: none;">
                            Order ID
                        </a>
                    </th>
                    <th class="<?php echo $sort == 'person_name' ? ($direction == 'ASC' ? 'sort-asc' : 'sort-desc') : 'sort-icon'; ?>">
                        <a href="?sort=person_name&dir=<?php echo $sort == 'person_name' && $direction == 'ASC' ? 'DESC' : 'ASC'; ?>" style="color: white; text-decoration: none;">
                            Person Name
                        </a>
                    </th>
                    <th class="<?php echo $sort == 'tablet_name' ? ($direction == 'ASC' ? 'sort-asc' : 'sort-desc') : 'sort-icon'; ?>">
                        <a href="?sort=tablet_name&dir=<?php echo $sort == 'tablet_name' && $direction == 'ASC' ? 'DESC' : 'ASC'; ?>" style="color: white; text-decoration: none;">
                            Tablet Name
                        </a>
                    </th>
                    <th class="<?php echo $sort == 'quantity' ? ($direction == 'ASC' ? 'sort-asc' : 'sort-desc') : 'sort-icon'; ?>">
                        <a href="?sort=quantity&dir=<?php echo $sort == 'quantity' && $direction == 'ASC' ? 'DESC' : 'ASC'; ?>" style="color: white; text-decoration: none;">
                            Quantity
                        </a>
                    </th>
                    <th class="<?php echo $sort == 'order_date' ? ($direction == 'ASC' ? 'sort-asc' : 'sort-desc') : 'sort-icon'; ?>">
                        <a href="?sort=order_date&dir=<?php echo $sort == 'order_date' && $direction == 'ASC' ? 'DESC' : 'ASC'; ?>" style="color: white; text-decoration: none;">
                            Order Date
                        </a>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>" . $row["order_id"] . "</td>
                                <td>" . $row["person_name"] . "</td>
                                <td>" . $row["tablet_name"] . "</td>
                                <td>" . $row["quantity"] . "</td>
                                <td>" . $row["order_date"] . "</td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No orders found</td></tr>";
                }
                ?>
            </tbody>
        </table>
        
        <a href="main_index.html" class="back-btn">Back to Main Page</a>
    </div>
</body>
</html>

<?php
$conn->close();
?>