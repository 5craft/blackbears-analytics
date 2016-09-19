<?php
namespace common\models;

use Yii;
use common\components\ClickhouseModelComponent;
use yii\helpers\Json;
class AdviewEvent extends ClickhouseModelComponent
{
    public $app_id;
    public $app_package_name;
    public $event_json;
    public $event_datetime;
    public $ios_ifa;
    public $android_id;
    public $country;
    public $platform;
    /**
     * @return string Returns table name
     */
    public static function tableName()
    {
        return parent::tableName().'adview_event';
    }

    /**
     * Transforms std::Object items to AdviewEvent objects
     * @param $found Array of found items, each of them is std::Object
     * @return array of found AdviewEvent objects
     */
    public static function afterFind($found)
    {
        $found = parent::afterFind($found);
        if(is_array($found)){
            foreach((array)$found as &$item){
                if(isset($item->event_json)) {
                    $decoded = Json::decode($item->event_json);
                    foreach ($decoded as $key => $jsonItem) {
                        $item->$key = strtolower($jsonItem);
                    }
                }
            }
        } else if(is_object($found) && isset($found->event_json)){
                $decoded = Json::decode($found->event_json);
                foreach ($decoded as $key => $jsonItem) {
                    $found->$key = strtolower($jsonItem);
                }
            }
        return  $found;
    }

    /**
     * @param $limit integer|null limit of  query
     * @param $offset integer|null offset of query
     * @return array of found view events as objects of AdviewEvent with extra fields "publisher_id" and "tracking_id"
     */
    public static function findWithPublisher($limit=null, $offset=null)
    {
        $query =
            '
            SELECT
                app_id,
                app_package_name,
                event_json,
                event_datetime,
                tracking_id,
                tracker_name,
                publisher_id,
                publisher_name
            FROM
            (
                SELECT *
                FROM '.self::tableName().'
            )
            ANY INNER JOIN
            (
                SELECT
                    tracking_id,
                    tracker_name,
                    publisher_id,
                    publisher_name,
                    ios_ifa,
                    android_id
                FROM '.Installation::tableName().'
            ) USING (android_id, ios_ifa)
          ';
        if (isset($limit)) {
            if (isset($offset))
                $limit = $offset . ',' . $limit;
            $query .= ' LIMIT ' . $limit;
        }
        $found = Yii::$app->clickhouse->select($query)->fetchAll();
        $result = [];
        if($found) {
            foreach ($found as &$item) {
                $view = new AdviewEvent();
                foreach (self::$FIELD_LIST as $field) {
                    if (isset($item->$field)) {
                        $view->$field = $item->$field;
                    }
                }
                if (isset($item->event_json)) {
                    $decoded = Json::decode($item->event_json);
                    foreach ($decoded as $key => $jsonItem) {
                        $view->$key = strtolower($jsonItem);
                    }
                }
                $view->publisher_id = $item->publisher_id;
                $view->tracking_id = $item->tracking_id;
                $view->event_datetime = $item->event_datetime;
                $result[] = $view;
            }
        }
        return $result;
    }

    /**
     *  Uses to save only those views, which haven't yet corresponding installation entry
     */
    public static function dropViewsWithInstalls()
    {
        $createTempTable = 'CREATE TABLE '.parent::tableName().'temporaryViews AS '.self::tableName();
        $insertQuery = 'INSERT INTO '.parent::tableName().'temporaryViews';
        self::updateFieldList();
        $insertValues =
            'SELECT '.implode(',',self::$FIELD_LIST).'
            FROM
            (
                SELECT *
                FROM '.self::tableName().'
            )
            ANY LEFT JOIN
            (
                SELECT tracking_id,publisher_id,ios_ifa,android_id
                FROM '.Installation::tableName().'
            ) USING (android_id, ios_ifa)
            WHERE publisher_id=\'\' AND tracking_id=\'\'';
        Yii::$app->clickhouse->execute($createTempTable);
        Yii::$app->clickhouse->execute($insertQuery.' '.$insertValues);
        Yii::$app->clickhouse->execute('DROP TABLE '.self::tableName());
        Yii::$app->clickhouse->execute('RENAME TABLE '.parent::tableName().'temporaryViews TO '.self::tableName());
    }
}