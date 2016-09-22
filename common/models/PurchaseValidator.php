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
 * This is the model class for table "purchase_validator"
 * The followings are the available columns in table "purchase_validator"
 * @property $user_id integer
 * @property $app_id integer
 * @property $app_package_name string
 * @property $os string
 * @property $app_key string
 */
class PurchaseValidator extends ClickhouseModelComponent
{
    public $user_id;
    public $app_id;
    public $app_package_name;
    public $os;
    public $app_key;
    /**
     * @return string Returns table name
     */
    public static function tableName()
    {
        return parent::tableName().'purchase_validator';
    }
    /**
     * Method calls every time before insertion to prepare model for insert in table
     */
    public function beforeInsert()
    {
      $this->user_id-=0;
        $this->app_id-=0;
    }

    /**
     * Get all app keys for purchase validation
     * @return array Associative array. Key represent app_package_name and value stores app_key
     */
    public static function getAllForVerify(){
        $result = self::find()->select('user_id, app_id, anyLast(app_package_name) as app_package_name, any(os) as os, anyLast(app_key) as app_key')->groupBy('app_id, user_id')->all();
        $return = [];
        foreach ($result as $item){
            $return[$item->app_package_name] = $item->app_key;
        }
        return $return;
    }

    public static function checkEmpty($appId){
        $validator = self::find()->select('anyLast(user_id) as user_id, app_id, anyLast(app_package_name) as app_package_name, any(os) as os, anyLast(app_key) as app_key')->andWhere('app_id='.$appId)->groupBy('app_id')->one();
        if(!$validator)
            return new ValidPurchase();
        if($validator->os == 'android' && (empty($validator->app_package_name) || empty($validator->app_key)))
            return $validator;
        return false;
    }
}