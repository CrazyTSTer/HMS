#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <ArduinoJson.h>

#define COLD_PIN D1
#define HOT_PIN  D2
#define LED      D4
#define OFF      HIGH
#define ON       LOW

//Константы
const int MINUTE = 60 * 1000;  //60секунд * 1000милиСекунд
const int DEBOUNCE_TIME = 100; //Интервал за который должен исчезнуть "дребезг" контактов
const int SEND_PERIOD = 15;    //Период отправки данных на сервер в минутах
const int BLINK_PERIOD = 1000; //Интервал мигания диодом при не удачном обращении к серверу

//Переменные счетчиков
unsigned int cold_firstTick = 0, cold_lastTick = 0, hot_firstTick = 0, hot_lastTick = 0;
unsigned int cold_addFirst, cold_addLast, hot_addFirst, hot_addLast;
unsigned int cold_volume, hot_volume;
boolean cold_isFirstTick = true, hot_isFirstTick = true;

//Переменные работы с контроллером
unsigned long prev_millis, blink_prev_millis;
boolean isWiFiConnected = false, sendFailure = false;

//WiFi login and password
const char* ssid     = "marakaza_2.4";
const char* password = "pafnutii24";

void setup() {
	prev_millis = millis();
	blink_prev_millis = millis();

	pinMode(COLD_PIN, INPUT_PULLUP);
	pinMode(HOT_PIN, INPUT_PULLUP);

	pinMode(LED, OUTPUT);
	digitalWrite(LED, OFF);

	/*
    //0 -- -- -- -- 2 -- -- -- 9 -- -- -- 0
	//       2      |    7     |     1
	//  замкнуто    |разомкнуто| замкнуто
	//       0           1           0

    //Если контроллер включился когда счетчик находился в положении замкнуто
	//Тогда с первым изменением состояния счетчика дописываем 3 литра воды (часть уже утекла)
	//Пример: счетчик перевалил за отметку 9 литров, кран выключили, ушли на работу,
	//пока были на работе, вырубили и врубили свет. Пришли домой вечером, помыли руки, вода перевалила
	//за отметку 2 литров.

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
	digitalWrite(LED, OFF);
	sendFailure = false;

	WiFi.disconnect(true);
	WiFi.mode(WIFI_STA);
	WiFi.begin(ssid, password);

	for (int i = 0; i < 60 || WiFi.status() != WL_CONNECTED; i++) {
		changeLedState();
		delay(500);
	}

	if (WiFi.status() != WL_CONNECTED) {
		digitalWrite(LED, OFF);
		return false;
	}

    digitalWrite(LED, ON);
	return true;
}

void cold_changeState()
{
	static unsigned long cold_prevMillis;
    if(millis() - cold_prevMillis > DEBOUNCE_TIME) {
        if (cold_isFirstTick) {
        	cold_firstTick++;
        	cold_isFirstTick = false;
        } else {
        	cold_lastTick++;
        	cold_isFirstTick = true;
        }
    }
    cold_prevMillis = millis();
}

void hot_changeState()
{
	static unsigned long hot_prevMillis;
    if(millis() - hot_prevMillis > DEBOUNCE_TIME) {
        if (hot_isFirstTick) {
        	hot_firstTick++;
        	hot_isFirstTick = false;
        } else {
        	hot_lastTick++;
        	hot_isFirstTick = true;
        }
    }
    hot_prevMillis = millis();
}

boolean sendDataToRemoteHost(int coldwater, int hotwater)
{
	if (WiFi.status() != WL_CONNECTED) {
		return false;
	}

	HTTPClient Client;

	Client.begin("http://192.168.1.2/HMS/WaterStat.php?action=set&values[coldwater]=" + String(coldwater) + "&values[hotwater]=" + String(hotwater));

    int httpCode = Client.GET();

	if (httpCode < 0 || httpCode != HTTP_CODE_OK) {
		return false;
	}

	String response = Client.getString();
	DynamicJsonBuffer jsonBuffer;

	JsonObject& json = jsonBuffer.parseObject(response);

	if (json["status"] == "success") {
		return true;
	}

	return false;
}

void changeLedState(void)
{
	if (digitalRead(LED) == OFF) {
		digitalWrite(LED, ON);
	} else {
		digitalWrite(LED, OFF);
	}
}

void loop()
{
	if (cold_firstTick > 0) {
		cold_firstTick--;
		cold_volume += cold_addFirst;
	}
	if (cold_lastTick > 0) {
		cold_lastTick--;
		cold_volume += cold_addLast;
	}

	if (hot_firstTick > 0) {
		hot_firstTick--;
		hot_volume += hot_addFirst;
	}
	if (hot_lastTick > 0) {
		hot_lastTick--;
		hot_volume += hot_addLast;
	}

	if (sendFailure == true && millis() - blink_prev_millis > BLINK_PERIOD) {
		changeLedState();
		blink_prev_millis = millis();
	}

	if (millis() - prev_millis > SEND_PERIOD * MINUTE) {
		prev_millis = millis();
		if (isWiFiConnected && WiFi.status() == WL_CONNECTED) {
			if (cold_volume != 0 || hot_volume != 0) {
				boolean result = sendDataToRemoteHost(cold_volume, hot_volume);
				if (result) {
					sendFailure = false;
					digitalWrite(LED, ON);
					cold_volume = 0;
					hot_volume = 0;
				} else {
					sendFailure = true;
				}
			}
		} else {
			isWiFiConnected = WiFiConnect();
		}
	}
}
