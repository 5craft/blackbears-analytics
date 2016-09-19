<?php
namespace console\controllers;

use common\models\Installation;
use common\models\PurchaseEvent;
use common\models\PurchaseValidator;
use common\models\ValidPurchase;
use GuzzleHttp\Client;
use Yii;
use common\models\IosPurchase;
use common\models\AndroidPurchase;
use yii\console\Controller;
use yii\helpers\Json;
class PurchaseController extends Controller{

    /**
     * Check purchase type
     * @param $receipt string
     * @return bool
     */
    private function isSandbox($receipt){
        $decodedData = @base64_decode($receipt);
        $decodedData = str_replace(array('=', ';'), array(':', ','), $decodedData);
        $lastComma = strrpos($decodedData, ',');
        if ($lastComma !== false) $decodedData = substr_replace($decodedData, '', $lastComma, 1);
        $decodedData = json_decode($decodedData);
        if ($decodedData && isset($decodedData->environment) && $decodedData->environment == "Sandbox")
            return true;
        return false;
    }

    /**
     * Send data to appstore
     * @param $url
     * @param $postData string Json
     * @return mixed
     * @throws \Exception
     */
    private function postToAppstore($url, $postData){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $errmsg = curl_error($ch);
        curl_close($ch);

        if ($errno != 0) {
            throw new \Exception($errmsg, $errno);
        }
        return $response;
    }

    /**
     * Verification for all ios purchases in the table
     */
    public function actionVerifyIos()
    {
        $cached = Yii::$app->cache->get('last_ios_verification');
        $limit = Yii::$app->params['purchase_limit'];
        $offset = 0;
        do {
            if ($cached != null && isset($cached['date']))
                $iosPurchases = IosPurchase::find()->andWhere('event_datetime >= \'' . $cached['date'] . '\'')->all();
            else
                $iosPurchases = IosPurchase::findAll();
            //TODO: pass??
            //        if ($pass) {
            //            $postData['password'] = $pass;
            //        }
            $verifiedPurchases = [];
            $oldEntries = true;
            if ($iosPurchases) {
                foreach ($iosPurchases as $purchase) {
                    $isSandbox = false;
                    if ($oldEntries && isset($cached['receipt'])) {
                        if ($cached['receipt'] == $purchase->receipt)
                            $oldEntries = false;
                        continue;
                    }
                    $lastVerification = ['date' => $purchase->event_datetime, 'receipt' => $purchase->receipt];
                    if ($this->isSandbox($purchase->receipt)){
                        if(!Yii::$app->params['billing']['ios']['validateSandbox'])
                            continue;
                        $Url = Yii::$app->params['billing']['ios']['address_sandbox'];
                    }
                    else
                        $Url = Yii::$app->params['billing']['ios']['address'];
                    $postData = Json::encode(['receipt-data' => $purchase->receipt]);
                    $response = Json::decode($this->postToAppstore($Url, $postData), true);

                    if ($response['status'] == 21007) {
                        $isSandbox = true;
                        $Url = Yii::$app->params['billing']['ios']['address_sandbox'];
                        $response = Json::decode($this->postToAppstore($Url, $postData));
                    }
                    $verification = $this->_verifyIosResponse($response, $purchase->product_id, $purchase->app_package_name);
                    if ($verification['validated']) {
                        $install = Installation::find()->andWhere('ios_ifa = \'' . $purchase->ios_ifa . '\'')->orderBy('install_datetime DESC')->one();
                        $validPurchase = new ValidPurchase();
                        $validPurchase->app_metrica_name = $install->app_metrica_name;
                        $validPurchase->app_id = $install->app_id;
                        $validPurchase->app_package_name = $purchase->app_package_name;
                        $validPurchase->purchase_datetime = $purchase->event_datetime;
                        $validPurchase->amount = $purchase->amount;
                        $validPurchase->product_id = $purchase->product_id;
                        $validPurchase->transaction_id = $verification['transaction_id'];
                        $validPurchase->os_name = 'ios';
                        $validPurchase->device_id = $purchase->ios_ifa;
                        $validPurchase->is_sandbox = $isSandbox;
                        $validPurchase->publisher_id = ($install ? $install->publisher_id : 0);
                        $validPurchase->publisher_name = ($install ? $install->publisher_name : '');
                        $validPurchase->tracking_id = ($install ? $install->tracking_id : 0);
                        $validPurchase->tracker_name = ($install ? $install->tracker_name : '');
                        $verifiedPurchases[] = $validPurchase;
                    }
                }
                if(isset($lastVerification))
                    Yii::$app->cache->set('last_ios_verification', $lastVerification);
                ValidPurchase::insertBatch($verifiedPurchases);
            }
            $offset += $limit;
        }while($iosPurchases);
    }

    /**
     * Verifying response from appstore
     * @param $data
     * @param $product_id
     * @param $package_name
     * @return array
     */
    protected function _verifyIosResponse($data, $product_id, $package_name)
    {
        $validation = [
            'validated' =>false,
            'transaction_id' => null
        ];

        if(!array_key_exists('status', $data) || $data['status'] != 0)
            return $validation;
        if (array_key_exists('receipt', $data) && is_array($data['receipt']) && array_key_exists('in_app', $data['receipt']) && is_array($data['receipt']['in_app'])) {
            if (array_key_exists('bundle_id', $data['receipt']) && ($data['receipt']['bundle_id'] != $package_name)) {
                 return $validation;
            }
            $inAppPurchase = end($data['receipt']['in_app']);
            if(!array_key_exists('product_id',$inAppPurchase) && $inAppPurchase['product_id'] != $product_id){
                    return $validation;
            }
            $validation['validated'] = true;
            $validation['transaction_id'] = $inAppPurchase['transaction_id'];
        } elseif (array_key_exists('receipt', $data)) {
            if (array_key_exists('receipt', $data) && array_key_exists('bid', $data['receipt'])) {
                if($data['receipt']['bid'] != $package_name)
                    return $validation;
                $validation['validated'] = true;
                $validation['transaction_id'] = $data['receipt']['transaction_id'];
            }
        }
        return $validation;
    }

