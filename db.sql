-- 1. สร้างฐานข้อมูล (ถ้ายังไม่มี)
CREATE DATABASE IF NOT EXISTS iot_miniproj;
USE iot_miniproj;

-- 2. สร้างตารางสำหรับเก็บข้อมูลเซนเซอร์
CREATE TABLE IF NOT EXISTS sensor_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    temperature FLOAT(5, 2) NOT NULL,
    humidity FLOAT(5, 2) NOT NULL,
    smoke_level INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS device_status (
    id INT PRIMARY KEY,
    device_name VARCHAR(50),
    status TINYINT(1) DEFAULT 0 -- 0 = Off/Close, 1 = On/Open
);

-- 3. เพิ่มตัวอย่างข้อมูล
INSERT INTO device_status (id, device_name, status)
VALUES (1, 'door', 0),
    (2, 'buzzer', 0),
    (3, 'fan', 0) ON DUPLICATE KEY
UPDATE status = status;

INSERT INTO sensor_logs (temperature, humidity, smoke_level)
VALUES (28.5, 65.2, 120),
    (29.0, 64.0, 115),
    (30.2, 62.5, 130);