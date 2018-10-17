<?php
/**
 * Created by PhpStorm.
 * User: flyn
 * Date: 2018/6/17
 * Time: 16:08
 */
namespace addons\Qnupload;

use think\Addons;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class Qnupload
{
    public static function run(&$param)
    {
        $bucket = Config('site.' .$param['type']. '_bucket');
        $accessKey = Config('site.qiniu_access_key');
        $secretKey = Config('site.qiniu_secret_key');
        $auth = new Auth($accessKey, $secretKey);
        $token = $auth->uploadToken($bucket);
        $key = preg_replace("/\//", "", $param['url'], 1);
        $uploadMgr = new UploadManager();
        $upload_data = [
            $token,
            $key,
            ROOT_PATH . 'public/' . $param['url']
        ];
        call_user_func_array(array($uploadMgr, 'putFile'), $upload_data);
        unlink(ROOT_PATH . 'public/' . $param['url']);
    }
}