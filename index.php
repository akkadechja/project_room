<?php
include 'db_config.php'; // เรียกใช้การเชื่อมต่อฐานข้อมูล
include 'get_data.php';  // เรียกใช้ข้อมูลกราฟ

// --- ส่วนที่ 1: ดึงสถานะปุ่ม (Switch) ล่าสุดจาก Database ---
// ต้องทำตรงนี้เพื่อให้ตอนเปิดเว็บมา ปุ่มจะอยู่ถูกตำแหน่ง (เปิด/ปิด) ตามจริง
$sql_init = "SELECT device_name, status FROM device_status";
$result_init = $conn->query($sql_init);
$device_status = [];

if ($result_init) {
    while ($row = $result_init->fetch_assoc()) {
        $device_status[$row['device_name']] = $row['status'];
    }
}
// -----------------------------------------------------
?>
<!DOCTYPE html>
<html>

<head>
    <title>IoT Mini Project - Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f7f6;
            margin: 0;
            padding: 20px;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        /* Value Cards */
        .value-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            width: 250px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-top: 5px solid #775DD0;
        }

        .value-card h3 {
            margin: 0;
            color: #666;
            font-size: 16px;
        }

        .value-card .value {
            font-size: 48px;
            font-weight: bold;
            margin: 10px 0;
            color: #333;
        }

        .value-card .unit {
            font-size: 18px;
            color: #888;
        }

        /* Charts */
        .chart-box {
            background: #fff;
            border-radius: 12px;
            padding: 15px;
            width: 100%;
            max-width: 550px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Top Row & Controls */
        .top-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            align-items: stretch;
            width: 100%;
            margin-bottom: 20px;
        }

        .control-group-card {
            background: #fff;
            border-radius: 12px;
            padding: 15px 20px;
            width: 280px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            border-top: 5px solid #775DD0;
            display: flex;
            flex-direction: column;
            justify-content: space-around;
        }

        .control-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .control-row:last-child {
            border-bottom: none;
        }

        .device-label {
            font-weight: bold;
            color: #444;
            font-size: 14px;
            display: flex;
            flex-direction: column;
        }

        .status-subtext {
            font-size: 11px;
            color: #888;
            font-weight: normal;
        }

        /* Switch Styles */
        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 22px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #00E396;
        }

        input:checked+.slider:before {
            transform: translateX(22px);
        }
    </style>
</head>

