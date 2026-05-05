<?php
session_start();
require_once 'config.php';

if (isset($_POST['register'])) {
    $name =$_POST['name'];
    $matric =$_POST['matric_no'];
    $email =$_POST['email'];
    $password =password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role =$_POST['role'];
    $department =$_POST['department'];
 

    $checkEmail = $conn->query("SELECT email FROM users WHERE email = '$email'");
    if ($checkEmail->num_rows > 0) {
        $_SESSION["register_error"] = 'Email is already registered!';
        $_SESSION["active_form"] = 'register';
    } else {
            $sql = "INSERT INTO users (name, matric_no, email, password, role, department) VALUES ('$name', '$matric', '$email', '$password', '$role', '$department')";
        if ($conn->query($sql)) {
            $_SESSION['register_success'] = 'Registration successful! Please log in.';
        } else {
            $_SESSION['register_error'] = 'Registration failed: ' . $conn->error;
            $_SESSION['active_form'] = 'register';
        }
    }

    header("Location: index.php");
    exit();
}
if (isset($_POST['login'])) {
    $email =$_POST['email'];
    $password =$_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($result->num_rows >0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['name']       = $user['name'];
            $_SESSION['email']      = $user['email'];
            $_SESSION['matric_no']  = $user['matric_no'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['role']       = $user['role'];
            $_SESSION['user_id']    = $user['id'];

            if ($user['role'] === 'admin') {
                header("Location: admin_page.php");
            } else {
                header("Location: user_page.php");
            }
            exit();
        }
    }
    $_SESSION['login_error']  = 'Incorrect email or password';
    $_SESSION['active_form'] = 'login';
    header("Location: index.php");
     exit();

    }

?>