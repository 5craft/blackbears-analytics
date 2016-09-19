<?php
namespace common\components;

use Yii;

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
