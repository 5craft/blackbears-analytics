<?php
/*
 * Black Bears Analytics
 * @author Blackbears
 * @link blackbears.mobi
 * @version 1.0
 */
namespace common\models;

use common\components\AdPlatformComponent;
use Yii;
use common\components\ClickhouseModelComponent;
/*
 * This is the model class for table "ecpm"
 * The followings are the available columns in table "ecpm"
 * @property $app_id integer
 * @property $app_package_name string
 * @property $platform string
 * @property $ecpm float
 * @property $date string
 * @property $country string
 */
class Ecpm extends ClickhouseModelComponent
{
    public $app_id;
    public $app_package_name;
    public $platform;
    public $ecpm;
    public $date;
    public $country;
    /**
     * @return string Returns table name
     */
    public static function tableName()
    {
        return parent::tableName().'ecpm';
    }
    /**
     * Method calls every time before insertion to prepare model for insert in table
     */
    public function beforeInsert()
    {
        parent::beforeInsert();
        $this->ecpm -= 0;
    }

    /**
     * Grabbing ecpm for platforms for chosen date period
     * @param $adPlatformKey AdPlatformKey
     * @param $start_date string Start date for report in format "yyyy-mm-dd"
     * @param $end_date string End date for report in format "yyyy-mm-dd"
     */
    public static function updateEcpm($adPlatformKey, $start_date, $end_date){
        $ecpms = AdPlatformComponent::grab($adPlatformKey, $start_date, $end_date);
        if($ecpms == false)
            return;
        foreach ($ecpms as &$ecpm) {
            $temp = $ecpm;
            $ecpm = new Ecpm();
            $ecpm->app_id = $temp['app_id'];
            $ecpm->app_package_name = $temp['app_package_name'];
            $ecpm->platform= $temp['platform'];
            $ecpm->ecpm= $temp['ecpm'];
            $ecpm->date= $temp['date'];
            $ecpm->country= $temp['country'];
        }
        parent::updateFieldList();
        if(count($ecpms)>0)
            self::insertBatch($ecpms);
    }

    /**
     * Returns list of advertising platforms those, which are exists in database for that application
     * @param $appId array|string List of apps id
     * @param $appPackageName  array|string List of apps package names
     * @return array List of advertising platforms
     */
    public static function getPlatformList($appId, $appPackageName){
        $result = self::find()->select('platform, any(app_id)')->addInWhere('app_id',$appId,1)->groupBy('platform')->all();
        if(!$result)
            $result = self::find()->select('platform, any(app_package_name)')->addInWhere('app_package_name',$appPackageName)->groupBy('platform')->all();
        $return = [];
        if($result)
            foreach($result as $platform)
                $return[] = $platform->platform;
        return $return;
    }

    /**
     * Returns list of advertising platforms those, which are exists in database
     * @return array List of advertising platforms
     */
    public static function getGlobalPlatformList(){
        $result = self::find()->select('platform')->groupBy('platform')->all();
        $return = [];
        if($result)
            foreach($result as $platform)
                $return[] = $platform->platform;
        return $return;
    }

    /**
     * Get ecpm for exact AdviewEvent
     * @param $adviewEventObj AdviewEvent
     * @return Ecpm
     */
    public static function getForView($adviewEventObj){
        $ecpm = Yii::$app->memcache->get($adviewEventObj->app_id . $adviewEventObj->app_package_name . date('Y-m-d', strtotime($adviewEventObj->event_datetime)) . strtolower($adviewEventObj->country) . strtolower($adviewEventObj->platform));
        if ($ecpm === false) {
            $ecpm = Ecpm::find()->andWhere('app_id = ' . $adviewEventObj->app_id . ' and date = \'' . date('Y-m-d', strtotime($adviewEventObj->event_datetime)) . '\' and platform = \'' . strtolower($adviewEventObj->platform) . '\' and country LIKE \'%' . strtolower($adviewEventObj->country) . '%\'')->one();
            Yii::$app->memcache->set($adviewEventObj->app_id . $adviewEventObj->app_package_name . date('Y-m-d', strtotime($adviewEventObj->event_datetime)) . strtolower($adviewEventObj->country) . strtolower($adviewEventObj->platform), $ecpm, 86400);
        }
        return $ecpm;
    }

    /**
     * Get date of last grabbed ecpm entry for chosen application
     * @param $appId integer
     * @param $appPackageName string
     * @return mixed
     */
    public static function getUpdateDate($appId, $appPackageName){
        $date = self::find()->select('any(date) as date')->andWhere('app_id='.$appId.' or app_package_name=\''.$appPackageName.'\'')->orderBy('date desc')->limit(1)->one();
        if($date)
            return $date->date;
    }
    /**
     * Get date of last grabbed ecpm entry
     * @return mixed
     */
    public static function getGlobalUpdateDate(){
        $date = self::find()->select('any(date) as date')->orderBy('date desc')->limit(1)->one();
        if($date)
            return $date->date;
    }
}