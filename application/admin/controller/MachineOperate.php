<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * app机器操作记录管理
 *
 * @icon fa fa-circle-o
 */
class MachineOperate extends Backend
{
    
    /**
     * Machineoperate模型对象
     * @var \app\admin\model\MachineOperate
     */
    protected $model = null;

    /**
     * 快速搜索时执行查找的字段
     */
    protected $searchFields = 'day_time, version_id';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\MachineOperate;

    }

}
