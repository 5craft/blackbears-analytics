<?php
namespace backend\controllers;

use common\models\AdPlatformKey;
use common\models\AdviewRevenue;
use common\models\AppmetricaApp;
use common\models\Ecpm;
use Yii;
use yii\helpers\Json;
use yii\web\Controller;
use common\models\ValidPurchase;
use common\models\Installation;
use yii\web\Cookie;

class SiteController extends Controller
{
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Get user cookies and check them
     * Redirects to login page with error code 6, if cookies are corrupted
     * @return array Fields: string id, string login;
     */
    protected function getUserCookies($redirect=true){
        $cookies = Yii::$app->request->cookies;
        $userCookie = $cookies->getValue('user');
        if(empty($userCookie['id']) || empty($userCookie['login']))
            if($redirect)
                $this->redirect('/login?err=6');
            else
                return false;
        return $userCookie;
    }

    /**
     * Get information about user applications
     * @param $appsList array Applications represented as array where key is app_id and value is array with app_id and name [123 => [ app_id => 123, name=> "myApp"]]
     * @return array
     *          [
     *              'apps' => [
     *                          123 => [                        //key is app_id
     *                                  name => "myApp",
     *                                  id => 123,
     *                                  os=> android|ios|null
     *                          ],
     *              ],
     *              'all_count' => quantity of all applications
     *              'ios_count' => quantity of ios apps
     *              'android_count' => quantity of android apps
     *          ]
     */
    protected static function getAppList($userId){
        $appsList = AppmetricaApp::getAppList($userId);
        $ios_count = 0;
        $android_count = 0;
        if($appsList) {
            $appsWithOs = Installation::find()->select('app_id, os_name')->addInWhere('app_id',array_keys($appsList))->groupBy('app_id,os_name')->all();
            if ($appsWithOs) {
                foreach ($appsWithOs as $app) {
                    if ($app->os_name == 'ios')
                        $ios_count++;
                    else
                        $android_count++;
                    $appsList[$app->app_id]['os'] = $app->os_name;
                }
            }
        }else
            $appsList = [];
        $appsList = array_map(function($item){ if(!isset($item['os'])) $item['os']=null; return $item;},$appsList);
        return [ 'apps' => $appsList, 'all_count' => count($appsList), 'ios_count' => $ios_count, 'android_count' => $android_count ];
    }
    /**
     * Rounds number ang appends prefix
     * @param $number
     * @param $precision integer precision of number rounding
     * @return string
     */
    protected static function nameTheNumeric($number, $precision = 2){
        $names = Yii::$app->params['metric_prefix'];
        $name = '';
        $i = 0;
        while(round($number/1000,$precision) > 1 && $i < count($names)){
            $name = $names[$i++];
            $number = round($number/1000,$precision);
        }

        return round($number,$precision).$name;
    }
    /**
     * Search application id by name. Returns any coincidence.
     * @param $name string Search string
     * @return array List of id with found apps
     */
    protected function searchApps($name){
        $userCookie  = $this->getUserCookies();
        $appsList = AppmetricaApp::getAppList($userCookie['id']);
        $found = [];
        foreach ($appsList as $app){
            if(strpos(strtolower($app['name']),strtolower($name)) !== false)
                $found[] = $app['id'];
        }

        return $found;
    }
    /**
     * Get dashboard values by filters
     * @param $chosenApps integer
     * @param $dateSince string
     * @param $dateUntil string
     * @param $publishersList array|integer
     * @param $trackersList array|integer
     * @param $platformsList array|string
     * @return array Associative array with next values: downloads, arpu, arppu, conversion, avgBill, avgCount, inappRevenue, adsRevenue, totalRevenue
     */
    protected function getDashboardByFilter($chosenApps=null, $os=null, $dateSince=null, $dateUntil=null, $publishersList=null, $trackersList=null, $platformsList=null){
        $installations = Installation::count($chosenApps, $os, $dateSince, $dateUntil, $publishersList, $trackersList) - 0;
        $summary = ValidPurchase::getSummary($chosenApps, $os, $dateSince, $dateUntil, $publishersList, $trackersList);
        $adsRevenue = AdviewRevenue::revenue($chosenApps, $os, $dateSince, $dateUntil, $publishersList, $trackersList, $platformsList);
        return [
            'downloads' => $this->nameTheNumeric($installations),
            'arpu' => $this->nameTheNumeric(($installations>0?$summary['summary']/$installations:0),3),
            'arppu' => $this->nameTheNumeric(($summary['payers']>0?$summary['summary']/$summary['payers']:0),3),
            'conversion' => $this->nameTheNumeric(($installations>0?$summary['payers']/$installations:0),3),
            'avgBill' => $this->nameTheNumeric($summary['payers']>0?$summary['average']/$summary['payers']:$summary['average'],3),
            'avgCount' => $this->nameTheNumeric($summary['payers']>0?$summary['count']/$summary['payers']:$summary['count'],3),
            'inappRevenue' => $this->nameTheNumeric($summary['summary']-0),
            'adsRevenue' => $this->nameTheNumeric($adsRevenue,3),
            'totalRevenue' => $this->nameTheNumeric($summary['summary']+$adsRevenue,3)
        ];
    }

