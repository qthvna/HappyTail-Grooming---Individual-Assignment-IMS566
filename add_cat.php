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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!$customer_id) {
        // Create customer record if it doesn't exist (using prepared statement)
        $insert_customer = "INSERT INTO customers (user_id) VALUES (?)";
        $stmt = mysqli_prepare($conn, $insert_customer);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $customer_id = (int)mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
        } else {
            $error = "Error creating customer profile: " . mysqli_error($conn);
            mysqli_stmt_close($stmt);
        }
    }
    
    if ($customer_id && !$error) {
        $cat_name = mysqli_real_escape_string($conn, $_POST['cat_name']);
        $breed = mysqli_real_escape_string($conn, isset($_POST['breed']) ? $_POST['breed'] : '');
        $age = isset($_POST['age']) && $_POST['age'] !== '' ? (int)$_POST['age'] : null;
        $special_notes = mysqli_real_escape_string($conn, isset($_POST['special_notes']) ? $_POST['special_notes'] : '');
        
        // Insert cat (using prepared statement)
        if ($age === null) {
            $insert_query = "INSERT INTO cats (customer_id, cat_name, breed, age, special_notes) VALUES (?, ?, ?, NULL, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "isss", $customer_id, $cat_name, $breed, $special_notes);
        } else {
            $insert_query = "INSERT INTO cats (customer_id, cat_name, breed, age, special_notes) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "issis", $customer_id, $cat_name, $breed, $age, $special_notes);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            // Redirect with success parameter to show popup
            header("Location: add_cat.php?success=1");
            exit();
        } else {
            $error = "Error adding cat: " . mysqli_error($conn);
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Cat - HappyTail Grooming</title>
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
                <h1>Add New Cat</h1>
                <p>Register a new cat for grooming services</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message" style="padding: 15px; margin: 20px 0;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Success Popup Modal -->
            <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
            <div id="successModal" class="modal" style="display: block;">
                <div class="modal-content">
                    <div class="modal-icon">✓</div>
                    <h2>Cat Added Successfully!</h2>
                    <p>Your cat has been registered. You will be redirected to your cats page.</p>
                    <button type="button" class="btn btn-primary btn-full" onclick="redirectToMyCats()">View My Cats</button>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="form-container" style="max-width: 600px; margin: 0 auto;">
                <form method="POST" action="" class="login-form">
                    <div class="form-group">
                        <label for="cat_name">Cat Name *</label>
                        <input type="text" id="cat_name" name="cat_name" 
                               value="<?php echo htmlspecialchars(isset($_POST['cat_name']) ? $_POST['cat_name'] : ''); ?>" 
                               placeholder="Enter cat's name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="breed">Breed</label>
                        <input type="text" id="breed" name="breed" 
                               value="<?php echo htmlspecialchars(isset($_POST['breed']) ? $_POST['breed'] : ''); ?>" 
                               placeholder="e.g., Persian, Siamese, Maine Coon">
                    </div>
                    
                    <div class="form-group">
                        <label for="age">Age (years)</label>
                        <input type="number" id="age" name="age" min="0" max="30" 
                               value="<?php echo htmlspecialchars(isset($_POST['age']) ? $_POST['age'] : ''); ?>" 
                               placeholder="Enter age">
                    </div>
                    
                    <div class="form-group">
                        <label for="special_notes">Special Notes</label>
                        <textarea id="special_notes" name="special_notes" rows="4" 
                                  placeholder="Any special care instructions, allergies, or behavioral notes..."><?php echo htmlspecialchars(isset($_POST['special_notes']) ? $_POST['special_notes'] : ''); ?></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-top: 20px;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">Add Cat</button>
                        <a href="my_cats.php" class="btn btn-secondary" style="flex: 1; text-align: center;">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include "../footer.php"; ?>
    
    <script>
        // Redirect to my cats page
        function redirectToMyCats() {
            window.location.href = 'my_cats.php';
        }
        
        // Auto-redirect after 3 seconds if modal is shown
        <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
        setTimeout(function() {
            redirectToMyCats();
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>

