<?php
namespace wsj\ali\market;

/**
 * 证件识别
 * Class DocumentIdentification
 * @package app\common\service
 */
class DocumentIdentification
{
    private $app_code;

    public function __construct()
    {
        $this->app_code=config('ali.market.app_code');
    }

    function curl_request($url,$data,$header=[],$method='POST'){
        try{
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            if (1 == strpos("$".$url, "https://"))
            {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            $info = curl_exec($curl);
        }catch (\Exception $e){
            return false;
        }
        return $info;
    }

    /**
     * 身份证识别
     * @param $file_url
     * @return bool|mixed
     */
    public function idCardDiscern($file_url)
    {
        $api_url='https://ocridcard.market.alicloudapi.com/idimages';
        $header = [
            "Authorization:APPCODE " . $this->app_code,
            "Content-Type".":"."application/x-www-form-urlencoded; charset=UTF-8",
        ];
        $data= 'image='.$file_url;
        return $this->curl_request($api_url,$data,$header);
    }

    /**
     * 身份证验证
     * @param string $code  证件号码
     * @param $name
     * @return bool
     */
    public function idCardVerify($code,$name)
    {
        $api_url='https://idcardcert.market.alicloudapi.com/idCardCert';
        $querys = "idCard=$code&name=$name";
        $url=$api_url.'?'.$querys;
        $header = [
            "Authorization:APPCODE " . $this->app_code,
        ];
        return $this->curl_request($url,[],$header,'GET');
    }
}