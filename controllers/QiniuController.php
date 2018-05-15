<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;

use app\models\FilesInfo;
use app\components\QiniuOSS;

class QiniuController extends Controller{
    
    public $enableCsrfValidation = false;
    
    public function actionTest(){
        //防盗链
        //1.使用私有空间，设置超时链接
        //2.配合七牛cdn
        
        // 裁剪图片
        // http://oy9b1m52u.bkt.clouddn.com/sfsfs?imageView2/1/w/100/h/100
        // 获取图片基本信息
        // http://oy9b1m52u.bkt.clouddn.com/sfsfs?imageInfo
        
        //高级处理
        
        //已有的图片迁移的问题
 
        $sizearr = array(0.3515625,
            369356.77734375,
            4279405.0488281,
            4.7900390625,
            3024.4765625,
            40624.291015625,
            5132.740234375,
            12338222.097656,
            331.1181640625,
            1901.2138671875,
            2922.90625,
            6077.8623046875,
            241.9228515625,
            316.5595703125,
            589448.57714844,
            138.8603515625,
            959274.48632812,
            6145.908203125,
            20152.34375,
            4132261.7783203,
            361397.21191406,
            160.724609375,
            0,
            3114901.7304688,
            3358486.1757812,
            310639.51757812,
            505476.07421875,
            79088.189453125,
            45.39453125,
            122.216796875,
            106276.40234375,
            7454083.46875,
            0.5263671875,
            25089030.040039,
            10077.748046875,
            15859.92382812,
            
            138936.32128906,
            1634.4638671875,
            
            7896724.3662109,
            17555.54296875,
            1881.3623046875,
            47639148.931641,
            1140.7109375,
            56225.249023438,
            1011.958984375,
            
        );
        
        $sum = 0;
        foreach ($sizearr as $key => $value) {
            $sum +=$value;
        }
        
        return $sum/1024/1024;
    }
    
    public function actionIndex(){
        
//         return $this->render('index');

        return $this->renderPartial('index.html');
    }
    
    /**
     * 获取上传token
     * @return string
     */
    public function actionGettoken(){
        $result = array("success"=>1,"data"=>"");
        
        $qiniu = new QiniuOSS();
        
        $policy =$qiniu->getCallbackPolicy();
        
        $token = $qiniu->getToken(null, 3600, $policy);
        
        $result["data"] = $token;
        
        return json_encode($result);
         
    }
    
    /**
     * 后端上传
     * @return string
     */
    public function actionUpload(){
        
        // 要上传文件的本地路径
        $filePath = 'C:/Users/Public/Pictures/Sample Pictures/20170831153444178.png';
        // 上传到七牛后保存的文件名
        $key = '20170831153444178.png';
        
        $qiniu = new QiniuOSS();
        
        $policy = $qiniu->getReturnBodyPolicy();
        $token = $qiniu->getToken(null, 3600, $policy);
        
        $res = $qiniu->upload($token, $key, $filePath);
 
//         $filesInfo = new FilesInfo();
        
//         $filesInfo->appid = $res["appid"];
//         $filesInfo->fname = $res["filename"];
//         $filesInfo->fkey = $res["key"];
        
//         $filesInfo->save();
        
        return json_encode($res);
    }
    
    /**
     * 上传成功回调
     */
    public function actionUploadcallback(){
        
        $request = Yii::$app->request;
        
        $appid = $request->post('appid'); 
        $filename = $request->post('filename'); 
        $filesize = $request->post('filesize');
        $fkey = $request->post('key');
        
        $params = $request->bodyParams;
        
        //获取回调的body信息
        $callbackBody = file_get_contents('php://input');
 
        
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
 
        $qiniu = new QiniuOSS();
        $res = $qiniu->verifyCallback($contentType, $authorization, $callbackBody);
        
        $filesInfo = new FilesInfo();
        
        $filesInfo->appid = $appid;
        $filesInfo->fname = $filename;
        $filesInfo->fkey = $fkey;
        
        $filesInfo->save();
        
        if ($res) {
            $resp = array('ret' => 'success');
        } else {
            $resp = array('ret' => 'failed');
        }
        
        return json_encode($resp);
    }
    
    public function actionGetbuckets(){
        $qiniu = new QiniuOSS();
        $res = $qiniu->getBuckets();
        
        return json_encode($res);
    }
    
    public function actionGetFileList(){
        $qiniu = new QiniuOSS();
        $res = $qiniu->GetFileList();
        
        return json_encode($res);
    }
    
    
}
 
 