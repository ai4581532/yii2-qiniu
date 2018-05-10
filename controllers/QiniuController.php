<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;

use Qiniu\Auth;
use app\models\FilesInfo;
use app\components\QiniuOSS;

class QiniuController extends Controller{
    
    public $enableCsrfValidation = false;
    
    public function actionTest(){
        //防盗链
        //1.使用私有空间，设置超时链接
        //2.配合七牛cdn
        
        //裁剪图片
        http://oy9b1m52u.bkt.clouddn.com/sfsfs?imageView2/1/w/100/h/100

        //高级处理
        
        //已有的图片迁移的问题
        
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
    public function actionGettoken(){
        $result = array("success"=>1,"data"=>"");
        
        $qiniu = new QiniuOSS();
        
        $policy =$qiniu->getPolicy();
        
        $token = $qiniu->getToken(null, 3600, $policy);
        
        $result["data"] = $token;
        
        //return json_encode($result);
        return $token;
    }
    
    /**
     * 后端上传
     * @return string
     */
    public function actionUpload(){
        $qiniu = new QiniuOSS();
        
        $policy = array(
            //'callbackUrl' => 'http://yju565.natappfree.cc/basic/web/index.php/qiniu/uploadcallback',
            //'callbackBody' => 'appid=123&filename=$(fname)&filesize=$(fsize)&key=$(key)&hash=$(etag)&mimeType=$(mimeType)&w=$(imageInfo.width)&h=$(imageInfo.height)', 
            'returnBody' => '{"appid":123,"filename":$(fname),"filesize":$(fsize), "key": $(key), "hash": $(etag), "w": $(imageInfo.width), "h": $(imageInfo.height)}' 
        );
        
        $token = $qiniu->getToken(null, 3600, $policy);
        
        // 要上传文件的本地路径
        $filePath = 'C:/Users/Public/Pictures/Sample Pictures/20170831153444178.png';
        // 上传到七牛后保存的文件名
        $key = '20170831153444178.png';
        
 
        // 调用 UploadManager 的 putFile 方法进行文件的上传。
        $res = $qiniu->upload($token, $key, $filePath);
 
        return json_encode($res);
            
        $filesInfo = new FilesInfo();
        
        $filesInfo->appid = $ret["appid"];
        $filesInfo->fname = $ret["filename"];
        $filesInfo->fkey = $ret["key"];
        
        $filesInfo->save();
        
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
        
        $request = Yii::$app->request;
        
        $appid = $request->post('appid'); 
        $filename = $request->post('filename'); 
        $filesize = $request->post('filesize');
        $fkey = $request->post('key');
        
        $params = $request->bodyParams;
        
        //获取回调的body信息
        $callbackBody = file_get_contents('php://input');
 
        
        echo json_encode(array('ret' => $params));exit();
        
        //回调的contentType
        $contentType = 'application/x-www-form-urlencoded';
        
        //回调的签名信息，可以验证该回调是否来自七牛
        $authorization = "";
        
        if(!empty($_SERVER['HTTP_AUTHORIZATION'])){
            $authorization = $_SERVER['HTTP_AUTHORIZATION'];
        }else{
            $resp = array('ret' => 'no HTTP_AUTHORIZATION!');
            return json_encode($_SERVER[]);
        }
        
        //七牛回调的url，具体可以参考：http://developer.qiniu.com/docs/v6/api/reference/security/put-policy.html
        $url = 'http://yju565.natappfree.cc/basic/web/index.php/qiniu/uploadcallback';
        
        $isQiniuCallback = $auth->verifyCallback($contentType, $authorization, $url, $callbackBody);
        
        if ($isQiniuCallback) {
            
            $filesInfo = new FilesInfo();
            
            $filesInfo->appid = $appid;
            $filesInfo->fname = $filename;
            $filesInfo->fkey = $fkey;
            
            $filesInfo->save();
            
            $resp = array('ret' => 'success');
        } else {
            $resp = array('ret' => 'failed');
        }
        
        return json_encode($resp);
    }
    
    
    public function actionRename(){
        
    }
    
    public function actionRemove(){
        
    }
    
    public function actionCopy(){
        
    }
    
    
    
    
}
 
 