<?php
include 'db_config.php';
include 'get_data.php'; // ดึงข้อมูลกราฟ ($chartData)

// เตรียมข้อมูลที่จะส่งกลับ
$data = $chartData; 

// --- ส่วนที่เพิ่ม: ดึงสถานะอุปกรณ์ล่าสุด ---
$sql_device = "SELECT device_name, status FROM device_status";
$result_device = $conn->query($sql_device);
$devices = [];

if ($result_device) {
    while($row = $result_device->fetch_assoc()) {
        $devices[$row['device_name']] = (int)$row['status'];
    }
}
$data['devices'] = $devices; 
// -------------------------------------

header('Content-Type: application/json');
echo json_encode($data);
?>