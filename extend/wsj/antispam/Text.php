<?php
namespace wsj\antispam;
/**
 * 文本反垃圾
 * Class Test
 * @package WSJ\antispam
 */
class Text extends Base
{
    //在线检测api请求地址
    private static $checkApiUrl='https://as.dun.163yun.com/v3/text/check';
    //在线检测api版本
    private static $checkApiVersion='v3.1';

    /**
     * 在线检测
     * @param string $dataId  数据唯一标识
     * @param string $content 内容
     * @param int $dataType 子数据类型
     * @param string $ip 用户IP地址
     * @param string $account 用户唯一标识
     * @param int $deviceType  用户设备类型，1：web， 2：wap， 3：android， 4：iphone， 5：ipad， 6：pc， 7：wp
     * @param string $deviceId  用户设备 id
     * @param string $callback  数据回调参数
     * @return array|mixed
     */
    public static function check($dataId,$content,$dataType=0,$ip='',$account='',$deviceType=0,$deviceId='',$callback=''){
        $params['dataId']=$dataId;
        $params['content']=$content;

        $antispamConfig=config('netease.antispam');
        $params["secretId"] = $antispamConfig['secret_id'];
        $params["businessId"] = $antispamConfig['text_business_id'];
        $params["version"] = self::$checkApiVersion;
        $params["timestamp"] = sprintf("%d", round(microtime(true)*1000));// time in milliseconds
        $params["nonce"] = sprintf("%d", rand()); // random int

        $params = self::toUtf8($params);
        $params["signature"] = self::gen_signature($antispamConfig['secret_key'], $params);
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'timeout' => $antispamConfig['api_timeout'], // read timeout in seconds
                'content' => http_build_query($params),
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
