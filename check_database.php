<?php
// Database Structure Checker
// This script will show you the actual structure of your database tables

include "db.php";

echo "<h2>Database Structure Check</h2>";
echo "<h3>Connection Status:</h3>";
if ($conn) {
    echo "✓ Connected to database: " . mysqli_get_host_info($conn) . "<br>";
    echo "Database: " . mysqli_select_db($conn, "happytail_grooming") ? "happytail_grooming" : "Not selected" . "<br><br>";
} else {
    echo "✗ Connection failed!<br>";
    exit;
}

// Check appointments table structure
echo "<h3>Appointments Table Structure:</h3>";
$result = mysqli_query($conn, "DESCRIBE appointments");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . mysqli_error($conn) . "<br>";
    echo "Table 'appointments' might not exist.<br>";
}

// Check if customer_id column exists
echo "<br><h3>Checking for customer_id column:</h3>";
$check = mysqli_query($conn, "SHOW COLUMNS FROM appointments LIKE 'customer_id'");
if (mysqli_num_rows($check) > 0) {
    echo "✓ customer_id column EXISTS<br>";
} else {
    echo "✗ customer_id column DOES NOT EXIST<br>";
    echo "<br><strong>Solution:</strong> The appointments table needs to be fixed.<br>";
}

// Show all tables
echo "<br><h3>All Tables in Database:</h3>";
$tables = mysqli_query($conn, "SHOW TABLES");
if ($tables) {
    echo "<ul>";
    while ($row = mysqli_fetch_array($tables)) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
}

mysqli_close($conn);
?>

