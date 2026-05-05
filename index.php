<?php
session_start();

$login_error    = '';
$register_error = '';

if (isset($_SESSION['login_error']))    $login_error    = $_SESSION['login_error'];
if (isset($_SESSION['register_error'])) $register_error = $_SESSION['register_error'];

$errors = [
    'login'    => $login_error,
    'register' => $register_error
];

$activeform = isset($_SESSION['active_form']) ? $_SESSION['active_form'] : 'login';

session_unset();

function showError($error) {
    return !empty($error) ? "<p class='error-message'>$error</p>" : '';
}
function isActiveForm($formname, $activeform) {
    return $formname === $activeform ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Bug 3 fixed: "widthdevice-width" changed to "width=device-width" -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interface for CGPA Calculator</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <div class="container">
        <div class="form-box <?= isActiveForm('login', $activeform); ?>" id="login-form">
            <!-- Bug 4 fixed: login form was missing action="login_register.php" -->
            <form action="login_register.php" method="post">
                <h2>Login</h2>
                <?= showError($errors['login']); ?>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
                <p>Don't have an account? <a href="#" onclick="showForm('register-form')">Create account</a></p>
            </form>
        </div>

        <div class="form-box <?= isActiveForm('register', $activeform); ?>" id="register-form">
            <form action="login_register.php" method="post">
                <h2>Register</h2>
                <?= showError($errors['register']); ?>
                <input type="text" name="name" placeholder="Name" required>
                <input type="text" name="matric_no" placeholder="Enter your matric_no/sid" required>
                <input type="text" name="department" placeholder="Department" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <select name="role" required>
                    <option value="">--Select Role--</option>
                    <option value="admin">Admin</option>
                    <option value="student">Student</option>
                </select>
                <button type="submit" name="register">Sumbit</button>
                <p>Already have an account? <a href="#" onclick="showForm('login-form')">Login</a></p>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
