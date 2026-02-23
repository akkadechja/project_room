<?php
header('Content-Type: application/json');
include 'db_config.php';

// เตรียม Array สำหรับเก็บข้อมูล
$response = array();

// ดึงสถานะอุปกรณ์ทั้ง 3 อย่าง
$sql = "SELECT device_name, status FROM device_status WHERE device_name IN ('door', 'buzzer', 'fan')";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $response['status'] = 'success';
    $response['data'] = array();
    
    while($row = $result->fetch_assoc()) {
        // เก็บค่าเป็นตัวเลข (int) เพื่อให้ ESP นำไปเปรียบเทียบง่ายๆ
        $response['data'][$row['device_name']] = (int)$row['status'];
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'No device data found';
}

echo json_encode($response);
$conn->close();
?>