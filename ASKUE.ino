#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <time.h>
#include "ArduinoJson-v5.13.1.h"

#define UART_BAUD               9600

#define BUILDIN_LED_PIN         D4
#define RS485_RX_TX_CONTROL_PIN D2

#define RS485_TRANSMITE         HIGH //Send data
#define RS485_RECEIVE           LOW  //Get data

#define OFF                     HIGH
#define ON                      LOW

//WiFi login and password
//const char* ssid     = "marakaza_2.4";
//const char* password = "pafnutii24";
//const char* ssid     = "BDGUEST";
//const char* password = "Bazinga!";
const char* ssid     = "Melia";
const char* password = "";

const String ELECTRICITY_LOCATION = "Electricity";  //php Class name
const String ELECTRICITY_ACTION   = "actionWhoAmI"; //php method in class

//Global variables
unsigned long prevMillisWhoAmI;
boolean       isWiFiConnected, isWhoAmISent;

//Time section
const int HOUR   = 3600;
const int SECOND = 1000;

time_t    now;
struct    tm * my_time;
int       prevSecond = 0;

//WiFi <--> UART Bridge section
const int  PORT        = 9876; // tcp Port
const int  TIMEOUT     = 5;    // ms (if nothing more on UART, then send packet)
const int  BUFFER_SIZE = 8192; // max data

WiFiServer WiFiServer(PORT);
WiFiClient WiFiClient;

uint8_t    wifiToUART[BUFFER_SIZE];
uint8_t    wifiToUART_counter = 0;

uint8_t    UARTToWifi[BUFFER_SIZE];
uint8_t    UARTToWiFi_counter = 0;

void setup()
{
	Serial.begin(UART_BAUD);

	pinMode(BUILDIN_LED_PIN, OUTPUT);
	digitalWrite(BUILDIN_LED_PIN, OFF);

	pinMode(RS485_RX_TX_CONTROL_PIN, OUTPUT);
	digitalWrite(RS485_RX_TX_CONTROL_PIN, RS485_RECEIVE);

	prevMillisWhoAmI = millis();

	isWiFiConnected = WiFiConnect();

	configTime(3 * HOUR, 0, "pool.ntp.org", "time.nist.gov");
}

boolean WiFiConnect()
{
	isWhoAmISent = false;

	digitalWrite(BUILDIN_LED_PIN, OFF);

	WiFi.disconnect(true);
	WiFi.mode(WIFI_STA);
	WiFi.begin(ssid, password);

	for (int i = 0; i < 60 && WiFi.status() != WL_CONNECTED; i++) {
		digitalWrite(BUILDIN_LED_PIN, !digitalRead(BUILDIN_LED_PIN));
		delay(500);
	}

	if (WiFi.status() != WL_CONNECTED) {
		digitalWrite(BUILDIN_LED_PIN, OFF);
		return false;
	}

    digitalWrite(BUILDIN_LED_PIN, ON);
	return true;
}

boolean SendWhoAmI()
{
	//return true;
	if (WiFi.status() != WL_CONNECTED) {
		return false;
	}

	HTTPClient Client;

	//Client.begin("http://192.168.1.2/HMS/index.php?location=" + ELECTRICITY_LOCATION + "&action=" + ELECTRICITY_ACTION + "&host=" + WiFi.localIP().toString() + "&port=" + String(PORT));
	Client.begin("http://192.168.2.248:8888/index.php?location=" + ELECTRICITY_LOCATION + "&action=" + ELECTRICITY_ACTION + "&host=" + WiFi.localIP().toString() + "&port=" + String(PORT));
	int httpCode = Client.GET();

	if (httpCode < 0 || httpCode != HTTP_CODE_OK) {
		return false;
	}

	String response = Client.getString();
	DynamicJsonBuffer jsonBuffer;

	JsonObject& json = jsonBuffer.parseObject(response);
	if (!json.success()) {
		return false;
	}

	if (json["status"] != "success") {
		return false;
	}

	return true;
}

boolean SendRedyToIterateNewData()
{
	//TODO: send data
	return false;
}

void doBridge(void)
{
	if(!WiFiClient.connected()) { // if client not connected
		WiFiClient = WiFiServer.available(); // wait for it to connect
		return;
	}

	// here we have a connected client success
	if(WiFiClient.available()) {
		while(WiFiClient.available()) {
			wifiToUART[wifiToUART_counter] = (uint8_t)WiFiClient.read(); // read char from client
			if (wifiToUART_counter < BUFFER_SIZE - 1) wifiToUART_counter++;
		}

		// now send to UART:
		digitalWrite(RS485_RX_TX_CONTROL_PIN, RS485_TRANSMITE);
		Serial.write(wifiToUART, wifiToUART_counter);
		Serial.flush();
		digitalWrite(RS485_RX_TX_CONTROL_PIN, RS485_RECEIVE);
		wifiToUART_counter = 0;
	}

	if(Serial.available()) {
		// read the data until pause:
		while(1) {
			if(Serial.available()) {
				UARTToWifi[UARTToWiFi_counter] = (char)Serial.read(); // read char from UART
				if (UARTToWiFi_counter < BUFFER_SIZE - 1) UARTToWiFi_counter++;
			} else {
				//delayMicroseconds(packTimeoutMicros);
				delay(TIMEOUT);
				if(!Serial.available()) {
					break;
				}
			}
		}

		// now send to WiFi:
		WiFiClient.write((char*)UARTToWifi, UARTToWiFi_counter);
		UARTToWiFi_counter = 0;
	}
}

void loop()
{
	if (isWiFiConnected && WiFi.status() == WL_CONNECTED) {
		if (WiFiServer.status() == CLOSED) {
			WiFiServer.begin();
		}

		if (isWhoAmISent == false && millis() - prevMillisWhoAmI > 10 * SECOND) {
			Serial.println("Trying to send WhoAmI");
			isWhoAmISent = SendWhoAmI();
			prevMillisWhoAmI = millis();
		}

		if (isWhoAmISent == true) {
			doBridge();
		}

		if (time(&now)) {
			my_time = localtime(&now);
			if (prevSecond != my_time->tm_sec) {
				Serial.println(String(my_time->tm_hour) + ":" + String(my_time->tm_min) + ":" + String(my_time->tm_sec));
				prevSecond = my_time->tm_sec;
			}
			if (isWhoAmISent == true) {
				//TODO DO ITERATE DATA
			}
		}
	} else {
		isWiFiConnected = WiFiConnect();
	}
}
