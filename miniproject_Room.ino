#include <WiFi.h>
#include <WiFiManager.h>
#include <ESP32Servo.h>
#include <DHT.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include "soc/soc.h"
#include "soc/rtc_cntl_reg.h"

#define DHTTYPE DHT11   

// --- กำหนดขา Pin ---
#define TRIG_PIN 32      
#define ECHO_PIN 33      
#define PIN_DHT 4        
#define PIN_MQ2 34       
#define PIN_SERVO 18     
#define PIN_FAN_MOSFET 19
#define PIN_BUZZER 21
#define PIN_LED_GREEN 25 
#define PIN_LED_RED_WIFI 26 
#define PIN_LED_RED_ALARM 27 
#define PIN_RESET_BUTTON 0 
#define PIN_EMERGENCY_BUTTON 23 

// --- Config ---
const int DISTANCE_THRESHOLD = 10;   
const int DOOR_MOVE_TIME = 3000;    
const int SERVO_OPEN_DIR = 180;      
const int SERVO_CLOSE_DIR = 90;      
const int GAS_THRESHOLD = 1500; 

// --- Define Door States ---
#define STATE_CLOSED 0
#define STATE_OPEN 1
#define STATE_OPENING 2  
#define STATE_CLOSING 3  

DHT dht(PIN_DHT, DHTTYPE);
Servo myServo;
WiFiManager wm; 

// *** อย่าลืมเช็ค IP ให้ตรง ***
const char* url_save = "http://172.24.163.159/iot-miniproj/save_data.php";
const char* url_get  = "http://172.24.163.159//iot-miniproj/get_status.php";

// ตัวแปรสถานะ
int doorStatus = STATE_CLOSED; 
int fanStatus = 0;
int buzzerStatus = 0;

// ตัวแปรเก็บค่า Sensor
float currentTemp = 0.0;
float currentHum = 0.0;
int currentGas = 0;

// ตัวแปรจากเว็บ
int web_buzzer = 0; 
int web_fan = 0;
int web_door = 0; 

// ตัวแปรจับเวลา (millis)
unsigned long lastMotionTime = 0; 
unsigned long lastServerUpdate = 0;
unsigned long doorMoveStartTime = 0;

int consecutiveDetectCount = 0; 

void setup() {
  WRITE_PERI_REG(RTC_CNTL_BROWN_OUT_REG, 0); 
  setCpuFrequencyMhz(80); 
  Serial.begin(115200); // *** เปิด Serial Monitor ที่ความเร็วนี้ ***

  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);
  pinMode(PIN_FAN_MOSFET, OUTPUT);
  pinMode(PIN_BUZZER, OUTPUT);
  pinMode(PIN_LED_GREEN, OUTPUT);
  pinMode(PIN_LED_RED_WIFI, OUTPUT);
  pinMode(PIN_LED_RED_ALARM, OUTPUT);
  pinMode(PIN_RESET_BUTTON, INPUT_PULLUP);
  pinMode(PIN_EMERGENCY_BUTTON, INPUT_PULLUP); 

  myServo.attach(PIN_SERVO);
  myServo.write(SERVO_CLOSE_DIR); 
  digitalWrite(PIN_LED_GREEN, LOW);
  
  dht.begin();

  wm.setConfigPortalBlocking(false); 
  if (!wm.autoConnect("SmartRoom_ESP")) {
    Serial.println("Offline Mode");
    digitalWrite(PIN_LED_RED_WIFI, LOW);
  } else {
    Serial.println("WiFi Connected!");
    digitalWrite(PIN_LED_RED_WIFI, HIGH);
  }
}

long readDistance() {
  digitalWrite(TRIG_PIN, LOW); delayMicroseconds(2);
  digitalWrite(TRIG_PIN, HIGH); delayMicroseconds(10);
  digitalWrite(TRIG_PIN, LOW);
  
  long duration = pulseIn(ECHO_PIN, HIGH, 30000); 
  long dist = duration * 0.034 / 2;
  
  if (dist == 0 || dist > 400) return 999; 
  return dist;
}

