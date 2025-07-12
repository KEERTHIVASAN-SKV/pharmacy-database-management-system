<?php
// manage_tablets.php - Add, Edit, Delete tablets
include 'db_connection.php';

// Initialize variables
$message = '';
$action = isset($_GET['action']) ? $_GET['action'] : ''; // Fix for undefined $action
$tablet_id = isset($_GET['id']) ? $_GET['id'] : '';
$tabletData = null;
$result = null; // Initialize $result variable

// Delete tablet
if ($action == 'delete' && !empty($tablet_id)) {
    $sql = "DELETE FROM tablets WHERE tablet_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $tablet_id);
    
    if ($stmt->execute()) {
        $message = '<div class="success">Tablet deleted successfully!</div>';
    } else {
        $message = '<div class="error">Error deleting tablet: ' . $stmt->error . '</div>';
    }
    $stmt->close();
}

// Edit tablet - fetch data
if ($action == 'edit' && !empty($tablet_id)) {
    $sql = "SELECT t.*, ti.disease_treatment, ti.description, ti.dosage_info, ti.side_effects 
            FROM tablets t 
            LEFT JOIN tablet_info ti ON t.tablet_id = ti.tablet_id 
            WHERE t.tablet_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $tablet_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tabletData = $result->fetch_assoc();
    $stmt->close();
}

// Process form submission for add/edit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $edit_id = isset($_POST['edit_id']) ? $_POST['edit_id'] : '';
    $tablet_name = $_POST['tablet_name'];
    $tablet_price = $_POST['tablet_price'];
    $tablet_weight = $_POST['tablet_weight'];
    $tablet_stock = $_POST['tablet_stock'];
    $disease_treatment = $_POST['disease_treatment'];
    $description = $_POST['description'];
    $dosage_info = $_POST['dosage_info'];
    $side_effects = $_POST['side_effects'];
    
    if (empty($edit_id)) {
        // Check if tablet already exists with same name, price and weight
        $checkSql = "SELECT tablet_id, tablet_stock FROM tablets WHERE tablet_name = ? AND tablet_price = ? AND tablet_weight = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("sdi", $tablet_name, $tablet_price, $tablet_weight);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            // Update existing tablet stock
            $existingTablet = $checkResult->fetch_assoc();
            $existingId = $existingTablet['tablet_id'];
            $newStock = $existingTablet['tablet_stock'] + $tablet_stock;
            
            $updateSql = "UPDATE tablets SET tablet_stock = ? WHERE tablet_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ii", $newStock, $existingId);
            
            if ($updateStmt->execute()) {
                $message = '<div class="success">Tablet already exists. Stock updated successfully!</div>';
            } else {
                $message = '<div class="error">Error updating stock: ' . $updateStmt->error . '</div>';
            }
            $updateStmt->close();
        } else {
            // Add new tablet
            $sql = "INSERT INTO tablets (tablet_name, tablet_price, tablet_weight, tablet_stock) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdii", $tablet_name, $tablet_price, $tablet_weight, $tablet_stock);
            
            if ($stmt->execute()) {
                $new_tablet_id = $stmt->insert_id;
                
                // Add tablet info
                $infoSql = "INSERT INTO tablet_info (tablet_id, disease_treatment, description, dosage_info, side_effects) VALUES (?, ?, ?, ?, ?)";
                $infoStmt = $conn->prepare($infoSql);
                $infoStmt->bind_param("issss", $new_tablet_id, $disease_treatment, $description, $dosage_info, $side_effects);
                $infoStmt->execute();
                $infoStmt->close();
                
                $message = '<div class="success">Tablet added successfully!</div>';
            } else {
                $message = '<div class="error">Error adding tablet: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        }
        $checkStmt->close();
    } else {
        // Update existing tablet
        $sql = "UPDATE tablets SET tablet_name = ?, tablet_price = ?, tablet_weight = ?, tablet_stock = ? WHERE tablet_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdiii", $tablet_name, $tablet_price, $tablet_weight, $tablet_stock, $edit_id);
        
        if ($stmt->execute()) {
            // Check if tablet info exists
            $checkSql = "SELECT info_id FROM tablet_info WHERE tablet_id = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("i", $edit_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $checkStmt->close();
            
            if ($checkResult->num_rows > 0) {
                // Update tablet info
                $infoSql = "UPDATE tablet_info SET disease_treatment = ?, description = ?, dosage_info = ?, side_effects = ? WHERE tablet_id = ?";
                $infoStmt = $conn->prepare($infoSql);
                $infoStmt->bind_param("ssssi", $disease_treatment, $description, $dosage_info, $side_effects, $edit_id);
            } else {
                // Insert tablet info
                $infoSql = "INSERT INTO tablet_info (tablet_id, disease_treatment, description, dosage_info, side_effects) VALUES (?, ?, ?, ?, ?)";
                $infoStmt = $conn->prepare($infoSql);
                $infoStmt->bind_param("issss", $edit_id, $disease_treatment, $description, $dosage_info, $side_effects);
            }
            
            $infoStmt->execute();
            $infoStmt->close();
            
            $message = '<div class="success">Tablet updated successfully!</div>';
        } else {
            $message = '<div class="error">Error updating tablet: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    }
    
    // Reset action after successful submission
    if (strpos($message, 'success') !== false) {
        $action = '';
        $tabletData = null;
    }
}

