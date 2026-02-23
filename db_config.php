<?php
date_default_timezone_set('Asia/Bangkok');
$host = "localhost";
$user = "root";
$pass = "";
$db   = "iot_miniproj";

$conn = new mysqli($host, $user, $pass, $db);

// ตั้งค่าให้รองรับภาษาไทย
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
