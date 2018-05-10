<?php

namespace app\components;

use Yii;
use yii\base\Component;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class QiniuOSS extends Component{

    private static $accessKey;
    private static $secretKey;
    private static $bucket;
    
    private static $auth;
    
    private static $callbackUrl = "http://yju565.natappfree.cc/basic/web/index.php/qiniu/uploadcallback";
    private static $callbackBody = "filename=$(fname)&filesize=$(fsize)&key=$(key)&hash=$(etag)&mimeType=$(mimeType)";
    
    public function __construct(){
        
        parent::__construct();
        
        self::$accessKey = Yii::$app->params['qiniu-accessKey'];
        self::$secretKey = Yii::$app->params['qiniu-secretKey'];
        self::$bucket = Yii::$app->params['qiniu-bucket'];
         
        self::$auth = new Auth(self::$accessKey , self::$secretKey);
        
        self::$callbackUrl = Yii::$app->params['qiniu-callbackUrl'];
        
    }
    
    public function getAuth(){
        return self::$auth;
    }
    
    public function getPolicy($callbackUrl=null,$callbackBody=null){
        
        $policy = array(
            'callbackUrl' => empty($callbackUrl)?self::$callbackUrl:$callbackUrl,
            'callbackBody' => empty($callbackBody)?self::$callbackBody:$callbackBody,
        );
        
        return $policy;
    }
    
    public function getToken($key = null, $expires = 3600, $policy = null){
        
        $token = self::$auth->uploadToken(self::$bucket, $key, $expires, $policy);
        
        return $token;
    }
    
    public function upload($token,$key,$filePath){
        $uploadMgr = new UploadManager();
        
        // 调用 UploadManager 的 putFile 方法进行文件的上传。
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        
        if ($err !== null) {
            return $err;
        } else {
            return $ret;
        }
        
    }
    
    public function verifyCallback($contentType, $authorization, $url, $callbackBody){
        
        $isQiniuCallback = self::$auth->verifyCallback($contentType, $authorization, $url, $callbackBody);
        
        if ($isQiniuCallback) {
            $resp = array('ret' => 'success');
        } else {
            $resp = array('ret' => 'failed');
        }
        
        return $resp;
    }
    
    public function getPrivateDownloadUrl($baseUrl, $expires = 3600){
        
        return self::$auth->privateDownloadUrl($baseUrl, $expires);
        
    }
    
}