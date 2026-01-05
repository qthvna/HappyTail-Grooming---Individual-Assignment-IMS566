<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Get the directory path relative to php/
$script_dir = dirname($_SERVER['PHP_SELF']);
$script_dir = str_replace('\\', '/', $script_dir); // Normalize path separators
$is_subdirectory = strpos($script_dir, '/customer') !== false || 
                   strpos($script_dir, '/staff') !== false || 
                   strpos($script_dir, '/admin') !== false;
$base_path = $is_subdirectory ? '../' : '';

// Determine dashboard link based on role
if ($role == 'customer') {
    $dashboard_link = (strpos($script_dir, '/customer') !== false) ? 'index.php' : 'customer/index.php';
} elseif ($role == 'staff') {
    $dashboard_link = (strpos($script_dir, '/staff') !== false) ? 'index.php' : 'staff/index.php';
} elseif ($role == 'admin') {
    $dashboard_link = (strpos($script_dir, '/admin') !== false) ? 'index.php' : 'admin/index.php';
} else {
    $dashboard_link = $base_path . 'index.php';
}
?>
<nav class="navbar">
    <div class="nav-container">
        <div class="nav-brand">
            <a href="<?php echo $dashboard_link; ?>">🐾 HappyTail Grooming</a>
        </div>
        <ul class="nav-menu">
            <li><a href="<?php echo $dashboard_link; ?>" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Dashboard</a></li>
            <?php if ($role == 'customer'): ?>
                <li><a href="<?php echo $base_path; ?>customer/my_appointments.php" class="<?php echo $current_page == 'my_appointments.php' ? 'active' : ''; ?>">My Appointments</a></li>
                <li><a href="<?php echo $base_path; ?>customer/my_cats.php" class="<?php echo $current_page == 'my_cats.php' ? 'active' : ''; ?>">My Cats</a></li>
                <li><a href="<?php echo $base_path; ?>customer/profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">Profile</a></li>
            <?php else: ?>
                <?php if ($role == 'admin'): ?>
                <li><a href="<?php echo $base_path; ?>admin/manage_appointments.php" class="<?php echo $current_page == 'manage_appointments.php' ? 'active' : ''; ?>">Appointments</a></li>
                <li><a href="<?php echo $base_path; ?>admin/manage_cats.php" class="<?php echo $current_page == 'manage_cats.php' ? 'active' : ''; ?>">Cats</a></li>
                <li><a href="<?php echo $base_path; ?>admin/manage_customers.php" class="<?php echo $current_page == 'manage_customers.php' ? 'active' : ''; ?>">Customers</a></li>
                <li><a href="<?php echo $base_path; ?>admin/manage_staff.php" class="<?php echo $current_page == 'manage_staff.php' ? 'active' : ''; ?>">Staff</a></li>
                <?php elseif ($role == 'staff'): ?>
                <li><a href="<?php echo $base_path; ?>staff/manage_appointments.php" class="<?php echo $current_page == 'manage_appointments.php' ? 'active' : ''; ?>">Appointments</a></li>
                <li><a href="<?php echo $base_path; ?>staff/manage_cats.php" class="<?php echo $current_page == 'manage_cats.php' ? 'active' : ''; ?>">Cats</a></li>
                <li><a href="<?php echo $base_path; ?>staff/manage_customers.php" class="<?php echo $current_page == 'manage_customers.php' ? 'active' : ''; ?>">Customers</a></li>
                <?php endif; ?>
            <?php endif; ?>
            <li class="nav-user">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <span class="user-role">(<?php echo ucfirst($role); ?>)</span>
                <a href="<?php echo $base_path; ?>logout.php" class="logout-btn">Logout</a>
            </li>
        </ul>
    </div>
</nav>

