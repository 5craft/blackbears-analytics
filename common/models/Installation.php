<?php
namespace common\models;

use Yii;
use common\components\ClickhouseModelComponent;
use yii\helpers\Json;
class Installation extends ClickhouseModelComponent
{
    public $app_metrica_name;
    public  $app_id;
    public $app_package_name;
    public $install_datetime;
    public $android_id;
    public $ios_ifa;
    public $os_name;
    public $publisher_id;
    public $publisher_name;
    public $tracking_id;
    public $tracker_name;

    /**
     * Get list of apps with id as associative array. Key represent app_id and value stores appmetrica name of application
     *
     * @return array
     */
    public static function getAppsList(){
        $apps = self::find()->select('app_id, app_metrica_name')->groupBy('app_id, app_metrica_name')->all();
        $appsList = [];
        if(!$apps)
            return $appsList;
        foreach ((array)$apps as $item)
            $appsList[$item->app_id] = $item->app_metrica_name;
        return $appsList;
    }
    /**
     * Method to count installations for chosen applications for chosen date period and filtered by any of next fields: publisher, tracker or platform
     * @param $app_id_list array|string List of apps id
     * @param $date_since null|string Filter by since date in format "yyyy-mm-dd"
     * @param $date_until null|string Filter by until date in format "yyyy-mm-dd"
     * @param $publisher null|string |array Filter by publisher by id
     * @param $tracker null|string |array Filter by tracker by id
     * @param $platform null|string |array Filter by advertisement platform name
     * @return int
     */
    public static function count($app_id_list=null, $os=null, $date_since=null, $date_until=null, $publisher=null, $tracker=null){
        $query = self::find()->select('uniq(android_id) android_id, uniqExact(ios_ifa) ios_ifa');
        if($app_id_list !== null)
            $query = $query->addInWhere('app_id', $app_id_list);
        if($os !== null)
            $query = $query->addInWhere('os_name', $os);
        if($date_since !== null)
            $query = $query->andWhere('install_datetime >= \''.strtotime($date_since).'\'');
        if($date_until !== null)
            $query = $query->andWhere('install_datetime <= \''.(strtotime($date_until)+86399).'\'');
        if($publisher !== null) {
            $query = $query->addInWhere('publisher_id', $publisher);
        }
        if($tracker !== null) {
            $query = $query->addInWhere('tracking_id',$tracker);
        }
        $result = $query->one();
        $result = Json::decode(Json::encode($result),true);
        return ($result['android_id']-0) + ($result['ios_ifa']-0);
    }

    /**
     * Get list of publishers and trackers by list of apps id
     * @param $app_id_list array|string  List of apps id
     * @return array Array of Installation objects with filled next fields: publisher_id, publisher_name, tracking_id and tracker_name
     */
    public static function getPublishersAndTrackersByAppId($app_id_list){
        return self::find()->select('publisher_id, publisher_name, tracking_id, tracker_name')->addInWhere('app_id',$app_id_list)->groupBy('tracking_id,publisher_id, publisher_name,  tracker_name')->all();
    }

    /**
     *Get list of trackers by os name
     * @param $os string name of operating system
     * @return array Array of Installation objects with filled next fields: tracking_id and tracker_name
     */
    public static function getTrackersByOs($os){
        $query = self::find()->select('tracking_id, tracker_name');
        if($os !== null)
            $query = $query->addInWhere('os_name',$os);
        return $query->groupBy('tracking_id,tracker_name')->all();

    }
    /**
     *Get list of trackers by list of apps id
     * @param $app_id_list
     * @return array Array of Installation objects with filled next fields: tracking_id and tracker_name
     */
    public static function getTrackersByAppId($app_id_list){
        return self::find()->select('tracking_id, tracker_name')->addInWhere('app_id',$app_id_list)->groupBy('tracking_id,tracker_name')->all();
    }

    /**
     * Get list of trackers by list of apps package name
     * @param $app_id_list array|string  List of apps package name
     * @return array Array of Installation objects with filled next fields: tracking_id and tracker_name
     */
    public static function getTrackersByPackage($app_package_name){
        return self::find()->select('tracking_id, tracker_name')->andWhere('app_package_name=\''.$app_package_name.'\'')->groupBy('tracking_id,tracker_name')->all();
    }

    /**
     * Get list of publishers and trackers by list of apps package name
     * @param $app_id_list array|string  List of apps package name
     * @return array Array of Installation objects with filled next fields: publisher_id, publisher_name, tracking_id and tracker_name
     */
    public static function getPublishersAndTrackersByPackage($app_package_name){
        return self::find()->select('publisher_id, publisher_name, tracking_id, tracker_name')->andWhere('app_package_name=\''.$app_package_name.'\'')->groupBy('tracking_id,publisher_id, publisher_name,  tracker_name')->all();
    }

    /**
     * @return string Returns table name
     */
    public static function tableName()
    {
        return parent::tableName().'installations';
    }
}