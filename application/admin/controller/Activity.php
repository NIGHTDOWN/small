<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\Activity as CommonActivity;

/**
 * 活动列表
 *
 * @icon fa fa-circle-o
 */
class Activity extends Backend
{

    /**
     * Activity模型对象
     * @var \app\admin\model\Activity
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Activity;

    }

    /**
     * 列表
     */
    public function index()
    {
//        $param = $this->request->request(['page' => 1, 'page_size' => 20, 'order_direction' => 1, 'order_field' => 'id', 'keyword' => '']);

//        // 校验数据 TODO
//        $valRes = $this->validate($param, 'Activity.list');
//        if ($valRes !== true) {
//            $this->error($valRes);
//        }
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams('a.title');
            $param = [];
            $param['order_field'] = $sort;
            $param['order_direction'] = $order;
            $param['offset'] = $offset;
            $param['page_size'] = $limit;
            // 列表
            $model = model('Activity');
            $list = $model->getList($param, $where);
            $result = array("total" => $list['total'], "rows" => $list['data']);
            return json($result);
        }

        return $this->fetch();
    }

    /**
     * 新增
     */
    public function add()
    {
        if ($this->request->isPost()) {
            // 接受数据
            $param = $this->request->only(['title', 'activity_details', 'share_details', 'start_time', 'end_time',
                'subject_id', 'order_sort', 'activity_rule', 'cover_image', 'image']);
            $paramReward = $this->request->only(['video_apply_open', 'video_like_open', 'video_play_open',
                'video_apply_val', 'video_like_val', 'video_play_val']);
            $param['image'] = !empty($param['image']) ? explode(',', $param['image']) : [];
            $param['create_admin_id'] = $this->auth->getUserinfo()['id'];

            // 数据校验
            $checkRes = $this->validate($param, 'Activity.add');
            if ($checkRes !== true) {
                $this->error($checkRes);
            }

            // 奖励方案
            $rewardOption = [
                'video_apply_open' => ['video_apply_val', 'video_apply'],
                'video_like_open' => ['video_like_val', 'video_like'],
                'video_play_open' => ['video_play_val', 'video_play'],
            ];
            $rewardSetting = [
                'video_apply' => ['is_open' => 0, 'reward_number' => 0],
                'video_like' => ['is_open' => 0, 'reward_number' => 0],
                'video_play' => ['is_open' => 0, 'reward_number' => 0]
            ];
            $haveReward = false;
            foreach ($paramReward as $key => $val) {
                foreach ($rewardOption as $k => $v) {
                    if ($key == $k && $val > 0) {
                        $rewardSetting[$v[1]]['is_open'] = 1;
                        if (!isset($paramReward[$v[0]]) || empty($paramReward[$v[0]])) {
                            $this->error(__('Open option that must have value'));
                        } else {
                            $rewardSetting[$v[1]]['reward_number'] = $paramReward[$v[0]];
                        }
                        $haveReward = true;
                    }
                }
            }
            $param['reward_setting'] = json_encode($rewardSetting);
            if (!$haveReward) {
                $this->error('缺少奖励方案');
            }

            // 请求数据
            if (!empty($param)) {
                $model = model('Activity');
                $result = $model->add($param);
                if (!$result) {
                    $this->error($model->getError());
                } else {
                    $this->success();
                }
            }
        }

        $this->assign('reward_setting_arr', [1 => '评论排行', 2 => '点赞排行', 3 => '播放排行']);
        return $this->fetch();
    }

    /**
     * 删除
     * @param string $ids
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function del($ids = "")
    {
        if (empty($ids)) {
            $this->error(__('Invalid parameters'));
        }
        $model = model('Activity');
        $result = $model->del($ids, $this->auth->getUserInfo()['id']);
        if (!$result) {
            $this->error(__($model->getError()));
        } else {
            $this->success();
        }
    }

    /**
     * 编辑
     * @param null $ids
     * @return string
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function edit($ids = NULL)
    {
        if (empty($ids)) {
            $this->error(__('Invalid parameters'));
        }
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $param = $this->request->only(['title', 'activity_details', 'share_details', 'start_time', 'end_time',
                'subject_id', 'order_sort', 'activity_rule', 'cover_image', 'image']);
            $param['image'] = !empty($param['image']) ? explode(',', $param['image']) : [];

            // 数据校验
            $checkRes = $this->validate($param, 'Activity.edit');
            if ($checkRes !== true) {
                $this->error($checkRes);
            }

            // 保存数据
            if (!empty($param)) {
                $param['id'] = $ids;
                $param['update_time'] = time();
                $param['last_edit_admin_id'] = $this->auth->getUserInfo()['id'];
                $param['image'] = serialize($param['image']);
                $model = model('Activity');
                $result = $model->edit($param);
                if (!$result) {
                    $this->error($model->getError());
                } else {
                    $this->success();
                }
            }
        }

        // 查询该活动信息
        $model = model('Activity');
        $row = $model->getRow($ids);
        if (!$row) {
            $this->error(__($model->getError()));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 主题选择列表
     */
    public function subjectSelectList()
    {
        if ($this->request->isAjax()) {
            $param['page'] = input('pageNumber') - 1;
            $param['page_size'] = input('pageSize');
            $param['keyword'] = input('subject_name');
            $param['selected'] = input('keyValue');
            $data = model('Subject')->selectList($param);
            return json($data);
        }
    }

    /**
     * 编辑排序 TODO 方法名
     * @param null $ids
     * @return string
     * @throws \think\Exception
     */
    public function edit_sort($ids = null)
    {
        if ($this->request->isPost()) {
            $param = $this->request->only(['order_sort']);
            if (!$ids || empty($param['order_sort'])) {
                $this->error('排序必须大于0');
            }
            $param['id'] = $ids;
            $model = model('Activity');
            $param['update_time'] = time();
            $param['last_edit_admin_id'] = $this->auth->getUserInfo()['id'];
            $model->editSort($param);
            $this->success();
        }
        return $this->view->fetch();
    }

    /**
     * 显示/隐藏
     * @param string $ids
     * @throws \think\Exception
     * @throws \think\exception\PDOException+
     */
    public function multi($ids = '')
    {
        if (empty($ids)) {
            $this->error(__('Invalid parameters'));
        }

        $param = input('params');
        if (strpos($param, 'status=1') !== false) {
            return $this->show($ids);
        } elseif (strpos($param, 'status=0') !== false) {
            return $this->hide($ids);
        }
    }

    /**
     * 隐藏
     * @param $ids
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function hide($ids)
    {
        if (empty($ids)) {
            $this->error(__('Invalid parameters'));
        }
        $model = model('Activity');
        $data = [
            'status' => CommonActivity::$status['HIDE'],
            'last_edit_admin_id' => $this->auth->getUserInfo()['id'],
            'update_time' => time(),
        ];
        $res = $model->hide($ids, $data);
        if ($res) {
            $this->success();
        } else {
            $this->error(__($model->getError()));
        }
    }

    /**
     * 显示
     * @param $ids
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function show($ids)
    {
        if (empty($ids)) {
            $this->error(__('Invalid parameters'));
        }
        $data = [
            'last_edit_admin_id' => $this->auth->getUserInfo()['id'],
            'update_time' => time(),
            'status' => CommonActivity::$status['DISPLAY']
        ];
        $model = model('Activity');
        $res = $model->show($ids, $data);
        if ($res) {
            $this->success();
        } else {
            $this->error(__($model->getError()));
        }
    }
}
