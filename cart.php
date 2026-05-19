<?php
@include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
}

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header('location:login.php');
    exit;
}

/* ================= UPDATE TOTAL DAYS ================= */
if (isset($_POST['update_update_btn'])) {
    $update_days = max(1, (int)$_POST['update_quantity']);
    $cart_id     = (int)$_POST['update_quantity_id'];

    $price_query = mysqli_query($conn, "
        SELECT price_per_day 
        FROM cart 
        WHERE cart_id = '$cart_id'
          AND user_id = '$user_id'
    ");

    if ($row = mysqli_fetch_assoc($price_query)) {
        $price_per_day = (float)$row['price_per_day'];
        $total_price  = $price_per_day * $update_days;

        mysqli_query($conn, "
            UPDATE cart
            SET total_days  = '$update_days',
                total_price = '$total_price'
            WHERE cart_id = '$cart_id'
              AND user_id = '$user_id'
        ");
    }

    header('Location: cart.php');
    exit;
}

/* ================= REMOVE SINGLE ITEM ================= */
if (isset($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];

    mysqli_query($conn, "
        DELETE FROM cart
        WHERE cart_id = '$remove_id'
          AND user_id = '$user_id'
    ");

    header('Location: cart.php');
    exit;
}

/* ================= DELETE ALL CART ITEMS ================= */
if (isset($_GET['delete_all'])) {
    mysqli_query($conn, "
        DELETE FROM cart
        WHERE user_id = '$user_id'
    ");

    header('Location: cart.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cart</title>
    <link rel="stylesheet" href="option.css">
</head>
<body>

<?php include 'header2.php'; ?>

<div class="container">
<section class="shopping-cart">

<h1 class="heading">Cars Cart</h1>

<table>
<thead>
<tr>
    <th>Image</th>
    <th>Brand</th>
    <th>Name</th>
    <th>Price / Day</th>
    <th>Total Days</th>
    <th>Total</th>
    <th>Action</th>
</tr>
</thead>

<tbody>
<?php
$select_cart = mysqli_query($conn, "
    SELECT *
    FROM cart
    WHERE user_id = '$user_id'
");

$grand_total = 0;

if (mysqli_num_rows($select_cart) > 0) {
    while ($fetch_cart = mysqli_fetch_assoc($select_cart)) {

        $days  = max(1, (int)$fetch_cart['total_days']);
        $price = (float)$fetch_cart['price_per_day'];
        $sub_total = $price * $days;

        // keep DB synced
        if ((float)$fetch_cart['total_price'] !== $sub_total) {
            mysqli_query($conn, "
                UPDATE cart
                SET total_price = '$sub_total'
                WHERE cart_id = '{$fetch_cart['cart_id']}'
                  AND user_id = '$user_id'
            ");
        }

        $grand_total += $sub_total;
?>
<tr>
    <td>
        <img src="uploaded_img/<?php echo $fetch_cart['image']; ?>" height="80">
    </td>

    <td><?php echo $fetch_cart['brand']; ?></td>
    <td><?php echo $fetch_cart['name']; ?></td>

    <td>RM <?php echo number_format($price, 2); ?></td>

    <td>
        <form method="post">
            <input type="hidden" name="update_quantity_id"
                   value="<?php echo $fetch_cart['cart_id']; ?>">

            <input type="number" name="update_quantity"
                   value="<?php echo $days; ?>" min="1" required>

            <input type="submit" name="update_update_btn" value="Update">
        </form>
    </td>

    <td>RM <?php echo number_format($sub_total, 2); ?></td>

    <td>
        <a href="cart.php?remove=<?php echo $fetch_cart['cart_id']; ?>"
           class="delete-btn"
           onclick="return confirm('Remove this car from cart?');">
           Remove
        </a>
    </td>
</tr>
<?php
    }
} else {
    echo '<tr><td colspan="7">Your cart is empty</td></tr>';
}
?>

<tr class="table-bottom">
    <td>
        <a href="viewcars.php" class="option-btn">
            Continue Browse Cars
        </a>
    </td>

    <td colspan="4"><strong>Grand Total</strong></td>

    <td>RM <?php echo number_format($grand_total, 2); ?></td>

    <td>
        <a href="cart.php?delete_all"
           class="delete-btn"
           onclick="return confirm('Delete all items?');">
           Delete All
        </a>
    </td>
</tr>

</tbody>
</table>

<a href="checkout.php" class="btn">Proceed to Checkout</a>

</section>
</div>

</body>
</html>