int readGasSmoothed() {
  long sum = 0;
  int samples = 10; 
  for(int i=0; i<samples; i++) {
    sum += analogRead(PIN_MQ2);
    delay(2); 
  }
  return (int)(sum / samples);
}

void checkResetWiFi() {
  if (digitalRead(PIN_RESET_BUTTON) == LOW) {
    unsigned long startPress = millis();
    bool resetFlag = false;
    while (digitalRead(PIN_RESET_BUTTON) == LOW) {
      if (millis() - startPress > 5000) { resetFlag = true; break; }
      digitalWrite(PIN_LED_RED_WIFI, !digitalRead(PIN_LED_RED_WIFI));
      delay(100);
    }
    if (resetFlag) {
      digitalWrite(PIN_LED_RED_WIFI, HIGH); tone(PIN_BUZZER, 2000, 500); 
      wm.resetSettings(); delay(1000); ESP.restart(); 
    } else {
      digitalWrite(PIN_LED_RED_WIFI, (WiFi.status() == WL_CONNECTED) ? HIGH : LOW);
    }
  }
}

// ฟังก์ชันเปิดประตู (เรียกใช้เมื่อมี Trigger)
void triggerOpenDoor() {
  if (doorStatus == STATE_CLOSED || doorStatus == STATE_CLOSING) {
    // ปริ้นท์สถานะว่ากำลังจะเปิด (เหตุผลปริ้นท์ก่อนเรียกฟังก์ชันนี้)
    myServo.write(SERVO_OPEN_DIR); 
    doorMoveStartTime = millis();  
    doorStatus = STATE_OPENING;    
    digitalWrite(PIN_LED_GREEN, HIGH);
  }
}

void triggerCloseDoor() {
  if (doorStatus == STATE_OPEN || doorStatus == STATE_OPENING) {
    Serial.println(">>> Closing Door (Auto or Command)"); // Debug
    myServo.write(SERVO_CLOSE_DIR); 
    doorMoveStartTime = millis();   
    doorStatus = STATE_CLOSING;     
  }
}

void processDoorState() {
  if (doorStatus == STATE_OPENING) {
    if (millis() - doorMoveStartTime >= DOOR_MOVE_TIME) {
      doorStatus = STATE_OPEN; 
      Serial.println("--- Door Fully OPEN ---");
    }
  }
  else if (doorStatus == STATE_CLOSING) {
    if (millis() - doorMoveStartTime >= DOOR_MOVE_TIME) {
      doorStatus = STATE_CLOSED; 
      digitalWrite(PIN_LED_GREEN, LOW); 
      Serial.println("--- Door Fully CLOSED ---");
    }
  }
}

void receiveData() {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.setTimeout(200); 
    http.begin(url_get);
    int httpCode = http.GET(); 

    if (httpCode > 0) {
      String payload = http.getString();
      StaticJsonDocument<1024> doc; 
      DeserializationError error = deserializeJson(doc, payload);
      if (!error && doc.containsKey("data")) {
        JsonObject data = doc["data"];
        web_door = data["door"];
        web_fan = data["fan"];
        web_buzzer = data["buzzer"];

        // *** Debug ดูค่าที่รับมาจากเว็บ ***
        Serial.print("WEB DATA -> Door: "); Serial.print(web_door);
        Serial.print(" | Fan: "); Serial.print(web_fan);
        Serial.print(" | Buzzer: "); Serial.println(web_buzzer);
      }
    }
    http.end();
  } 
  // else {
  //    *** คอมเมนต์ปิดตรงนี้ เพื่อไม่ให้มันสั่งปิดเองเวลาเน็ตหลุด ***
  //    web_door = 0; web_fan = 0; web_buzzer = 0;
  // }
}

void sendData(float t, float h, int g) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.setTimeout(200);
    http.begin(url_save);
    http.addHeader("Content-Type", "application/json");
    StaticJsonDocument<200> doc;
    doc["t"] = t; doc["h"] = h; doc["s"] = g;
    // ส่งสถานะปัจจุบันกลับไป (เพื่อให้ PHP รู้ว่าประตูเปิดอยู่หรือเปล่า)
    doc["door"] = (doorStatus == STATE_OPEN || doorStatus == STATE_OPENING) ? 1 : 0; 
    doc["buzzer"] = buzzerStatus; 
    doc["fan"] = fanStatus;
    String body;
    serializeJson(doc, body);
    http.POST(body);
    http.end();
  }
}

