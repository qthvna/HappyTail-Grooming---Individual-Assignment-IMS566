<?php
include "../auth.php";
include "../db.php";

// Ensure only admin can access
if ($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

$staff_user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$staff_user_id) {
    header("Location: manage_staff.php");
    exit();
}

// Get staff data
$staff_query = "SELECT u.user_id, u.name, u.email 
                FROM users u 
                WHERE u.user_id = ? AND u.role = 'staff'";
$stmt = mysqli_prepare($conn, $staff_query);
mysqli_stmt_bind_param($stmt, "i", $staff_user_id);
mysqli_stmt_execute($stmt);
$staff_result = mysqli_stmt_get_result($stmt);
$staff_data = mysqli_fetch_assoc($staff_result);
mysqli_stmt_close($stmt);

if (!$staff_data) {
    header("Location: manage_staff.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        // Check if email already exists for another user
        $email_check = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $stmt = mysqli_prepare($conn, $email_check);
        mysqli_stmt_bind_param($stmt, "si", $email, $staff_user_id);
        mysqli_stmt_execute($stmt);
        $email_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($email_result) > 0) {
            $error = "Email already exists for another user.";
            mysqli_stmt_close($stmt);
        } else {
            mysqli_stmt_close($stmt);
            
            // Update user table
            $update_user_query = "UPDATE users SET name = ?, email = ? WHERE user_id = ? AND role = 'staff'";
            $stmt = mysqli_prepare($conn, $update_user_query);
            mysqli_stmt_bind_param($stmt, "ssi", $name, $email, $staff_user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                // Redirect with success message
                header("Location: edit_staff.php?id=" . $staff_user_id . "&success=1");
                exit();
            } else {
                $error = "Error updating staff: " . mysqli_error($conn);
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Staff - HappyTail Grooming</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            background: var(--bg-primary);
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow);
        }
        
        .form-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .form-buttons .btn {
            flex: 1;
        }
        
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
        
        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
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
                <h1>Edit Staff</h1>
                <p>Update staff information</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message" style="padding: 15px; margin: 20px 0;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" action="" class="login-form">
                    <div class="form-group">
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" 
                               value="<?php echo htmlspecialchars($staff_data['name']); ?>" 
                               placeholder="Enter staff name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($staff_data['email']); ?>" 
                               placeholder="Enter email address" required>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary btn-full">Update Staff</button>
                        <a href="manage_staff.php" class="btn btn-secondary btn-full">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include "../footer.php"; ?>
    
    <!-- Success Modal -->
    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
    <div id="successModal" class="modal" style="display: block;">
        <div class="modal-content">
            <div class="modal-icon">✓</div>
            <h2>Staff Updated Successfully!</h2>
            <p>Staff information has been updated. You will be redirected to the staff page.</p>
            <div class="modal-buttons">
                <button type="button" class="btn btn-primary btn-full" onclick="redirectToManageStaff()">View Staff</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        // Redirect to manage staff page
        function redirectToManageStaff() {
            window.location.href = 'manage_staff.php';
        }
        
        // Auto-redirect after 3 seconds if modal is shown
        <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
        setTimeout(function() {
            redirectToManageStaff();
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>

