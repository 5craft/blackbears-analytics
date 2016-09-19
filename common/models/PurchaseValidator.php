<?php
namespace common\models;

use Yii;
use common\components\ClickhouseModelComponent;
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
        $result = self::findAll();
        $return = [];
        foreach ($result as $item){
            $return[$item->app_package_name] = $item->app_key;
        }
        return $return;
    }

}