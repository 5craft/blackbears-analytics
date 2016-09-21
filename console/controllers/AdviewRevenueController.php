<?php
/*
 * Black Bears Analytics
 * @author Blackbears
 * @link blackbears.mobi
 * @version 1.0
 */
namespace console\controllers;


use common\models\AdPlatformKey;
use common\models\AdviewEvent;
use common\models\AdviewRevenue;
use common\models\Ecpm;
use Yii;
use yii\console\Controller;
class AdviewRevenueController extends Controller{
    /**
     * Updates Ecpm table for chosen date period
     * @param $start_date null|string Updates since date in format "yyyy-mm-dd"
     * @param $end_date null|string Updates until date in format "yyyy-mm-dd"
     */
    private static function updateEcpm($start_date = null, $end_date = null){
        $adPlatformKeys = AdPlatformKey::find()->andWhere('app_key!=\'\' or api_key!=\'\'')->all();
        if($start_date === null){
            $earlyDate = AdviewEvent::find()->select('event_datetime')->orderBy('event_datetime')->limit(1)->one();
            if($earlyDate)
                $start_date = date('Y-m-d',strtotime($earlyDate->event_datetime));
            else
                $start_date = date('Y-m-d',strtotime('-2 day'));
        }
        if($end_date === null)
            $end_date = date('Y-m-d',strtotime('-1 day'));

        foreach ($adPlatformKeys as $entry){
            Ecpm::updateEcpm($entry,$start_date,$end_date);
        }
    }


    /**
     * Saves all views with its revenue in table AdviewRevenue and deletes them from adview event
     */
    public function actionUpdate()
    {
        self::updateEcpm();
        $offset = 0;
        $limit = Yii::$app->params['adview_limit'];
        $insertRevenue = [];
        do {
            try {
                $views = AdviewEvent::findWithPublisher($limit, $offset);
            } Catch (\Exception $e) {
                break;
            }
            if ($views) {
                foreach ($views as $view) {
                    $ecpm = Ecpm::getForView($view);
                    if ($ecpm && ($ecpm->ecpm - 0 > 0)) {
                        $revenue = new AdviewRevenue();
                        $revenue->app_id = $view->app_id - 0;
                        $revenue->app_package_name = strtolower($view->app_package_name);
                        $revenue->platform = $ecpm->platform;
                        $revenue->date = $view->event_datetime;
                        $revenue->country = $ecpm->country;
                        $revenue->revenue = $ecpm->ecpm - 0;
                        $revenue->publisher_id = $view->publisher_id;
                        $revenue->tracking_id = $view->tracking_id;
                        $revenue->os_name = ($view->ios_ifa?'ios':'android');
                        $insertRevenue[] = $revenue;
                    }
                }
            }
            if (count($insertRevenue) > 0) {
                AdviewRevenue::insertBatch($insertRevenue);
            }
            $offset += $limit;
        } while ($views);
        AdviewEvent::dropViewsWithInstalls();
    }
}