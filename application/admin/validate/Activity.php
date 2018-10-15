<?php

namespace app\admin\validate;

use think\Validate;
use think\Db;
use app\common\model\Activity as CommonActivity;

class Activity extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'page' => 'require|integer|gt:0',
        'page_size' => 'require|integer|between:1,100',
        'order_direction' => 'require|integer|in:0,1',
        'order_field' => 'require|in:id,order_sort',

        'id' => 'require|integer',
        'title' => 'require|length:1,50', // |string
        'image' => 'require|array',
        'activity_details' => 'require', // |string
        'activity_rule' => 'require|string',
        'share_details' => 'require|string',
        'start_time' => 'require|date|startTimeCheck',
        'end_time' => 'require|date|endTimeCheck',
        'subject_id' => 'require|integer|subjectIdCheck|subjectIdExist',//|subjectIdExist
//        'reward_setting' => 'require',
        'order_sort' => 'require|integer|gt:-1',
    ];
    /**
     * 提示消息
     */
    protected $message = [
        'page.require' => '非法页码',
        'page.integer' => '非法页码',
        'page.gt' => '非法页码',
        'page_size.require' => '单页条数合法范围1-100整数',
        'page_size.integer' => '单页条数合法范围1-100整数',
        'page_size.between' => '单页条数合法范围1-100整数',
        'order_direction.require' => '非法排序方向',
        'order_direction.in' => '非法排序方向',
        'order_field.require' => '非法排序字段',
        'order_field.in' => '非法排序字段',

        'id.require' => '缺少id',
        'id.integer' => '非法id',
        'title.require' => '缺少标题',
//        'title.string' => '标题必须为字符串',
        'title.length' => '标题合法长度1',
        'image.require' => '必须上传图片',
        'image.array' => '图片必须为字符串',
        'activity_details.require' => '缺少活动详情',
        'activity_details.string' => '活动详情必须为字符串',

        'start_time.require' => '缺少开始时间',
        'start_time.date' => '开始时间格式不正确',
        'start_time.startTimeCheck' => '开始时间不能小于当前时间',
        'end_time.require' => '缺少结束时间',
        'end_time.date' => '结束时间格式不正确',
        'end_time.endTimeCheck' => '结束时间必须大于开始时间',
        'subject_id.require' => '缺少主题id',
        'subject_id.integer' => '主题id格式错误',
        'subject_id.subjectIdExist' => '主题已经关联过活动',
        'subject_id.subjectIdCheck' => '非法的主题id',
//        'reward_setting.require' => '缺少奖励方案',
        'order_sort.require' => '缺少排序参数',
        'order_sort.gt' => '排序必须大于或等于0',
        'share_details.require' => '缺少分享文案',
        'share_details.string' => '分享文案必须为字符串',
        'activity_rule.require' => '缺少活动规则',
        'activity_rule.string' => '活动规则必须为字符串',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'list' => ['page', 'page_size', 'order_direction', 'order_field'],
        'add' => ['title', 'image', 'activity_details', 'start_time', 'end_time', 'subject_id', 'reward_setting', 'order_sort'],
        'edit' => ['title', 'image', 'activity_details', 'order_sort'],
        'editSort' => ['id', 'order_sort'],
    ];

    /**
     * 开始时间检查
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool
     */
    protected function startTimeCheck($value, $rule, $data = [])
    {
        $value = strtotime($value);
        if ($value < time()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 结束时间检查
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool
     */
    protected function endTimeCheck($value, $rule, $data = [])
    {
        if (strtotime($value) > strtotime($data['start_time'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 主题id存在
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool
     */
    protected function subjectIdExist($value, $rule, $data = [])
    {
        $where = [
            'subject_id' => $value,
            'status' => ['<>', CommonActivity::$status['DELETE']]
        ];
        $count = Db::name('activity')->where($where)->count();
        if ($count) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 主题id检查
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool
     */
    protected function subjectIdCheck($value, $rule, $data = [])
    {
        $count = Db::name('subject')->where('id', $value)->count();
        if ($count) {
            return true;
        } else {
            return false;
        }
    }

}
