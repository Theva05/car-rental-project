<?php
@include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ================= ADD CAR ================= */
if (isset($_POST['add_car'])) {
    $c_brand = $_POST['c_brand'];
    $c_name = $_POST['c_name'];
    $price_per_day = $_POST['price_per_day'];

    $c_image = $_FILES['c_image']['name'];
    $c_image_tmp_name = $_FILES['c_image']['tmp_name'];
    $c_image_folder = 'uploaded_img/' . $c_image;

    $insert_query = mysqli_query(
        $conn,
        "INSERT INTO `cars` (car_brand, car_name, price_per_day, image, status)
         VALUES ('$c_brand', '$c_name', '$price_per_day', '$c_image', 'available')"
    );

    if ($insert_query) {
        move_uploaded_file($c_image_tmp_name, $c_image_folder);
        $message[] = 'Car added successfully';
    } else {
        $message[] = 'Failed to add car';
    }
}

/* ================= DELETE CAR ================= */
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM `cars` WHERE car_id='$delete_id'");
    header('location:action.php');
    exit;
}

/* ================= TOGGLE STATUS ================= */
if (isset($_GET['status'])) {
    $car_id = $_GET['status'];
    $new_status = $_GET['value'];

    mysqli_query($conn, "UPDATE `cars` SET status='$new_status' WHERE car_id='$car_id'");
    header('location:action.php');
    exit;
}

/* ================= UPDATE CAR ================= */
if (isset($_POST['update_car'])) {
    $edit_c_id = $_POST['edit_c_id'];
    $edit_c_brand = $_POST['edit_c_brand'];
    $edit_c_name = $_POST['edit_c_name'];
    $edit_c_price_per_day = $_POST['edit_price_per_day'];

    $edit_c_image = $_FILES['edit_c_image']['name'];
    $edit_c_image_tmp_name = $_FILES['edit_c_image']['tmp_name'];
    $edit_c_image_folder = 'uploaded_img/' . $edit_c_image;

    if (!empty($edit_c_image)) {
        // Update WITH image
        $update_query = mysqli_query(
            $conn,
            "UPDATE `cars` SET 
                car_brand='$edit_c_brand',
                car_name='$edit_c_name',
                price_per_day='$edit_c_price_per_day',
                image='$edit_c_image'
             WHERE car_id='$edit_c_id'"
        );

        if ($update_query) {
            move_uploaded_file($edit_c_image_tmp_name, $edit_c_image_folder);
        }
    } else {
        // Update WITHOUT image
        $update_query = mysqli_query(
            $conn,
            "UPDATE `cars` SET 
                car_brand='$edit_c_brand',
                car_name='$edit_c_name',
                price_per_day='$edit_c_price_per_day'
             WHERE car_id='$edit_c_id'"
        );
    }

    header('location:action.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Car Management</title>
    <link rel="stylesheet" href="option.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>
<body>

<?php
if (isset($message)) {
    foreach ($message as $msg) {
        echo '
        <div class="message">
            <span>' . $msg . '</span>
            <i class="fas fa-times" onclick="this.parentElement.style.display=`none`;"></i>
        </div>';
    }
}
?>

<?php include 'header.php'; ?>

<div class="container">

<!-- ================= ADD CAR FORM ================= -->
<section>
    <form action="" method="post" enctype="multipart/form-data" class="add-car-form">
        <h3>Add New Car</h3>
        <input type="text" name="c_brand" class="box" placeholder="Car Brand" required>
        <input type="text" name="c_name" class="box" placeholder="Car Name" required>
        <input type="number" name="price_per_day" class="box" placeholder="Price Per Day" min="0" required>
        <input type="file" name="c_image" class="box" required>
        <input type="submit" name="add_car" value="Add Car" class="btn">
    </form>
</section>

<!-- ================= DISPLAY CARS ================= -->
<section class="display-cars-table">
<table>
    <thead>
        <tr>
            <th>Image</th>
            <th>Brand</th>
            <th>Name</th>
            <th>Price / Day</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>

<?php
$select_cars = mysqli_query($conn, "SELECT * FROM `cars`");

if (mysqli_num_rows($select_cars) > 0) {
    while ($row = mysqli_fetch_assoc($select_cars)) {
?>
<tr>
    <td><img src="uploaded_img/<?php echo $row['image']; ?>" height="80"></td>
    <td><?php echo $row['car_brand']; ?></td>
    <td><?php echo $row['car_name']; ?></td>
    <td>RM <?php echo $row['price_per_day']; ?></td>
    <td>
        <?php echo ($row['status'] == 'available')
            ? '<span style="color:green;font-weight:bold;">Available</span>'
            : '<span style="color:red;font-weight:bold;">Unavailable</span>'; ?>
    </td>
    <td>
        <a href="action.php?delete=<?php echo $row['car_id']; ?>" class="delete-btn"
           onclick="return confirm('Delete this car?');">
           <i class="fas fa-trash"></i>
        </a>

        <a href="action.php?edit=<?php echo $row['car_id']; ?>" class="option-btn">
           <i class="fas fa-edit"></i>
        </a>

        <?php if ($row['status'] == 'available') { ?>
            <a href="action.php?status=<?php echo $row['car_id']; ?>&value=unavailable" class="delete-btn">
                Make Unavailable
            </a>
        <?php } else { ?>
            <a href="action.php?status=<?php echo $row['car_id']; ?>&value=available" class="option-btn">
                Make Available
            </a>
        <?php } ?>
    </td>
</tr>
<?php
    }
} else {
    echo '<tr><td colspan="6">No cars found</td></tr>';
}
?>

    </tbody>
</table>
</section>

<!-- ================= EDIT FORM ================= -->
<section class="edit-form-container">
<?php
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_query = mysqli_query($conn, "SELECT * FROM `cars` WHERE car_id='$edit_id'");

    if (mysqli_num_rows($edit_query) > 0) {
        $fetch_edit = mysqli_fetch_assoc($edit_query);
?>
<form action="" method="post" enctype="multipart/form-data">
    <img src="uploaded_img/<?php echo $fetch_edit['image']; ?>" height="150">
    <input type="hidden" name="edit_c_id" value="<?php echo $fetch_edit['car_id']; ?>">
    <input type="text" name="edit_c_brand" class="box" value="<?php echo $fetch_edit['car_brand']; ?>" required>
    <input type="text" name="edit_c_name" class="box" value="<?php echo $fetch_edit['car_name']; ?>" required>
    <input type="number" name="edit_price_per_day" class="box" value="<?php echo $fetch_edit['price_per_day']; ?>" required>
    <input type="file" name="edit_c_image" class="box">
    <input type="submit" name="update_car" value="Update Car" class="btn">
    <input type="reset" value="Cancel" class="option-btn" onclick="window.location='action.php'">
</form>

<script>
document.querySelector('.edit-form-container').style.display = 'flex';
</script>

<?php } } ?>
</section>

</div>

<script src="option.js"></script>
</body>
</html>
