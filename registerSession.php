<?php
session_start();
include "db.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Check if email already exists using prepared statement
    if (empty($errors)) {
        $check_email = "SELECT user_id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $check_email);
        if ($stmt === false) {
            $errors[] = "Database error. Please try again.";
        } else {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $email_result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($email_result) > 0) {
                $errors[] = "Email already registered. Please use a different email or login.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // If there are errors, redirect back with error message
    if (!empty($errors)) {
        $error_message = implode(". ", $errors);
        header("Location: register.php?error=" . urlencode($error_message));
        exit();
    }
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user with role 'customer' using prepared statement
    $insert_query = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'customer')";
    $stmt = mysqli_prepare($conn, $insert_query);
    
    if ($stmt === false) {
        header("Location: register.php?error=" . urlencode("Database error. Please try again."));
        exit();
    }
    
    mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashed_password);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        // Registration successful - redirect to login page
        header("Location: login.php?success=" . urlencode("Registration successful! Please login with your credentials."));
        exit();
    } else {
        // Database error
        mysqli_stmt_close($stmt);
        header("Location: register.php?error=" . urlencode("Registration failed. Please try again."));
        exit();
    }
} else {
    // If not POST request, redirect to register page
    header("Location: register.php");
    exit();
}
?>

