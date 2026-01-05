<?php
session_start();
session_destroy();

// Determine correct path based on current directory
$script_dir = dirname($_SERVER['PHP_SELF']);
$script_dir = str_replace('\\', '/', $script_dir); // Normalize path separators
$is_subdirectory = strpos($script_dir, '/customer') !== false || 
                   strpos($script_dir, '/staff') !== false || 
                   strpos($script_dir, '/admin') !== false ||
                   strpos($script_dir, '/core') !== false;
$base_path = $is_subdirectory ? '../' : '';

header("Location: " . $base_path . "login.php");
exit();
?>
