<?php
namespace common\models;

use Yii;
use common\components\ClickhouseModelComponent;
class ValidPurchase extends ClickhouseModelComponent
{
    public $app_metrica_name;
    public $app_id;
    public $app_package_name;
    public $purchase_datetime;
    public $amount;
    public $product_id;
    public $transaction_id;
    public $os_name;
    public $device_id;
    public $is_sandbox;
    public $publisher_id;
    public $publisher_name;
    public $tracking_id;
    public $tracker_name;

    /**
     * @return string Returns table name
     */
    static function tableName()
    {
        return parent::tableName().'valid_purchase';
    }
    /**
     * Method to get summary purchases information for chosen applications for chosen date period and filtered by any of next fields: publisher, tracker or platform
     * @param $app_id_list array|string List of apps id
     * @param $date_since null|string Filter by since date in format "yyyy-mm-dd"
     * @param $date_until null|string Filter by until date in format "yyyy-mm-dd"
     * @param $publisher null|string |array Filter by publisher by id
     * @param $tracker null|string |array Filter by tracker by id
     * @param $platform null|string |array Filter by advertisement platform name
     * @return array Array with fields 'summary', 'payers', 'average', 'count'
     */
    public static function getSummary($app_id_list=null, $os=null, $date_since=null, $date_until=null, $publisher=null, $tracker=null)
    {
        $query = self::find()->select('sum(amount) as summary, avg(amount) as average, count() as count, uniqExact(device_id) as payers');
        if($app_id_list !== null)
            $query = $query->addInWhere('app_id', $app_id_list);
        if($os !== null)
            $query = $query->addInWhere('os_name', $os);
        if ($date_since !== null)
            $query = $query->andWhere('purchase_datetime >= \'' . strtotime($date_since) . '\'');
        if ($date_until !== null)
            $query = $query->andWhere('purchase_datetime <= \'' . (strtotime($date_until) + 86399) . '\'');
        if ($publisher !== null)
            $query = $query->addInWhere('publisher_id', (array)$publisher);
        if ($tracker !== null)
            $query = $query->addInWhere('tracking_id', (array)$tracker);
        if (!Yii::$app->params['billing']['ios']['validateSandbox'])
            $query = $query->andWhere('is_sandbox=0');
        $return = [
            'summary' => 0,
            'payers' => 0,
            'average' => 0,
            'count' => 0
        ];
        $sql = $query->getRawSql();
        if ($result = self::getDb()->select($sql)->fetchAll()){
            $return['summary'] += $result[0]->summary;
            $return['payers'] += $result[0]->payers;
            $return['average'] += $result[0]->average;
            $return['count'] += $result[0]->count;
        }
        return $return;
    }
}



