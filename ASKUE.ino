#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include "ArduinoJson-v5.13.4.h"

#define UART_BAUD               9600

#define BUILDIN_LED_PIN         D4
#define RS485_RX_TX_CONTROL_PIN D2

#define RS485_TRANSMITE         HIGH //Send data
#define RS485_RECEIVE           LOW  //Get data

#define OFF                     HIGH
#define ON                      LOW

//WiFi login and password
const char* ssid     = "marakaza_2.4";
const char* password = "pafnutii24";

const String PHP_LOCATION      = "ElectricityMetersSettings";  //php Class name
const String PHP_ACTION_WHOAMI = "actionESPWhoAmI"; //php method in class

//Global variables
unsigned long prevMillisWhoAmI, prevMillisSerial;
boolean       isWiFiConnected, isWhoAmISent, isChunkFromSerialSentToWiFi, espCloseConnection, espSendNewLine;

//WiFi <--> UART Bridge section
const int PORT = 9876; // tcp Port
unsigned int espTimeout;

WiFiServer WiFiServer(PORT);
WiFiClient WiFiClient;


size_t wifiLen = 0, serialLen = 0;

void setup()
{
	Serial.begin(UART_BAUD);

	pinMode(BUILDIN_LED_PIN, OUTPUT);
	digitalWrite(BUILDIN_LED_PIN, OFF);

	pinMode(RS485_RX_TX_CONTROL_PIN, OUTPUT);
	digitalWrite(RS485_RX_TX_CONTROL_PIN, RS485_RECEIVE);

	prevMillisWhoAmI = prevMillisSerial = millis();

	isWiFiConnected = WiFiConnect();
}

boolean WiFiConnect()
{
	isWhoAmISent = false;
	isChunkFromSerialSentToWiFi = false;

	WiFiServer.close();

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

	WiFiServer.begin();
	//WiFiServer.setNoDelay(true);

    digitalWrite(BUILDIN_LED_PIN, ON);

	return true;
}

boolean SendWhoAmI()
{
	if (WiFi.status() != WL_CONNECTED) {
		return false;
	}

	HTTPClient Client;

	Client.begin("http://192.168.1.2/HMS/index.php?location=" + PHP_LOCATION + "&action=" + PHP_ACTION_WHOAMI + "&host=" + WiFi.localIP().toString() + "&port=" + String(PORT));

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

	if (json["data"]["ESPTimeout"].success()) {
		espTimeout = json["data"]["ESPTimeout"];
	} else {
		espTimeout = 5;
	}

	if (json["data"]["ESPCloseConnection"].success()) {
		espCloseConnection = json["data"]["ESPCloseConnection"];
	} else {
		espCloseConnection = true;
	}

	if (json["data"]["ESPSendNewLine"].success()) {
		espSendNewLine = json["data"]["ESPSendNewLine"];
	} else {
		espSendNewLine = true;
	}

	return true;
}

void doBridge(void)
{
	if (WiFiServer.hasClient()) {
		if (!WiFiClient.connected()){
			if (WiFiClient) WiFiClient.stop();
			WiFiClient = WiFiServer.available();
			isChunkFromSerialSentToWiFi = false;
		}
	}

	if (WiFiClient && WiFiClient.connected()){
		if (WiFiClient.available()) {
			wifiLen = WiFiClient.available();
			uint8_t *wifiBuf = (uint8_t *)malloc(wifiLen);
			WiFiClient.readBytes(wifiBuf, wifiLen);
			digitalWrite(RS485_RX_TX_CONTROL_PIN, RS485_TRANSMITE);
			Serial.write(wifiBuf, wifiLen);
			Serial.flush();
			digitalWrite(RS485_RX_TX_CONTROL_PIN, RS485_RECEIVE);
			free(wifiBuf);
		}
	}

	if (Serial.available()) {
		serialLen = Serial.available();
		uint8_t *serialBuf = (uint8_t *)malloc(serialLen);
		Serial.readBytes(serialBuf, serialLen);
		if (WiFiClient && WiFiClient.connected()){
			WiFiClient.write(serialBuf, serialLen);
			WiFiClient.flush();
			isChunkFromSerialSentToWiFi = true;
		}
		free(serialBuf);
		prevMillisSerial = millis();
	} else {
		if (millis() - prevMillisSerial > espTimeout && isChunkFromSerialSentToWiFi) {
			if (espCloseConnection) {
				WiFiClient.stop();
			} else {
				if (espSendNewLine) {
					WiFiClient.println();
					WiFiClient.flush();
				}
			}
			isChunkFromSerialSentToWiFi = false;
		}
	}
}

void loop()
{
	if (isWiFiConnected && WiFi.status() == WL_CONNECTED) {
		if (isWhoAmISent == false && millis() - prevMillisWhoAmI > 5 * 1000) {
			isWhoAmISent = SendWhoAmI();
			prevMillisWhoAmI = millis();
		}

		if (isWhoAmISent == true) {
			doBridge();
		}
	} else {
		isWiFiConnected = WiFiConnect();
	}
}
