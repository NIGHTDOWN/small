<?php
namespace wsj\antispam;
/**
 * 图片
 * Class Image
 * @package WSJ\antispam
 */
class Image extends Base
{
    //在线检测api请求地址
    private static $checkApiUrl='https://as.dun.163yun.com/v3/image/check';
    //在线检测api版本
    private static $checkApiVersion='v3.1';

    /**
     * 在线检测
     * @param array $images 包含图片信息的一维数组
     * @param int $type  1表示传图片url检查  2表示传图片base64编码进行检查
     * @return array|mixed
     */
    public static function check($images,$type=1){
        $params['images']=[];
        foreach ($images as $image){
            $params['images'][]=[
                'name'=>$image,
                'type'=>$type,
                'data'=>$image,
            ];
        }
        $params['images']=json_encode($params['images']);

        $antispamConfig=config('netease.antispam');
        $params["secretId"] = $antispamConfig['secret_id'];
        $params["businessId"] = $antispamConfig['image_business_id'];
        $params["version"] = self::$checkApiVersion;
        $params["timestamp"] = sprintf("%d", round(microtime(true)*1000));// time in milliseconds
        $params["nonce"] = sprintf("%d", rand()); // random int

        $params = self::toUtf8($params);
        $params["signature"] = self::gen_signature($antispamConfig['secret_key'], $params);

        $options = array(
            "http" => array(
                "header"  => "Content-type: application/x-www-form-urlencoded\r\n",
                "method"  => "POST",
                "timeout" => $antispamConfig['api_timeout'], // read timeout in seconds
                "content" => http_build_query($params),
            ),
        );
        $context  = stream_context_create($options);
        $result = file_get_contents(self::$checkApiUrl, false, $context);
        if($result === FALSE){
            return array("code"=>500, "msg"=>"file_get_contents failed.");
        }else{
            return json_decode($result, true);
        }
    }
}