#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <ArduinoJson.h>
#include <Ticker.h>


#define COLD_PIN D1
#define HOT_PIN  D2
#define LED      D4
#define OFF      HIGH
#define ON       LOW

//Константы
const int MINUTE             = 60 * 1000; //60секунд * 1000милиСекунд
const int DEBOUNCE_TIME      = 100;       //Интервал за который должен исчезнуть "дребезг" контактов
const int SEND_PERIOD        = 1;         //Период отправки данных на сервер в минутах
const int BLINK_PERIOD       = 1000;      //Интервал мигания диодом при не удачном обращении к серверу
const int CHECK_METER_PERIOD = 10;         //Интервал опроса счетчика

//Переменные счетчиков
int cold_nextPinState, cold_pinState, hot_nextPinState, hot_pinState;
unsigned int cold_volume, cold_tmpVolume, hot_volume, hot_tmpVolume;
unsigned long cold_prevMillis, hot_prevMillis;

boolean cold_waitForNextInterrupt = true;
boolean hot_waitForNextInterrupt = true;

Ticker cold_CheckState, hot_CheckState;

//Переменные работы с контроллером
unsigned long prevMillis;
boolean isWiFiConnected = false;
Ticker Blink;

//WiFi login and password
const char* ssid     = "marakaza_2.4";
const char* password = "pafnutii24";

void setup() {
	prevMillis = cold_prevMillis = hot_prevMillis = millis();

	pinMode(COLD_PIN, INPUT_PULLUP);
	pinMode(HOT_PIN, INPUT_PULLUP);

	pinMode(LED, OUTPUT);
	digitalWrite(LED, OFF);

	//0 -- -- -- -- 2 -- -- -- 9 -- -- -- 0
	//       2      |    7     |     1
	//  замкнуто    |разомкнуто| замкнуто
	//   0(LOW)       1 (HIGH)    0 (LOW)

	//Если контроллер включился когда счетчик находился в положении замкнуто
	//Тогда с первым изменением состояния счетчика дописываем 3 литра воды (часть уже утекла)
	//Пример: счетчик перевалил за отметку 9 литров, кран выключили, ушли на работу,
	//пока были на работе, вырубили и врубили свет. Пришли домой вечером, помыли руки, вода перевалила
	//за отметку 2 литров.

	//Если контроллер включился когда счетчик находился в положении разомкнуто
	//Тогда с первым изменением состояния счетчика дописываем 7 литров воды

	if (digitalRead(COLD_PIN) == LOW) {
		cold_nextPinState = HIGH;
	} else {
		cold_nextPinState = LOW;
	}

	if (digitalRead(HOT_PIN) == LOW) {
		hot_nextPinState = HIGH;
	} else {
		hot_nextPinState = LOW;
	}

	attachInterrupt(COLD_PIN, cold_InterruptHandler, CHANGE);
	attachInterrupt(HOT_PIN, hot_InterruptHandler, CHANGE);

	cold_CheckState.attach_ms(CHECK_METER_PERIOD, cold_CheckMeterState);
	hot_CheckState.attach_ms(CHECK_METER_PERIOD, hot_CheckMeterState);

	isWiFiConnected = WiFiConnect();
}

boolean WiFiConnect()
{
	Blink.detach();
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
		return false;
	}

	digitalWrite(LED, ON);
	return true;
}

void cold_InterruptHandler()
{
	cold_prevMillis = millis();
	cold_waitForNextInterrupt = false;
}

void hot_InterruptHandler()
{
	hot_prevMillis = millis();
	hot_waitForNextInterrupt = false;
}

void cold_CheckMeterState(void)
{
	unsigned long tmp;
	tmp = millis() - cold_prevMillis;
	if (tmp > DEBOUNCE_TIME && tmp < 3 * DEBOUNCE_TIME && cold_waitForNextInterrupt == false) {
		cold_pinState = digitalRead(COLD_PIN);
		if (cold_pinState == cold_nextPinState) {
			cold_waitForNextInterrupt = true;
			cold_nextPinState = !cold_nextPinState;
			if (cold_pinState == LOW) {
				cold_volume += 7;
			} else {
				cold_volume += 3;
			}
		}
	}
}

void hot_CheckMeterState(void)
{
	unsigned long tmp;
	tmp = millis() - hot_prevMillis;
	if (tmp > DEBOUNCE_TIME && tmp < 3 * DEBOUNCE_TIME && hot_waitForNextInterrupt == false) {
		hot_pinState = digitalRead(HOT_PIN);
		if (hot_pinState == hot_nextPinState) {
			hot_waitForNextInterrupt = true;
			hot_nextPinState = !hot_nextPinState;
			if (hot_pinState == LOW) {
				hot_volume += 7;
			} else {
				hot_volume += 3;
			}
		}
	}
}

boolean SendDataToRemoteHost(unsigned int coldwater, unsigned int hotwater)
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

void ChangeLedState(void)
{
	if (digitalRead(LED) == OFF) {
		digitalWrite(LED, ON);
	} else {
		digitalWrite(LED, OFF);
	}
}

void loop()
{
	if (millis() - prevMillis > SEND_PERIOD * MINUTE) {
		if (isWiFiConnected && WiFi.status() == WL_CONNECTED) {
			if (cold_volume != 0 || hot_volume != 0) {
				cold_tmpVolume = cold_volume;
				hot_tmpVolume = hot_volume;
				boolean result = SendDataToRemoteHost(cold_tmpVolume, hot_tmpVolume);
				if (result) {
					cold_volume -= cold_tmpVolume;
					hot_volume -= hot_tmpVolume;

					Blink.detach();
					digitalWrite(LED, ON);
				} else {
					Blink.attach(1, ChangeLedState);
				}
			}
		} else {
			isWiFiConnected = WiFiConnect();
		}
		prevMillis = millis();
	}
}
