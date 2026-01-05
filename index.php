<?php
include "../auth.php";
include "../db.php";

// Ensure only admin can access
if ($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HappyTail Grooming</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        /* Make stats grid fit in one row */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 15px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            padding: 16px;
            min-width: 0; /* Allow cards to shrink */
        }
        
        .stat-icon {
            font-size: 2rem;
            width: 60px;
            height: 60px;
        }
        
        .stat-info h3 {
            font-size: 1.5rem;
        }
        
        .stat-info p {
            font-size: 0.85rem;
        }
        
        /* Responsive: stack on smaller screens */
        @media (max-width: 1400px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Recent sections grid */
        .recent-section h2 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--text-primary);
        }
        
        @media (max-width: 1024px) {
            div[style*="grid-template-columns: 1fr 1fr"] {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>
<body>
    <?php include "../menu.php"; ?>
    
    <div class="main-content">
        <div class="container">
            <h1>Admin Dashboard</h1>
            <p class="welcome-text">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
            
            <div class="stats-grid">
                <?php
                // Total appointments
                $appt_query = "SELECT COUNT(*) as total FROM appointments";
                $appt_result = mysqli_query($conn, $appt_query);
                $appt_data = mysqli_fetch_assoc($appt_result);
                
                // Total cats
                $cat_query = "SELECT COUNT(*) as total FROM cats";
                $cat_result = mysqli_query($conn, $cat_query);
                $cat_data = mysqli_fetch_assoc($cat_result);
                
                // Total customers
                $customer_query = "SELECT COUNT(*) as total FROM customers";
                $customer_result = mysqli_query($conn, $customer_query);
                $customer_data = mysqli_fetch_assoc($customer_result);
                
                // Pending appointments
                $pending_query = "SELECT COUNT(*) as total FROM appointments WHERE status = 'pending'";
                $pending_result = mysqli_query($conn, $pending_query);
                $pending_data = mysqli_fetch_assoc($pending_result);
                
                // Upcoming appointments (next 7 days)
                $upcoming_query = "SELECT COUNT(*) as total FROM appointments 
                                  WHERE appointment_date >= CURDATE() 
                                  AND appointment_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                                  AND status != 'completed' AND status != 'cancelled'";
                $upcoming_result = mysqli_query($conn, $upcoming_query);
                $upcoming_data = mysqli_fetch_assoc($upcoming_result);
                
                // Total staff
                $staff_query = "SELECT COUNT(*) as total FROM users WHERE role = 'staff'";
                $staff_result = mysqli_query($conn, $staff_query);
                $staff_data = mysqli_fetch_assoc($staff_result);
                
                // Recent customers (4 newest)
                $recent_customers_query = "SELECT u.*, c.phone, c.address 
                                          FROM users u 
                                          LEFT JOIN customers c ON u.user_id = c.user_id 
                                          WHERE u.role = 'customer' 
                                          ORDER BY u.created_at DESC 
                                          LIMIT 4";
                $recent_customers_result = mysqli_query($conn, $recent_customers_query);
                
                // Recent cats (4 newest)
                $recent_cats_query = "SELECT cat.*, u.name as owner_name 
                                     FROM cats cat 
                                     JOIN customers c ON cat.customer_id = c.customer_id 
                                     JOIN users u ON c.user_id = u.user_id 
                                     ORDER BY cat.cat_id DESC 
                                     LIMIT 4";
                $recent_cats_result = mysqli_query($conn, $recent_cats_query);
                ?>
                
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-info">
                        <h3><?php echo $appt_data['total']; ?></h3>
                        <p>Total Appointments</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🐱</div>
                    <div class="stat-info">
                        <h3><?php echo $cat_data['total']; ?></h3>
                        <p>Registered Cats</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3><?php echo $customer_data['total']; ?></h3>
                        <p>Total Customers</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <h3><?php echo $pending_data['total']; ?></h3>
                        <p>Pending Appointments</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📆</div>
                    <div class="stat-info">
                        <h3><?php echo $upcoming_data['total']; ?></h3>
                        <p>Upcoming (7 days)</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👨‍💼</div>
                    <div class="stat-info">
                        <h3><?php echo $staff_data['total']; ?></h3>
                        <p>Total Staff</p>
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px;">
                <!-- Recent Customers -->
                <div class="recent-section">
                    <h2>Recent Customers</h2>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Registered</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (mysqli_num_rows($recent_customers_result) > 0) {
                                    while ($customer = mysqli_fetch_assoc($recent_customers_result)) {
                                        echo "<tr>";
                                        echo "<td><strong>" . htmlspecialchars($customer['name']) . "</strong></td>";
                                        echo "<td>" . htmlspecialchars($customer['email']) . "</td>";
                                        $phone = isset($customer['phone']) && !empty($customer['phone']) ? htmlspecialchars($customer['phone']) : 'N/A';
                                        echo "<td>" . $phone . "</td>";
                                        echo "<td>" . date('M d, Y', strtotime($customer['created_at'])) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='no-data'>No customers found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Recent Cats -->
                <div class="recent-section">
                    <h2>Recent Cats Registered</h2>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Cat Name</th>
                                    <th>Breed</th>
                                    <th>Owner</th>
                                    <th>Age</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (mysqli_num_rows($recent_cats_result) > 0) {
                                    while ($cat = mysqli_fetch_assoc($recent_cats_result)) {
                                        echo "<tr>";
                                        echo "<td><strong>" . htmlspecialchars($cat['cat_name']) . "</strong></td>";
                                        $breed = isset($cat['breed']) && !empty($cat['breed']) ? htmlspecialchars($cat['breed']) : 'N/A';
                                        echo "<td>" . $breed . "</td>";
                                        echo "<td>" . htmlspecialchars($cat['owner_name']) . "</td>";
                                        $age = isset($cat['age']) && !empty($cat['age']) ? $cat['age'] . ' years' : 'N/A';
                                        echo "<td>" . $age . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='no-data'>No cats found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="recent-section">
                <h2>Recent Appointments</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Customer</th>
                                <th>Cat Name</th>
                                <th>Service</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent_query = "SELECT a.*, cat.cat_name, s.service_name, u.name as customer_name 
                                            FROM appointments a 
                                            JOIN cats cat ON a.cat_id = cat.cat_id 
                                            JOIN services s ON a.service_id = s.service_id 
                                            JOIN customers c ON a.customer_id = c.customer_id 
                                            JOIN users u ON c.user_id = u.user_id 
                                            ORDER BY a.appointment_date DESC, a.appointment_time DESC 
                                            LIMIT 10";
                            $recent_result = mysqli_query($conn, $recent_query);
                            
                            if (mysqli_num_rows($recent_result) > 0) {
                                while ($row = mysqli_fetch_assoc($recent_result)) {
                                    echo "<tr>";
                                    echo "<td>" . date('M d, Y', strtotime($row['appointment_date'])) . "</td>";
                                    echo "<td>" . date('h:i A', strtotime($row['appointment_time'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['cat_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['service_name']) . "</td>";
                                    echo "<td><span class='status-badge status-" . $row['status'] . "'>" . ucfirst($row['status']) . "</span></td>";
                                    echo "<td><a href='manage_appointments.php' class='btn btn-primary btn-small'>View</a></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' class='no-data'>No appointments found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php include "../footer.php"; ?>
</body>
</html>

