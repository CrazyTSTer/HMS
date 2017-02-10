
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <ArduinoJson.h>

#define COLD_PIN D1
#define HOT_PIN  D2

unsigned int cold_i = 0, hot_i = 0;
unsigned int cold_FirstTick = 0, cold_LastTick = 0, hot_FirstTick = 0, hot_LastTick = 0;
unsigned int cold_addFirst, cold_addLast, hot_addFirst, hot_addLast;
unsigned long cold_volume, hot_volume, prev_millis;
boolean isWiFiConnected;

//const char* ssid     = "marakaza_2.4";
//const char* password = "pafnutii24";
const char* ssid     = "htc";
const char* password = "00000000";

const int MINUTE = 60 * 1000; //60секунд * 1000милиСекунд
const int DEBOUNCE_TIME = 100;

void setup() {
	prev_millis = millis();

	Serial.begin(115200);

	pinMode(COLD_PIN, INPUT_PULLUP);
	pinMode(HOT_PIN, INPUT_PULLUP);

	/*
    //0 -- -- -- -- 3 -- -- -- 7 -- -- -- 9
	//       4      |    3     |     3
	//  разомкнуто  | замкнуто | разомкнуто
	//       1           0           1

    //Если контроллер включился когда счетчик находился в положении замкнуто
	//Тогда с первым изменением состояния счетчика дописываем 3 литра воды (часть уже утекла)
	//Пример: счетчик перевалил за отметку 3 литра, кран выключили, ушли на работу,
	//пока были на работе, вырубили и врубили свет. Пришли домой вечером, помыли руки, вода перевалила
	//за отметку 7 литров.

	//Если контроллер включился когда счетчик находился в положении разомкнуто
	//Тогда с первым изменением состояния счетчика дописываем 7 литров воды
	//Должно утечь 3 литра воды*/
	if (digitalRead(COLD_PIN) == LOW) {
		cold_addFirst = 3;
		cold_addLast = 7;
	} else {
		cold_addFirst = 7;
		cold_addLast = 3;
	}

	if (digitalRead(HOT_PIN) == LOW) {
		hot_addFirst = 3;
		hot_addLast = 7;
	} else {
		hot_addFirst = 7;
		hot_addLast = 3;
	}

	attachInterrupt(COLD_PIN, cold_changeState, CHANGE);
	attachInterrupt(HOT_PIN, hot_changeState, CHANGE);

	isWiFiConnected = WiFiConnect();
}

boolean WiFiConnect()
{
	WiFi.disconnect(true);
	WiFi.begin(ssid, password);
	for (int i = 0; i < 60 || WiFi.status() != WL_CONNECTED; i++) {
		delay(500);
	}

	if (WiFi.status() != WL_CONNECTED) {
		return false;
	}

	return true;
}

void cold_changeState()
{
	static unsigned long cold_prevMillis;
    if(millis() - cold_prevMillis > DEBOUNCE_TIME) {
    	cold_i++;
        if (cold_i%2 == 1) {
        	cold_FirstTick++;
        } else {
        	cold_LastTick++;
        }
    }
    cold_prevMillis = millis();
}

void hot_changeState()
{
	static unsigned long hot_prevMillis;
    if(millis() - hot_prevMillis > DEBOUNCE_TIME) {
    	hot_i++;
        if (hot_i%2 == 1) {
        	hot_FirstTick++;
        } else {
        	hot_LastTick++;
        }
    }
    hot_prevMillis = millis();
}

boolean sendDataToRemoteHost(long coldwater, long hotwater)
{
	if (WiFi.status() != WL_CONNECTED) {
		return false;
	}

	HTTPClient Client;

	Client.begin("http://192.168.1.2/HMS/WaterStat.php?set['coldwater']=" + String(coldwater) + "&set['hotwater']=" + String(hotwater));

    int httpCode = Client.GET();

	if (httpCode < 0 || httpCode != HTTP_CODE_OK) {
		return false;
	}

	String response = Client.getString();
	DynamicJsonBuffer jsonBuffer;

	JsonObject& json = jsonBuffer.parseObject(response);

	if (json["status"] != "success") {
		return false;
	}

	if (bool(json["data"]) != true) {
		return false;
	}

	return true;
}

void loop()
{
	if (cold_FirstTick > 0) {
		cold_FirstTick--;
		cold_volume += cold_addFirst;
	}
	if (cold_LastTick > 0) {
		cold_LastTick--;
		cold_volume += cold_addLast;
	}

	if (cold_FirstTick > 0) {
		hot_FirstTick--;
		hot_volume += hot_addFirst;
	}
	if (cold_LastTick > 0) {
		hot_LastTick--;
		hot_volume += hot_addLast;
	}

	if (millis() - prev_millis > 5 * MINUTE) {
		prev_millis = millis();
		if (isWiFiConnected && WiFi.status() == WL_CONNECTED) {
			if (sendDataToRemoteHost(cold_volume, hot_volume)) {
				cold_volume = 0;
				hot_volume = 0;
			}
		} else {
			isWiFiConnected = WiFiConnect();
		}
	}
}



