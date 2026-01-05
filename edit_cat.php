<?php
include "../auth.php";
include "../db.php";

// Ensure only customers can access
if ($_SESSION['role'] != 'customer') {
    header("Location: ../index.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$message = '';
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

$cat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify cat belongs to customer (using prepared statement)
if ($cat_id && $customer_id) {
    $cat_check = "SELECT * FROM cats WHERE cat_id = ? AND customer_id = ?";
    $stmt = mysqli_prepare($conn, $cat_check);
    mysqli_stmt_bind_param($stmt, "ii", $cat_id, $customer_id);
    mysqli_stmt_execute($stmt);
    $cat_result = mysqli_stmt_get_result($stmt);
    $cat = mysqli_fetch_assoc($cat_result);
    mysqli_stmt_close($stmt);
    
    if (!$cat) {
        header("Location: my_cats.php");
        exit();
    }
} else {
    header("Location: my_cats.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cat_name = mysqli_real_escape_string($conn, $_POST['cat_name']);
    $breed = mysqli_real_escape_string($conn, isset($_POST['breed']) ? $_POST['breed'] : '');
    $age = isset($_POST['age']) && $_POST['age'] !== '' ? (int)$_POST['age'] : null;
    $special_notes = mysqli_real_escape_string($conn, isset($_POST['special_notes']) ? $_POST['special_notes'] : '');
    
    // Update cat (using prepared statement)
    // Handle nullable age
    if ($age === null) {
        $update_query = "UPDATE cats SET cat_name = ?, breed = ?, age = NULL, special_notes = ? 
                         WHERE cat_id = ? AND customer_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "sssii", $cat_name, $breed, $special_notes, $cat_id, $customer_id);
    } else {
        $update_query = "UPDATE cats SET cat_name = ?, breed = ?, age = ?, special_notes = ? 
                         WHERE cat_id = ? AND customer_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ssisii", $cat_name, $breed, $age, $special_notes, $cat_id, $customer_id);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        $message = "Cat information updated successfully!";
        // Refresh cat data
        $stmt = mysqli_prepare($conn, $cat_check);
        mysqli_stmt_bind_param($stmt, "ii", $cat_id, $customer_id);
        mysqli_stmt_execute($stmt);
        $cat_result = mysqli_stmt_get_result($stmt);
        $cat = mysqli_fetch_assoc($cat_result);
        mysqli_stmt_close($stmt);
    } else {
        $error = "Error updating cat: " . mysqli_error($conn);
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Cat - HappyTail Grooming</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/customer/customer.css">
</head>
<body>
    <?php include "../menu.php"; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>Edit Cat Information</h1>
                <p>Update your cat's details</p>
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
                        <label for="cat_name">Cat Name *</label>
                        <input type="text" id="cat_name" name="cat_name" 
                               value="<?php echo htmlspecialchars($cat['cat_name']); ?>" 
                               placeholder="Enter cat's name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="breed">Breed</label>
                        <input type="text" id="breed" name="breed" 
                               value="<?php echo htmlspecialchars(isset($cat['breed']) ? $cat['breed'] : ''); ?>" 
                               placeholder="e.g., Persian, Siamese, Maine Coon">
                    </div>
                    
                    <div class="form-group">
                        <label for="age">Age (years)</label>
                        <input type="number" id="age" name="age" min="0" max="30" 
                               value="<?php echo htmlspecialchars(isset($cat['age']) ? $cat['age'] : ''); ?>" 
                               placeholder="Enter age">
                    </div>
                    
                    <div class="form-group">
                        <label for="special_notes">Special Notes</label>
                        <textarea id="special_notes" name="special_notes" rows="4" 
                                  placeholder="Any special care instructions, allergies, or behavioral notes..."><?php echo htmlspecialchars(isset($cat['special_notes']) ? $cat['special_notes'] : ''); ?></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-top: 20px;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">Update Cat</button>
                        <a href="my_cats.php" class="btn btn-secondary" style="flex: 1; text-align: center;">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include "../footer.php"; ?>
</body>
</html>

