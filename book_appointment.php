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

// Check which columns exist in appointments table
$has_customer_id_column = false;
$has_notes_column = false;

$check_column = mysqli_query($conn, "SHOW COLUMNS FROM appointments LIKE 'customer_id'");
if ($check_column && mysqli_num_rows($check_column) > 0) {
    $has_customer_id_column = true;
}

$check_notes = mysqli_query($conn, "SHOW COLUMNS FROM appointments LIKE 'notes'");
if ($check_notes && mysqli_num_rows($check_notes) > 0) {
    $has_notes_column = true;
}

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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $customer_id) {
    $cat_id = mysqli_real_escape_string($conn, isset($_POST['cat_id']) ? $_POST['cat_id'] : '');
    $service_id = mysqli_real_escape_string($conn, isset($_POST['service_id']) ? $_POST['service_id'] : '');
    $appointment_date = mysqli_real_escape_string($conn, isset($_POST['appointment_date']) ? $_POST['appointment_date'] : '');
    $appointment_time = mysqli_real_escape_string($conn, isset($_POST['appointment_time']) ? $_POST['appointment_time'] : '');
    $notes = mysqli_real_escape_string($conn, isset($_POST['notes']) ? $_POST['notes'] : '');
    
    // Validation
    if (empty($cat_id)) {
        $error = "Please select a cat.";
    } elseif (empty($service_id)) {
        $error = "Please select a service.";
    } elseif (empty($appointment_date)) {
        $error = "Please select an appointment date.";
    } elseif (empty($appointment_time)) {
        $error = "Please select an appointment time.";
    } else {
        // Cast to integers for security
        $cat_id = (int)$cat_id;
        $service_id = (int)$service_id;
        
        // Verify cat belongs to customer (using prepared statement)
        $cat_check = "SELECT cat_id FROM cats WHERE cat_id = ? AND customer_id = ?";
        $stmt = mysqli_prepare($conn, $cat_check);
        mysqli_stmt_bind_param($stmt, "ii", $cat_id, $customer_id);
        mysqli_stmt_execute($stmt);
        $cat_check_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($cat_check_result) == 1) {
            mysqli_stmt_close($stmt);
            
            // Verify service exists (using prepared statement)
            $service_check = "SELECT service_id FROM services WHERE service_id = ?";
            $stmt = mysqli_prepare($conn, $service_check);
            mysqli_stmt_bind_param($stmt, "i", $service_id);
            mysqli_stmt_execute($stmt);
            $service_check_result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($service_check_result) == 1) {
                mysqli_stmt_close($stmt);
                
                // Insert appointment (using prepared statement)
                // Build query based on which columns exist
                if ($has_customer_id_column && $has_notes_column) {
                    // Full structure with both customer_id and notes
                    $insert_query = "INSERT INTO appointments (customer_id, cat_id, service_id, appointment_date, appointment_time, status, notes) 
                                    VALUES (?, ?, ?, ?, ?, 'pending', ?)";
                    $stmt = mysqli_prepare($conn, $insert_query);
                    if ($stmt !== false) {
                        mysqli_stmt_bind_param($stmt, "iiisss", $customer_id, $cat_id, $service_id, $appointment_date, $appointment_time, $notes);
                    }
                } elseif ($has_customer_id_column && !$has_notes_column) {
                    // Has customer_id but no notes
                    $insert_query = "INSERT INTO appointments (customer_id, cat_id, service_id, appointment_date, appointment_time, status) 
                                    VALUES (?, ?, ?, ?, ?, 'pending')";
                    $stmt = mysqli_prepare($conn, $insert_query);
                    if ($stmt !== false) {
                        mysqli_stmt_bind_param($stmt, "iiiss", $customer_id, $cat_id, $service_id, $appointment_date, $appointment_time);
                    }
                } elseif (!$has_customer_id_column && $has_notes_column) {
                    // No customer_id but has notes
                    $insert_query = "INSERT INTO appointments (cat_id, service_id, appointment_date, appointment_time, status, notes) 
                                    VALUES (?, ?, ?, ?, 'pending', ?)";
                    $stmt = mysqli_prepare($conn, $insert_query);
                    if ($stmt !== false) {
                        mysqli_stmt_bind_param($stmt, "iisss", $cat_id, $service_id, $appointment_date, $appointment_time, $notes);
                    }
                } else {
                    // Neither customer_id nor notes
                    $insert_query = "INSERT INTO appointments (cat_id, service_id, appointment_date, appointment_time, status) 
                                    VALUES (?, ?, ?, ?, 'pending')";
                    $stmt = mysqli_prepare($conn, $insert_query);
                    if ($stmt !== false) {
                        mysqli_stmt_bind_param($stmt, "iiss", $cat_id, $service_id, $appointment_date, $appointment_time);
                    }
                }
                
                if ($stmt === false) {
                    $error = "Error preparing query: " . mysqli_error($conn) . ". Please check your appointments table structure.";
                } else {
                    if (mysqli_stmt_execute($stmt)) {
                        mysqli_stmt_close($stmt);
                        // Redirect with success parameter to show popup
                        header("Location: book_appointment.php?success=1");
                        exit();
                    } else {
                        $error = "Error booking appointment: " . mysqli_stmt_error($stmt) . " (MySQL Error: " . mysqli_error($conn) . ")";
                        mysqli_stmt_close($stmt);
                    }
                }
            } else {
                $error = "Invalid service selected.";
                mysqli_stmt_close($stmt);
            }
        } else {
            $error = "Invalid cat selected.";
            mysqli_stmt_close($stmt);
        }
    }
}

