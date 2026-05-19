<?php
@include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
}

include 'header2.php';

/* ================= ADD TO CART ================= */
if (isset($_POST['add_to_cart'])) {

    $user_id = $_SESSION['user_id'] ?? null;

    if (!$user_id) {
        header('location:login.php');
        exit;
    }

    $car_id   = $_POST['car_id'];
    $c_brand  = $_POST['c_brand'];
    $c_name   = $_POST['c_name'];
    $c_price  = $_POST['c_price'];
    $c_image  = $_POST['c_image'];
    $c_total_days = 1;

    // Check car availability
    $check_status = mysqli_query(
        $conn,
        "SELECT status FROM cars WHERE car_id = '$car_id'"
    );

    $car = mysqli_fetch_assoc($check_status);

    if ($car['status'] === 'unavailable') {

        $message[] = 'This car is currently unavailable';

    } else {

        // Check if already in cart
        $select_cart = mysqli_query(
            $conn,
            "SELECT * FROM cart 
             WHERE user_id = '$user_id' 
             AND car_id = '$car_id'"
        );

        if (mysqli_num_rows($select_cart) > 0) {

            $message[] = 'Car already added to cart';

        } else {

            $total_price = $c_price * $c_total_days;

            mysqli_query(
                $conn,
                "INSERT INTO cart
                (user_id, car_id, brand, name, price_per_day, image, total_days, total_price)
                VALUES
                ('$user_id', '$car_id', '$c_brand', '$c_name', '$c_price', '$c_image', '$c_total_days', '$total_price')"
            );

            $message[] = 'Car added to cart successfully!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Cars</title>
    <link rel="stylesheet" href="option.css">
</head>
<body>

<?php
if (isset($message)) {
    foreach ($message as $msg) {
        echo '<div class="message"><span>' . $msg . '</span></div>';
    }
}
?>

<div class="container">
<section class="cars">

<h1 class="heading">Cars List</h1>

<!-- SEARCH FORM -->
<form method="get" class="search-form">
    <input type="text"
           name="search"
           placeholder="Search by brand or availability (available / unavailable)"
           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
    <button type="submit" class="btn">Search</button>
</form>

<div class="box-container">

<?php
$search = trim($_GET['search'] ?? '');

if ($search !== '') {

    if (strtolower($search) === 'available') {

        $select_cars = mysqli_query(
            $conn,
            "SELECT * FROM cars WHERE status = 'available'"
        );

    } elseif (strtolower($search) === 'unavailable') {

        $select_cars = mysqli_query(
            $conn,
            "SELECT * FROM cars WHERE status = 'unavailable'"
        );

    } else {

        // Brand search only
        $select_cars = mysqli_query(
            $conn,
            "SELECT * FROM cars 
             WHERE car_brand LIKE '%$search%'"
        );
    }

} else {
    $select_cars = mysqli_query($conn, "SELECT * FROM cars");
}

if (mysqli_num_rows($select_cars) > 0) {
    while ($fetch_cars = mysqli_fetch_assoc($select_cars)) {
?>

<form method="post">
    <div class="box <?php echo ($fetch_cars['status'] === 'unavailable') ? 'unavailable' : ''; ?>"
         data-car-id="<?php echo $fetch_cars['car_id']; ?>">

        <img src="uploaded_img/<?php echo $fetch_cars['image']; ?>" height="250" alt="">

        <h3><?php echo $fetch_cars['car_brand']; ?></h3>
        <h3><?php echo $fetch_cars['car_name']; ?></h3>

        <div class="price">
            RM <?php echo number_format($fetch_cars['price_per_day'], 2); ?>/day
        </div>

        <p style="font-weight:bold;color:<?php echo ($fetch_cars['status'] === 'available') ? 'green' : 'red'; ?>">
            <?php echo ucfirst($fetch_cars['status']); ?>
        </p>

        <!-- Hidden -->
        <input type="hidden" name="car_id" value="<?php echo $fetch_cars['car_id']; ?>">
        <input type="hidden" name="c_brand" value="<?php echo $fetch_cars['car_brand']; ?>">
        <input type="hidden" name="c_name" value="<?php echo $fetch_cars['car_name']; ?>">
        <input type="hidden" name="c_price" value="<?php echo $fetch_cars['price_per_day']; ?>">
        <input type="hidden" name="c_image" value="<?php echo $fetch_cars['image']; ?>">

        <?php if ($fetch_cars['status'] === 'available') { ?>
            <input type="submit" name="add_to_cart" value="Add to Cart" class="btn">
        <?php } else { ?>
            <input type="button" value="Unavailable" class="btn" disabled style="background:#999;">
        <?php } ?>

    </div>
</form>

<?php
    }
} else {
    echo '<p class="empty">Sorry, no record found</p>';
}
?>

</div>
</section>
</div>

<!-- ================= AUTO UPDATE AVAILABILITY ================= -->
<script>
function updateCarStatus() {
    fetch('fetch_car_status.php')
        .then(response => response.json())
        .then(data => {
            document.querySelectorAll('.box[data-car-id]').forEach(box => {
                const carId = box.getAttribute('data-car-id');
                const status = data[carId];
                if (!status) return;

                const btn = box.querySelector('input[type="submit"], input[type="button"]');

                if (status === 'unavailable') {
                    box.classList.add('unavailable');
                    if (btn) {
                        btn.value = 'Unavailable';
                        btn.disabled = true;
                        btn.style.background = '#999';
                    }
                } else {
                    box.classList.remove('unavailable');
                    if (btn) {
                        btn.value = 'Add to Cart';
                        btn.disabled = false;
                        btn.style.background = '';
                        btn.type = 'submit';
                    }
                }
            });
        });
}

// Poll every 5 seconds
setInterval(updateCarStatus, 5000);
</script>

</body>
</html>
