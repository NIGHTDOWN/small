<?php

namespace app\admin\controller\site\banner;

use app\common\controller\Backend;

use app\admin\model\SiteBanner as SiteBanner;

/**
 * 官网轮播图类别
 *
 * @icon fa fa-circle-o
 */
class Type extends Backend
{
    
    /**
     * Site_banner_type模型对象
     * @var \app\admin\model\Site_banner_type
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\SiteBannerType;

    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $count = $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $count = SiteBanner::TypeDel($ids);
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

}
