<?php

namespace app\common\model;

use think\Model;
use think\Db;

/**
 * 分类模型
 */
class Category Extends Model
{

    const STATUS = [
        'show' => 1,
        'hide' => 0,
        'delete' => -1
    ];

    const STATUS_TEXT = [
        -1 => '正常',
        0 => '隐藏',
        1 => '已禁用'
    ];

    /**
     * 读取分类列表
     * @param string $type      指定类型
     * @param string $status    指定状态
     * @return array
     */
    public static function getCategoryArray($type = NULL, $status = NULL)
    {
        $list = collection(self::where(function($query) use($type, $status) {
                    if (!is_null($type))
                    {
                        $query->where('type', '=', $type);
                    }
                    if (!is_null($status))
                    {
                        $query->where('status', '=', $status);
                    }
                })->order('weigh', 'desc')->select())->toArray();
        return $list;
    }
}