    /**
     * Get list of application trackers as array, where key is tracker id and value is tracker's name
     * @param $app_id
     * @param $package_name
     * @return array
     */
    protected function getAppTrackers($app_id, $package_name) {
		$trackers = [];
		$appTrackers = Installation::getTrackersByAppId($app_id);
		if(!$appTrackers)
			$appTrackers = Installation::getTrackersByPackage($package_name);
        if(count($appTrackers)>1)
            foreach ((array)$appTrackers as $tracker) {
                if (isset($tracker->tracking_id))
                    $trackers[$tracker->tracking_id] = ($tracker->tracker_name?$tracker->tracker_name:'ORGANIC_TRACKER');
            }
		return $trackers;
	}
	
	protected function returnAjaxResponse($response) {
		Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;	
		return $response;
	}

    /**
     * Index page for ajax script
     * @return array
     */
    public function actionAjaxIndex() {
		if (!Yii::$app->request->isAjax) 
			return $this->returnAjaxResponse(['status' => 'error', 'error' => "It's not ajax request"]);
	    $chosenApps = Yii::$app->request->post('app_id');
		$trackersList = Yii::$app->request->post('tracker');
	    $platformsList = Yii::$app->request->post('platforms');
		$dateSince = Yii::$app->request->post('date_start');
	    $dateUntil = Yii::$app->request->post('date_end');
	    if ($dateSince) $dateSince = date('Y-m-d',$dateSince);
	    if ($dateUntil) $dateUntil = date('Y-m-d',$dateUntil);
        
		$userCookie = $this->getUserCookies(false);
        $userId = $userCookie['id'];
		if(!$userId)
	        return $this->returnAjaxResponse(['status' => 'error', 'error' => "User id isn't set."]);	
        
		$apps = $this->getAppList($userCookie['id']);

		$resultParams = $this->getAppInfo($userId, $chosenApps, $dateSince, $dateUntil, $trackersList, $platformsList, $apps['apps']);
		if($resultParams)
            $response = [
                'status' => 'ok',
                'html'	=> $this->renderAjax('_index_center', $resultParams),
            ];
        else
            $response = ['status' => 'error', 'error' => "Unknown app id."];
		
		Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;	
		return $response;
	}

    /**
     * Main page.
     * @return string
     */
    public function actionIndex() {
        Yii::$app->clickhouse->checkTables();
        $cookies = Yii::$app->request->cookies;

        if(!$cookies->getValue('oAuth')){
            return  $this->redirect('/login');
        }
        $userCookie = $this->getUserCookies();
        $apps = $this->getAppList($userCookie['id']);
        if(!$apps['all_count']){
            Yii::$app->response->cookies->remove('oAuth');
            return $this->render('error',['message' => 'No applications in your account', 'name' => 'No applications', 'error_code' => 44]);
        }
		
		$chosenApps = Yii::$app->request->get('app_id');
		$dateSince = Yii::$app->request->get('date_start', null);
        $dateUntil = Yii::$app->request->get('date_end', null);
        $trackersList = Yii::$app->request->get('tracker', null);
        $platformsList = Yii::$app->request->get('platforms', null);
		
		$resultParams = $this->getAppInfo($userCookie['id'],$chosenApps, $dateSince, $dateUntil, $trackersList, $platformsList, $apps['apps']);
        if(!$resultParams) {
            return $this->render('error', ['message' => 'UNKNOWN_APP_ID_TEXT', 'name' => 'UNKNOWN_APP_ID_TITLE']);
        }

		$resultParams = array_merge($resultParams, [
			'apps' => $apps,
            'userLogin' => $userCookie['login']
		]);
            
		return $this->render('index', $resultParams);
		
    }

