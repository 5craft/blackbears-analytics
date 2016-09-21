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
/*
 * This is the model class for table "purchase_event"
 * The followings are the available columns in table "purchase_event"
 * @property $app_metrica_name string
 * @property  $app_id string
 * @property $app_package_name string
 * @property $event_name string
 * @property $event_json string
 * @property $event_datetime string
 * @property $os_name string
 * @property $ios_ifa string
 * @property $android_id string
 */
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