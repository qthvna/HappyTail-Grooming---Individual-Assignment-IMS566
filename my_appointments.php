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

// Handle cancel appointment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_appointment']) && $customer_id) {
    $appointment_id = (int)$_POST['appointment_id'];
    
    // Verify appointment belongs to customer
    $verify_query = "SELECT a.appointment_id, a.status 
                     FROM appointments a 
                     JOIN cats cat ON a.cat_id = cat.cat_id 
                     WHERE a.appointment_id = ? AND cat.customer_id = ?";
    $stmt = mysqli_prepare($conn, $verify_query);
    mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $customer_id);
    mysqli_stmt_execute($stmt);
    $verify_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($verify_result) > 0) {
        $appointment = mysqli_fetch_assoc($verify_result);
        $current_status = strtolower($appointment['status']);
        
        // Only allow canceling if status is pending or confirmed
        if ($current_status == 'pending' || $current_status == 'confirmed') {
            // Update appointment status to cancelled
            $update_query = "UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "i", $appointment_id);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                $message = "Appointment cancelled successfully!";
                // Refresh page to show updated status
                header("Location: my_appointments.php?cancelled=1");
                exit();
            } else {
                $error = "Error cancelling appointment: " . mysqli_error($conn);
                mysqli_stmt_close($stmt);
            }
        } else {
            $error = "Cannot cancel appointment. Only pending or confirmed appointments can be cancelled.";
        }
    } else {
        $error = "Appointment not found or you don't have permission to cancel it.";
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - HappyTail Grooming</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/customer/customer.css">
</head>
<body>
    <?php include "../menu.php"; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>My Appointments</h1>
                <p>View and manage your grooming appointments</p>
                <a href="book_appointment.php" class="btn btn-primary" style="margin-top: 10px;">Book New Appointment</a>
            </div>
            
            <?php if ($customer_id): ?>
                <?php if (isset($_GET['cancelled']) && $_GET['cancelled'] == '1'): ?>
                    <div class="alert-message" style="background-color: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin: 20px 0;">
                        Appointment cancelled successfully!
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
                            <th>Date</th>
                            <th>Time</th>
                            <th>Cat Name</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT a.*, cat.cat_name, cat.customer_id, s.service_name, s.price 
                                 FROM appointments a 
                                 JOIN cats cat ON a.cat_id = cat.cat_id 
                                 JOIN services s ON a.service_id = s.service_id 
                                 WHERE cat.customer_id = ? 
                                 ORDER BY a.appointment_date DESC, a.appointment_time DESC";
                        
                        $stmt = mysqli_prepare($conn, $query);
                        mysqli_stmt_bind_param($stmt, "i", $customer_id);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . $row['appointment_id'] . "</td>";
                                echo "<td>" . date('M d, Y', strtotime($row['appointment_date'])) . "</td>";
                                echo "<td>" . date('h:i A', strtotime($row['appointment_time'])) . "</td>";
                                echo "<td>" . htmlspecialchars($row['cat_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['service_name']) . " (RM " . number_format($row['price'], 2) . ")</td>";
                                echo "<td><span class='status-badge status-" . strtolower($row['status']) . "'>" . ucfirst($row['status']) . "</span></td>";
                                $notes = isset($row['notes']) ? $row['notes'] : '';
                                echo "<td>" . htmlspecialchars(substr($notes, 0, 50)) . (strlen($notes) > 50 ? '...' : '') . "</td>";
                                
                                // Cancel button - only show for pending or confirmed appointments
                                $status_lower = strtolower($row['status']);
                                echo "<td>";
                                if ($status_lower == 'pending' || $status_lower == 'confirmed') {
                                    echo "<form method='POST' action='' style='display: inline;' onsubmit='return confirmCancel(" . $row['appointment_id'] . ");'>";
                                    echo "<input type='hidden' name='appointment_id' value='" . $row['appointment_id'] . "'>";
                                    echo "<input type='hidden' name='cancel_appointment' value='1'>";
                                    echo "<button type='submit' class='btn btn-action danger btn-small'>Cancel</button>";
                                    echo "</form>";
                                } else {
                                    echo "<span style='color: var(--text-secondary); font-size: 0.85rem;'>-</span>";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8' class='no-data'>No appointments found. <a href='book_appointment.php'>Book your first appointment!</a></td></tr>";
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
        function confirmCancel(appointmentId) {
            return confirm('Are you sure you want to cancel this appointment? This action cannot be undone.');
        }
    </script>
</body>
</html>

