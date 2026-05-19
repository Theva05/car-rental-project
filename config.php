<?php

session_start();

$conn = mysqli_connect('localhost', 'root', '', 'car_rental_db', 3307);

if(!$conn){
    die("Connection failed: " . mysqli_connect_error());
}
?>
