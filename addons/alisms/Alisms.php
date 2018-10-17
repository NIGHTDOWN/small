<?php
/**
 * Created by PhpStorm.
 * User: flyn
 * Date: 2018/6/11
 * Time: 16:08
 */
namespace addons\Alisms;

use think\Addons;

use joy\SignatureHelper;
use think\Config;

class Alisms
{
    public static function run(&$param)
    {
        echo '1';
        exit;
//        $url = Config::get('site')['sms_server'];
//        $content = str_replace('{code}', $param['code'],  Config::get('site')['sms_signature'] . Config::get('site')['sms_'.$param['event']]);
        $params = array ();
        $accessKeyId = 'LTAIUhnXx92AdfBT';
        $accessKeySecret = 'IseOzJdK0gi5S5HT9vnlUnKTZP8BlS';
        $domain = 'dysmsapi.aliyuncs.com';
        $params['PhoneNumbers'] = $param['mobile'];
        $params['SignName'] = Config::get('site')['sign_name'];
        $params['TemplateCode'] = Config::get('site')['sms_'.$param['event']];

        // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        switch ($param['event'])
        {
            case 'signup':
                $params['TemplateParam'] = Array (
                    "code" => $param['code']
                );
                break;
            default:
                $params['TemplateParam'] = Array (
                    "content" => $param['code']
                );
        }

        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }

        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();

        // 此处可能会抛出异常，注意catch
        $result = $helper->request(
            $accessKeyId,
            $accessKeySecret,
            $domain,
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            ))
        // fixme 选填: 启用https
        // ,true
        );
        if($result)
        {
            if($result->Code == 'OK')
            {
                return true;
            }else {
                return false;
            }
        }else{
            return false;
        }
    }
}