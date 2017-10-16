#include <ESP8266WiFi.h>
#include <WiFiClient.h>
#include <ESP8266HTTPClient.h>
#include <ArduinoJson.h>
#include <Ticker.h>


// ESP8266 WiFi <-> UART Bridge
#define UART_BAUD 9600
#define packTimeout 5 // ms (if nothing more on UART, then send packet)
#define bufferSize 8192

#define LED      D4
#define OFF      HIGH
#define ON       LOW


const char *ssid = "";  // Your ROUTER SSID
const char *password = ""; // and WiFi PASSWORD

const int port = 23;

int i = 0;

WiFiServer WiFi_Server(port);
WiFiClient WiFi_Client;

uint8_t WiFiBuffer[bufferSize];
uint8_t WiFiByteCounter=0;

uint8_t SerialBuffer[bufferSize];
uint8_t SerialByteCounter=0;

boolean isWiFiConnected;

void ChangeLedState(void)
{
	if (digitalRead(LED) == OFF) {
		digitalWrite(LED, ON);
	} else {
		digitalWrite(LED, OFF);
	}
}

boolean WiFiConnect()
{
	Serial.println("Connecting to WiFi...");
	digitalWrite(LED, OFF);

	WiFi.disconnect(true);
	WiFi.mode(WIFI_STA);
	WiFi.begin(ssid, password);

	for (int i = 0; i < 60 && WiFi.status() != WL_CONNECTED; i++) {
		ChangeLedState();
		delay(500);
	}

	if (WiFi.status() != WL_CONNECTED) {
		digitalWrite(LED, OFF);
		Serial.println("Connection failed");
		return false;
	}

    digitalWrite(LED, ON);
    Serial.print("Connected to WiFi; IP="); Serial.println(WiFi.localIP());
    delay(500);
	return true;
}

void setup() {
	//Turn build-in led off
	pinMode(LED, OUTPUT);
	digitalWrite(LED, OFF);

	//Init uart interface
	Serial.begin(UART_BAUD);

	Serial.println("ESP-8266: Init begin");
	isWiFiConnected = WiFiConnect();

	if (isWiFiConnected) {
		Serial.println("Starting TCP Server...");
		delay(500);
		WiFi_Server.begin(); // start TCP server
		Serial.println("TCP Server now running...");
		delay(500);
	} else {
		Serial.println("Failed to start TCP Server due to WiFi connection failed");
		delay(500);
	}

	Serial.println("ESP-8266: Init finish... Now swapping serial pins");
	delay(500);
	Serial.swap();
}

void loop()
{
	if (!WiFi_Client.connected()) { // if client not connected
		WiFi_Client = WiFi_Server.available(); // wait for it to connect
		return;
	}

	// here we have a connected client
	if (WiFi_Client.available()) {
		while (WiFi_Client.available()) {
			WiFiBuffer[WiFiByteCounter] = (uint8_t)WiFi_Client.read(); // read char from client
			if (WiFiByteCounter < bufferSize - 1) {
				WiFiByteCounter++;
			}
		}
		// now send to UART:
		Serial.write(WiFiBuffer, WiFiByteCounter);
		WiFiByteCounter = 0;
	}

	if (Serial.available()) {
		// read the data until pause:
		while (1) {
    		if (Serial.available()) {
    			SerialBuffer[SerialByteCounter] = (char)Serial.read(); // read char from UART
    			if (SerialByteCounter < bufferSize - 1) {
    				SerialByteCounter++;
    			}
    		} else {
    			//delayMicroseconds(packTimeoutMicros);
    			delay(packTimeout);
    			if(!Serial.available()) {
    				break;
    			}
    		}
    	}
    	// now send to WiFi:
		WiFi_Client.write((char*)SerialBuffer, SerialByteCounter);
		SerialByteCounter = 0;
	}
}
