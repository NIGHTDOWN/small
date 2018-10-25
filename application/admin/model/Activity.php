<?php

namespace app\admin\model;

use think\Model;
use think\Db;
use think\Cache;
use app\common\model\Activity as CommonActivity;
use wsj\WQiniu;
use think\Session;


/**
 * 活动模型
 * Class Activity
 * @package app\admin\model
 */
class Activity extends Model
{
    // 表名
    protected $name = 'activity';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;

    // 追加属性
    protected $append = [
        'start_time_text',
        'end_time_text',
        'create_time_text',
        'update_time_text'
    ];

    public function getStartTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['start_time']) ? $data['start_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getEndTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['end_time']) ? $data['end_time'] : '');
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

    protected function setStartTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setEndTimeAttr($value)
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
     * 列表
     * @param $param
     * @param array $map
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList($param, $map = [])
    {
        $where = [];
        $where['a.status'] = ['neq', CommonActivity::$status['DELETE']];
        if (isset($param['keyword']) && !empty($param['keyword'])) {
            $where['a.title'] = ['like', '%' . trim($param['keyword']) . '%'];
        }
        // 列表
        $total = $this->alias('a')
            ->join('subject s', 'a.subject_id=s.id', 'left')
            ->where($map)
            ->where($where)
            ->count();
        $list = $this->alias('a')
            ->field(['a.id', 'a.title', 'a.start_time', 'a.end_time', 'a.subject_id', 'a.order_sort',
                'a.status', 's.subject_name', 'a.activity_rule'])
            ->join('subject s', 'a.subject_id=s.id', 'left')
            ->where($map)
            ->where($where)
            ->order($param['order_field'], $param['order_direction'])
            ->limit($param['offset'], $param['page_size']) // 校验这里是否正确
            ->select();

        //查询主题
        $subject_ids=array_unique(array_filter(array_column($list,'subject_id')));
        $subject_names = Db::name('subject')->where('id', 'in',$subject_ids)->column('subject_name','id');

        $user_total_array = Db::name("activity_top_data")
            ->field(['count(DISTINCT user_id) as user_total,activity_id'])
            ->where(['status' => CommonActivity::$topDataStatus['PASS']])
            ->group('activity_id')
            ->select();
        $user_totals = [];

        foreach ($user_total_array as $key => $value) {
            $user_totals[$value['activity_id']] = $value['user_total'];
        }
        foreach ($list as $key => $value) {
            $list[$key]['subject_name']=$subject_names[$value['subject_id']]??'';
            $list[$key]['start_time'] = date('Y-m-d H:i:s', $value['start_time']);
            $list[$key]['end_time'] = date('Y-m-d H:i:s', $value['end_time']);
            $list[$key]['user_total'] = isset($user_totals[$value['id']]) ? $user_totals[$value['id']] : 0;
            $list[$key]['activity_rule'] = special_chars_decode($list[$key]['activity_rule']);
        }

        return ['data' => $list, 'total' => $total];
    }

    /**
     * 增加
     * @param $data
     * @return bool
     */
    public function add($data)
    {
        $data = $this->beforeAdd($data);

        try {
            $id = Db::name('activity')->insertGetId($data);
            $data['id'] = $id;
            Cache::set(CommonActivity::ACTIVITY_SETTING_PRE . $id, $data, $data['end_time'] - time());
            return true;
        } catch (ErrorException $exception) {
            $this->error = 'Invalid parameters';
            return false;
        }
    }

    /**
     * 编辑
     * @param $data
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit($data)
    {
        $row = Db::name('activity')->field(['id', 'image', 'status'])->where('id', $data['id'])->find();
        // 是否存在
        if (!$row) {
            $this->error = 'No results were found';
            return false;
        }
        // 是否已删除
        if ($row['status'] === CommonActivity::$status['DELETE']) {
            $this->error = 'No results were found';
            return false;
        }
        //删除旧图片
        $old_image=$row['image'];
        if ($data['image']){
            if ($old_image!==$data['image']){
                $this->deleteRemoteActivityImageFile($old_image);
            }
        }
        unset($data['id']);
        $res = Db::name('activity')->where(['id' => $row['id']])->update($data);
        if (!$res) {
            $this->error = 'Invalid parameters';
            return false;
        }
        return true;
    }


    /**
     * 增加前
     * @param $data
     * @return mixed
     */
    protected function beforeAdd($data)
    {
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']);
        $data['create_admin_id'] = $data['create_admin_id'];
        $data['create_time'] = time();
        $data['update_time'] = $data['create_time'];
        $data['image'] = serialize($data['image']);
        $data['reward_setting'] = isset($data['reward_setting']) ?
            htmlspecialchars_decode($data['reward_setting']) : '';
        return $data;
    }

    /**
     * 删除
     * @param $id
     * @param $uId
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function del($id, $uId)
    {
        if (!$id) {
            $this->error = 'Invalid parameters';
            return false;
        }
        $row = Db::name('activity')->field(['image', 'status', 'start_time'])->where(['id' => $id])->find();
        if (!$row) {
            $this->error = 'No results were found';
            return false;
        }
        if ($row['status'] === CommonActivity::$status['DELETE']) {
            $this->error = 'No rows were deleted';
            return false;
        }
        // 已到开始时间的活动不能删除
        $now = time();
        if ($now > $row['start_time']) {
            $this->error = '已到开始时间的活动不能删除';
            return false;
        }
        $data = [
            'status' => CommonActivity::$status['DELETE'],
            'last_edit_admin_id' => $uId,
            'update_time' => $now,
        ];
        $where = [
            'id' => $id,
            'status' => ['neq', CommonActivity::$status['DELETE']],
        ];
        $ret = Db::name('activity')->where($where)->update($data);

        // 删除活动排行榜数据
        Db::name('activity_top_data')
            ->where(['activity_id' => $id])
            ->update(['status' => CommonActivity::$topDataStatus['DELETE'], 'update_time' => $now]);
        if (!$ret) {
            $this->error = 'Operation failed';
            return false;
        }
        $image = unserialize($row['image']);
        // 删除远程图片资源
        foreach ($image as $value) {
            $this->deleteRemoteActivityImageFile($value);
        }
        return true;
    }

    /**
     * 删除远程活动图片资源
     * @param $image
     */
    public function deleteRemoteActivityImageFile($image)
    {
        if ($image) {
            $bucket = self::getRemoteImgBucket();
            WQiniu::delete($bucket, $image);
        }
    }

    /**
     * 获取远程图片存储空间
     * @return mixed
     */
    public static function getRemoteImgBucket()
    {
        return config('site.avatar_bucket');
    }

    /**
     * 查询活动信息
     * @param $id
     * @return array|bool|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRow($id)
    {
        if (empty($id)) {
            $this->error = 'Invalid parameters';
            return false;
        }
        $row = Db::name('activity')
            ->field(['id', 'title', 'activity_details', 'activity_rule', 'start_time', 'end_time', 'create_time',
                'update_time', 'subject_id', 'reward_setting', 'order_sort', 'status', 'image', 'cover_image',
                'share_details'])
            ->where(['id' => $id])
            ->find();

        if (!$row) {
            $this->error = 'No results were found';
            return false;
        } else {
            $row['image'] = !empty($row['image']) ? implode(',', unserialize($row['image'])) : '';
            //获取关联主题
            $subject_name = Db::name('subject')->where('id', $row['subject_id'])->value('subject_name');
            $row['subject_name'] = $subject_name ? $subject_name : '';
            $row['reward_setting'] = json_decode($row['reward_setting'], 1);
            $row['start_time'] = date('Y-m-d H:i:s', $row['start_time']);
            $row['end_time'] = date('Y-m-d H:i:s', $row['end_time']);
            $row['create_time'] = date('Y-m-d H:i:s', $row['create_time']);
            $row['update_time'] = date('Y-m-d H:i:s', $row['update_time']);
            $row['share_details'] = special_chars_decode($row['share_details']);
            $row['activity_rule'] = special_chars_decode($row['activity_rule']);
            $row['activity_details'] = special_chars_decode($row['activity_details']);
            return $row;
        }
    }

    /**
     * 编辑排序
     * @param $data
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function editSort($data)
    {
        $where = ['id' => $data['id']];
        unset($data['id']);
        Db::name('activity')->where($where)->update($data);
        return true;
    }

    /**
     * 显示 TODO 和下面hide方法合并
     * @param $id
     * @param array $data
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function show($id, $data = [])
    {
        if (empty($data)) {
            $this->error = 'Invalid parameters';
            return false;
        }
        $where = [
            'id' => $id,
            'status' => CommonActivity::$status['HIDE'],
        ];
        Db::name('activity')->where($where)->update($data);
        return true;
    }

    /**
     * 隐藏
     * @param $id
     * @param array $data
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function hide($id, $data = [])
    {
        if (empty($data)) {
            $this->error = 'Invalid parameters';
            return false;
        }
        $where = [
            'id' => $id,
            'status' => CommonActivity::$status['DISPLAY'],
        ];
        Db::name('activity')->where($where)->update($data);
        return true;
    }

}
