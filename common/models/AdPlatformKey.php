<?php
namespace common\models;

use Yii;
use common\components\ClickhouseModelComponent;
class AdPlatformKey extends ClickhouseModelComponent
{
    public $user_id;
    public $app_id;
    public $platform;
    public $api_key;
    public $app_key;
    public $time;
    /**
     * @return string Returns table name
     */
    public static function tableName()
    {
        return parent::tableName().'ad_platform_key';
    }

    /**
     * Method calls every time before insertion to prepare model for insert in table
     */
    public function beforeInsert()
    {
        parent::beforeInsert();
        $this->user_id -=0;
        $this->app_id -=0;
        $this->platform.='';
        $this->api_key.='';
        $this->app_key.='';
        if(empty($this->time))
            $this->time = time();
    }

    /**
     * Get list of connected advertisement platforms of application
     * @param $userId User id
     * @return array
     */
    public static function getConnectedApps($userId){
        $found = self::find()->select('platform, app_id, anyLast(app_key) as app_key, anyLast(api_key) as api_key, anyLast(time) as time')->andWhere('user_id='.$userId)->groupBy('app_id, platform ')->orderBy('time')->all();
        if(!$found)
            return [];
        $apps = [];
        foreach ($found as $app){
            $apps[$app->app_id][$app->platform]['api_key'] = $app->api_key;
            $apps[$app->app_id][$app->platform]['app_key'] = $app->app_key;
        }
        return $apps;
    }
    /**
     * Get list of connected advertisement platforms
     * @return array
     */
    public static function getPlatforms($userId,$appId=null){
        $platforms = array_keys(Yii::$app->params['ad_platform']);
        if(isset($appId)) {
            if(is_array($appId))
                $appId=array_shift($appId);
            $userPlatforms = self::find()->select('platform, anyLast(api_key) as api_key, anyLast(app_key) as app_key, anyLast(time) as time')->andWhere('user_id=' . $userId)->andWhere('app_id=' . ($appId - 0))->groupBy('platform')->orderBy('time')->all();
        }
        $connected = [];
        if($platforms){
            if(isset($userPlatforms)){
                foreach ($userPlatforms as $platform){
                    if(!empty($platform->api_key) || !empty($platform->app_key))
                        $connected[$platform->platform] = [
                            'api_key' => $platform->api_key,
                            'app_key' => $platform->app_key
                        ];
                }
                foreach ($platforms as $platform){
                    if(!isset($connected[$platform]))
                        $connected[$platform] = false;
                }
            }else
                foreach ($platforms as $platform){
                $connected[$platform] = false;
                }
        }
        ksort($connected);
        return $connected;
    }
}