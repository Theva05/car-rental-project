<?php

@include 'config.php';
if(!isset($_SESSION['admin_name'])) {
    header('location:login.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>adminpage</title>
    <link rel="stylesheet" href="page.css">
</head>
<body>
    <div class="container">
        <div class="content">
            <h3>Hi, <span><?php echo $_SESSION['admin_name']?></span></h3>
            <h1>Welcome </h1>
            <p>This is admin page</p>
            <a href="action.php" class="btn">Action</a>
            <a href="logout.php" class="btn">Logout</a>
        </div>
    </div>
</body>
</html>