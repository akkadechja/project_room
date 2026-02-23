<?php
include 'db_config.php';

function getSensorData($conn) {
    // ดึงค่าโดยใช้ created_at
    $sql = "SELECT temperature, humidity, smoke_level, created_at FROM sensor_logs ORDER BY id DESC LIMIT 10";
    $result = $conn->query($sql);

    $data = ['temp' => [], 'humid' => [], 'smoke' => [], 'timestamps' => []];

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data['temp'][] = (float)$row["temperature"];
            $data['humid'][] = (float)$row["humidity"];
            $data['smoke'][] = (int)$row["smoke_level"];
            
            // แปลง TIMESTAMP จาก DB ให้เป็นรูปแบบที่ JavaScript เข้าใจ (ISO 8601)
            // เช่น 2024-05-20 10:30:00 -> 2024-05-20T10:30:00Z
            $data['timestamps'][] = date('c', strtotime($row["created_at"]));
        }
    }

    $data['temp'] = array_reverse($data['temp']);
    $data['humid'] = array_reverse($data['humid']);
    $data['smoke'] = array_reverse($data['smoke']);
    $data['timestamps'] = array_reverse($data['timestamps']);

    return $data;
}
$chartData = getSensorData($conn);
?>