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
    
    private static $callbackUrl = "http://ppshny.natappfree.cc/basic/web/index.php/qiniu/uploadcallback";
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
    
    /**
     * 获取auth
     * @return \Qiniu\Auth
     */
    public function getAuth(){
        return self::$auth;
    }
    
    /**
     * 获取buckets列表
     * @return \Qiniu\Storage\string[]
     */
    public function getBuckets(){
        $bucketManager = new BucketManager(self::$auth);
        return $bucketManager->buckets();
    }
    
    /**
     * 获取buckets绑定domains
     * @return \Qiniu\Storage\string[]
     */
    public function getDomains(){
        $bucketManager = new BucketManager(self::$auth);
        return $bucketManager->domains(self::$bucket);
    }
    
    /**
     * 获取回调上传的策略
     * @param unknown $callbackUrl
     * @param unknown $callbackBody
     * @return string[]|mixed[]
     */
    public function getCallbackPolicy($callbackUrl=null,$callbackBody=null){
        
        $policy = array(
            'callbackUrl' => empty($callbackUrl)?self::$callbackUrl:$callbackUrl,
            'callbackBody' => empty($callbackBody)?self::$returnBody:$callbackBody,
        );
        
        return $policy;
    }
    
    /**
     * 获取带返回值的上传策略
     * @param unknown $returnBody
     * @return string[]
     */
    public function getReturnBodyPolicy($returnBody=null){
        
        $policy = array(
            'returnBody' => empty($returnBody)?self::$returnBody:$returnBody,
        );
        
        return $policy;
    }
    
    /**
     * 获取上传token
     * @param unknown $policy
     * @param number $expires
     * @param unknown $key
     * @return string
     */
    public function getToken($policy = null, $expires = 3600, $key = null){
        
        $token = self::$auth->uploadToken(self::$bucket, $key, $expires, $policy);
        
        return $token;
    }
    
    /**
     * 上传文件
     * @param unknown $token 
     * @param unknown $key 文件保存名
     * @param unknown $filePath
     * @return number[]|string[]|unknown[]|array[]
     */
    public function upload($token, $key=null, $filePath){
        $uploadMgr = new UploadManager();
   
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        
        $result = array("success"=>1,"data"=>"");
        
        if ($err !== null) {
            $result["success"]=0;
            $result["data"]=$err;
            //return $err;
        } else {
            $result["data"]=$ret;
            //return $ret;
        }
        
        return $result;
    }
    
    /**
     * 验证是否七牛云回调
     * @param unknown $contentType
     * @param unknown $authorization
     * @param unknown $callbackBody
     * @return number[]|string[]
     */
    public function verifyCallback($contentType, $authorization, $callbackBody){
        
        $isQiniuCallback = self::$auth->verifyCallback($contentType, $authorization, self::$callbackUrl, $callbackBody);
        
        if ($isQiniuCallback) {
            $result = array("success"=>1,"data"=>"");
        } else {
            $result = array("success"=>0,"data"=>"");
        }
        
        return $result;
    }
    
    /**
     * 获取私有下载地址
     * @param unknown $baseUrl
     * @param number $expires
     * @return string
     */
    public function getPrivateDownloadUrl($baseUrl, $expires = 3600){
        return self::$auth->privateDownloadUrl($baseUrl, $expires);
    }
    
    /**
     * 列举bucket中的文件
     * @param string $prefix
     * @param string $marker
     * @param number $limit
     * @return array
     */
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
        
        $ops = $bucketManager->buildBatchDelete(self::$bucket, $keys);
        list($ret, $err) = $bucketManager->batch($ops);
        
        if ($err) {
            print_r($err);
        } else {
            print_r($ret);
        }
    }
    
}