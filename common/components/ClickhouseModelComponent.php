<?php
namespace common\components;

use Yii;
use yii\helpers\Json;

abstract class ClickhouseModelComponent implements ModelComponent{

    public static $FIELD_LIST=[];

    /**
     * @return Connection
     */
    public static function getDb(){
        return Yii::$app->clickhouse;
    }

    /**
     * Insertion method
     * prepares model by method "beforeInsert" and inserts that into db
     */
    public function insert(){
        $this::updateFieldList();
        $this->beforeInsert();
        $this->getDb()->insert($this::tableName(),$this::$FIELD_LIST,[get_object_vars($this)]);
        $this->afterInsert();
    }

    /**
     *  Truncate table
     */
    public static function truncate(){
        $classCaller = get_called_class();
        $tableMeta = Json::decode($classCaller::getDb()->select('show create table '.$classCaller::tableName())->getRawResult());
        $createTableQuery = $tableMeta['data'][0]['statement'];
        $classCaller::getDb()->execute('DROP TABLE IF EXISTS '.$classCaller::tableName());
        $classCaller::getDb()->execute($createTableQuery);
    }

    /**
     * Inserts batch of objects
     * @param array $batch
     */
    public static function insertBatch($batch){
        $classCaller = get_called_class();
        $classCaller::updateFieldList();
        $insertBatch = [];
        foreach ($batch as $obj){
            $obj->beforeInsert();
            $insertBatch[] = get_object_vars($obj);
        }
        $classCaller::getDb()->insert($classCaller::tableName(),$classCaller::$FIELD_LIST,$insertBatch );
    }

    /**
     * Fill field list of model
     */
    protected static function updateFieldList(){
        $classCaller = get_called_class();
        $meta = $classCaller::getDb()->select('describe table '.$classCaller::tableName())->fetchAll();
        $fields = [];
        foreach($meta as $field){
            $fields[] = $field->name;
        }
        $classCaller::$FIELD_LIST = $fields;
    }


    /**
     * Method calls before insertion
     */
    public function beforeInsert(){

    }

    /**
     * Method calls after insertion
     */
    public function afterInsert(){

    }

    /**
     * Method calls before find
     */
    public static function beforeFind(){
    }

    /**
     * Method calls after find, transforms database answer to kind of called object
     * @param array $found
     * @return array
     */
    public static function afterFind($found){
        $classCaller = get_called_class();
        $result = [];
        if($found){
            foreach($found as &$item){
                $classObj = new $classCaller();
                foreach ($classObj::$FIELD_LIST as $field) {
                    if(isset($item->$field)){
                        $classObj->$field = $item->$field;
                    }
                }
                $result[] = $classObj;
            }
        }
        return  empty($result)?[]:$result;
    }
    /**
     * @return string Prepends database name before table name
     */
    public static function tableName()
    {
        return isset(self::getDb()->database)?self::getDb()->database.'.':'';
    }
    /**
     * Get all entries of the table
     * @return array|null
     */
    public static function findAll(){
        $classCaller = get_called_class();
        $classCaller::updateFieldList();
        return (new ClickhouseQueryComponent(static::tableName(),get_called_class()))->all();
    }

    /**
     * Get Clickhouse Query object for model
     * @return \common\components\ClickhouseQueryComponent
     */
    public static function find(){
        $classCaller = get_called_class();
        $classCaller::updateFieldList();
        return new ClickhouseQueryComponent(static::tableName(),get_called_class());
    }
}