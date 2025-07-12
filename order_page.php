<?php
// order_page.php - Place new order
include 'db_connection.php';

$message = '';
$tablets = [];

// Fetch all tablets for selection
$sql = "SELECT t.tablet_id, t.tablet_name, t.tablet_price, t.tablet_weight, t.tablet_stock, 
        ti.disease_treatment, ti.description 
        FROM tablets t 
        LEFT JOIN tablet_info ti ON t.tablet_id = ti.tablet_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $tablets[] = $row;
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $person_name = $_POST['person_name'];
    $tablet_id = $_POST['tablet_id'];
    $quantity = $_POST['quantity'];
    
    // Validate inputs
    if (empty($person_name) || empty($tablet_id) || empty($quantity) || $quantity <= 0) {
        $message = '<div class="error">Please fill all fields with valid values.</div>';
    } else {
        // Get tablet details
        $tabletSql = "SELECT tablet_name, tablet_stock FROM tablets WHERE tablet_id = ?";
        $stmt = $conn->prepare($tabletSql);
        $stmt->bind_param("i", $tablet_id);
        $stmt->execute();
        $tabletResult = $stmt->get_result();
        $tabletData = $tabletResult->fetch_assoc();
        $stmt->close();
        
        // Check if we have enough stock
        if ($tabletData['tablet_stock'] < $quantity) {
            $message = '<div class="error">Not enough stock available. Only ' . $tabletData['tablet_stock'] . ' available.</div>';
        } else {
            // Create order
            $orderSql = "INSERT INTO orders (person_name, tablet_id, tablet_name, quantity) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($orderSql);
            $stmt->bind_param("sisi", $person_name, $tablet_id, $tabletData['tablet_name'], $quantity);
            
            if ($stmt->execute()) {
                // Update stock
                $updateSql = "UPDATE tablets SET tablet_stock = tablet_stock - ? WHERE tablet_id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("ii", $quantity, $tablet_id);
                $updateStmt->execute();
                $updateStmt->close();
                
                $message = '<div class="success">Order placed successfully!</div>';
            } else {
                $message = '<div class="error">Error placing order: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order - Pharmacy Management</title>
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
        .order-form {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .tablet-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #e9f7fe;
            border-radius: 5px;
            display: none;
        }
        .submit-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .submit-btn:hover {
            background-color: #2980b9;
        }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #7f8c8d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Place New Order</h1>
            <p>Fill the form below to place an order</p>
        </div>
        
        <?php echo $message; ?>
        
        <div class="order-form">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="person_name">Your Name:</label>
                    <input type="text" id="person_name" name="person_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="tablet_id">Select Tablet:</label>
                    <select id="tablet_id" name="tablet_id" class="form-control" required onchange="showTabletInfo(this.value)">
                        <option value="">-- Select Tablet --</option>
                        <?php foreach ($tablets as $tablet): ?>
                            <option value="<?php echo $tablet['tablet_id']; ?>" data-price="<?php echo $tablet['tablet_price']; ?>" data-stock="<?php echo $tablet['tablet_stock']; ?>" data-treatment="<?php echo $tablet['disease_treatment']; ?>" data-description="<?php echo $tablet['description']; ?>">
                                <?php echo $tablet['tablet_name'] . ' (' . $tablet['tablet_weight'] . 'mg) - $' . $tablet['tablet_price']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="tablet-info" class="tablet-info">
                    <h3>Tablet Information</h3>
                    <p><strong>Treatment for:</strong> <span id="treatment"></span></p>
                    <p><strong>Description:</strong> <span id="description"></span></p>
                    <p><strong>Price:</strong> $<span id="price"></span></p>
                    <p><strong>Available Stock:</strong> <span id="stock"></span></p>
                </div>
                
                <div class="form-group">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" class="form-control" min="1" required>
                </div>
                
                <button type="submit" class="submit-btn">Place Order</button>
            </form>
        </div>
        
        <a href="main_index.html" class="back-btn">Back to Main Page</a>
    </div>

    <script>
        function showTabletInfo(tabletId) {
            if (tabletId) {
                const selectElement = document.getElementById('tablet_id');
                const selectedOption = selectElement.options[selectElement.selectedIndex];
                
                document.getElementById('treatment').textContent = selectedOption.getAttribute('data-treatment');
                document.getElementById('description').textContent = selectedOption.getAttribute('data-description');
                document.getElementById('price').textContent = selectedOption.getAttribute('data-price');
                document.getElementById('stock').textContent = selectedOption.getAttribute('data-stock');
                
                document.getElementById('tablet-info').style.display = 'block';
            } else {
                document.getElementById('tablet-info').style.display = 'none';
            }
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>