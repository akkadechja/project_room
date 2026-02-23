# 🛡️ Smart Room Security System

## 🇹🇭 ภาษาไทย

### 📌 รายละเอียดโครงงาน

Smart Room Security System เป็นระบบรักษาความปลอดภัยห้องอัจฉริยะที่พัฒนาโดยใช้เทคโนโลยี Internet of Things (IoT) ร่วมกับ Web Application และฐานข้อมูล MariaDB เพื่อเฝ้าระวังเหตุการณ์ผิดปกติแบบ Real-time

ระบบสามารถตรวจจับควัน ตรวจวัดอุณหภูมิและความชื้น ตรวจสอบสถานะประตู และแสดงผลผ่าน Web Dashboard พร้อมบันทึกข้อมูลย้อนหลัง

---

### 🔥 ความสามารถของระบบ (Features)

- ตรวจจับควันด้วย MQ-2
- วัดอุณหภูมิและความชื้นด้วย DHT11
- ตรวจสอบสถานะประตูด้วย Ultrasonic Sensor (HC-SR04)
- ควบคุมประตูด้วย Servo Motor 180°
- แจ้งเตือนด้วย Buzzer
- LED แสดงสถานะ WiFi และประตู
- แสดงผลผ่าน Web Dashboard
- บันทึกข้อมูลลงฐานข้อมูล MariaDB
- ปุ่มเปิดประตูฉุกเฉิน

---

### 🏗️ โครงสร้างระบบ (System Architecture)

Sensors → ESP32 → Web Server (PHP) → MariaDB → Web Dashboard

---

### 🔧 อุปกรณ์ที่ใช้ (Hardware)

- MQ-2 Gas Sensor
- DHT11 Temperature & Humidity Sensor
- HC-SR04 Ultrasonic Sensor
- Servo Motor 180°
- IRF520N MOSFET Module
- ESP32
- LED Indicators
- Buzzer
- Emergency Button

---

### 💻 เทคโนโลยีที่ใช้ (Software Stack)

- PHP
- MariaDB
- HTML / CSS / JavaScript
- XAMPP (Apache Server)
- Arduino IDE

---

### 🗄️ โครงสร้างฐานข้อมูล (Database Structure) ***

**Table 1: `sensor_logs`**
| Field        | Type          | Description                     |
|--------------|---------------|---------------------------------|
| id           | INT (PK)      | รหัส Auto Increment              |
| temperature  | FLOAT(5, 2)   | ค่าอุณหภูมิ                        |
| humidity     | FLOAT(5, 2)   | ค่าความชื้นสัมพัทธ์                  |
| smoke_level  | INT           | ระดับควัน                         |
| created_at   | TIMESTAMP     | เวลาที่บันทึกข้อมูล                  |

**Table 2: `device_status`**
| Field        | Type          | Description                     |
|--------------|---------------|---------------------------------|
| id           | INT (PK)      | รหัสอุปกรณ์                       |
| device_name  | VARCHAR(50)   | ชื่ออุปกรณ์ (door, buzzer, fan)    |
| status       | TINYINT(1)    | 0 = Off/Close, 1 = On/Open      |

---

### 🚀 วิธีติดตั้งระบบ

#### 1️⃣ ติดตั้ง Web Server

1. ติดตั้ง XAMPP  
2. เปิด Apache และ MariaDB  
3. สร้างฐานข้อมูลชื่อ `iot_security`  
4. สร้างตาราง `sensor_logs` และ `device_status`
5. นำไฟล์เว็บไปไว้ในโฟลเดอร์ htdocs/
6. ตั้งค่า WiFi และ URL ของเซิร์ฟเวอร์ในโค้ด ESP
7. อัปโหลดโค้ด (Firmware) ลงบอร์ด

#### 2️⃣ นำไฟล์เว็บไปไว้ใน

```
htdocs/
```

เปิดผ่าน Browser:

```
http://localhost/iot-miniproject
```

#### 3️⃣ อัปโหลดโค้ดลง ESP

- เปิด Arduino IDE  
- ใส่ WiFi SSID และ Password  
- กำหนด URL ของ Web Server  
- Upload ลงบอร์ด  

---

### ⚙️ หลักการทำงาน

1. ESP อ่านค่าจากเซนเซอร์  
2. ส่งข้อมูลผ่าน WiFi ไปยัง PHP  
3. PHP บันทึกข้อมูลลง MariaDB  
4. Dashboard แสดงผลแบบ Real-time  
5. PHP ส่งสถานะของ Outputs ไปยัง ESP

---

### 🧪 ผลการทดสอบ

- ตรวจจับควันได้ภายใน 2–3 วินาที  
- ตรวจจับการเปิดประตูได้แม่นยำ  
- บันทึกข้อมูลต่อเนื่องทุก 5 วินาที  

---

## 🇬🇧 English Version

### 📌 Project Overview

Smart Room Security System is an IoT-based smart room monitoring system integrated with a Web Application and MariaDB database for real-time monitoring and data logging.

The system detects smoke, measures temperature and humidity, monitors door status, and displays data through a web dashboard.

---

### 🔥 Features

- Smoke detection using MQ-2
- Temperature & humidity monitoring using DHT11
- Door status detection using Ultrasonic Sensor (HC-SR04)
- Door control via 180° Servo Motor
- Buzzer alert system
- WiFi and door status LED indicators
- Web-based real-time dashboard
- Data logging with MariaDB
- Emergency door release button

---

### 🏗️ System Architecture

Sensors → ESP8266/ESP32 → Web Server (PHP) → MariaDB → Web Dashboard

---

### 🔧 Hardware Components

- MQ-2 Gas Sensor
- DHT11 Sensor
- HC-SR04 Ultrasonic Sensor
- 180° Servo Motor
- IRF520N MOSFET Module
- ESP8266 / ESP32
- LEDs
- Buzzer
- Emergency Button

---

### 💻 Software Stack

- PHP
- MariaDB
- HTML / CSS / JavaScript
- Apache (XAMPP)
- Arduino IDE

---

### 🗄️ Database Structure

**Table 1: `sensor_logs`**
| Field        | Type          | Description                     |
|--------------|---------------|---------------------------------|
| id           | INT (PK)      | Auto Increment ID               |
| temperature  | FLOAT(5, 2)   | Temperature value               |
| humidity     | FLOAT(5, 2)   | Relative humidity value         |
| smoke_level  | INT           | Smoke level                     |
| created_at   | TIMESTAMP     | Record timestamp                |

**Table 2: `device_status`**
| Field        | Type          | Description                     |
|--------------|---------------|---------------------------------|
| id           | INT (PK)      | Device ID                       |
| device_name  | VARCHAR(50)   | Device name (door, buzzer, fan) |
| status       | TINYINT(1)    | 0 = Off/Close, 1 = On/Open      |

---

### 🚀 Installation

1. Install XAMPP  
2. Start Apache & MariaDB  
3. Create database `iot_security`  
4. Create table `sensor_logs` and `device_status`  
5. Upload web files to `htdocs/`  
6. Configure WiFi and server URL in ESP code  
7. Upload firmware  

---

### ⚙️ How It Works

1. ESP reads sensor data  
2. Sends data via WiFi to PHP server  
3. PHP stores data in MariaDB  
4. Dashboard displays real-time information
5. PHP send outputs status to ESP

---

### 🧪 Testing Results

- Smoke detected within 2–3 seconds  
- Accurate door detection  
- Continuous data logging every 5 seconds  

---