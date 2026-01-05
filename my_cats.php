<?php
include "../auth.php";
include "../db.php";

// Ensure only customers can access
if ($_SESSION['role'] != 'customer') {
    header("Location: ../index.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$error = '';

// Get customer_id (using prepared statement)
$customer_query = "SELECT customer_id FROM customers WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $customer_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$customer_result = mysqli_stmt_get_result($stmt);
$customer_data = mysqli_fetch_assoc($customer_result);
$customer_id = isset($customer_data['customer_id']) ? (int)$customer_data['customer_id'] : null;
mysqli_stmt_close($stmt);

// Handle delete cat
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_cat']) && $customer_id) {
    $cat_id = (int)$_POST['cat_id'];
    
    // Verify cat belongs to customer
    $verify_query = "SELECT cat_id, cat_name FROM cats WHERE cat_id = ? AND customer_id = ?";
    $stmt = mysqli_prepare($conn, $verify_query);
    mysqli_stmt_bind_param($stmt, "ii", $cat_id, $customer_id);
    mysqli_stmt_execute($stmt);
    $verify_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($verify_result) > 0) {
        $cat = mysqli_fetch_assoc($verify_result);
        
        // Check if cat has any appointments
        $appt_check = "SELECT COUNT(*) as count FROM appointments WHERE cat_id = ?";
        $stmt = mysqli_prepare($conn, $appt_check);
        mysqli_stmt_bind_param($stmt, "i", $cat_id);
        mysqli_stmt_execute($stmt);
        $appt_result = mysqli_stmt_get_result($stmt);
        $appt_data = mysqli_fetch_assoc($appt_result);
        $appt_count = (int)$appt_data['count'];
        mysqli_stmt_close($stmt);
        
        if ($appt_count > 0) {
            $error = "Cannot delete cat. This cat has " . $appt_count . " appointment(s). Please cancel or complete the appointments first.";
        } else {
            // Delete cat
            $delete_query = "DELETE FROM cats WHERE cat_id = ? AND customer_id = ?";
            $stmt = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($stmt, "ii", $cat_id, $customer_id);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                // Redirect with success parameter to show popup
                header("Location: my_cats.php?deleted=1");
                exit();
            } else {
                $error = "Error deleting cat: " . mysqli_error($conn);
                mysqli_stmt_close($stmt);
            }
        }
    } else {
        $error = "Cat not found or you don't have permission to delete it.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cats - HappyTail Grooming</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/customer/customer.css">
    <style>
        /* Success Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }
        
        .modal-content {
            background-color: var(--bg-primary);
            margin: 10% auto;
            padding: 40px;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s ease;
        }
        
        .modal-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            font-weight: bold;
        }
        
        .modal-content h2 {
            color: var(--text-primary);
            margin-bottom: 15px;
            font-size: 1.8rem;
        }
        
        .modal-content p {
            color: var(--text-secondary);
            margin-bottom: 30px;
            font-size: 1rem;
            line-height: 1.6;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <?php include "../menu.php"; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>My Cats</h1>
                <p>Manage your registered cats</p>
                <a href="add_cat.php" class="btn btn-primary" style="margin-top: 10px;">Add New Cat</a>
            </div>
            
            <?php if ($customer_id): ?>
                <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
                <!-- Success Popup Modal -->
                <div id="successModal" class="modal" style="display: block;">
                    <div class="modal-content">
                        <div class="modal-icon">✓</div>
                        <h2>Cat Deleted Successfully!</h2>
                        <p>The cat has been removed from your account.</p>
                        <button type="button" class="btn btn-primary btn-full" onclick="closeModal()">OK</button>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="error-message" style="padding: 15px; margin: 20px 0;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cat Name</th>
                            <th>Breed</th>
                            <th>Age</th>
                            <th>Special Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM cats WHERE customer_id = ? ORDER BY cat_name";
                        $stmt = mysqli_prepare($conn, $query);
                        mysqli_stmt_bind_param($stmt, "i", $customer_id);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . $row['cat_id'] . "</td>";
                                echo "<td><strong>" . htmlspecialchars($row['cat_name']) . "</strong></td>";
                                $breed = isset($row['breed']) ? $row['breed'] : 'N/A';
                                $age = isset($row['age']) ? $row['age'] : 'N/A';
                                $notes = isset($row['special_notes']) ? $row['special_notes'] : '';
                                echo "<td>" . htmlspecialchars($breed) . "</td>";
                                echo "<td>" . $age . "</td>";
                                echo "<td>" . htmlspecialchars(substr($notes, 0, 50)) . (strlen($notes) > 50 ? '...' : '') . "</td>";
                                echo "<td>";
                                echo "<div style='display: flex; gap: 8px; align-items: center;'>";
                                echo "<a href='edit_cat.php?id=" . $row['cat_id'] . "' class='btn btn-primary btn-small'>Edit</a>";
                                echo "<form method='POST' action='' style='display: inline; margin: 0;' onsubmit='return confirmDelete(" . $row['cat_id'] . ", \"" . htmlspecialchars(addslashes($row['cat_name'])) . "\");'>";
                                echo "<input type='hidden' name='cat_id' value='" . $row['cat_id'] . "'>";
                                echo "<input type='hidden' name='delete_cat' value='1'>";
                                echo "<button type='submit' class='btn btn-action danger btn-small'>Delete</button>";
                                echo "</form>";
                                echo "</div>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='no-data'>No cats registered. <a href='add_cat.php'>Add your first cat!</a></td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert-message" style="background-color: #fef3c7; color: #92400e; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <p><strong>Profile Incomplete:</strong> Please complete your customer profile first. <a href="profile.php">Update Profile</a></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include "../footer.php"; ?>
    
    <script>
        function confirmDelete(catId, catName) {
            return confirm('Are you sure you want to delete "' + catName + '"? This action cannot be undone. If this cat has appointments, they must be cancelled first.');
        }
        
        function closeModal() {
            document.getElementById('successModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('successModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>

