<?php
namespace wsj\antispam;
class Base
{
    /**
     * 计算参数签名
     * @param $secretKey
     * @param array $params 请求参数
     * @return string
     */
    public static function gen_signature($secretKey, $params){
        ksort($params);
        $buff="";
        foreach($params as $key=>$value){
            if($value !== null) {
                $buff .=$key;
                $buff .=$value;
            }
        }
        $buff .= $secretKey;
        return md5($buff);
    }

    /**
     * 将输入数据的编码统一转换成utf8
     * @param array $params 输入的参数
     * @return array
     */
    public static function toUtf8($params){
        $utf8s = array();
        foreach ($params as $key => $value) {
            $utf8s[$key] = is_string($value) ? mb_convert_encoding($value, "utf8", config('netease.antispam.internal_string_charset')) : $value;
        }
        return $utf8s;
    }
}