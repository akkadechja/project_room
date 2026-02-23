<?php
// ไฟล์: update_device.php
include 'db_config.php';

// รับค่าที่ส่งมาจาก JavaScript (index.php)
if (isset($_POST['device']) && isset($_POST['status'])) {
    
    $device = $_POST['device']; // เช่น 'door', 'fan', 'buzzer'
    $status = $_POST['status']; // 1 หรือ 0

    // อัปเดตลงฐานข้อมูล
    $sql = "UPDATE device_status SET status = $status WHERE device_name = '$device'";

    if ($conn->query($sql) === TRUE) {
        echo "Success";
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "No data sent";
}

$conn->close();
?>