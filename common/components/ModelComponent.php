<?php
/*
 * Black Bears Analytics
 * @author Blackbears
 * @link blackbears.mobi
 * @version 1.0
 */
namespace common\components;

use Yii;
/*
 * Interface of model
 */
interface ModelComponent {
    /**
     * @return string Returns table name
     */
    static function tableName();

    /**
     * @return Connection
     */
    static function getDb();
}
