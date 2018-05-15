<?php

namespace app\components;

use Yii;
use yii\base\Component;

use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Storage\UploadManager;
use Qiniu\Storage\BucketManager;

class QiniuOSS extends Component{

    private static $accessKey;
    private static $secretKey;
    private static $bucket;
    
    private static $auth;
    
    private static $callbackUrl = "http://yju565.natappfree.cc/basic/web/index.php/qiniu/uploadcallback";
    private static $callbackBody = "filename=$(fname)&filesize=$(fsize)&key=$(key)&hash=$(etag)&mimeType=$(mimeType)&w=$(imageInfo.width)&h=$(imageInfo.height)";
    private static $returnBody = '{"filename":$(fname),"filesize":$(fsize),"key":$(key),"hash":$(etag),"mimeType":$(mimeType),"w":$(imageInfo.width),"h":$(imageInfo.height)}';
    
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
    
    public function getBuckets(){
        $bucketManager = new BucketManager(self::$auth);
        return $bucketManager->buckets();
    }
    
    public function getDomains(){
        $bucketManager = new BucketManager(self::$auth);
        return $bucketManager->domains(self::$bucket);
    }
    
    public function getCallbackPolicy($callbackUrl=null,$callbackBody=null){
        
        $policy = array(
            'callbackUrl' => empty($callbackUrl)?self::$callbackUrl:$callbackUrl,
            'callbackBody' => empty($callbackBody)?self::$returnBody:$callbackBody,
        );
        
        return $policy;
    }
    
    public function getReturnBodyPolicy($returnBody=null){
        
        $policy = array(
            'returnBody' => empty($returnBody)?self::$returnBody:$returnBody,
        );
        
        return $policy;
    }
    
    public function getToken($key = null, $expires = 3600, $policy = null){
        
        $token = self::$auth->uploadToken(self::$bucket, $key, $expires, $policy);
        
        return $token;
    }
    
    public function upload($token,$key=null,$filePath){
        $uploadMgr = new UploadManager();
        
        // 调用 UploadManager 的 putFile 方法进行文件的上传。
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        
        if ($err !== null) {
            return $err;
        } else {
            return $ret;
        }
        
    }
    
    public function verifyCallback($contentType, $authorization, $callbackBody){
        
        $isQiniuCallback = self::$auth->verifyCallback($contentType, $authorization, self::$callbackUrl, $callbackBody);
        
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
    
    public function getFileList($prefix= '', $marker= '', $limit= 100){
        
        $bucketManager = new BucketManager(self::$auth);
 
        $delimiter = "/";
        
        // 列举文件
        list($ret, $err) = $bucketManager->listFiles(self::$bucket, $prefix, $marker, $limit, $delimiter);
        
        if ($err !== null) {
            
            return $err;
            
        } else {
            if (array_key_exists('marker', $ret)) {
                $marker = $ret["marker"];
            }
           
            return $ret;
        }
    }
    
    public function getFileInfo($key){
        $config = new Config();
        $bucketManager = new BucketManager(self::$auth, $config);
        list($fileInfo, $err) = $bucketManager->stat(self::$bucket, $key);
        if ($err) {
            print_r($err);
        } else {
            print_r($fileInfo);
        }
    }
    
    public function rename($srcKey,$destKey){
        $config = new Config();
        $bucketManager = new BucketManager(self::$auth, $config);
        
        $err = $bucketManager->move(self::$bucket, $srcKey, self::$bucket, $destKey, true);
        
        return $err;
    }
    
    public function move($srcKey, $destBucket, $destKey){
        $config = new Config();
        $bucketManager = new BucketManager(self::$auth, $config);
        
        $err = $bucketManager->move(self::$bucket, $srcKey, $destBucket, $destKey, true);
        
        return $err;
    }
    
    public function copy($srcKey, $destBucket, $destKey){
        $config = new Config();
        $bucketManager = new BucketManager(self::$auth, $config);
        
        $err = $bucketManager->copy(self::$bucket, $srcKey, $destBucket, $destKey, true);
        
        return $err;
    }
    
    public function delete($key){
        $config = new Config();
        $bucketManager = new BucketManager(self::$auth, $config);
        
        $err = $bucketManager->delete(self::$bucket, $key);
        
        return $err;
    }
    
    public function deleteAfterDays($key, $days){
        $config = new Config();
        $bucketManager = new BucketManager(self::$auth, $config);
        
        $err = $bucketManager->deleteAfterDays(self::$bucket, $key, $days);
        
        return $err;
    }
    
    public function deleteBatch($keys){
        $config = new Config();
        $bucketManager = new BucketManager(self::$auth, $config);
        
        //每次最多不能超过1000个
//         $keys = array(
//             'qiniu.mp4',
//             'qiniu.png',
//             'qiniu.jpg'
//         );
        
        $ops = $bucketManager->buildBatchDelete(self::$bucket, $keys);
        list($ret, $err) = $bucketManager->batch($ops);
        if ($err) {
            print_r($err);
        } else {
            print_r($ret);
        }
    }
    
}