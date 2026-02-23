<?php
// ไฟล์: get_status.php
header('Content-Type: application/json');
include 'db_config.php';

// ดึงสถานะล่าสุดจาก Database ส่งให้ ESP32
$sql = "SELECT device_name, status FROM device_status";
$result = $conn->query($sql);

$data = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // สร้าง Array เช่น "door" => 1
        $data[$row['device_name']] = intval($row['status']);
    }
}

// ส่งออกเป็น JSON ให้ ESP32 อ่านง่ายๆ
// รูปแบบ: {"data": {"door": 1, "fan": 0, "buzzer": 0}}
echo json_encode(array("data" => $data));

$conn->close();
?>