// Get customer's cats (using prepared statement)
if ($customer_id) {
    $cats_query = "SELECT * FROM cats WHERE customer_id = ? ORDER BY cat_name";
    $stmt = mysqli_prepare($conn, $cats_query);
    if ($stmt === false) {
        $cats_result = false;
        error_log("Error preparing cats query: " . mysqli_error($conn));
    } else {
        mysqli_stmt_bind_param($stmt, "i", $customer_id);
        mysqli_stmt_execute($stmt);
        $cats_result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
    }
} else {
    // Return empty result if no customer_id
    $cats_query = "SELECT * FROM cats WHERE customer_id = 0";
    $cats_result = mysqli_query($conn, $cats_query);
}

// Get available services
$services_query = "SELECT * FROM services ORDER BY service_name";
$services_result = mysqli_query($conn, $services_query);
$services_error = '';
if (!$services_result) {
    $services_error = "Error loading services: " . mysqli_error($conn);
}

// Function to get service icon based on service name
function getServiceIcon($service_name) {
    $icons = [
        'Basic Grooming' => '🛁',
        'Full Grooming' => '✂️',
        'Nail Trim Only' => '💅',
        'Bath & Brush' => '✨',
        'Premium Package' => '👑'
    ];
    return isset($icons[$service_name]) ? $icons[$service_name] : '🐱';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - HappyTail Grooming</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/customer/customer.css">
    <link rel="stylesheet" href="../../css/home/index.css">
    <style>
        .service-selection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .service-card-selectable {
            background: var(--bg-primary);
            border-radius: 12px;
            padding: 15px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            text-align: center;
            cursor: pointer;
            border: 2px solid transparent;
            position: relative;
        }
        .service-card-selectable:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }
        .service-card-selectable.selected {
            border-color: var(--primary-color);
            background: rgba(99, 102, 241, 0.05);
        }
        .service-card-selectable input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
            margin: 0;
        }
        .service-icon-selectable {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .service-card-selectable h3 {
            font-size: 1rem;
            margin-bottom: 8px;
            color: var(--text-primary);
        }
        .service-card-selectable p {
            color: var(--text-secondary);
            line-height: 1.4;
            margin-bottom: 10px;
            font-size: 0.8rem;
        }
        .service-price-selectable {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        .service-duration-selectable {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        .form-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .form-buttons .btn {
            width: auto;
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
                <h1>Book Appointment</h1>
                <p>Schedule a grooming appointment for your cat</p>
            </div>
            
            <?php if ($customer_id): ?>
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
                        <h2>Appointment Booked Successfully!</h2>
                        <p>Your appointment has been confirmed. You will be redirected to your appointments page.</p>
                        <button type="button" class="btn btn-primary" onclick="redirectToAppointments()">View My Appointments</button>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="form-container" style="max-width: 1200px; margin: 0 auto;">
                    <form method="POST" action="" class="login-form">
                        <div class="form-group">
                            <label for="cat_id">Select Cat *</label>
                            <select id="cat_id" name="cat_id" required>
                                <option value="">-- Select a cat --</option>
                                <?php
                                if (mysqli_num_rows($cats_result) > 0) {
                                    while ($cat = mysqli_fetch_assoc($cats_result)) {
                                        echo "<option value='" . $cat['cat_id'] . "'>" . htmlspecialchars($cat['cat_name']) . "</option>";
                                    }
                                }
                                ?>
                            </select>
                            <?php if ($cats_result === false || mysqli_num_rows($cats_result) == 0): ?>
                                <p style="color: #ef4444; font-size: 0.9rem; margin-top: 5px;">No cats registered. <a href="add_cat.php">Add a cat first</a></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 15px; font-weight: 600;">Select Service *</label>
                            <?php if ($services_error): ?>
                                <div class="error-message" style="padding: 15px; margin: 10px 0;">
                                    <?php echo htmlspecialchars($services_error); ?>
                                </div>
                            <?php elseif (mysqli_num_rows($services_result) == 0): ?>
                                <div class="alert-message" style="background-color: #fef3c7; color: #92400e; padding: 15px; border-radius: 8px; margin: 10px 0;">
                                    <p><strong>No services available.</strong> Please contact the administrator to add services to the system.</p>
                                </div>
                            <?php else: ?>
                                <div class="service-selection-grid" id="serviceGrid" required>
                                    <?php
                                    mysqli_data_seek($services_result, 0);
                                    $first_service = true;
                                    while ($service = mysqli_fetch_assoc($services_result)) {
                                        $icon = getServiceIcon($service['service_name']);
                                        $description = htmlspecialchars(isset($service['description']) ? $service['description'] : $service['service_name']);
                                        $service_id_attr = 'service_' . $service['service_id'];
                                        echo '<label class="service-card-selectable" for="' . $service_id_attr . '">';
                                        // Only first radio needs required attribute for HTML5 validation
                                        $required_attr = $first_service ? 'required' : '';
                                        echo '<input type="radio" id="' . $service_id_attr . '" name="service_id" value="' . $service['service_id'] . '" ' . $required_attr . '>';
                                        echo '<div class="service-icon-selectable">' . $icon . '</div>';
                                        echo '<h3>' . htmlspecialchars($service['service_name']) . '</h3>';
                                        echo '<p>' . $description . '</p>';
                                        echo '<div class="service-price-selectable">RM ' . number_format($service['price'], 2) . '</div>';
                                        echo '<div class="service-duration-selectable">' . $service['duration'] . ' minutes</div>';
                                        echo '</label>';
                                        $first_service = false;
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                            <div id="service-error" style="color: #ef4444; font-size: 0.9rem; margin-top: 10px; display: none;">Please select a service</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="appointment_date">Appointment Date *</label>
                            <input type="date" id="appointment_date" name="appointment_date" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="appointment_time">Appointment Time *</label>
                            <input type="time" id="appointment_time" name="appointment_time" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Special Notes</label>
                            <textarea id="notes" name="notes" rows="4" placeholder="Any special instructions or notes..."></textarea>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="submit" class="btn btn-primary" <?php echo mysqli_num_rows($cats_result) == 0 ? 'disabled' : ''; ?>>Book Appointment</button>
                            <a href="my_appointments.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
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
        // Handle service card selection
        document.querySelectorAll('.service-card-selectable').forEach(card => {
            const radio = card.querySelector('input[type="radio"]');
            const errorDiv = document.getElementById('service-error');
            
            card.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove selected class from all cards
                document.querySelectorAll('.service-card-selectable').forEach(c => {
                    c.classList.remove('selected');
                });
                
                // Add selected class to clicked card
                card.classList.add('selected');
                
                // Check the radio button
                radio.checked = true;
                
                // Hide error message if shown
                if (errorDiv) {
                    errorDiv.style.display = 'none';
                }
                
                // Trigger change event for form validation
                radio.dispatchEvent(new Event('change', { bubbles: true }));
            });
            
            // Also handle radio change
            radio.addEventListener('change', function() {
                if (this.checked) {
                    document.querySelectorAll('.service-card-selectable').forEach(c => {
                        c.classList.remove('selected');
                    });
                    card.classList.add('selected');
                    if (errorDiv) {
                        errorDiv.style.display = 'none';
                    }
                }
            });
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const serviceSelected = document.querySelector('input[name="service_id"]:checked');
            const errorDiv = document.getElementById('service-error');
            
            if (!serviceSelected) {
                e.preventDefault();
                if (errorDiv) {
                    errorDiv.style.display = 'block';
                }
                // Scroll to service selection
                document.getElementById('serviceGrid').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
        });
        
        // Redirect to appointments page
        function redirectToAppointments() {
            window.location.href = 'my_appointments.php';
        }
        
        // Auto-redirect after 3 seconds if modal is shown
        <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
        setTimeout(function() {
            redirectToAppointments();
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>

