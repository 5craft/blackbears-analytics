<?php
/*
 * Black Bears Analytics
 * @author Blackbears
 * @link blackbears.mobi
 * @version 1.0
 */
namespace common\models;

use Yii;
use common\components\ClickhouseModelComponent;
use yii\helpers\Json;
/*
 * This is the model class for table "purchase_android"
 * The followings are the available columns in table "purchase_android"
 * @property $app_metrica_name string
 * @property $app_id string
 * @property $app_package_name string
 * @property $event_json string
 * @property $event_datetime string
 * @property $android_id string
 * @property $amount string Amount field parsed from $event_json
 * @property $signature string Signature field parsed from $event_json
 * @property $response_data string Response data field parsed from $event_json
 */
class AndroidPurchase extends ClickhouseModelComponent
{
    public $app_metrica_name;
    public $app_id;
    public $app_package_name;
    public $event_json;
    public $event_datetime;
    public $android_id;
    public $amount;
    public $signature;
    public $response_data;
    /**
     * Transforms std::Object items to AndroidPurchase objects
     * Also value of event_json field decodes and each field of decoded data appends as class property
     * @param $found Array of found items, each of them is std::Object
     * @return array of found AndroidPurchase objects
     */
    public static function afterFind($found)
    {
        $found = parent::afterFind($found);
        foreach((array)$found as &$item){
            if(isset($item->event_json)) {
                $decoded = Json::decode($item->event_json);
                foreach ($decoded as $key => $jsonItem) {
                    $item->$key = $jsonItem;
                }
            }
        }
        return  $found;
    }
    /**
     * @return string Returns table name
     */
    public static function tableName()
    {
        return parent::tableName().'purchase_android';
    }


}