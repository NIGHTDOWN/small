<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/29
 * Time: 9:31
 */

namespace app\common\model;
use think\Model;

/**
 * 主页轮播图管理模型
 * Class SiteBanner
 * @package app\well\model
 */
class SiteBanner extends Model
{

    const STATUS = [
        'DISABLED' => 0,
        'ENABLED' => 1,
        'DELETE' => 2
    ];

    const STATUS_TXET = [
        0 => '已关闭',
        1 => '已启用',
        2 => '已删除'
    ];

    const TYPE_STATUS = [
        'DISABLED' => 0,
        'ENABLED' => 1,
        'DELETE' => 2
    ];

    const TYPE_STATUS_TXET = [
        0 => '已关闭',
        1 => '已启用',
        2 => '已删除'
    ];

    const CLIENT_TYPE = [
        'MOBILE' => 0,
        'PC' => 1,
    ];

    const CLIENT_TYPE_TEXT = [
        0 => '手机',
        1 => 'PC',
    ];

}