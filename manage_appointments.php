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

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $appointment_id = (int)$_POST['appointment_id'];
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Validate status
    $valid_statuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
    if (in_array($new_status, $valid_statuses)) {
        $update_query = "UPDATE appointments SET status = ? WHERE appointment_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $new_status, $appointment_id);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            $message = "Appointment status updated successfully!";
        } else {
            $error = "Error updating appointment: " . mysqli_error($conn);
            mysqli_stmt_close($stmt);
        }
    } else {
        $error = "Invalid status selected.";
    }
}

// Handle delete appointment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_appointment'])) {
    $appointment_id = (int)$_POST['appointment_id'];
    
    // Delete appointment
    $delete_query = "DELETE FROM appointments WHERE appointment_id = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        // Redirect with success message
        $redirect_url = "manage_appointments.php?deleted=1";
        if (!empty($filter_status)) {
            $redirect_url .= "&status=" . urlencode($filter_status);
        }
        if (!empty($search_term)) {
            $redirect_url .= "&search=" . urlencode($search_term) . "&search_by=" . urlencode($search_by);
        }
        header("Location: " . $redirect_url);
        exit();
    } else {
        $error = "Error deleting appointment: " . mysqli_error($conn);
        mysqli_stmt_close($stmt);
    }
}

// Get filter status (if any) - validated against whitelist
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$valid_statuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
if (!empty($filter_status) && !in_array($filter_status, $valid_statuses)) {
    $filter_status = '';
}

// Handle search - validate search_by against whitelist
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_by = isset($_GET['search_by']) ? $_GET['search_by'] : 'appointment_id';
$valid_search_fields = ['appointment_id', 'customer_name', 'cat_name', 'service_name', 'customer_email'];
if (!in_array($search_by, $valid_search_fields)) {
    $search_by = 'appointment_id';
}

// Build query with optional filter and search using prepared statements
$where_conditions = [];
$where_params = [];
$where_types = '';

// Add status filter (already validated)
if (!empty($filter_status)) {
    $where_conditions[] = "a.status = ?";
    $where_params[] = $filter_status;
    $where_types .= 's';
}

// Add search conditions
if (!empty($search_term)) {
    switch ($search_by) {
        case 'appointment_id':
            // Search by exact ID match - cast to int for safety
            $appointment_id_search = (int)$search_term;
            if ($appointment_id_search > 0) {
                $where_conditions[] = "a.appointment_id = ?";
                $where_params[] = $appointment_id_search;
                $where_types .= 'i';
            } else {
                $where_conditions[] = "1=0"; // No results if invalid ID
            }
            break;
        case 'customer_name':
            $where_conditions[] = "u.name LIKE ?";
            $where_params[] = "%" . $search_term . "%";
            $where_types .= 's';
            break;
        case 'cat_name':
            $where_conditions[] = "cat.cat_name LIKE ?";
            $where_params[] = "%" . $search_term . "%";
            $where_types .= 's';
            break;
        case 'service_name':
            $where_conditions[] = "s.service_name LIKE ?";
            $where_params[] = "%" . $search_term . "%";
            $where_types .= 's';
            break;
        case 'customer_email':
            $where_conditions[] = "u.email LIKE ?";
            $where_params[] = "%" . $search_term . "%";
            $where_types .= 's';
            break;
        default:
            $appointment_id_search = (int)$search_term;
            if ($appointment_id_search > 0) {
                $where_conditions[] = "a.appointment_id = ?";
                $where_params[] = $appointment_id_search;
                $where_types .= 'i';
            } else {
                $where_conditions[] = "1=0";
            }
    }
}

// Build base query
$query = "SELECT a.*, cat.cat_name, s.service_name, s.price, u.name as customer_name, u.email as customer_email, c.phone as customer_phone
          FROM appointments a 
          JOIN cats cat ON a.cat_id = cat.cat_id 
          JOIN services s ON a.service_id = s.service_id 
          JOIN customers c ON a.customer_id = c.customer_id 
          JOIN users u ON c.user_id = u.user_id";

// Add WHERE clause if we have conditions
if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

