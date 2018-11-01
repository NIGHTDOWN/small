<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/28
 * Time: 8:50
 */
namespace app\common\job;

use app\admin\model\CashWithdraw;
use think\queue\Job;

class CashOrderPay
{
    public function fire(Job $job, $data){

        if ($data['order_id']) {
            $res = CashWithdraw::pay($data['order_id']);
        }
        //执行成功,删除这个任务
        $job->delete();
    }

    /**
     * 任务失败处理
     * (任务执行次数大于命令行 --tries 设置次数)
     * @param $data
     */
    public function failed($data){


    }
}