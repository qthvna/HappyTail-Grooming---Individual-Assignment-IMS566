<?php
include "../core/auth.php";
include "../core/db.php";

// Ensure only customers can access
if ($_SESSION['role'] != 'customer') {
    header("Location: ../index.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$message = '';
$error = '';

// Get customer data (using prepared statement)
$customer_query = "SELECT c.*, u.name, u.email FROM customers c 
                   RIGHT JOIN users u ON c.user_id = u.user_id 
                   WHERE u.user_id = ?";
$stmt = mysqli_prepare($conn, $customer_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$customer_result = mysqli_stmt_get_result($stmt);
$customer_data = mysqli_fetch_assoc($customer_result);
mysqli_stmt_close($stmt);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = mysqli_real_escape_string($conn, isset($_POST['phone']) ? $_POST['phone'] : '');
    $address = mysqli_real_escape_string($conn, isset($_POST['address']) ? $_POST['address'] : '');
    
    if (isset($customer_data['customer_id']) && $customer_data['customer_id']) {
        // Update existing customer (using prepared statement)
        $update_query = "UPDATE customers SET phone = ?, address = ? WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ssi", $phone, $address, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            $message = "Profile updated successfully!";
            // Refresh data
            $stmt = mysqli_prepare($conn, $customer_query);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $customer_result = mysqli_stmt_get_result($stmt);
            $customer_data = mysqli_fetch_assoc($customer_result);
            mysqli_stmt_close($stmt);
        } else {
            $error = "Error updating profile: " . mysqli_error($conn);
            mysqli_stmt_close($stmt);
        }
    } else {
        // Create new customer record (using prepared statement)
        $insert_query = "INSERT INTO customers (user_id, phone, address) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $phone, $address);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            $message = "Profile created successfully!";
            // Refresh data
            $stmt = mysqli_prepare($conn, $customer_query);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $customer_result = mysqli_stmt_get_result($stmt);
            $customer_data = mysqli_fetch_assoc($customer_result);
            mysqli_stmt_close($stmt);
        } else {
            $error = "Error creating profile: " . mysqli_error($conn);
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
    <title>My Profile - HappyTail Grooming</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/customer/customer.css">
</head>
<body>
    <?php include "../core/menu.php"; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>My Profile</h1>
                <p>Manage your account information</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert-message" style="background-color: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin: 20px 0;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error-message" style="padding: 15px; margin: 20px 0;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container" style="max-width: 600px; margin: 0 auto;">
                <form method="POST" action="" class="login-form">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($customer_data['name']); ?>" disabled>
                        <small style="color: #6b7280;">Name cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($customer_data['email']); ?>" disabled>
                        <small style="color: #6b7280;">Email cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars(isset($customer_data['phone']) ? $customer_data['phone'] : ''); ?>" 
                               placeholder="Enter your phone number">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3" 
                                  placeholder="Enter your address"><?php echo htmlspecialchars(isset($customer_data['address']) ? $customer_data['address'] : ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
    
    <?php include "../core/footer.php"; ?>
</body>
</html>

