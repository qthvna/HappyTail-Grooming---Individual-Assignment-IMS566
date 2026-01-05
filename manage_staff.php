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

// Handle delete staff
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_staff'])) {
    $staff_user_id = (int)$_POST['user_id'];
    
    // Prevent deleting yourself
    if ($staff_user_id == $user_id) {
        $error = "You cannot delete your own account.";
    } else {
        // Delete user record (staff are just users with role='staff')
        $delete_user_query = "DELETE FROM users WHERE user_id = ? AND role = 'staff'";
        $stmt = mysqli_prepare($conn, $delete_user_query);
        mysqli_stmt_bind_param($stmt, "i", $staff_user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            // Redirect with success message
            header("Location: manage_staff.php?deleted=1");
            exit();
        } else {
            $error = "Error deleting staff: " . mysqli_error($conn);
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle search
$search_term = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$search_by = isset($_GET['search_by']) ? mysqli_real_escape_string($conn, $_GET['search_by']) : 'name';

// Build query with search
$where_clause = "WHERE u.role = 'staff'";
if (!empty($search_term)) {
    switch ($search_by) {
        case 'user_id':
            // Search by exact ID match
            $user_id_search = (int)$search_term;
            if ($user_id_search > 0) {
                $where_clause .= " AND u.user_id = " . $user_id_search;
            } else {
                $where_clause .= " AND 1=0"; // No results if invalid ID
            }
            break;
        case 'name':
            $where_clause .= " AND u.name LIKE '%" . $search_term . "%'";
            break;
        case 'email':
            $where_clause .= " AND u.email LIKE '%" . $search_term . "%'";
            break;
        default:
            $where_clause .= " AND u.name LIKE '%" . $search_term . "%'";
    }
}

$query = "SELECT u.user_id, u.name, u.email, u.created_at
          FROM users u 
          " . $where_clause . "
          ORDER BY u.user_id DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff - HappyTail Grooming</title>
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
                <h1>Staff</h1>
                <p>View and manage all staff members</p>
            </div>
            
            <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
                <div class="alert-message" style="background-color: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin: 20px 0;">
                    Staff deleted successfully!
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error-message" style="padding: 15px; margin: 20px 0;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Search Section -->
            <div class="search-section">
                <div style="display: flex; gap: 10px; align-items: center;">
                    <a href="add_staff.php" class="btn btn-primary" style="padding: 8px 20px; font-size: 0.9rem; text-decoration: none; display: inline-block;">New Staff</a>
                    <?php if (!empty($search_term)): ?>
                        <a href="manage_staff.php" class="clear-btn">Clear</a>
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 10px; align-items: center; margin-left: auto;">
                    <select name="search_by" class="search-select" form="searchForm">
                        <option value="user_id" <?php echo $search_by == 'user_id' ? 'selected' : ''; ?>>ID</option>
                        <option value="name" <?php echo $search_by == 'name' ? 'selected' : ''; ?>>Name</option>
                        <option value="email" <?php echo $search_by == 'email' ? 'selected' : ''; ?>>Email</option>
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
                    (<?php echo mysqli_num_rows($result); ?> result(s))
                </div>
            <?php endif; ?>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . $row['user_id'] . "</td>";
                                echo "<td><strong>" . htmlspecialchars($row['name']) . "</strong></td>";
                                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                $registered_date = date('Y-m-d', strtotime($row['created_at']));
                                echo "<td>" . $registered_date . "</td>";
                                echo "<td style='white-space: nowrap;'>";
                                echo "<div class='action-buttons'>";
                                echo "<a href='edit_staff.php?id=" . $row['user_id'] . "' class='edit-btn'>Edit</a>";
                                echo "<form method='POST' action='' style='display: inline; margin: 0;' onsubmit='return confirmDelete(" . $row['user_id'] . ", \"" . htmlspecialchars(addslashes($row['name'])) . "\");'>";
                                echo "<input type='hidden' name='user_id' value='" . $row['user_id'] . "'>";
                                echo "<button type='submit' name='delete_staff' class='delete-btn'>Delete</button>";
                                echo "</form>";
                                echo "</div>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='no-data'>No staff found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php include "../footer.php"; ?>
    
    <script>
        function confirmDelete(userId, staffName) {
            return confirm('Are you sure you want to DELETE staff "' + staffName + '"?\n\nThis action cannot be undone!');
        }
        
        // Sync search_by dropdown with hidden input
        document.querySelector('.search-select').addEventListener('change', function() {
            document.querySelector('input[name="search_by"]').value = this.value;
        });
    </script>
</body>
</html>

