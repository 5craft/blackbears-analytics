<?php
namespace common\components;

use common\models\AdPlatformKey;
use Yii;
use yii\base\Exception;
use yii\helpers\Json;

class AdPlatformComponent {
    /**
     * Grab ecpm for exact ad_view
     * @param $adPlatformKey AdPlatformKey
     * @param $start_date string Start date for report in format "yyyy-mm-dd"
     * @param $end_date string End date for report in format "yyyy-mm-dd"
     * @return array full of items kinda [ 'app_id' => int $value0, 'app_package_name' => string $value1, 'platform' => string $value2, 'ecpm' => float $value3, 'date' => string $value4, 'country' => string $value5]
     */
    public static function grab($adPlatformKey, $startDate, $endDate){
        return call_user_func_array([get_class(),'get'.$adPlatformKey->platform.'Ecpm'],[$adPlatformKey,$startDate,$endDate]);
    }

    /**
     * Grab ecpm for exact ad_view from unity
     * @param $adPlatformKey AdPlatformKey
     * @param $start_date string Start date for report in format "yyyy-mm-dd"
     * @param $end_date string End date for report in format "yyyy-mm-dd"
     * @return array|bool
     */
    public static function getUnityEcpm($adPlatformKey, $startDate, $endDate){
        $url= str_replace('%api_key%',$adPlatformKey->api_key,Yii::$app->params['ad_platform']['unity']['ecpm_url']);
        $url= str_replace('%app_key%',$adPlatformKey->app_key,$url);
        $url= str_replace('%start_date%',$startDate,$url);
        $url= str_replace('%end_date%',$endDate,$url);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_USERAGENT, Yii::$app->params['user-agent']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
        $data = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($httpcode<200 || $httpcode>=300) {
            Yii::error('Error on unity connection. Error code: '.$httpcode.', url: '.$url);
            return false;
        }
        curl_close($ch);
        $csv = array_map('str_getcsv',explode(PHP_EOL, $data));
        array_pop($csv);
        array_walk($csv, function(&$a) use ($csv) {
            $a = array_combine($csv[0], $a);
        });
        array_shift($csv);
        $ecpms = [];
        if($csv) {
            foreach ($csv as $item) {
                $ecpm = ($item['views']) ? 1000 * $item['revenue'] / $item['views'] : 0;
                if ($ecpm <= 0)
                    continue;
                $ecpms[] = [
                    'app_id' => $adPlatformKey->app_id-0,
                    'app_package_name' => strtolower($item['Source game name']).'',
                    'platform' =>'unity',
                    'ecpm' => $ecpm-0,
                    'date' => date('Y-m-d', strtotime($item['Date'])),
                    'country' => strtolower($item['Country code']),
                ];
            }
        }
        return$ecpms;
    }
    /**
     * Grab ecpm for exact ad_view from vungle
     * @param $adPlatformKey AdPlatformKey
     * @param $start_date string Start date for report in format "yyyy-mm-dd"
     * @param $end_date string End date for report in format "yyyy-mm-dd"
     * @return array|bool
     */
    public static function getVungleEcpm($adPlatformKey, $startDate, $endDate){
        $url= str_replace('%api_key%',$adPlatformKey->api_key,Yii::$app->params['ad_platform']['vungle']['ecpm_url']);
        $url= str_replace('%app_key%',$adPlatformKey->app_key,$url);
        $url= str_replace('%start_date%',$startDate,$url);
        $url= str_replace('%end_date%',$endDate,$url);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT,Yii::$app->params['user-agent']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $data = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($httpcode<200 || $httpcode>=300) {
            Yii::error('Error on vungle connection. Error code: '.$httpcode.', url: '.$url);
            return false;
        }
        $data = Json::decode($data);
        $ecpms = [];
        if($data) {
            foreach ($data as $day) {
                if (isset($day['geo_eCPMs']))
                    foreach ($day['geo_eCPMs'] as $item) {
                        if ($item['eCPM'] <= 0)
                            continue;
                        $ecpms[] = [
                            'app_id' => $adPlatformKey->app_id-0,
                            'app_package_name' => '',
                            'platform' =>'vungle',
                            'ecpm' => $item['eCPM']-0,
                            'date' => $day['date'],
                            'country' => strtolower($item['country']),
                        ];
                    }
            }
        }
        return $ecpms;
    }
    /**
     * Grab ecpm for exact ad_view from applovin
     * @param $adPlatformKey AdPlatformKey
     * @param $start_date string Start date for report in format "yyyy-mm-dd"
     * @param $end_date string End date for report in format "yyyy-mm-dd"
     * @return array|bool
     */
    public static function getApplovinEcpm($adPlatformKey, $startDate, $endDate){
        $url= str_replace('%api_key%',$adPlatformKey->api_key,Yii::$app->params['ad_platform']['applovin']['ecpm_url']);
        $url= str_replace('%start_date%',$startDate,$url);
        $url= str_replace('%end_date%',$endDate,$url);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, Yii::$app->params['user-agent']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $data = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($httpcode<200 || $httpcode>=300) {
            Yii::error('Error on applovin connection. Error code: '.$httpcode.', url: '.$url);
            return false;
        }
        $data = Json::decode($data);
        $ecpms = [];
        if($data) {
            foreach ($data['results'] as $item) {
                if ($item['ecpm'] <= 0)
                    continue;
                $ecpms[] = [
                    'app_id' => $adPlatformKey->app_id-0,
                    'app_package_name' => strtolower($item['package_name']),
                    'platform' =>'applovin',
                    'ecpm' => $item['ecpm'] - 0,
                    'date' => $item['day'],
                    'country' => strtolower($item['country']),
                ];
            }
        }
        return $ecpms;
    }
}