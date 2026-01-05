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

// Handle delete cat
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_cat'])) {
    $cat_id = (int)$_POST['cat_id'];
    
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
        $delete_query = "DELETE FROM cats WHERE cat_id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $cat_id);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            // Redirect with success message
            header("Location: manage_cats.php?deleted=1");
            exit();
        } else {
            $error = "Error deleting cat: " . mysqli_error($conn);
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle search
// Handle search - validate search_by against whitelist
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_by = isset($_GET['search_by']) ? $_GET['search_by'] : 'cat_name';
$valid_search_fields = ['cat_id', 'cat_name', 'owner_name', 'breed', 'owner_email'];
if (!in_array($search_by, $valid_search_fields)) {
    $search_by = 'cat_name';
}

// Build query with search using prepared statements
$where_conditions = [];
$where_params = [];
$where_types = '';

if (!empty($search_term)) {
    switch ($search_by) {
        case 'cat_id':
            // Search by exact ID match - cast to int for safety
            $cat_id_search = (int)$search_term;
            if ($cat_id_search > 0) {
                $where_conditions[] = "cat.cat_id = ?";
                $where_params[] = $cat_id_search;
                $where_types .= 'i';
            } else {
                $where_conditions[] = "1=0"; // No results if invalid ID
            }
            break;
        case 'cat_name':
            $where_conditions[] = "cat.cat_name LIKE ?";
            $where_params[] = "%" . $search_term . "%";
            $where_types .= 's';
            break;
        case 'owner_name':
            $where_conditions[] = "u.name LIKE ?";
            $where_params[] = "%" . $search_term . "%";
            $where_types .= 's';
            break;
        case 'breed':
            $where_conditions[] = "cat.breed LIKE ?";
            $where_params[] = "%" . $search_term . "%";
            $where_types .= 's';
            break;
        case 'owner_email':
            $where_conditions[] = "u.email LIKE ?";
            $where_params[] = "%" . $search_term . "%";
            $where_types .= 's';
            break;
        default:
            $where_conditions[] = "cat.cat_name LIKE ?";
            $where_params[] = "%" . $search_term . "%";
            $where_types .= 's';
    }
}

// Build base query
$query = "SELECT cat.*, u.name as owner_name, u.email as owner_email, c.phone as owner_phone, c.customer_id
          FROM cats cat 
          JOIN customers c ON cat.customer_id = c.customer_id 
          JOIN users u ON c.user_id = u.user_id";

// Add WHERE clause if we have conditions
if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " ORDER BY cat.cat_id DESC";

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
    <title>Cats - HappyTail Grooming</title>
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
        
        .delete-btn {
            padding: 8px 16px;
            font-size: 0.9rem;
            border: none;
            border-radius: 8px;
            background: var(--danger-color);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            width: 80px;
            text-align: center;
            box-sizing: border-box;
            display: inline-block;
            line-height: 1.4;
        }
        
        .delete-btn:hover {
            background: #dc2626;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .edit-btn {
            padding: 8px 16px;
            font-size: 0.9rem;
            border: none;
            border-radius: 8px;
            background: var(--primary-color);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            width: 80px;
            text-align: center;
            box-sizing: border-box;
            line-height: 1.4;
        }
        
        .edit-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .action-buttons form {
            display: inline-block;
            margin: 0;
            padding: 0;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            align-items: center;
        }
    </style>
</head>
<body>
    <?php include "../menu.php"; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>Cats</h1>
                <p>View and manage all registered cats</p>
            </div>
            
            <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
                <div class="alert-message" style="background-color: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin: 20px 0;">
                    Cat deleted successfully!
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error-message" style="padding: 15px; margin: 20px 0;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Search Section -->
            <div class="search-section">
                <?php if (!empty($search_term)): ?>
                    <a href="manage_cats.php" class="clear-btn">Clear</a>
                <?php endif; ?>
                <div style="display: flex; gap: 10px; align-items: center; margin-left: auto;">
                    <select name="search_by" class="search-select" form="searchForm">
                        <option value="cat_id" <?php echo $search_by == 'cat_id' ? 'selected' : ''; ?>>ID</option>
                        <option value="cat_name" <?php echo $search_by == 'cat_name' ? 'selected' : ''; ?>>Cat Name</option>
                        <option value="owner_name" <?php echo $search_by == 'owner_name' ? 'selected' : ''; ?>>Owner Name</option>
                        <option value="breed" <?php echo $search_by == 'breed' ? 'selected' : ''; ?>>Breed</option>
                        <option value="owner_email" <?php echo $search_by == 'owner_email' ? 'selected' : ''; ?>>Owner Email</option>
                    </select>
                    <form method="GET" action="" class="search-form" id="searchForm">
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
                            <th>Cat Name</th>
                            <th>Breed</th>
                            <th>Age</th>
                            <th>Owner</th>
                            <th>Special Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result !== false && mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . $row['cat_id'] . "</td>";
                                echo "<td><strong>" . htmlspecialchars($row['cat_name']) . "</strong></td>";
                                $breed = isset($row['breed']) && !empty($row['breed']) ? htmlspecialchars($row['breed']) : 'N/A';
                                $age = isset($row['age']) && !empty($row['age']) ? $row['age'] . ' years' : 'N/A';
                                $notes = isset($row['special_notes']) ? $row['special_notes'] : '';
                                echo "<td>" . $breed . "</td>";
                                echo "<td>" . $age . "</td>";
                                echo "<td>";
                                echo "<strong>" . htmlspecialchars($row['owner_name']) . "</strong><br>";
                                echo "<small style='color: var(--text-secondary);'>" . htmlspecialchars($row['owner_email']) . "</small>";
                                if (!empty($row['owner_phone'])) {
                                    echo "<br><small style='color: var(--text-secondary);'>" . htmlspecialchars($row['owner_phone']) . "</small>";
                                }
                                echo "</td>";
                                echo "<td>" . htmlspecialchars(substr($notes, 0, 50)) . (strlen($notes) > 50 ? '...' : '') . "</td>";
                                echo "<td style='white-space: nowrap;'>";
                                echo "<div class='action-buttons'>";
                                echo "<a href='../customer/edit_cat.php?id=" . $row['cat_id'] . "' class='edit-btn'>Edit</a>";
                                echo "<form method='POST' action='' style='display: inline; margin: 0;' onsubmit='return confirmDelete(" . $row['cat_id'] . ", \"" . htmlspecialchars(addslashes($row['cat_name'])) . "\", \"" . htmlspecialchars(addslashes($row['owner_name'])) . "\");'>";
                                echo "<input type='hidden' name='cat_id' value='" . $row['cat_id'] . "'>";
                                echo "<button type='submit' name='delete_cat' class='delete-btn'>Delete</button>";
                                echo "</form>";
                                echo "</div>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='no-data'>No cats found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php include "../footer.php"; ?>
    
    <script>
        function confirmDelete(catId, catName, ownerName) {
            return confirm('Are you sure you want to DELETE cat "' + catName + '"?\n\nOwner: ' + ownerName + '\n\nThis action cannot be undone!\n\nNote: If this cat has appointments, they must be cancelled first.');
        }
        
        // Sync search_by dropdown with hidden input
        document.querySelector('.search-select').addEventListener('change', function() {
            document.querySelector('input[name="search_by"]').value = this.value;
        });
    </script>
</body>
</html>

