<?php

namespace app\common\model;


use app\common\controller\Backend;

class AppVersion extends Backend
{
    const STATUS=[
        'DELETE'=>-1,
        'CLOSE'=>0,
        'OPEN'=>1,
    ];

    const STATUS_TEXT = [
        -1 => '删除',
        0  => '关闭',
        1  => '开启',
    ];

    const CHANNELS = [
        'alpha' => '官网',
//        'tencent' => '腾讯',
//        'baidu' => '百度',
//        'app360' => '360',
//        'anzhi' => '安智',
//        'wandoujia' => '豌豆荚',
//        'hauwei' => '华为',
//        'xiaomi' => 'vivo',
//        'vivo' => 'oppo',
//        'meizu' => '魅族',
//        'lenovo' => '联想',
//        'chuizi' => '锤子',
//        'leshi' => '乐视',
    ];
}