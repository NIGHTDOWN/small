<?php

namespace app\admin\validate;

use think\Validate;

class VideoPutPlan extends Validate
{
   protected $rule=[
        'interval_time_min'=>'require|integer|gt:-1',
        'interval_time_max'=>'require|integer|gt:-1|minAndMaxCheck',
        'start_time'=>'require|date|startTimeCheck',
    ];

    protected $message=[
        'interval_time_min.require'=>'间隔最小时间必填',
        'interval_time_min.integer'=>'间隔最小时间错误',
        'interval_time_min.gt'=>'间隔最小时间等于或大于0',
        'interval_time_max.require'=>'间隔最大时间必填',
        'interval_time_max.integer'=>'间隔最大时间错误',
        'interval_time_max.gt'=>'间隔最大时间等于或大于0',
        'interval_time_max.minAndMaxCheck'=>'间隔最小时间不可大于最大时间',
        'start_time.require'=>'开始时间必填',
        'start_time.date'=>'开始时间错误',
        'start_time.startTimeCheck'=>'开始时间必须大于当前时间',
        // 'interval_time_min.require'=>1600,
        // 'interval_time_min.integer'=>1601,
        // 'interval_time_min.gt'=>1601,
        // 'interval_time_max.require'=>1602,
        // 'interval_time_max.integer'=>1603,
        // 'interval_time_max.gt'=>1603,
        // 'interval_time_max.minAndMaxCheck'=>1608,
        // 'start_time.require'=>1605,
        // 'start_time.date'=>1606,
        // 'start_time.startTimeCheck'=>1607,
    ];

    protected $scene=[
        'set'=>['interval_time_min','interval_time_max','start_time'],
    ];

    /**
     * 最小间隔最大间隔检查
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool
     */
    protected function minAndMaxCheck($value, $rule, $data = [])
    {
        if ($data['interval_time_min']>$data['interval_time_max']){
            return false;
        }
        return true;
    }

    /**
     * 开始时间检查
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool
     */
    protected function startTimeCheck($value, $rule, $data = [])
    {
        if (strtotime($value)<time()){
            return false;
        }
        return true;
    }
    
}
