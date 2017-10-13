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


const char *ssid = "BD-GUEST";  // Your ROUTER SSID
const char *password = "yabadabadoo!"; // and WiFi PASSWORD
const int port = 23;

WiFiServer WiFiServer1(port);
WiFiClient WiFiClient1;

unsigned char buf1[bufferSize];
int i1=0;

unsigned char buf2[bufferSize];
uint8_t i2=0;

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
    Serial.print("Connected to WIFI; IP=");Serial.println(WiFi.localIP());
	return true;
}

void setup() {
	delay(500);

	Serial.begin(UART_BAUD);

	isWiFiConnected = WiFiConnect();

	Serial.println("Starting TCP Server");
	WiFiServer1.begin(); // start TCP server
}

void loop()
{
	if(!WiFiClient1.connected()) { // if client not connected
		WiFiClient1 = WiFiServer1.available(); // wait for it to connect
		return;
	}

	// here we have a connected client
	if(WiFiClient1.available()) {
		while(WiFiClient1.available()) {
			buf1[i1] = (uint8_t)WiFiClient1.read(); // read char from client
			if (i1 < bufferSize - 1) {
				i1++;
			}
		}
		// now send to UART:
		Serial.write(buf1, i1);
		i1 = 0;
	}

	if(Serial.available()) {
		// read the data until pause:
		while(1) {
    		if(Serial.available()) {
    			buf2[i2] = (char)Serial.read(); // read char from UART
    			if (i2 < bufferSize - 1) {
    				i2++;
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
    	WiFiClient1.write((char*)buf2, i2);
    	i2 = 0;
	}
}