    /**
     * Verification for all android purchases in the table
     *
     */
    public function actionVerifyAndroid(){
        $cached = Yii::$app->cache->get('last_android_verification');
        $limit = Yii::$app->params['purchase_limit'];
        $offset = 0;
        $appKeys = PurchaseValidator::getAllForVerify();
        if(empty($appKeys))
            return;
        do {
            if ($cached !== null && isset($cached['date']))
                $androidPurchases = AndroidPurchase::find()->andWhere('event_datetime >= \'' . $cached['date'] . '\'')->limit($limit)->offset($offset)->all();
            else
                $androidPurchases = AndroidPurchase::find()->limit($limit)->offset($offset)->all();
            $verifiedPurchases = [];
            $oldEntries = true;
            if ($androidPurchases) {
                foreach ($androidPurchases as $purchase) {
                    if ($oldEntries && $cached) {
                        if (isset($cached['signature']) && $cached['signature'] == $purchase->signature)
                            $oldEntries = false;
                        continue;
                    }
                    $lastVerification =  ['date' => $purchase->event_datetime, 'signature' => $purchase->signature];

                    if (!array_key_exists($purchase->app_package_name, $appKeys)) {
                        Yii::error('Unknown app package name "' . $purchase->app_package_name . '". There are no key for that app.', 'purchaseVerification');
                        continue;
                    }
                    $key = Yii::$app->params['billing']['android']['key_prefix'] . chunk_split($appKeys[$purchase->app_package_name], 64, "\n") . Yii::$app->params['billing']['android']['key_suffix'];
                    $key = openssl_get_publickey($key);
                    if (false === $key) {
                        Yii::error('Please pass a Base64-encoded public key from the Market portal');
                        continue;
                    }
                    if (!is_string($purchase->response_data)) {
                        Yii::error('Invalid response data, expected string');
                        continue;
                    }

                    $validation = $this->_verifyAndroidResponse($purchase->response_data, $purchase->app_package_name, $purchase->signature, $key);
                    $response = Json::decode($purchase->response_data, true);
                    if ($validation['validated']) {
                        $install = Installation::find()->andWhere('android_id = \'' . $purchase->android_id . '\'')->orderBy('install_datetime DESC')->one();
                        $validPurchase = new ValidPurchase();
                        $validPurchase->app_metrica_name = $install->app_metrica_name;
                        $validPurchase->app_id = $install->app_id;
                        $validPurchase->app_package_name = $purchase->app_package_name;
                        $validPurchase->purchase_datetime = $purchase->event_datetime;
                        $validPurchase->amount = $purchase->amount;
                        $validPurchase->product_id = $response['productId'];
                        $validPurchase->transaction_id = $validation['transaction_id'];
                        $validPurchase->os_name = 'android';
                        $validPurchase->device_id = $purchase->android_id;
                        $validPurchase->is_sandbox = false;
                        $validPurchase->publisher_id = ($install ? $install->publisher_id : 0);
                        $validPurchase->publisher_name = ($install ? $install->publisher_name : '');
                        $validPurchase->tracking_id = ($install ? $install->tracking_id : 0);
                        $validPurchase->tracker_name = ($install ? $install->tracker_name : '');
                        $verifiedPurchases[] = $validPurchase;
                    }
                }
                if(isset($lastVerification))
                    Yii::$app->cache->set('last_android_verification',$lastVerification);
                ValidPurchase::insertBatch($verifiedPurchases);
            }
            $offset += $limit;
        }while($androidPurchases);
    }

    /**
     * Verify google.play response for purchase
     * @param $responseData
     * @param $packageName
     * @param $signature
     * @param $key
     * @return array
     */
    protected function _verifyAndroidResponse($responseData, $packageName, $signature, $key)
    {
        $jsonResponse = Json::decode($responseData);
        $validation = [
            'validated' => false,
            'transaction_id' => null,
            'product_id' => null,
        ];
        if(!array_key_exists('packageName',$jsonResponse) || $jsonResponse['packageName'] != $packageName){
                Yii::error('Package names don\'t match');
                return $validation;
            }
        if(!array_key_exists('purchaseToken',$jsonResponse)){
            Yii::error('No purchase token');
            return $validation;
        }
        if(!array_key_exists('purchaseState',$jsonResponse) || $jsonResponse['purchaseState'] != 0){
            Yii::error('Product wasn\'t purchased');
            return $validation;
        }
        if(!array_key_exists('productId',$jsonResponse)){
            Yii::error('No product id');
            return $validation;
        }
        $result = openssl_verify($responseData, base64_decode($signature),
            $key, Yii::$app->params['billing']['android']['sig_algorithm']);
        if ($result === 0){
            Yii::error('SSL didn\'t verify');
            return $validation;
        }
        else if ($result !== 1){
            Yii::error('Unknown error verifying the signature in openssl_verify');
            return $validation;
        }
        $validation['validated'] = true;
        $validation['transaction_id'] = $jsonResponse['purchaseToken'];
        $validation['product_id'] = $jsonResponse['productId'];
        return $validation;
    }

    /**
     * Verifying all platfroms at once and clear unverified purchases
     */
    public function actionVerifyAll(){
        $this->actionVerifyIos();
        $this->actionVerifyAndroid();
        PurchaseEvent::truncate();
    }
}