<?php
@include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
}

/* ===== SAFE SESSION HANDLING ===== */
$user_id = $_SESSION['user_id'] ?? null;

$row_count = 0;

if ($user_id) {
    $select_rows = mysqli_query(
        $conn,
        "SELECT * FROM cart WHERE user_id = '$user_id'"
    );

    $row_count = mysqli_num_rows($select_rows);
}
?>

<header class="header">
    <div class="flex">
        <a href="viewcars.php" class="logo">HorizoN</a>

        <nav class="navbar">
            <a href="bookinghistory.php">My Rentals</a>
            <a href="logout.php">Logout</a>
        </nav>

        <a href="cart.php" class="cart">
            Cart <span><?= $row_count ?></span>
        </a>
    </div>
</header>
