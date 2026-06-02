<?php
require 'includes/auth.php';
require 'db.php';

session_start();

if (isset($_SESSION['user'])) {
    // Redirect based on role
    switch ($_SESSION['user']['role_id']) {
        case 1:
            header('Location: admin/dashboard.php');
            break;
        case 2:
            header('Location: doctor/dashboard.php');
            break;
        case 3:
            header('Location: nurse/dashboard.php');
            break;
        case 4:
            header('Location: cashier/dashboard.php');
            break;
        case 5:
            header('Location: pharmacist/dashboard.php');
            break;
        case 6:
            header('Location: lab/dashboard.php');
            break;
        case 7:
            header('Location: patient/dashboard.php');
            break;
        default:
            // If role is not recognized, log out user
            session_destroy();
            header('Location: login.php');
            break;
    }
} else {
    // No user logged in, redirect to login page
    header('Location: login.php');
}
exit;


?>