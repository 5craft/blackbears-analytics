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
class AppmetricaApp extends ClickhouseModelComponent{
    public $user_id;
    public $token;
    public $id;
    public $name;
    /**
     * @return string Returns table name
     */
    public static function tableName(){
        return parent::tableName().'appmetrica_app';
    }
    /**
     * Method calls every time before insertion to prepare model for insert in table
     */
    public function beforeInsert(){
        parent::beforeInsert();
        $this->user_id -=0;
        $this->token .= '';
        $this->id -=0;
        $this->name .= '';
    }

    /**
     * Get list of id of apps in user's yandex.appmetrica account
     * @param $userId int User id
     * @return array
     */
    public static function getAppList($userId){
        if(!$userId)
            return [];
        $apps= self::find()->select('id,name')->andWhere('user_id='.$userId)->groupBy('id,name')->all();
        $list = [];
        if($apps)
            foreach ($apps as $app){
                $list[$app->id] = [
                    'id' => $app->id,
                    'name' => $app->name
                ];
            }
        return $list;
    }

    public static function deleteOldApps($userId, array $apps){
        Yii::info('deletOldApps called on apps: '.Json::encode($apps));
        $inStatement = [];
        foreach ($apps as $app)
            $inStatement[] = '('.$userId.','.$app.')';
        $inStatement = 'WHERE (user_id,id) NOT IN ('.implode(',',$inStatement).')';
        $createTempTable = 'CREATE TABLE IF NOT EXISTS '.parent::tableName().'temporaryApps AS '.self::tableName();
        $insertQuery = 'INSERT INTO '.parent::tableName().'temporaryApps  SELECT * FROM '.self::tableName().' '.$inStatement;
        $dropTableQuery = 'DROP TABLE IF EXISTS '.self::tableName();
        $renameTableQuery = 'RENAME TABLE '.parent::tableName().'temporaryApps TO '.self::tableName();
        Yii::$app->clickhouse->execute($createTempTable);
        Yii::$app->clickhouse->execute($insertQuery);
        Yii::$app->clickhouse->execute($dropTableQuery);
        Yii::$app->clickhouse->execute($renameTableQuery);
//        Yii::$app->clickhouse->execute($createTempTable);
    }
}