<?php
session_start();
$error = isset($_GET['error']) ? $_GET['error'] : '';
$success = isset($_GET['success']) ? $_GET['success'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - HappyTail Grooming</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="logo-section">
                <h1>🐾 HappyTail Grooming</h1>
                <p>Create Your Account</p>
            </div>
            <form action="registerSession.php" method="POST" class="login-form">
                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert-message" style="background-color: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required minlength="6">
                </div>
                <button type="submit" class="btn btn-primary btn-full">Sign Up</button>
                <div style="text-align: center; margin-top: 20px;">
                    <p style="color: var(--text-secondary);">Already have an account? <a href="login.php" style="color: var(--primary-color); text-decoration: none;">Login here</a></p>
                </div>
            </form>
        </div>
        <div style="text-align: center; margin-top: 20px;">
            <a href="../index.html" style="color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; opacity: 0.9; transition: opacity 0.3s; padding: 8px 12px; border-radius: 6px; background-color: rgba(255, 255, 255, 0.1);" 
               onmouseover="this.style.opacity='1'; this.style.backgroundColor='rgba(255, 255, 255, 0.2)'" 
               onmouseout="this.style.opacity='0.9'; this.style.backgroundColor='rgba(255, 255, 255, 0.1)'">
                <span style="font-size: 18px;">←</span> Back to Home
            </a>
        </div>
    </div>
</body>
</html>