void loop() {
  wm.process(); 
  checkResetWiFi();
  processDoorState(); 
  long dist = readDistance();

  // --- Web Sync & Sensor (Every 3 Seconds) ---
  if (millis() - lastServerUpdate > 3000) { 
    lastServerUpdate = millis();

    float newT = dht.readTemperature();
    float newH = dht.readHumidity();
    if (!isnan(newT)) currentTemp = newT;
    if (!isnan(newH)) currentHum = newH;
    currentGas = readGasSmoothed();

    Serial.print("SENSORS -> T: "); Serial.print(currentTemp);
    Serial.print(" | H: "); Serial.print(currentHum);
    Serial.print(" | Gas: "); Serial.println(currentGas);

    digitalWrite(PIN_LED_RED_WIFI, (WiFi.status() == WL_CONNECTED) ? HIGH : LOW);
    receiveData();  
    
    // Logic Fan (Temp OR Gas OR Web)
    if (currentTemp >= 32.0 || currentGas > GAS_THRESHOLD || web_fan == 1) { 
        if(fanStatus == 0) Serial.println(">>> Fan ON"); // Debug
        digitalWrite(PIN_FAN_MOSFET, HIGH); fanStatus = 1; 
    } else { 
        if(fanStatus == 1) Serial.println(">>> Fan OFF"); // Debug
        digitalWrite(PIN_FAN_MOSFET, LOW); fanStatus = 0; 
    }

    // Logic Gas Alarm
    if (currentGas > GAS_THRESHOLD || web_buzzer == 1) { 
        digitalWrite(PIN_LED_RED_ALARM, HIGH); tone(PIN_BUZZER, 1000); buzzerStatus = 1; 
    } else { 
        digitalWrite(PIN_LED_RED_ALARM, LOW); noTone(PIN_BUZZER); buzzerStatus = 0; 
    }

    sendData(currentTemp, currentHum, currentGas);  
  }

  // =========================================================
  // 🔥 [DEBUG ZONE] ส่วนเช็คสาเหตุการเปิดประตู 🔥
  // =========================================================

  // 1. เช็คปุ่มกดฉุกเฉิน
  if (digitalRead(PIN_EMERGENCY_BUTTON) == LOW) {
    if (doorStatus == STATE_CLOSED) {
        Serial.println("📢 REASON: Emergency Button Pressed! (ปุ่มกด)");
        triggerOpenDoor();
    }
    lastMotionTime = millis(); 
  }
  // 2. เช็คคำสั่งจากเว็บ (web_door)
  else if (web_door == 1) {
    if (doorStatus == STATE_CLOSED) {
        Serial.println("📢 REASON: Web Command! (สั่งจากเว็บ)");
        triggerOpenDoor();
    }
    lastMotionTime = millis();
  }
  // 3. เช็คเซนเซอร์ Ultrasonic
  else {
    if (dist != 999 && dist < DISTANCE_THRESHOLD) {
      consecutiveDetectCount++; 
    } else {
      consecutiveDetectCount = 0; 
    }

    if (consecutiveDetectCount >= 3) {
      if (doorStatus == STATE_CLOSED) {
          Serial.print("📢 REASON: Ultrasonic Sensor! Dist: ");
          Serial.print(dist);
          Serial.println(" cm (เจอคน)");
          triggerOpenDoor();
      }
      lastMotionTime = millis();
    }
    
    // ปิดประตูอัตโนมัติ (ถ้าไม่มีอะไรขวาง + เวลาผ่านไป 3 วิ)
    if (doorStatus == STATE_OPEN) {
       if (millis() - lastMotionTime > 3000) {
          triggerCloseDoor();
          consecutiveDetectCount = 0; 
       }
    }
    delay(10); 
  }
}