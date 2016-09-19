<?php
namespace common\models;

use Yii;
use common\components\ClickhouseModelComponent;
use yii\helpers\Json;
class IosPurchase extends ClickhouseModelComponent
{
    public $app_metrica_name;
    public $app_id;
    public $app_package_name;
    public $event_datetime;
    public $receipt;
    public $amount;
    public $product_id;
    public $ios_ifa;
    /**
     * Transforms std::Object items to IosPurchase objects
     * Also value of event_json field decodes and each field of decoded data appends as class property
     * @param $found Array of found items, each of them is std::Object
     * @return array of found IosPurchase objects
     */
    public static function afterFind($found)
    {
        $result = [];
        if($found){
            foreach($found as &$item){
                $purchase = new IosPurchase();
                $decoded = Json::decode($item->event_json);
                foreach ($decoded as $key => $jsonItem) {
                    $purchase->$key = $jsonItem;
                }
                $purchase->event_datetime = $item->event_datetime;
                $purchase->ios_ifa = $item->ios_ifa;
                $purchase->app_package_name = $item->app_package_name;
                $result[] = $purchase;
            }
            return  $result;
        }
    }
    /**
     * @return string Returns table name
     */
    public static function tableName()
    {
        return parent::tableName().'purchase_ios';
    }
}