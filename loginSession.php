<?php
session_start();
include "db.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Use prepared statement to prevent SQL injection
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email=?");
    if ($stmt === false) {
        header("Location: login.php?error=Database error. Please try again.");
        exit();
    }
    
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        // Check password - support both hashed and plaintext (for existing users)
        // TODO: Migrate all plaintext passwords to hashed in the future
        $password_valid = false;
        if (password_verify($password, $user['password'])) {
            // Password is hashed and matches
            $password_valid = true;
        } elseif ($user['password'] == $password) {
            // Plaintext password fallback (for existing users)
            // Auto-upgrade to hashed password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
            if ($update_stmt !== false) {
                mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user['user_id']);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
            }
            $password_valid = true;
        }
        
        if ($password_valid) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on user role
            if ($user['role'] == 'customer') {
                header("Location: customer/index.php");
            } elseif ($user['role'] == 'staff') {
                header("Location: staff/index.php");
            } elseif ($user['role'] == 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            mysqli_stmt_close($stmt);
            header("Location: login.php?error=Invalid email or password");
            exit();
        }
    } else {
        mysqli_stmt_close($stmt);
        header("Location: login.php?error=Invalid email or password");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>

