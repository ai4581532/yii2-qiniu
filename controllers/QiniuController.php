<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class QiniuController extends Controller{
    
    public function actionTest(){
        return "ok";
    }
    
    public function actionIndex(){
        
//         return $this->render('index');

        return $this->renderPartial('index.html');
        
    }
    
    /**
     * 获取token
     * @return string
     */
    public function actionGetToken(){
        $result = array("success"=>1,"data"=>"");
        
        $accessKey = Yii::$app->params['qiniu-accessKey'];
        $secretKey = Yii::$app->params['qiniu-secretKey'];
        
        $bucket = Yii::$app->params['qiniu-bucket'];
        
        // 初始化签权对象
        $auth = new Auth($accessKey, $secretKey);
        
        $policy = array(
            'callbackUrl' => 'http://yju565.natappfree.cc/basic/web/index.php?r=qi-niu/upload-callback',
            'callbackBody' => 'filename=$(fname)&filesize=$(fsize)'
        );
        
        // 生成上传Token
        $token = $auth->uploadToken($bucket, null, 3600, $policy);
        
        $result["data"] = $token;
        
        //return json_encode($result);
        return $token;
    }
    
    public function actionUpload(){
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = Yii::$app->params['qiniu-accessKey'];
        $secretKey = Yii::$app->params['qiniu-secretKey'];
        
        $bucket = Yii::$app->params['qiniu-bucket'];
        
        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        
        // 生成上传 Token
        //$token = $auth->uploadToken($bucket);
        
        $policy = array(
            'scope'=>$bucket, //http://your.domain.com/upload_verify_callback.php
            
            'callbackUrl' => "http://yju565.natappfree.cc/basic/web/index.php/qiniu/uploadcallback",
            
            'callbackBody' => 'filename=$(fname)&filesize=$(fsize)'
        );
        $token = $auth->uploadToken($bucket, null, 3600000, $policy);
        
        // 要上传文件的本地路径
        $filePath = 'C:/Users/Public/Pictures/Sample Pictures/500455638.jpg';
        
        // 上传到七牛后保存的文件名
        $key = '500455638.jpg';
        
        // 初始化 UploadManager 对象并进行文件的上传。
        $uploadMgr = new UploadManager();
        
        // 调用 UploadManager 的 putFile 方法进行文件的上传。
        list($ret, $err) = $uploadMgr->putFile($token, null, $filePath);
 
        
        if ($err !== null) {
            return var_dump($err);
        } else {
            return var_dump($ret);
        }
 
        
    }
    
    /**
     * 上传成功回调
     */
    public function actionUploadcallback(){
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = Yii::$app->params['qiniu-accessKey'];
        $secretKey = Yii::$app->params['qiniu-secretKey'];
        
        $bucket = Yii::$app->params['qiniu-bucket'];
        
        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        
        //获取回调的body信息
        $callbackBody = file_get_contents('php://input');
        
        //return json_encode(array('ret' => 'success'));
        
        
        
        //回调的contentType
        $contentType = 'application/x-www-form-urlencoded';
        
        //回调的签名信息，可以验证该回调是否来自七牛
        $authorization = $_SERVER['HTTP_AUTHORIZATION'];
        
        //七牛回调的url，具体可以参考：http://developer.qiniu.com/docs/v6/api/reference/security/put-policy.html
        $url = 'http://yju565.natappfree.cc//basic//web//index.php/qiniu/uploadcallback';
        
        $isQiniuCallback = $auth->verifyCallback($contentType, $authorization, $url, $callbackBody);
        
        if ($isQiniuCallback) {
            $resp = array('ret' => 'success');
        } else {
            $resp = array('ret' => 'failed');
        }
        
        echo json_encode($resp);
    }
    
}
 
 