// Fetch all tablets for list - Make sure this runs in all cases
$sql = "SELECT t.tablet_id, t.tablet_name, t.tablet_price, t.tablet_weight, t.tablet_stock 
        FROM tablets t 
        ORDER BY t.tablet_id DESC";
$result = $conn->query($sql);  // Fix for undefined $result
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tablets - Pharmacy Management</title>
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
            margin-bottom: 30px;
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
        .add-btn, .edit-btn, .delete-btn, .back-btn, .submit-btn {
            display: inline-block;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }
        .add-btn {
            background-color: #2ecc71;
            color: white;
            margin-bottom: 20px;
        }
        .edit-btn {
            background-color: #3498db;
            color: white;
        }
        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }
        .back-btn {
            background-color: #7f8c8d;
            color: white;
            margin-top: 20px;
        }
        .submit-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
        }
        .tablet-form {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
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
        .form-title {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Manage Tablets</h1>
            <p>Add, Edit, or Delete tablets from inventory</p>
        </div>
        
        <?php echo $message; ?>
        
        <?php if ($action == 'add' || $action == 'edit'): ?>
            <!-- Add/Edit Form -->
            <div class="tablet-form">
                <h2 class="form-title"><?php echo $action == 'add' ? 'Add New Tablet' : 'Edit Tablet'; ?></h2>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <?php if ($action == 'edit' && $tabletData): ?>
                        <input type="hidden" name="edit_id" value="<?php echo $tabletData['tablet_id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="tablet_name">Tablet Name:</label>
                        <input type="text" id="tablet_name" name="tablet_name" class="form-control" required
                               value="<?php echo ($action == 'edit' && $tabletData) ? htmlspecialchars($tabletData['tablet_name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="tablet_price">Price ($):</label>
                        <input type="number" id="tablet_price" name="tablet_price" class="form-control" required step="0.01" min="0"
                               value="<?php echo ($action == 'edit' && $tabletData) ? htmlspecialchars($tabletData['tablet_price']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="tablet_weight">Weight (mg):</label>
                        <input type="number" id="tablet_weight" name="tablet_weight" class="form-control" required min="1"
                               value="<?php echo ($action == 'edit' && $tabletData) ? htmlspecialchars($tabletData['tablet_weight']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="tablet_stock">Stock Quantity:</label>
                        <input type="number" id="tablet_stock" name="tablet_stock" class="form-control" required min="0"
                               value="<?php echo ($action == 'edit' && $tabletData) ? htmlspecialchars($tabletData['tablet_stock']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="disease_treatment">Disease/Treatment For:</label>
                        <input type="text" id="disease_treatment" name="disease_treatment" class="form-control" required
                               value="<?php echo ($action == 'edit' && $tabletData && isset($tabletData['disease_treatment'])) ? htmlspecialchars($tabletData['disease_treatment']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" class="form-control" rows="3"><?php echo ($action == 'edit' && $tabletData && isset($tabletData['description'])) ? htmlspecialchars($tabletData['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="dosage_info">Dosage Information:</label>
                        <textarea id="dosage_info" name="dosage_info" class="form-control" rows="3"><?php echo ($action == 'edit' && $tabletData && isset($tabletData['dosage_info'])) ? htmlspecialchars($tabletData['dosage_info']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="side_effects">Side Effects:</label>
                        <textarea id="side_effects" name="side_effects" class="form-control" rows="3"><?php echo ($action == 'edit' && $tabletData && isset($tabletData['side_effects'])) ? htmlspecialchars($tabletData['side_effects']) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn"><?php echo $action == 'add' ? 'Add Tablet' : 'Update Tablet'; ?></button>
                    <a href="manage_tablets.php" class="back-btn">Cancel</a>
                </form>
            </div>
        <?php else: ?>
            <!-- Tablets List -->
            <a href="manage_tablets.php?action=add" class="add-btn">Add New Tablet</a>
            
            <table class="tablets-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price ($)</th>
                        <th>Weight (mg)</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>
                                    <td>" . $row["tablet_id"] . "</td>
                                    <td>" . htmlspecialchars($row["tablet_name"]) . "</td>
                                    <td>$" . htmlspecialchars($row["tablet_price"]) . "</td>
                                    <td>" . htmlspecialchars($row["tablet_weight"]) . " mg</td>
                                    <td>" . htmlspecialchars($row["tablet_stock"]) . "</td>
                                    <td>
                                        <a href='manage_tablets.php?action=edit&id=" . $row["tablet_id"] . "' class='edit-btn'>Edit</a>
                                        <a href='manage_tablets.php?action=delete&id=" . $row["tablet_id"] . "' class='delete-btn' onclick='return confirm(\"Are you sure you want to delete this tablet?\")'>Delete</a>
                                    </td>
                                </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No tablets found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            
            <a href="main_index.html" class="back-btn">Back to Main Page</a>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
// Make sure $conn exists before trying to close it
if (isset($conn)) {
    $conn->close();
}
?>