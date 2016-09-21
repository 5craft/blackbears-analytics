<?php
/*
 * Black Bears Analytics
 * @author Blackbears
 * @link blackbears.mobi
 * @version 1.0
 */
namespace common\components;
use Yii;
use ClickHouse\Client;
use yii\base\Component;
/*
 * @method \ClickHouse\Query\Builder table(string $table)
 * @method Statement select(string $sql, array $bindings)
 * @method mixed|void insert(string $table, array $values, array $columns)
 * @method mixed insertBatch(string $table, array $data, null|string $formatName)
 * @method Statement execute(string $sql, array $bindings)
 * @method bool ping()
 */
class Connection extends Component {
    public $username;
    public $password;
    public $port;
    public $address;
    public $database;
    private $connection;

    /**
     * Connection constructor.
     * @param array $config
     * @throws \Exception
     */
    public function __construct($config = [])
    {
        if (!empty($config)) {
            Yii::configure($this, $config);
        }
        $this->connection = new Client($this->address, $this->port, $this->username, $this->password);
        $isLive = $this->ping();
        if (false === $isLive)
            throw new \Exception('No connection to Clickhouse');
    }

    /**
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args){
        return call_user_func_array([$this->connection, $method], $args);
    }

    /**
     *  Check tables in database and create which is absent
     */
    public function checkTables(){
        $this->connection->execute('CREATE DATABASE IF NOT EXISTS '.$this->database);
        $this->connection->execute('CREATE TABLE IF NOT EXISTS '.$this->database.'.ad_platform_key (user_id UInt32, app_id UInt32, platform String, api_key String, app_key String, time DateTime) Engine=Log');
        $this->connection->execute('CREATE TABLE IF NOT EXISTS '.$this->database.'.appmetrica_app (user_id UInt32, token String, id UInt32, name String) Engine=Log');
        $this->connection->execute('CREATE TABLE IF NOT EXISTS '.$this->database.'.purchase_validator (user_id UInt32, app_id UInt32, app_package_name String, os String, app_key String) Engine=Log');
        $this->connection->execute('CREATE TABLE IF NOT EXISTS '.$this->database.'.ecpm(app_id UInt32, app_package_name String, platform String, ecpm Float32, date Date, country String) Engine=Log');
        $this->connection->execute('CREATE TABLE IF NOT EXISTS '.$this->database.'.adview_revenue(app_id UInt32,app_package_name String,date DateTime,platform String,country String,revenue Float32, publisher_id String, tracking_id String, os_name String) ENGINE = Log');
        $this->connection->execute('CREATE TABLE IF NOT EXISTS '.$this->database.'.adview_event ( app_id String, app_package_name String, event_json String, event_datetime DateTime, ios_ifa String, android_id String) ENGINE = Log;');
        $this->connection->execute('CREATE TABLE IF NOT EXISTS '.$this->database.'.purchase_event ( app_metrica_name String, app_id String, app_package_name String,event String, event_json String,     event_datetime DateTime,     os_name String,     ios_ifa String,     android_id String) ENGINE = Log;');
        $this->connection->execute('CREATE  VIEW IF NOT EXISTS '.$this->database.'.purchase_ios AS SELECT app_metrica_name, app_id, app_package_name, event_json, event_datetime, ios_ifa FROM '.$this->database.'.purchase_event WHERE os_name=\'ios\'');
        $this->connection->execute('CREATE  VIEW IF NOT EXISTS '.$this->database.'.purchase_android AS SELECT app_metrica_name, app_id, app_package_name, event_json, event_datetime, android_id FROM '.$this->database.'.purchase_event WHERE os_name=\'android\'');
        $this->connection->execute('CREATE TABLE IF NOT EXISTS '.$this->database.'.installations (app_metrica_name String,    app_id String,    app_package_name String,    install_datetime DateTime,    os_name String,     ios_ifa String,     android_id String,    publisher_id String,    publisher_name String,    tracking_id String,    tracker_name String) ENGINE = Log');
        $this->connection->execute('CREATE TABLE IF NOT EXISTS '.$this->database.'.valid_purchase(app_metrica_name String,    app_id String,    app_package_name String,    purchase_datetime DateTime,    amount Float32,    product_id String,    transaction_id String,    os_name String,    device_id String,    is_sandbox UInt8,    publisher_id String,    publisher_name String,    tracking_id String,    tracker_name String) ENGINE = Log');
    }
}