    /**
     * Get full application info
     * @param integer $userId
     * @param null $chosenApps
     * @param null $dateSince
     * @param null $dateUntil
     * @param null $trackersList
     * @param null $platformsList
     * @param array $appsList
     * @return array|bool False if unknown app was chosen
     */
    protected function getAppInfo($userId, $chosenApps = null, $dateSince = null, $dateUntil = null, $trackersList = null, $platformsList = null, $appsList = []) {
        if(empty($userId))
            return false;
    	$hideSpecFilter = true;
        $lastEcpmUpdate = '';
        $trackers = [];
        $connectedPlatforms = [];
		switch($chosenApps) {
            case 0:
			case null:
            case 'all': {
            	$chosenApps=null;
			}
            case 'android':
            case 'ios': {
                $dashboard = $this->getDashboardByFilter(null, $chosenApps, $dateSince, $dateUntil, null, $trackersList, $platformsList);
                $connectedPlatforms = AdPlatformKey::getPlatforms($userId);
                $hideSpecFilter = true;
                break;
			}
            default: {
                if(array_search($chosenApps,array_keys($appsList)) === false) {
                    return false;
				}
		 		$hideSpecFilter = false;
                $app = Installation::find()->select('app_id, anyLast(app_package_name) as app_package_name, max(install_datetime) as install_datetime')->addInWhere('app_id',$chosenApps)->groupBy('app_id')->orderBy('install_datetime asc')->one();
                $connectedPlatforms = AdPlatformKey::getPlatforms($userId,$chosenApps);
                $dashboard = $this->getDashboardByFilter($chosenApps, null, $dateSince, $dateUntil, null, $trackersList, $platformsList);
                if($app) {
                    $lastEcpmUpdate = Ecpm::getUpdateDate($app->app_id, $app->app_package_name);
                    $trackers = $this->getAppTrackers($chosenApps, $app->app_package_name);
                }
				break;
            }
        }

        $params = [
            'dashboard' => $dashboard,
            'chosenApps' => $chosenApps,
            'trackersList'=> $trackersList,
            'trackers' => $trackers,
            'platformsList' => $platformsList,
           	'connectedPlatforms' => $connectedPlatforms,
            'lastEcpmUpdate' => ($lastEcpmUpdate!=''?strtotime($lastEcpmUpdate):0),
            'hideSpecFilter' => $hideSpecFilter
        ];
        
        return $params;
    }

    /**
     * Update keys of the application
     * @return array
     */
    public function actionAjaxSaveAdkey(){
    	if (!Yii::$app->request->isAjax) 
			return $this->returnAjaxResponse(['status' => 'error', 'error' => "It's not ajax request"]);
		
		$userCookie = $this->getUserCookies(false);
        $userId = $userCookie['id'];
		if(!$userId)
	        return $this->returnAjaxResponse(['status' => 'error', 'error' => "User id isn't set."]);
		 	
		$keys = Yii::$app->request->post('keys');
		if (!is_array($keys)) return $this->returnAjaxResponse(['status' => 'error', 'error' => "Keys id isn't set."]);	
        
        $connectedApps = AdPlatformKey::getConnectedApps($userId);
        $newKeys = [];
        foreach ($keys as $app_id => $app){
         	if (!is_array($keys)) return $this->returnAjaxResponse(['status' => 'error', 'error' => "Keys id isn't set."]);	
            foreach ($app as $platform => $apiKeys){
            	if(!isset($connectedApps[$app_id]) || !isset($connectedApps[$app_id][$platform]) || $apiKeys != $connectedApps[$app_id][$platform]){
            		$newKey = new AdPlatformKey();
                    $newKey->app_id = $app_id;
                    $newKey->user_id = $userId;
                    $newKey->app_key = (isset($apiKeys['app_key'])) ? $apiKeys['app_key'] : null;
                    $newKey->api_key = (isset($apiKeys['api_key'])) ? $apiKeys['api_key'] : null;
                    $newKey->platform = $platform;
                   	$newKey->time = time();
                    $newKeys[] = $newKey;
                }
            }
		}
		if(count($newKeys) > 0)
			AdPlatformKey::insertBatch($newKeys);
		
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $this->returnAjaxResponse(['status' => 'ok']);
    }
	
