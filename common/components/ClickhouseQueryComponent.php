<?php
/*
 * Black Bears Analytics
 * @author Blackbears
 * @link blackbears.mobi
 * @version 1.0
 */
namespace common\components;

use Yii;
use yii\helpers\Json;
/*
 * Class for assembling and executing query to clickhouse
 * @property $selectStatement string|'*'
 * @property $andWhere array
 * @property $groupStatement string
 * @property $orderStatement string
 * @property $limitStatement string
 * @property $offsetStatement string
 * @property $tableName string
 * @property $classCaller string
 */
class ClickhouseQueryComponent
{
    const TYPE_STRING = 0;
    const TYPE_NUMERIC = 1;
    private $selectStatement = '*';
    private $andWhere = [];
    private $groupStatement;
    private $orderStatement;
    private $limitStatement;
    private $offsetStatement;
    private $tableName;
    private $classCaller;

    /**
     * ClickhouseQueryComponent constructor.
     * @param $tableName
     * @param $class
     */
    public function __construct($tableName, $class){
        $this->tableName = $tableName;
        $this->classCaller = $class;
    }

    /**
     * Set select part of query
     * @param string $select
     * @return ClickhouseQueryComponent $this
     */
    public function select($select){
        $this->selectStatement = trim($select);
        return $this;
    }
    /**
     * Adds condition to where part of query
     * @param string $andWhere
     * @return ClickhouseQueryComponent $this
     */
    public function  andWhere($andWhere){
        $this->andWhere[] = '('.$andWhere.')';
        return $this;
    }
    /**
     * Adds IN condition to where part of query
     * @param string $field Field name
     * @param array|mixed Parameters for condition
     * @param int $type Type of params (string,numeric)
     * @return ClickhouseQueryComponent $this
     */
    public function addInWhere($field, $params, $type=self::TYPE_STRING){
        if(is_array($params) && (count($params) == 0))
            throw new \Exception('Empty "where in" condition');
        $params = (array)$params;
        switch ($type){
            case self::TYPE_STRING:
                foreach ($params as &$param)
                    $param = '\''.addslashes($param).'\'';
                break;
            case self::TYPE_NUMERIC:
                foreach((array)$params as &$param)
                    $param = floatval($param);
                break;
            default:
                return null;
        }
        if(count($params)>1)
            $this->andWhere($field .' IN ('.implode(',', $params).')');
        else
            $this->andWhere($field .' = '.array_shift($params));
        return $this;
    }

    /**
     * Set groupBy statement of query
     * @param string $groupBy
     * @return ClickhouseQueryComponent $this
     */
    public function groupBy($groupBy){
        $this->groupStatement = trim($groupBy);
        return $this;
    }
    /**
     * Set orderBy statement of query
     * @param string $orderBy
     * @return ClickhouseQueryComponent $this
     */
    public function orderBy($orderBy){
        $this->orderStatement = trim($orderBy);
        return $this;
    }

    /**
     * Set limit of query
     * @param int $limit
     * @return ClickhouseQueryComponent $this
     */
    public function limit($limit){
        $this->limitStatement = trim($limit);
        return $this;
    }

    /**
     * Set offset of query
     * @param int $offset
     * @return ClickhouseQueryComponent $this
     */
    public function offset($offset){
        $this->offsetStatement = trim($offset);
        return $this;
    }

    /**
     * Get current statement
     * @return string Raw sql statement
     * @throws \Exception if table name or select statement is empty
     */
    public function getRawSql(){
        if(strlen($this->selectStatement) == 0 || strlen($this->tableName) == 0)
            throw new \Exception('Table name or select statement is empty');
        $rawQuery = 'SELECT ';
        $rawQuery .= $this->selectStatement;
        $rawQuery .= ' FROM ';
        $rawQuery .=  $this->tableName;
        if(count($this->andWhere)>0){
            $rawQuery .= ' WHERE '.implode(' AND ',$this->andWhere);
        }
        if(strlen($this->groupStatement)>0){
            $rawQuery .= ' GROUP BY ';
            $rawQuery .= $this->groupStatement;
        }
        if(strlen($this->orderStatement)>0){
            $rawQuery .= ' ORDER BY ';
            $rawQuery .= $this->orderStatement;
        }
        if(strlen($this->limitStatement)>0){
            $rawQuery .= ' LIMIT ';
            if(strlen($this->offsetStatement)>0){
                $rawQuery .= $this->offsetStatement.', ';
            }
            $rawQuery .= $this->limitStatement;
        }
        return $rawQuery;
    }

    /**
     * Get table description (fields type, name, etc)
     * @return mixed
     */
    public function getSchema(){
        $query = 'DESCRIBE TABLE '.$this->tableName;
        return    Yii::$app->clickhouse->select($query)->fetchAll();
    }

    /**
     * Get all found entries as array
     * @return array|null
     * @throws \Exception if sql statement is empty
     */
    public function all(){
        $rawSql = $this->getRawSql();
        if(!$rawSql)
            throw new \Exception('Sql statement is empty');
        $statement = Yii::$app->clickhouse->select($this->getRawSql());
        if($this->classCaller)
            return (new $this->classCaller)->afterFind($statement->fetchAll());
        return $statement->fetchAll();
    }
    /**
     * Get one found entries as object
     * @return object
     * @throws \Exception if sql statement is empty
     */
    public function one(){
        $this->limit(1);
        $rawSql = $this->getRawSql();
        if(!$rawSql)
            throw new \Exception('Sql statement is empty');
        $statement = Yii::$app->clickhouse->select($this->getRawSql());
        if($this->classCaller){
            $return = (new $this->classCaller)->afterFind($statement->fetchAll());
            return (empty($return)?null:$return[0]);
        }
        return $statement->fetchAll();
    }

    /**
     * @param null $field
     * @return mixed
     * @throws \Exception
     */
    public function scalar($field=null){
        $this->limit(1);
        $rawSql = $this->getRawSql();
        if(!$rawSql)
            throw new \Exception('Sql statement is empty');
        $statement = Json::decode(Json::encode(Yii::$app->clickhouse->select($this->getRawSql())->fetchOne()),true);
        if($field == null)
            return $statement[$this->selectStatement];
        return $statement[$field];
    }
}