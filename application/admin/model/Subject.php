<?php

namespace app\admin\model;

use think\Model;
use think\Db;
use app\common\model\Subject as CommonSubject;

class Subject extends Model
{
    // 表名
    protected $name = 'subject';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;

    // 追加属性
    protected $append = [
        'new_join_time_text',
        'create_time_text',
        'update_time_text'
    ];


    /**
     * 状态
     * @var array
     */
    public static $status = [
        'DELETE' => -1,
        'HIDE' => 0,
        'SHOW' => 1,
    ];

    /**
     * 状态
     * @var array
     */
    public static $statusText = [
        -1 => '删除',
        0 => '隐藏',
        1 => '显示',
    ];

    public function getNewJoinTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['new_join_time']) ? $data['new_join_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getUpdateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['update_time']) ? $data['update_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setNewJoinTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setUpdateTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    /**
     * 选择列表
     * @param $param
     * @return array
     * @throws \think\exception\DbException
     */
    public function selectList($param)
    {
        $where = [];
        if (isset($param['selected'])) {
            // 选中的
            $where['id'] = $param['selected'];
        } else {
            // 初始化的
            $where['status'] = self::$status['SHOW'];
            if (isset($param['keyword']) && !empty($param['keyword'])) {
                $where['subject_name'] = ['like', '%' . trim($param['keyword']) . '%'];
            }
        }

        // 数据
        $total = $this->where($where)->count();
        $list = $this
            ->field(['id', 'subject_name'])
            ->where($where)
            ->order('weight', 'desc')
            ->limit($param['page'] * $param['page_size'], $param['page_size'])
            ->select() ?: [];

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 列表
     * @param $param
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList($param, $where = [])
    {
        //组装条件
        if (!empty($param['keyword'])) {
            $where['a.subject_name'] = ['like', '%' . trim($param['keyword']) . '%'];
        }
        if (isset($param['status']) && $param['status'] !== '') {
            $where['a.status'] = $param['status'];
        }

        //数据查询
        $total = $this->alias('a')->where($where)->count();
        $list = $this
            ->alias('a')
            ->field(['a.id', 'a.subject_name', 'a.create_user_id', 'a.compere_user_id', 'a.weight', 'a.recommend',
                'a.video_total', 'a.new_join_time', 'a.create_time', 'a.update_time', 'a.status',
                'a.status as status_text'])
            ->where($where)
            ->order($param['order_field'], $param['order_direction'])
            ->limit($param['offset'], $param['page_size'])
            ->select();

        //是否为热门
        $subject_id = array_column($list, 'id');
        $hot_subject_id = Db::name('hot_subject')
            ->where('subject_id', 'in', $subject_id)
            ->column('subject_id', 'subject_id');
        //查询用户
        $user_ids = array_filter(array_unique(array_merge(
            array_column($list, 'create_user_id'),
            array_column($list, 'compere_user_id')
        )));
        $user_nicknames = Db::name('user')
            ->where('id', 'in', $user_ids)
            ->column('nickname', 'id');

        //获取
        $subject_extends = Db::name('subject_extend')
            ->where('subject_id', 'in', $subject_id)
            ->column('cover_img_1,cover_img_2,cover_img_3,cover_img_4', 'subject_id');

        //数据处理
        foreach ($list as $key => $value) {
            $list[$key]['create_user_nickname'] = $user_nicknames[$value['create_user_id']] ?? '';
            $list[$key]['compere_user_nickname'] = $user_nicknames[$value['compere_user_id']] ?? '';
            $list[$key]['is_hot'] = isset($hot_subject_id[$value['id']]) ? 1 : 0;
            $list[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
            $list[$key]['update_time'] = date('Y-m-d H:i:s', $value['update_time']);
            $list[$key]['new_join_time'] = $value['new_join_time'] ? date('Y-m-d H:i:s', $value['new_join_time']) : '';
            $list[$key]['status_text'] = self::$statusText[$value['status']];

            $list[$key]['video_cover_img_list'] = [];
            if (isset($subject_extends[$value['id']])) {
//                if ($subject_extends[$value['id']]['cover_img_1']) {
//                    $list[$key]['video_cover_img_list'][] = CommonSubject::getCoverImgUrl($subject_extends[$value['id']]['cover_img_1']);
//                }
//                if ($subject_extends[$value['id']]['cover_img_2']) {
//                    $list[$key]['video_cover_img_list'][] = CommonSubject::getCoverImgUrl($subject_extends[$value['id']]['cover_img_2']);
//                }
//                if ($subject_extends[$value['id']]['cover_img_3']) {
//                    $list[$key]['video_cover_img_list'][] = CommonSubject::getCoverImgUrl($subject_extends[$value['id']]['cover_img_3']);
//                }
//                if ($subject_extends[$value['id']]['cover_img_4']) {
//                    $list[$key]['video_cover_img_list'][] = CommonSubject::getCoverImgUrl($subject_extends[$value['id']]['cover_img_4']);
//                }
            }
        }

        return ['total' => $total, 'rows' => $list];
    }

    /**
     * 添加
     * @param $data
     * @return bool
     */
    public function add($data)
    {
        $data['subject_name'] = trim($data['subject_name']);
        $data['create_time'] = time();
        $data['update_time'] = $data['create_time'];
        $res = $this->save($data);
        if (!$res) {
            $this->error = '新增失败';
            return false;
        }
        return true;
    }

    /**
     * 编辑
     * @param $data
     * @param $ids
     * @return bool
     */
    public function edit($data, $ids)
    {
        $data['subject_name'] = trim($data['subject_name']);
        $data['update_time'] = time();

        $res = $this->where(['id' => $ids])->update($data);
        if (!$res) {
            $this->error = '更新失败';
            return false;
        }
        return true;
    }

    /**
     * 获取单条数据
     * @param $ids
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRow($ids)
    {
        $data = $this->field(['id', 'subject_name', 'status'])
            ->where(['id' => $ids])
            ->find() ?: [];
        return $data;
    }

}
