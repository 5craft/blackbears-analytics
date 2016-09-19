<?php
namespace common\models;

use common\components\ClickhouseQueryComponent;
use Yii;
use common\components\ClickhouseModelComponent;
use yii\helpers\Json;
class AdviewRevenue extends ClickhouseModelComponent
{
    public $app_id;
    public $app_package_name;
    public $date;
    public $platform;
    public $country;
    public $revenue;
    public $publisher_id;
    public $tracking_id;
    public $os_name;
    /**
     * @return string Returns table name
     */
    public static function tableName()
    {
        return parent::tableName().'adview_revenue';
    }

    /**
     * Method to get revenue for advertisement views for chosen date period and filtered by any of next fields: publisher, tracker or platform
     * @param $app_id_list array|string List of apps id
     * @param $date_since null|string Filter by since date in format "yyyy-mm-dd"
     * @param $date_until null|string Filter by until date in format "yyyy-mm-dd"
     * @param $publisher null|string |array Filter by publisher by id
     * @param $tracker null|string |array Filter by tracker by id
     * @param $platform null|string |array Filter by advertisement platform name
     * @return float
     */
    public static function revenue($app_id_list=null, $os=null, $date_since = null, $date_until = null, $publisher=null, $tracker=null, $platform=null){
        $query = self::find()->select('count()*any(revenue)/1000 as revenue');
        if($app_id_list !== null)
            $query = $query->addInWhere('app_id', $app_id_list,ClickhouseQueryComponent::TYPE_NUMERIC);
        if($os !== null)
            $query = $query->addInWhere('os_name', $os);
        if($date_since !== null)
            $query = $query->andWhere('date >= \''.strtotime($date_since).'\'');
        if($date_until !== null)
            $query = $query->andWhere('date <= \''.(strtotime($date_until)+86399).'\'');
        if($publisher !== null){
            $query = $query->addInWhere('publisher_id', $publisher);
        }
        if($tracker !== null)
            $query = $query->addInWhere('tracking_id', $tracker);
        if($platform !== null)
            $query = $query->addInWhere('platform', $platform);
        $query = $query->groupBy('date');
        $revenueByDay = Yii::$app->clickhouse->select($query->getRawSql())->fetchAll();
        $totalRevenue = 0;
        if($revenueByDay)
            foreach ($revenueByDay as $day){
                $totalRevenue += $day->revenue;
            }
        return $totalRevenue;
    }
}