<?php
@include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
}

/* ================= LOGIN PROTECTION ================= */
if (!isset($_SESSION['user_id'])) {
    header('location:login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Booking History</title>
    <link rel="stylesheet" href="option.css">
</head>
<body>

<?php include 'header2.php'; ?>

<div class="container">

<h1 class="heading">My Booking History</h1>

<table>
    <thead>
        <tr>
            <th>Booking ID</th>
            <th>Cars</th>
            <th>Total Days</th>
            <th>Total Price (RM)</th>
            <th>Payment Method</th>
            <th>Payment Status</th>
            <th>Booking Status</th> <!-- ✅ ADDED -->
        </tr>
    </thead>

    <tbody>
    <?php
    $select_booking = mysqli_query($conn, "
        SELECT 
            b.booking_id,
            b.total_days,
            b.total_price,
            b.p_method,
            b.payment_status,
            b.booking_status,
            GROUP_CONCAT(c.car_name SEPARATOR ', ') AS car_names
        FROM booking b
        JOIN cars c ON FIND_IN_SET(c.car_id, b.car_id)
        WHERE b.user_id = '$user_id'
        GROUP BY b.booking_id
        ORDER BY b.booking_id DESC
    ");

    if (mysqli_num_rows($select_booking) > 0) {
        while ($row = mysqli_fetch_assoc($select_booking)) {
    ?>
        <tr>
            <td><?php echo $row['booking_id']; ?></td>
            <td><?php echo $row['car_names']; ?></td>
            <td><?php echo $row['total_days']; ?></td>
            <td>RM <?php echo number_format($row['total_price'], 2); ?></td>
            <td><?php echo $row['p_method']; ?></td>

            <!-- PAYMENT STATUS -->
            <td>
                <?php if ($row['payment_status'] === 'Paid') { ?>
                    <span style="color:green;font-weight:bold;">Paid</span>
                <?php } else { ?>
                    <span style="color:orange;font-weight:bold;">Pending</span>
                <?php } ?>
            </td>

            <!-- BOOKING STATUS -->
            <td>
                <?php
                $status_color = match ($row['booking_status']) {
                    'Confirmed' => 'blue',
                    'Ongoing'   => 'purple',
                    'Completed' => 'green',
                    'Cancelled' => 'red',
                    default     => 'orange'
                };
                ?>
                <span style="color:<?php echo $status_color; ?>;font-weight:bold;">
                    <?php echo $row['booking_status']; ?>
                </span>
            </td>
        </tr>
    <?php
        }
    } else {
        echo '<tr><td colspan="7">No bookings found</td></tr>';
    }
    ?>
    </tbody>
</table>

</div>

</body>
</html>
