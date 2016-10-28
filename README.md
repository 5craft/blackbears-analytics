# Black Bears Analytics
----

BlackBears Analytics - аналитика для мобильных приложений, позволяющая узнать актуальные показатели вашего мобильного приложения: Downloads, ARPU, ARPPU, доход по проверенным  покупкам пользователей внутри приложений, а так же рекламный доход и средней чек пользователей. BlackBears Analytics позволяет фильтровать полученные данные и делать расчет показателей пользователей с делением по источникам трафика и рекламным площадкам. BlackBears Analytics разрабатывается совместно с Yandex и использует технологии [Яндекс.AppMetrica](appmetrika.yandex.ru) и [ClickHouse](https://clickhouse.yandex) . В качестве фреймворка используется [Yii 2.x](http://www.yiiframework.com/)


### Минимальные требования
----

* Linux, x86_64 with SSE 4.2.
* curl, sed, grep, date, cut, tail
* PHP 5.5+ with fpm, memcached
* memcached
* clickhouse-server
* clickhouse-client

### Первоначальная установка и настройка
----

1.	Загрузить проект.
2.	Выполнить команду _composer global require "fxp/composer-asset-plugin:*"_, затем запустить скрипт из папки с корнем проекта _composer_ командой `composer install`

        2.1.	Если ваша версия PHP < 5.6 следует применить фикс: из папки _корень-проекта/data/fix_ скопировать файл _Statement.php_ в директорию _корень-проекта/vendor/8bitov/clickhouse-php-client/src/_
3.	Выполнить следующую команду из корня проекта `php init` и проследовать инструкциям

4.	Выполнить необходимые минимальные настройки в конфигах:
        1. В файле _/backend/config/params-local.php_ указать значение параметра _yandex_oauth_id_. [Инструкция по получению __yandex_oauth_id__](#Настройка-Яндекс-oauth-авторизации). 
        2. В файле _/common/config/main-local.php_ указать параметры подключения к ClickHouse: 
            * _components['clickhouse']['address']_
            * _components['clickhouse']['port']_
            * _components['clickhouse']['username']_
            * _components['clickhouse']['password']_
            * _components['clickhouse']['database']_
5.	Настройка веб-сервера идентична настройке проекта на базе Yii2. Подробную инструкцию можно увидеть по ссылке: https://github.com/yiisoft/yii2-app-advanced/blob/master/docs/guide/start-installation.md
6. Настроить [отправку событий в AppMerica](#Отправка-в-appmetrica-событий-и-их-структура)
7. Запуск скрипта по [сборку событий из AppMerica](#Сбор-информации-по-событиям-из-appmetrica)

  Минимальная конфигурация проекта завершена. 

8. Если требуется подсчет __In-App Revenue__ и в AppMerica передается соответствующее событие, то необходимо запустить на автозапуск скрипт для верификации покупок на серверах Apple и Google с помощью команды `php yii purchase/verify-all` с периодичностью порядка 12 ч.

9. Если требуется подсчет __Ads Revenue__ и в AppMerica передается соответствующее событие, то необходимо [подключить рекламные площадки](#Получение-информации-ads-revenue-по-приложениям)

  Установка и настройка завершена.


### Настройка Яндекс oAuth-авторизации
----

[Яндекс oAuth-авторизация](https://tech.yandex.ru/oauth/) позволяет получить доступ к вашим приложениям в AppMetrica.
Для этого необходимо [создать Yandex oAuth-приложение](https://oauth.yandex.ru/client/new)  со следующими правами:
  * Appmetrica -> Получение статистики, чтение параметров настройки своих и доверенных приложений;
  * Appmetrica -> Создание приложений, изменение параметров настройки своих и доверенных приложений.

В "_Callback URL_" указать путь "_http://адресвашегопроекта/login?_".
Сохраните приложение с вышеуказанными параметрами.
От данного приложения понадобится его ID, он отобразится сразу после создания приложения. ID нужно указать в поле _yandex_oauth_id_ в конфиге проекта: _/backend/config/params-local.php_.

### Отправка в AppMetrica событий и их структура
----

Для получения информации по __In-App Revenue__ и __Ads Revenue__ необходимо передавать дополнительные события в AppMerica. Если подсчет In-App Revenue или Ads Revenue вам не нужен, то вы можете не передавать соответствующие параметры в AppMetrica и пропустить этот шаг.

1. Для подсчета __In-App Revenue__ приложения в AppMerica необходимо отправлять событие _purchase_ (вы можете использовать любое название события, не забудьте только указать его в параметрах скрипта для [сбора информации из AppMetrica](#Сбор-информации-по-событиям-из-appmetrica)) со следующими параметрами:
  1 __для iOS-приложений__: 
    * _amount_ - сумма покупки
    * _product_id_ - ID продукта
    * _receipt_ - квитанция от AppStore на покупку
  2 __для Android-приложений__: 
    * _amount_ - сумма покупки
    * _response_data_ - ответ от Google Play
    * _signature_ - подпись покупки
    
После того, как __Black Bears Analytics__ получит от AppMetrica события об оплате, каждая покупка проверяется и для подсчета In-App Revenue приложения используются только покупки, подтвержденные Apple и Google.
2. Для подсчета __Ads Revenue__ приложения в AppMetrica необходимо отправлять событие _ads_view_ (вы можете использовать любое название события, не забудьте только указать его в параметрах скрипта для [сбора информации из AppMetrica](#Сбор-информации-по-событиям-из-appmetrica)) со следующими параметрами:
  * _country_ - код страны местоположения девайса в двубуквенном формате - RU, GE и т.д.)
  * _platform_ - название рекламной платформы - _unity/applovin/vungle_

### Сбор информации по событиям из AppMetrica
----

Для того, чтобы собиралась статистика с AppMetrica необходимо поставить на автозапуск скрипт `data/grabber` с периодичностью не более 12 ч. При запуске можно указать следующие параметры:


Ключ  | Описание   | Значение по умолчанию
------------- | ------------- | -------------
--ch-server | Адрес сервера с ClickHouse | localhost
-db, --ch-database | Название базы данных на сервере ClickHouse | default
--ch-password | Пароль пользователя ClickHouse | 
--ch-user | Имя пользователя ClickHouse | default
--purchase-event | Название события о покупке в AppMetrica | purchase
--adview-event | Название события о просмотре рекламы в AppMetrica | ads_view
--date-since | Дата, начиная с которой соберутся данные из AppMetrica | последние 24 часа
--date-until | Дата, начиная по которую соберутся данные из AppMetrica | текущее время 
--purchase-queue | Название файла с очередью для сбора данных о покупках | purchase_queue
--adview-queue | Название файла с очередью для сбора данных о просмотрах рекламы | adview_queue
--install-queue | Название файла с очередью для сбора данных об установках | install_queue
-log, --log-file | Путь к файлу, для логирования работы скрипта | /dev/null

Параметры необходимо задавать через знак равно. Например: `data/grabber --purchase-event=mypurchase`

### Получение информации Ads Revenue по приложениям
----

Для того, чтобы рассчитать Ads Revenue необходимо определить eCPM вашего приложения в конкретной стране. 
Для этого необходимо выполнить 2 действия:
1. организовать отправку в [AppMetrica событий о просмотре рекламы](#Отправка-в-appmetrica-событий-и-их-структура) 
2. подключить одну или более рекламных площадок, которые вы используете в выбранном приложении. В настоящий момент __Black Bears Analytics__ собирает информацию о eCPM с 3 рекламных площадок: _Unity_, _Applovin_, _Vungle_. 

Для подключения рекламной площадки к __Black Bears Analytics__ необходимо ввести соответствующие ключи, полученные от рекламных площадок. Процесс получения ключей в каждой рекламной площадке описан ниже:

1.	__Applovin__ 
После регистрации и авторизации на данной платформе, перейдите по следующей [ссылке](https://www.applovin.com/account#keys) и скопируйте _Report key_:  
2.	__Vungle__ 
После регистрации и авторизации на данной платформе, нажмите на пункт меню _publisher -> details_, затем добавьте приложение посредством кнопки _Add new application_ в правом столбце. В случае, приложение уже существует в Vungle, стоит так же перейти на страницу _publisher -> details_, и выбрать пункт в верхней части окна под названием _Application stage_. Откроется окно созданного приложения, отсюда нам нужно значение _Reporting API ID_ - это ключ приложения. Для получения общего API ключа необходимо перейти по [ссылке](https://v.vungle.com/dashboard/accounts/details) и снизу нужный нам ключ в поле под названием _Reporting API Key_ 
3.	__UnityAds__
Авторизуйтесь на странице UnityAds, затем откройте вкладку _API Keys_ в интерфейсе UnityAds, там будет указан общий _API key_. Для получения _App Key_ необходимо создать приложение. На вкладке _Projects_ кликните по кнопке _Add new project_, заполните поля и нажмите _Continue_. На следующем экране скопируйте значение _GameID_ - это и есть необходимый нам _App Key_.

После подключения соответствующих ключей от рекламной площадки их необходимо ввести в интерфейсе __Black Bears Analytics__. Для этого выберите в списке приложений слева нужное приложение, затем выберите нужную рекламную площадку
(http://take.ms/mgmDj) и нажмите на _Изменить_. В открывшейся форме введите соответствующие выбранной рекламной площадки и приложению ключи и нажмите _Сохранить_ (http://take.ms/KDznXh). После добавления ключей рекламной площадки вы сможете получать информацию в дашборде об Ads Revenue.

License
----

Apache License
