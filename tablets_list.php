<?php
// tablets_list.php - Display all available tablets
include 'db_connection.php';

// Fetch all tablets
$sql = "SELECT t.tablet_id, t.tablet_name, t.tablet_price, t.tablet_weight, t.tablet_stock, 
        ti.disease_treatment, ti.description 
        FROM tablets t 
        LEFT JOIN tablet_info ti ON t.tablet_id = ti.tablet_id";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Tablets - Pharmacy Management</title>
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
        .tablets-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .tablets-table th, .tablets-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .tablets-table th {
            background-color: #3498db;
            color: white;
        }
        .tablets-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .tablets-table tr:hover {
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
        .stock-low {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Available Tablets</h1>
            <p>Current inventory of tablets</p>
        </div>
        
        <table class="tablets-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Price ($)</th>
                    <th>Weight (mg)</th>
                    <th>Stock</th>
                    <th>Treatment For</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $stockClass = $row["tablet_stock"] < 30 ? "stock-low" : "";
                        echo "<tr>
                                <td>" . $row["tablet_id"] . "</td>
                                <td>" . $row["tablet_name"] . "</td>
                                <td>$" . $row["tablet_price"] . "</td>
                                <td>" . $row["tablet_weight"] . " mg</td>
                                <td class='" . $stockClass . "'>" . $row["tablet_stock"] . "</td>
                                <td>" . $row["disease_treatment"] . "</td>
                                <td>" . $row["description"] . "</td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No tablets found</td></tr>";
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