<?php
@include 'config.php';

/* ================= MARK AS PAID ================= */
if (isset($_GET['pay'])) {
    $booking_id = $_GET['pay'];

    mysqli_query($conn, "
        UPDATE booking
        SET payment_status = 'Paid'
        WHERE booking_id = '$booking_id'
    ");

    header('Location: rentrecord.php');
    exit;
}

/* ================= UPDATE BOOKING STATUS ================= */
if (isset($_GET['status'], $_GET['id'])) {

    $booking_id = $_GET['id'];
    $new_status = $_GET['status'];

    // Get current booking info
    $current = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT booking_status, payment_status, car_id
        FROM booking
        WHERE booking_id = '$booking_id'
    "));

    // ❌ Block any changes if booking already completed
    if ($current['booking_status'] === 'Completed') {
        header("Location: rentrecord.php?error=completed");
        exit;
    }

    // ❌ Prevent cancelling unpaid bookings (optional safety)
    if ($new_status === 'Cancelled' && $current['payment_status'] !== 'Paid') {
        header("Location: rentrecord.php?error=unpaid");
        exit;
    }

    // ✅ Update booking status
    mysqli_query($conn, "
        UPDATE booking
        SET booking_status = '$new_status'
        WHERE booking_id = '$booking_id'
    ");

    // ✅ Handle car availability
    if (!empty($current['car_id'])) {
        $car_ids = explode(',', $current['car_id']);

        if ($new_status === 'Confirmed' || $new_status === 'Ongoing') {
            foreach ($car_ids as $cid) {
                mysqli_query($conn, "
                    UPDATE cars
                    SET status = 'unavailable'
                    WHERE car_id = '$cid'
                ");
            }
        }

        if ($new_status === 'Completed' || $new_status === 'Cancelled') {
            foreach ($car_ids as $cid) {
                mysqli_query($conn, "
                    UPDATE cars
                    SET status = 'available'
                    WHERE car_id = '$cid'
                ");
            }
        }
    }

    header('Location: rentrecord.php');
    exit;
}

/* ================= REFUND PROCESS ================= */
if (isset($_GET['refund'])) {

    $booking_id = $_GET['refund'];

    $check = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT booking_status, payment_status
        FROM booking
        WHERE booking_id = '$booking_id'
    "));

    // ❌ No refund if booking is completed
    if ($check['booking_status'] === 'Completed') {
        header("Location: rentrecord.php?error=norefund");
        exit;
    }

    // ✅ Refund only if cancelled + paid
    if (
        $check['booking_status'] === 'Cancelled' &&
        $check['payment_status'] === 'Paid'
    ) {
        mysqli_query($conn, "
            UPDATE booking
            SET refund_status = 'Refunded'
            WHERE booking_id = '$booking_id'
        ");
    }

    header('Location: rentrecord.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Report</title>
    <link rel="stylesheet" href="option.css">
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
<h1 class="heading">BOOKING REPORT</h1>

<!-- ================= ERROR MESSAGES ================= -->
<?php
if (isset($_GET['error'])) {
    $messages = [
        'completed' => 'Booking is completed. No further action allowed.',
        'norefund'  => 'Booking is completed. No need to refund.',
        'unpaid'    => 'Cannot cancel an unpaid booking.'
    ];

    echo "<p style='color:red;font-weight:bold;text-align:center;'>
        {$messages[$_GET['error']]}
    </p>";
}
?>

<table>
<thead>
<tr>
    <th>ID</th>
    <th>Customer</th>
    <th>Cars</th>
    <th>Total Days</th>
    <th>Total Price</th>
    <th>Payment</th>
    <th>Booking Status</th>
    <th>Refund</th>
    <th>Action</th>
</tr>
</thead>

<tbody>
<?php
$select_booking = mysqli_query($conn, "
    SELECT 
        b.*,
        GROUP_CONCAT(c.car_name SEPARATOR ', ') AS car_names
    FROM booking b
    LEFT JOIN cars c ON FIND_IN_SET(c.car_id, b.car_id)
    GROUP BY b.booking_id
    ORDER BY b.booking_id DESC
");

if (mysqli_num_rows($select_booking) > 0) {
    while ($row = mysqli_fetch_assoc($select_booking)) {
?>
<tr>
    <td><?= $row['booking_id']; ?></td>
    <td><?= $row['name']; ?></td>
    <td><?= $row['car_names'] ?: 'N/A'; ?></td>
    <td><?= $row['total_days']; ?></td>
    <td>RM <?= number_format($row['total_price'], 2); ?></td>

    <td>
        <?= $row['payment_status'] === 'Paid'
            ? "<span style='color:green;font-weight:bold;'>Paid</span>"
            : "<span style='color:orange;font-weight:bold;'>Pending</span>"; ?>
    </td>

    <td>
        <select onchange="location=this.value;"
            <?= $row['booking_status'] === 'Completed' ? 'disabled' : '' ?>>
            <option value="">-- Select --</option>
            <?php
            foreach (['Pending','Confirmed','Ongoing','Completed','Cancelled'] as $s) {
                $selected = ($row['booking_status'] === $s) ? 'selected' : '';
                echo "<option value='rentrecord.php?status=$s&id={$row['booking_id']}' $selected>$s</option>";
            }
            ?>
        </select>
    </td>

    <td>
        <?= $row['refund_status'] === 'Refunded'
            ? "<span style='color:green;font-weight:bold;'>Refunded</span>"
            : "<span style='color:#999;'>Not Refunded</span>"; ?>
    </td>

    <td>
        <?php if ($row['payment_status'] === 'Pending') { ?>
            <a href="rentrecord.php?pay=<?= $row['booking_id']; ?>"
               class="option-btn"
               onclick="return confirm('Mark this booking as PAID?');">
               Mark Paid
            </a>
        <?php } ?>

        <?php if (
            $row['booking_status'] === 'Cancelled' &&
            $row['payment_status'] === 'Paid' &&
            $row['refund_status'] === 'Not Refunded'
        ) { ?>
            <a href="rentrecord.php?refund=<?= $row['booking_id']; ?>"
               class="delete-btn"
               onclick="return confirm('Confirm refund for this booking?');">
               Refund
            </a>
        <?php } ?>
    </td>
</tr>
<?php
    }
} else {
    echo '<tr><td colspan="9">No bookings found</td></tr>';
}
?>
</tbody>
</table>
</div>

</body>
</html>
