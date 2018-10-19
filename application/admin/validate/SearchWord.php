<?php

namespace app\admin\validate;

use think\Validate;
use app\common\model\SearchWord as SearchWordCommonModel;

class SearchWord extends Validate
{
    protected $field=[
        'word'=>'词条',
        'order_sort'=>'排序',
        'status'=>'状态',
    ];

    /**
     * 验证规则
     */
    protected $rule = [
        'word'=>'require|unique:search_word',
        'order_sort'=>['require','in'=>SearchWordCommonModel::ORDER_SORT],
        'status'=>['require','in'=>SearchWordCommonModel::STATUS],
    ];
    /**
     * 提示消息
     */
    protected $message = [
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => ['word','order_sort','status'],
    ];
    
}
