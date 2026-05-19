<?php

@include 'config.php';

if(!isset($_SESSION['user_name'])) {
    header('location:login.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>userpage</title>
    <link rel="stylesheet" href="page.css">
</head>
<body>
    <div class="container">
        <div class="content">
            <h3>Hi, <span><?php echo $_SESSION['user_name']?></span></h3>
            <h1>Welcome </h1>
            <p>This is user page</p>
            <a href="viewcars.php" class="btn">Browse Cars</a>
            <a href="logout.php" class="btn">Logout</a>
        </div>
    </div>
</body>
</html>