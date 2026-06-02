<?php
require '../includes/auth.php';
checkRole(7); // 2 = Doctor
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Doctor Dashboard</title>
</head>
<body>
    <h1>Welcome Lab, <?= htmlspecialchars($_SESSION['user']['full_name']) ?>!</h1>
    <p>Your shift: <?= htmlspecialchars($_SESSION['user']['shift']?? '') ?></p>
    <a href="../logout.php">Logout</a>
</body>
</html>
