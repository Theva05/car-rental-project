<?php
@include 'config.php';

header('Content-Type: application/json');

$result = mysqli_query($conn, "SELECT car_id, status FROM cars");

$cars = [];

while ($row = mysqli_fetch_assoc($result)) {
    $cars[$row['car_id']] = $row['status'];
}

echo json_encode($cars);
