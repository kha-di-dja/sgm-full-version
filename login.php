<?php 
include ("login-connect.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | SGM </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%23035772'/><text x='50' y='60' text-anchor='middle' fill='white' font-size='30' font-weight='bold'>SGM</text></svg>">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="login.css">
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <!-- Logo -->
        <div class="login-logo">
            <div class="text-logo">SGM</div>
            <div class="logo-sub">Students Grades Management </div>
        </div>


        <!-- Login Form -->
        <form action="login-logic.php" method="post">
            <div class="input-group">
                <label>Email Address</label>
                <div class="input-icon">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="text" name="email" placeholder="Enter your email" required>
                </div>
            </div>

            <div class="input-group">
                <label>Password</label>
                <div class="input-icon">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>
            <input type="submit" class="login-btn" value="login" name="submit">
            </button>
        </form>

        <!-- Back to Home -->
        <div class="back-home">
            <a href="home.html">
                <i class="fa-solid fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>
</div>

</body>
</html>