// Execute query with prepared statement if we have parameters
if (!empty($where_params)) {
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt !== false) {
        mysqli_stmt_bind_param($stmt, $where_types, ...$where_params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $result = false;
        $error = "Error preparing search query: " . mysqli_error($conn);
    }
} else {
    $result = mysqli_query($conn, $query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - HappyTail Grooming</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        /* Search Section */
        .search-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 10px;
        }
        
        .search-group {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-left: auto;
        }
        
        .search-select {
            padding: 8px 12px;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            font-size: 0.9rem;
            background: var(--bg-primary);
            color: var(--text-primary);
            cursor: pointer;
        }
        
        .search-select:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .search-form {
            display: flex;
            gap: 0;
            align-items: center;
        }
        
        .search-input {
            padding: 8px 12px;
            border: 2px solid var(--border-color);
            border-radius: 6px 0 0 6px;
            font-size: 0.9rem;
            width: 250px;
            border-right: none;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .search-btn {
            padding: 8px 20px;
            font-size: 0.9rem;
            border: 2px solid var(--primary-color);
            border-radius: 0 6px 6px 0;
            background: var(--primary-color);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: none;
        }
        
        .search-btn:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .clear-btn {
            padding: 8px 20px;
            font-size: 0.9rem;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            background: var(--bg-primary);
            color: var(--text-primary);
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .clear-btn:hover {
            background: var(--bg-tertiary);
            border-color: var(--primary-color);
        }
        
        .filter-section {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            border: 2px solid var(--border-color);
            background: var(--bg-primary);
            color: var(--text-primary);
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .filter-btn:hover {
            background: var(--bg-tertiary);
            border-color: var(--primary-color);
        }
        
        .filter-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .status-form {
            display: flex;
            gap: 5px;
            align-items: center;
            white-space: nowrap;
        }
        
        .status-select {
            padding: 4px 8px;
            border: 2px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.75rem;
            background: var(--bg-primary);
            color: var(--text-primary);
            cursor: pointer;
            min-width: 90px;
            max-width: 90px;
        }
        
        .status-select:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .update-btn {
            padding: 4px 10px;
            font-size: 0.75rem;
            border: none;
            border-radius: 4px;
            background: var(--primary-color);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .update-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }
        
        .delete-btn {
            padding: 4px 10px;
            font-size: 0.75rem;
            border: none;
            border-radius: 4px;
            background: var(--danger-color);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .delete-btn:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        /* Reduce table cell padding and font sizes */
        .data-table th,
        .data-table td {
            padding: 10px 8px;
            font-size: 0.85rem;
        }
        
        .data-table th {
            font-size: 0.8rem;
        }
        
        /* Make customer info more compact */
        .data-table td small {
            font-size: 0.75rem;
            display: block;
            line-height: 1.3;
        }
        
        /* Reduce table container width if needed */
        .table-container {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include "../menu.php"; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>Appointments</h1>
                <p>Update appointment statuses and manage all grooming appointments</p>
            </div>
            
            <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
                <div class="alert-message" style="background-color: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin: 20px 0;">
                    Appointment deleted successfully!
                </div>
            <?php endif; ?>
            
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
            
            <!-- Filter and Search Section -->
            <div class="filter-section">
                <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <strong>Filter by Status:</strong>
                    <div class="filter-buttons">
                        <a href="manage_appointments.php<?php echo !empty($search_term) ? '?search=' . urlencode($search_term) . '&search_by=' . urlencode($search_by) : ''; ?>" class="filter-btn <?php echo empty($filter_status) ? 'active' : ''; ?>">All</a>
                        <a href="manage_appointments.php?status=Pending<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) . '&search_by=' . urlencode($search_by) : ''; ?>" class="filter-btn <?php echo $filter_status == 'Pending' ? 'active' : ''; ?>">Pending</a>
                        <a href="manage_appointments.php?status=Confirmed<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) . '&search_by=' . urlencode($search_by) : ''; ?>" class="filter-btn <?php echo $filter_status == 'Confirmed' ? 'active' : ''; ?>">Confirmed</a>
                        <a href="manage_appointments.php?status=Completed<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) . '&search_by=' . urlencode($search_by) : ''; ?>" class="filter-btn <?php echo $filter_status == 'Completed' ? 'active' : ''; ?>">Completed</a>
                        <a href="manage_appointments.php?status=Cancelled<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) . '&search_by=' . urlencode($search_by) : ''; ?>" class="filter-btn <?php echo $filter_status == 'Cancelled' ? 'active' : ''; ?>">Cancelled</a>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; align-items: center; margin-left: auto;">
                    <?php if (!empty($search_term)): ?>
                        <a href="manage_appointments.php<?php echo !empty($filter_status) ? '?status=' . urlencode($filter_status) : ''; ?>" class="clear-btn">Clear</a>
                    <?php endif; ?>
                    <select name="search_by" class="search-select" form="searchForm">
                        <option value="appointment_id" <?php echo $search_by == 'appointment_id' ? 'selected' : ''; ?>>ID</option>
                        <option value="customer_name" <?php echo $search_by == 'customer_name' ? 'selected' : ''; ?>>Customer Name</option>
                        <option value="cat_name" <?php echo $search_by == 'cat_name' ? 'selected' : ''; ?>>Cat Name</option>
                        <option value="service_name" <?php echo $search_by == 'service_name' ? 'selected' : ''; ?>>Service Name</option>
                        <option value="customer_email" <?php echo $search_by == 'customer_email' ? 'selected' : ''; ?>>Customer Email</option>
                    </select>
                    <form method="GET" action="" class="search-form" id="searchForm">
                        <?php if (!empty($filter_status)): ?>
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
                        <?php endif; ?>
                        <input type="hidden" name="search_by" value="<?php echo htmlspecialchars($search_by); ?>">
                        <input type="text" name="search" class="search-input" 
                               placeholder="Search..." 
                               value="<?php echo htmlspecialchars($search_term); ?>">
                        <button type="submit" class="search-btn">Search</button>
                    </form>
                </div>
            </div>
            
            <?php if (!empty($search_term)): ?>
                <div style="margin-bottom: 15px; color: var(--text-secondary); font-size: 0.9rem;">
                    Showing results for: "<strong><?php echo htmlspecialchars($search_term); ?></strong>" 
                    (<?php echo ($result !== false) ? mysqli_num_rows($result) : 0; ?> result(s))
                </div>
            <?php endif; ?>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Customer</th>
                            <th>Cat Name</th>
                            <th>Service</th>
                            <th>Price</th>
                            <th>Current Status</th>
                            <th>Update Status</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result !== false && mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . $row['appointment_id'] . "</td>";
                                echo "<td>" . date('M d, Y', strtotime($row['appointment_date'])) . "</td>";
                                echo "<td>" . date('h:i A', strtotime($row['appointment_time'])) . "</td>";
                                echo "<td>";
                                echo "<strong>" . htmlspecialchars($row['customer_name']) . "</strong><br>";
                                echo "<small style='color: var(--text-secondary);'>" . htmlspecialchars($row['customer_email']) . "</small>";
                                if (!empty($row['customer_phone'])) {
                                    echo "<br><small style='color: var(--text-secondary);'>" . htmlspecialchars($row['customer_phone']) . "</small>";
                                }
                                echo "</td>";
                                echo "<td>" . htmlspecialchars($row['cat_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['service_name']) . "</td>";
                                echo "<td>RM " . number_format($row['price'], 2) . "</td>";
                                echo "<td><span class='status-badge status-" . strtolower($row['status']) . "'>" . ucfirst($row['status']) . "</span></td>";
                                echo "<td style='white-space: nowrap; min-width: 150px;'>";
                                echo "<form method='POST' action='' class='status-form' onsubmit='return confirmUpdate(" . $row['appointment_id'] . ", this.status.value);'>";
                                echo "<input type='hidden' name='appointment_id' value='" . $row['appointment_id'] . "'>";
                                echo "<select name='status' class='status-select' required>";
                                $statuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
                                foreach ($statuses as $status) {
                                    $selected = ($row['status'] == $status) ? 'selected' : '';
                                    echo "<option value='" . $status . "' " . $selected . ">" . $status . "</option>";
                                }
                                echo "</select>";
                                echo "<button type='submit' name='update_status' class='update-btn'>Update</button>";
                                echo "</form>";
                                echo "</td>";
                                $notes = isset($row['notes']) ? $row['notes'] : '';
                                echo "<td>" . htmlspecialchars(substr($notes, 0, 50)) . (strlen($notes) > 50 ? '...' : '') . "</td>";
                                echo "<td style='white-space: nowrap;'>";
                                echo "<form method='POST' action='' style='display: inline; margin: 0;' onsubmit='return confirmDelete(" . $row['appointment_id'] . ", \"" . htmlspecialchars(addslashes($row['customer_name'])) . "\", \"" . date('M d, Y', strtotime($row['appointment_date'])) . "\");'>";
                                echo "<input type='hidden' name='appointment_id' value='" . $row['appointment_id'] . "'>";
                                echo "<button type='submit' name='delete_appointment' class='delete-btn'>Delete</button>";
                                echo "</form>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='11' class='no-data'>No appointments found" . (!empty($filter_status) ? " with status: " . htmlspecialchars($filter_status) : "") . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php include "../footer.php"; ?>
    
    <script>
        function confirmUpdate(appointmentId, newStatus) {
            return confirm('Are you sure you want to update appointment #' + appointmentId + ' status to "' + newStatus + '"?');
        }
        
        function confirmDelete(appointmentId, customerName, appointmentDate) {
            return confirm('Are you sure you want to DELETE appointment #' + appointmentId + '?\n\nCustomer: ' + customerName + '\nDate: ' + appointmentDate + '\n\nThis action cannot be undone!');
        }
        
        // Sync search_by dropdown with hidden input
        document.querySelector('.search-select').addEventListener('change', function() {
            document.querySelector('input[name="search_by"]').value = this.value;
        });
    </script>
</body>
</html>

