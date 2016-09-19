<?php
namespace common\models;

use Yii;
use common\components\ClickhouseModelComponent;

class PurchaseEvent extends ClickhouseModelComponent
{
    public $app_metrica_name;
    public  $app_id;
    public $app_package_name;
    public $event_name;
    public $event_json;
    public $event_datetime;
    public $os_name;
    public $ios_ifa;
    public $android_id;
    /**
     * @return string Returns table name
     */
    public static function tableName()
    {
        return parent::tableName().'purchase_event';
    }

}