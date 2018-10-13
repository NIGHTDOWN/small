<?php
namespace wsj\ali;
use Mrgoon\AliSms\AliSms as MrgoonAliSms;
class AliSms
{
    private static $aliSms;

    /**
     * 发送短信
     * @param $mobile
     * @param $template_code
     * @param array $data
     * @param null $config
     * @param string $outId
     * @return bool
     */
    public static function sendSms($mobile,$template_code,$data=[],$config = null, $outId = '')
    {
        if (!(self::$aliSms instanceof MrgoonAliSms)){
            self::$aliSms=new MrgoonAliSms();
        }
        if (!$config){
            $config=config('ali.sms');
        }
        $response=self::$aliSms->sendSms($mobile,$template_code,$data,$config,$outId);
        return $response && $response->Code == 'OK' && $response->Message == 'OK' ? true : false;
    }
}