<body>

    <div class="dashboard-header">
        <h1>Environment Monitoring</h1>
    </div>

    <div class="container">
        <div style="width: 100%; display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">

            <?php
            $latest_smoke = end($chartData['smoke']);
            $latest_temp = end($chartData['temp']);

            // Logic เช็คสถานะไฟไหม้
            $is_fire = ($latest_smoke > 1500);
            $smoke_color = ($latest_smoke > 1500) ? '#FF4560' : '#775DD0';
            $fire_status = $is_fire ? "DANGER" : "NORMAL";
            $fire_color = $is_fire ? "#FF4560" : "#00E396";
            ?>

            <div class="top-row">
                <div class="value-card" style="border-top-color: <?php echo $smoke_color; ?>;">
                    <h3>Smoke Level</h3>
                    <div class="value" id="smokeVal" style="color: <?php echo $smoke_color; ?>; font-size: 40px; font-weight: bold;">
                        <?php echo $latest_smoke; ?>
                    </div>
                    <div class="unit">PPM</div>
                </div>

                <div class="value-card" id="fireCard" style="border-top-color: <?php echo $fire_color; ?>;">
                    <h3>Fire Status</h3>
                    <div class="value" id="fireStatus" style="color: <?php echo $fire_color; ?>; font-size: 28px; font-weight: bold;">
                        <?php echo $fire_status; ?>
                    </div>
                    <div class="unit" id="fireDetail"><?php echo $is_fire ? "DANGER" : "SAFE"; ?></div>
                </div>

                <div class="control-group-card">
                    <div class="control-row">
                        <div class="device-label">
                            🚪 Door Status
                            <span class="status-subtext" id="status-door">
                                <?php echo ($device_status['door'] ?? 0) == 1 ? 'OPEN' : 'CLOSED'; ?>
                            </span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="sw-door" onchange="toggleDevice('door', this.checked)"
                                <?php echo (($device_status['door'] ?? 0) == 1) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="control-row">
                        <div class="device-label">
                            🚨 Buzzer Alarm
                            <span class="status-subtext" id="status-buzzer">
                                <?php echo ($device_status['buzzer'] ?? 0) == 1 ? 'ON' : 'OFF'; ?>
                            </span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="sw-buzzer" onchange="toggleDevice('buzzer', this.checked)"
                                <?php echo (($device_status['buzzer'] ?? 0) == 1) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="control-row">
                        <div class="device-label">
                            🌀 Ventilation Fan
                            <span class="status-subtext" id="status-fan">
                                <?php echo ($device_status['fan'] ?? 0) == 1 ? 'ON' : 'OFF'; ?>
                            </span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="sw-fan" onchange="toggleDevice('fan', this.checked)"
                                <?php echo (($device_status['fan'] ?? 0) == 1) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="chart-box">
            <div id="chartTemp"></div>
        </div>
        <div class="chart-box">
            <div id="chartHumi"></div>
        </div>
    </div>

    <script>
        var timestamps = <?php echo json_encode($chartData['timestamps']); ?>;
        var commonOptions = {
            chart: {
                height: 250,
                type: 'area',
                toolbar: {
                    show: false
                },
                animations: {
                    enabled: true
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'straight',
                width: 3
            },
            xaxis: {
                type: 'datetime',
                categories: timestamps,
                labels: {
                    datetimeUTC: false, // ปิด UTC เพื่อให้แสดงเวลาตามเครื่อง (ไทย)
                    format: 'HH:mm:ss', // เพิ่ม :ss เพื่อให้แสดงวินาที
                },
                tickAmount: 5 // ปรับจำนวนขีดบนแกน X ให้พอดีไม่ซ้อนกัน
            }
        };
        var chartTemp = new ApexCharts(document.querySelector("#chartTemp"), Object.assign({}, commonOptions, {
            series: [{
                name: 'Temp',
                data: <?php echo json_encode($chartData['temp']); ?>
            }],
            colors: ['#FF4560'],
            title: {
                text: 'Temperature (°C)',
                align: 'left'
            }
        }));
        var chartHumi = new ApexCharts(document.querySelector("#chartHumi"), Object.assign({}, commonOptions, {
            series: [{
                name: 'Humidity',
                data: <?php echo json_encode($chartData['humid']); ?>
            }],
            colors: ['#00E396'],
            title: {
                text: 'Humidity (%)',
                align: 'left'
            }
        }));
        chartTemp.render();
        chartHumi.render();

        // --- ส่วนที่ 2: ฟังก์ชันสำหรับกดปุ่มแล้วส่งค่า (ที่คุณขาดไป) ---
        function toggleDevice(deviceName, isChecked) {
            const statusValue = isChecked ? 1 : 0;

            // อัปเดตข้อความทันทีที่กด
            updateCardUI(deviceName, isChecked);

            // ส่งค่าไปบันทึก (AJAX)
            const formData = new FormData();
            formData.append('device', deviceName);
            formData.append('status', statusValue);

            fetch('update_device.php', {
                method: 'POST',
                body: formData
            }).catch(err => console.error('Error:', err));
        }

        // ฟังก์ชันเปลี่ยนข้อความ (OPEN/CLOSED, ON/OFF)
        function updateCardUI(device, isChecked) {
            const statusText = document.getElementById(`status-${device}`);
            if (statusText) {
                if (isChecked) {
                    statusText.innerText = (device === 'door') ? "OPEN" : "ON";
                    statusText.style.color = "#00E396"; // สีเขียว
                } else {
                    statusText.innerText = (device === 'door') ? "CLOSED" : "OFF";
                    statusText.style.color = "#888"; // สีเทา
                }
            }
        }

        async function updateData() {
            try {
                const response = await fetch('api.php');
                const data = await response.json();

                // อัปเดต Smoke
                const latestSmoke = data.smoke[data.smoke.length - 1];
                const latestTemp = data.temp[data.temp.length - 1];
                document.querySelector("#smokeVal").innerText = latestSmoke;

                // อัปเดต Fire Status
                const fireStatusEl = document.querySelector("#fireStatus");
                const fireCardEl = document.querySelector("#fireCard");
                const fireDetailEl = document.querySelector("#fireDetail");

                if (latestSmoke > 1500) {
                    fireStatusEl.innerText = "DANGER";
                    fireStatusEl.style.color = "#FF4560";
                    fireCardEl.style.borderTopColor = "#FF4560";
                    fireDetailEl.innerText = "Fire Detected!";
                } else {
                    fireStatusEl.innerText = "NORMAL";
                    fireStatusEl.style.color = "#00E396";
                    fireCardEl.style.borderTopColor = "#00E396";
                    fireDetailEl.innerText = "Safe";
                }

                // อัปเดตสถานะปุ่ม (Sync จาก Database)
                if (data.devices) {
                    updateSwitchState('door', data.devices.door);
                    updateSwitchState('buzzer', data.devices.buzzer);
                    updateSwitchState('fan', data.devices.fan);
                }

                // อัปเดตกราฟ
                chartTemp.updateSeries([{
                    data: data.temp
                }]);
                chartHumi.updateSeries([{
                    data: data.humid
                }]);
                chartTemp.updateOptions({
                    xaxis: {
                        categories: data.timestamps
                    }
                });
                chartHumi.updateOptions({
                    xaxis: {
                        categories: data.timestamps
                    }
                });

            } catch (error) {
                console.error("Error:", error);
            }
        }

        function updateSwitchState(name, statusFromDB) {
            const toggle = document.getElementById('sw-' + name);
            const isDbOn = (statusFromDB == 1);
            if (toggle && toggle.checked !== isDbOn) {
                toggle.checked = isDbOn;
                updateCardUI(name, isDbOn);
            }
        }

        setInterval(updateData, 3000);
    </script>
</body>

</html>