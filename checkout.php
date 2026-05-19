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

$logged_name  = $_SESSION['user_name'] ?? '';
$logged_email = $_SESSION['user_email'] ?? '';

/* ================= BOOKING PROCESS ================= */
if (isset($_POST['book-btn'])) {

    $name      = $_POST['name'];
    $p_number  = $_POST['p_number'];
    $l_number  = $_POST['l_number'];
    $l_expiry  = $_POST['l_expiry'];
    $email     = $_POST['email'];
    $p_method  = $_POST['p_method'];
    $flat      = $_POST['flat'];
    $street    = $_POST['street'];
    $city      = $_POST['city'];
    $state     = $_POST['state'];
    $country   = $_POST['country'];
    $poskod    = $_POST['poskod'];

    /* ================= LICENSE VALIDATION (SERVER SIDE) ================= */
    if (strtotime($l_expiry) < strtotime(date('Y-m-d'))) {
        echo "
        <div class='rent-message-container'>
            <div class='message-container'>
                <h3 style='color:red;'>Booking Failed</h3>
                <p>Your driving license has expired.</p>
                <a href='checkout.php' class='btn'>Go Back</a>
            </div>
        </div>";
        exit();
    }

    /* ================= PAYMENT STATUS ================= */
    $payment_status = ($p_method === 'Cash') ? 'Pending' : 'Paid';

    /* ================= GET CART DATA ================= */
    $cart_query = mysqli_query(
        $conn,
        "SELECT * FROM cart WHERE user_id = '$user_id'"
    );

    if (mysqli_num_rows($cart_query) == 0) {
        header('location:viewcars.php');
        exit();
    }

    $price_total = 0;
    $days_total  = 0;
    $car_ids     = [];
    $car_names   = [];

    while ($item = mysqli_fetch_assoc($cart_query)) {
        $price_total += (float)$item['total_price'];
        $days_total  += $item['total_days'];
        $car_ids[]    = $item['car_id'];
        $car_names[]  = $item['name'];
    }

    $car_id_string   = implode(',', $car_ids);
    $car_name_string = implode(', ', $car_names);

    /* ================= INSERT BOOKING ================= */
    $detail_query = mysqli_query($conn, "
        INSERT INTO booking
        (user_id, car_id, car_name, name, p_number, l_number, l_expiry, email, p_method,
         flat_name, st_name, city, state, country, poskod,
         total_days, total_price, payment_status)
        VALUES
        ('$user_id', '$car_id_string', '$car_name_string', '$name', '$p_number', '$l_number', '$l_expiry', '$email', '$p_method',
         '$flat', '$street', '$city', '$state', '$country', '$poskod',
         '$days_total', '$price_total', '$payment_status')
    ");

    if ($detail_query) {

        mysqli_query($conn, "
        DELETE FROM cart
        WHERE user_id = '$user_id'
        ");
        echo "
        <div class='rent-message-container'>
            <div class='message-container'>
                <h3>Thank you for renting!</h3>
                <p>Cars: <b>$car_name_string</b></p>
                <p>Total Days: <b>$days_total</b></p>
                <p>Total Price: <b>RM " . number_format($price_total, 2) . "</b></p>
                <p>Payment Status:
                    <span style='color:" . ($payment_status == 'Paid' ? 'green' : 'orange') . "'>
                        $payment_status
                    </span>
                </p>
                <a href='viewcars.php' class='btn'>Continue Shopping</a>
            </div>
        </div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
    <link rel="stylesheet" href="option.css">

    <!-- ===== EMBEDDED CSS FOR LICENSE MODAL ===== -->
    <style>
    .blur {
        filter: blur(5px);
        pointer-events: none;
    }

    .license-modal {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.6);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .license-modal.active {
        display: flex;
    }

    .license-box {
        background: #fff;
        padding: 25px;
        width: 360px;
        text-align: center;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        animation: popIn 0.3s ease;
    }

    .license-box h3 {
        color: #d63031;
        margin-bottom: 10px;
    }

    .license-box p {
        font-size: 14px;
        margin-bottom: 15px;
    }

    .license-box .btn {
        background: #d63031;
        color: #fff;
        padding: 8px 20px;
        border-radius: 5px;
        border: none;
        cursor: pointer;
    }

    .license-box .btn:hover {
        background: #b71c1c;
    }

    .main-link {
        color: #0984e3;
        text-decoration: underline;
        font-size: 14px;
    }

    .main-link:hover {
        color: #0652dd;
    }

    @keyframes popIn {
        from { transform: scale(0.8); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    </style>
</head>

<body>

<?php include 'header2.php'; ?>

<div class="container" id="checkoutContainer">
<section class="checkout-form">

<h1 class="heading">Complete Your Booking</h1>

<form method="post">

<div class="flex">
    <div class="inputBox">
        <span>Your Name</span>
        <input type="text" name="name" value="<?= $logged_name ?>" readonly>
    </div>

    <div class="inputBox">
        <span>Phone Number</span>
        <input type="tel" name="p_number" required>
    </div>

    <div class="inputBox">
        <span>License Number</span>
        <input type="text" name="l_number" required>
    </div>

    <div class="inputBox">
        <span>License Expiry</span>
        <input type="date" name="l_expiry" id="licenseExpiry" required>
    </div>

    <div class="inputBox">
        <span>Email</span>
        <input type="email" name="email" value="<?= $logged_email ?>" readonly>
    </div>

    <div class="inputBox">
        <span>Payment Method</span>
        <select name="p_method">
            <option value="Cash">Cash</option>
            <option value="Credit/Debit Card">Card</option>
            <option value="Online Payment">Online</option>
        </select>
    </div>

    <div class="inputBox">
        <span>Address Line 1</span>
        <input type="text" name="flat" required>
    </div>

    <div class="inputBox">
        <span>Address Line 2</span>
        <input type="text" name="street" required>
    </div>

    <div class="inputBox">
        <span>City</span>
        <input type="text" name="city" required>
    </div>

    <div class="inputBox">
        <span>State</span>
        <input type="text" name="state" required>
    </div>

    <div class="inputBox">
        <span>Country</span>
        <input type="text" name="country" required>
    </div>

    <div class="inputBox">
        <span>Postcode</span>
        <input type="text" name="poskod" required>
    </div>
</div>

<input type="submit" value="Book Now" name="book-btn" class="btn" id="bookBtn">

</form>
</section>
</div>

<!-- ===== LICENSE EXPIRED MODAL ===== -->
<div class="license-modal" id="licenseModal">
    <div class="license-box">
        <h3>License Expired</h3>
        <p>Your driving license has expired.<br>Please renew it before renting.</p>

        <p>
            <a href="viewcars.php" class="main-link">
                Click here to go to main page
            </a>
        </p>

        <button class="btn" onclick="closeLicenseModal()">OK</button>
    </div>
</div>

<!-- ===== JS ===== -->
<script>
const expiryInput = document.getElementById('licenseExpiry');
const modal = document.getElementById('licenseModal');
const container = document.getElementById('checkoutContainer');
const bookBtn = document.getElementById('bookBtn');

if (expiryInput) {
    expiryInput.addEventListener('change', function () {
        const selectedDate = new Date(this.value);
        const today = new Date();
        today.setHours(0,0,0,0);

        if (selectedDate < today) {
            modal.classList.add('active');
            container.classList.add('blur');
            bookBtn.disabled = true;
        } else {
            closeLicenseModal();
            bookBtn.disabled = false;
        }
    });
}

function closeLicenseModal() {
    modal.classList.remove('active');
    container.classList.remove('blur');
}
</script>

</body>
</html>
