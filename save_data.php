<?php
include 'db_config.php';
date_default_timezone_set('Asia/Bangkok'); // ตั้งเวลาไทย

// --- ตั้งค่า Telegram ---
$token = "8301475593:AAHal0Te3CFBHlJyGfKU55_py5TOsgJkTRk";
$chat_id = "8428843945";

function sendTelegram($message, $token, $chat_id) {
    $url = "https://api.telegram.org/bot" . $token . "/sendMessage?chat_id=" . $chat_id . "&text=" . urlencode($message);
    @file_get_contents($url);
}
// ----------------------------

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$response = ["status" => "pending"];

if ($data) {
    // -----------------------------------------------------------
    // ส่วนที่ 1: บันทึกค่าเซนเซอร์ (Sensor Logs)
    // -----------------------------------------------------------
    $temp  = $data['t'] ?? null;
    $humi  = $data['h'] ?? null;
    $smoke = $data['s'] ?? null;

    if ($temp !== null && $humi !== null && $smoke !== null) {
        $stmt = $conn->prepare("INSERT INTO sensor_logs (temperature, humidity, smoke_level) VALUES (?, ?, ?)");
        $stmt->bind_param("ddd", $temp, $humi, $smoke);
        
        if ($stmt->execute()) {
            $response["sensor_log"] = "saved";
            
            // Auto Cleanup: ลบข้อมูลเก่ากว่า 1 วัน (ป้องกัน DB บวม)
            $sql_cleanup = "DELETE FROM sensor_logs WHERE created_at < (NOW() - INTERVAL 1 DAY)";
            $conn->query($sql_cleanup);
            
        } else {
            $response["sensor_log"] = "error: " . $stmt->error;
        }
        $stmt->close();

        // --- Logic แจ้งเตือน Telegram ---
        // 🟢 แก้ไข: เปลี่ยนเงื่อนไขเป็น มากกว่า 1500 ถึงจะส่งแจ้งเตือน
        if ($smoke > 1500) {
            $msg = "⚠️ แจ้งเตือนระดับควันผิดปกติ! ⚠️\n";
            $msg .= "ระดับควัน: " . $smoke . " PPM\n";
            $msg .= "อุณหภูมิ: " . $temp . " °C\n";
            $msg .= "เวลา: " . date("H:i:s");
            
            sendTelegram($msg, $token, $chat_id);
        }
    }

    // -----------------------------------------------------------
    // ส่วนที่ 2: อัปเดตสถานะปุ่ม (Device Status)
    // 🔴 ปิดการทำงานส่วนนี้ไว้ เพื่อไม่ให้ ESP32 เขียนทับคำสั่งจาก Web
    // (ทำให้กดปุ่มบนเว็บแล้วทำงานได้ชัวร์ ไม่เด้งกลับ)
    // -----------------------------------------------------------
    /* $devices_to_update = ['door', 'buzzer', 'fan'];
    foreach ($devices_to_update as $device) {
        if (isset($data[$device])) {
            $status = (int)$data[$device];
            $upd = $conn->prepare("UPDATE device_status SET status = ? WHERE device_name = ?");
            $upd->bind_param("is", $status, $device);
            $upd->execute();
            $upd->close();
        }
    }
    */
    // -----------------------------------------------------------

    $response["status"] = "success";

} else {
    $response["status"] = "invalid_json";
}

// 3. ส่งสถานะ "ล่าสุดจาก Database" กลับไปให้ ESP32 ทำตาม
// ESP32 จะอ่านค่าจากตรงนี้ไปทำงาน (เช่น เปิดประตู/เปิดพัดลม)
$result = $conn->query("SELECT device_name, status FROM device_status");
$current_devices = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $current_devices[$row['device_name']] = (int)$row['status'];
    }
}
$response["data"] = $current_devices;

header('Content-Type: application/json');
echo json_encode($response);
$conn->close();
?>