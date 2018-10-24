<?php

namespace app\admin\validate;

use think\Validate;
use app\common\model\Mission as MissionCommonModel;

class Mission extends Validate
{
    protected $field=[
        'mission_group'=>'任务组',
        'title'=>'任务名',
        'mission_explain'=>'任务说明',
        'mission_tag'=>'任务标签',
        'repeat_type'=>'类型',
        'bonus_setting'=>'奖励设置',
        'bonus_limit'=>'奖励上限',
        'quantity_condition'=>'数量条件',
        'status'=>'状态',
    ];

    /**
     * 验证规则
     */
    protected $rule = [
        'mission_group'=>'require',
        'title'=>'require',
//        'mission_explain'=>'',
        'mission_tag'=>'require',
        'repeat_type'=>['require','in'=>MissionCommonModel::REPEAT_TYPE],
        'bonus_setting'=>['require','regex'=>'^(100|[\d][\d]?)\*[\d]+-[\d]+$'],
        'quantity_condition'=>'require|integer',
        'status'=>['require','in'=>MissionCommonModel::STATUS],
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
        'add'  => ['mission_group','title','mission_explain','mission_tag','repeat_type','bonus_setting','bonus_limit','quantity_condition','status'],
        'edit' => ['title','mission_explain','bonus_setting','bonus_limit','quantity_condition','status'],
    ];
    
}
