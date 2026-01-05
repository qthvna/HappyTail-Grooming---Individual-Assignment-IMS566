<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "happytail_grooming";

// Try connecting with default port 3306 first (XAMPP default)
$conn = @mysqli_connect($host, $user, $pass, $db);

// If connection fails, try with port 3307 (alternative XAMPP configuration)
if (!$conn) {
    $port = 3307;
    $conn = @mysqli_connect($host, $user, $pass, $db, $port);
}

// If connection fails, try without database name (to check if MySQL is running)
if (!$conn) {
    $test_conn = @mysqli_connect($host, $user, $pass);
    if ($test_conn) {
        // MySQL is running but database might not exist
        mysqli_close($test_conn);
        die("Connection failed: Database 'happytail_grooming' does not exist.<br><br>" .
            "Please:<br>" .
            "1. Open phpMyAdmin (http://localhost/phpmyadmin)<br>" .
            "2. Create database 'happytail_grooming'<br>" .
            "3. Import sql/database_setup.sql");
    } else {
        die("Connection failed: " . mysqli_connect_error() . "<br><br>" .
            "Please ensure:<br>" .
            "1. XAMPP MySQL service is running (check XAMPP Control Panel)<br>" .
            "2. MySQL is running on port 3306 or 3307<br>" .
            "3. Database 'happytail_grooming' exists in phpMyAdmin");
    }
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8");
?>
