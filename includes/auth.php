<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function checkRole($requiredRoleId) {

    // Not logged in
    if (!isset($_SESSION['user'])) {

        if (isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Not authenticated'
            ]);
            exit;
        }

        header("Location: ../login.php");
        exit;
    }

    // Logged in but wrong role
    if ($_SESSION['user']['role_id'] != $requiredRoleId) {

        if (isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ]);
            exit;
        }

        header("Location: ../unauthorized.php");
        exit;
    }
}