    /**
     * Get data from url request
     * @param $url
     * @return mixed|void
     */
    private function getUrlData($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $data = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($httpcode<200 || $httpcode>=300) {
            Yii::error('Connecting to '.$url.' was failed with error code '.$httpcode);
            return;
        }
        curl_close($ch);
        return $data;
    }

    /**
     * Login page
     * @return string
     */
    public function actionLogin(){
        Yii::$app->clickhouse->checkTables();
        $responseCookies = Yii::$app->response->cookies;
        if(Yii::$app->request->get('logout') !== null)
            $responseCookies->remove('oAuth');
        return $this->render('login',[
            'client_id' => Yii::$app->params['yandex_oauth_id'],
            'error_code'=>Yii::$app->request->get('err')
        ]);
    }

    /**
     * Refresh applications in user cookies
     * @param $oauthToken string Token of  yandex oAuth
     * @return bool
     */
    private function refreshApplications($oauthToken){
        $url= str_replace('%oauth_token%',$oauthToken,Yii::$app->params['yandex_url']['appmetrica_app_list']);
        $rawApplications = $this->getUrlData($url);
        if(empty($rawApplications))
            return false;
        $rawApplications = Json::decode($rawApplications);
        $rawApplications = $rawApplications['applications'];
        $apps_id_array = [];
        $appsForCookie =[];
        foreach ($rawApplications as $item) {
            $appsForCookie[$item['id']] = $item['name'];
            $apps_id_array[] = $item['id'];
            $appsNames[$item['id']] = $item['name'];
        }
        $userCookie  = $this->getUserCookies();
        $ch_apps_id = AppmetricaApp::getAppList($userCookie['id']);
        $appsToDelete = [];
        foreach($ch_apps_id as $ch_app){
            $key = array_search($ch_app['id'],$apps_id_array,true);
            if($key !== false) {
                unset($apps_id_array[$key]);
            }else
                $appsToDelete[] = $ch_app['id'];

        }

        $insertApps = [];
        foreach ($apps_id_array as $appId) {
            $newApp = new AppmetricaApp();
            $newApp->user_id = $userCookie['id'];
            $newApp->token = $oauthToken;
            $newApp->id = $appId;
            $newApp->name = $appsNames[$appId];
            $insertApps[] = $newApp;
        }
        if(count($insertApps)>0)
            AppmetricaApp::insertBatch($insertApps);
        if(count($appsToDelete)>0)
            AppmetricaApp::deleteOldApps($userCookie['id'],$appsToDelete);

        return true;
    }

    /**
     * Refresh user info in cookies
     * @param $oauthToken
     * @return bool
     */
    private function refreshUserCookies($oauthToken){
        $responseCookies = Yii::$app->response->cookies;
        $url= str_replace('%oauth_token%',$oauthToken,Yii::$app->params['yandex_url']['account_info']);
        $user = $this->getUrlData($url);
        if(empty($user))
            return false;
        $user = Json::decode($user);
        $responseCookies->remove('user');
        $responseCookies->add(new Cookie([
            'name' => 'user',
            'value' => [
                'id' => $user['id'],
                'login' => $user['login']
            ],
            'expire' => time() + 60 * 60 * 24 * 3,
        ]));
        return true;
    }

    /**
     *  Set oAuth cookies
     * @return \yii\web\Response
     */
    public function actionYandexOAuth(){
        $responseCookies = Yii::$app->response->cookies;
        if(!$oauth_token = Yii::$app->request->get('oauth_token'))
            return $this->redirect('/login?err=9');
        $responseCookies->add(new Cookie([
            'name' => 'oAuth',
            'value' => [
                'token' => $oauth_token,
            ],
            'expire' => time() + 60 * 60 * 24 * 3,
        ]));
        if(!$this->refreshUserCookies($oauth_token))
            return $this->redirect('/login?err=8');
        if(! $this->refreshApplications($oauth_token))
            return $this->redirect('/login?err=7');
        return $this->redirect('/index');
    }
}