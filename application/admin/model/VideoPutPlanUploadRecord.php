<?php

namespace app\admin\model;

use think\Model;
use think\Db;
use wsj\PhpSpreadsheet;
use wsj\WQiniu;

class VideoPutPlanUploadRecord extends Model
{
    // 表名
    protected $name = 'video_put_plan_upload_record';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        // 'create_time_text'
    ];

    const STATUS = [
        'PENDING' => 0,
        'DONE' => 1,
        'ERROR' => 2,
    ];

    const STATUS_TEXT = [
        0 => '待处理',
        1 => '完成',
        2 => '错误',
    ];
    
    /**
     * 增加
     * @param $data
     * @return bool
     */
    public function add($param)
    {
        // $validate = validate('VideoPutPlanUploadRecord');
        // $ret = $validate->scene('add')->check($data);
        // if (!$ret) {
        //     $this->error = $validate->getError();
        //     return false;
        // }
        $fileName = ROOT_PATH . 'public' . $param['file_name'];
        if (!file_exists($fileName)) {
             $this->error = '文件不存在';
             return false;
        } 
        $fileInfo = pathinfo($fileName);
        if (!in_array($fileInfo['extension'], ['xlsx','xls'])) {
            $this->error = '仅可上传xlsx和xls格式文件';
            return false;
        }
        $data = ['file_name' => $fileInfo['basename']];
        $data['status'] = self::STATUS['PENDING'];
        $data['create_time'] = time();
        $data['file_md5'] = md5($data['file_name']);
        $data['error_info'] = '';
        $id = Db::name('video_put_plan_upload_record')->insertGetId($data);
        if (!$id) {
            $this->error = '上传失败';
            return false;
        }
        // 队列
        publish_message([
            'action' => 'videoPutPlanRelationTableParse',
            'params' => [
                'upload_record_id' => $id,
            ],
        ]);
        return true;
    }


    /**
     * 关系表解析
     * (队列调起)
     * @param $id
     */
    public function relationTableParse($id)
    {
        $upload_record = Db::name('video_put_plan_upload_record')
            ->master()
            ->where([
                'id' => ['=', $id],
                'status' => ['=', self::STATUS['PENDING']]
            ])
            ->find();
        if (!$upload_record) {
            return ;
        }
        //读取
        $data = false;
        $dir = env('ROOT_PATH') . 'public/upload/';
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        $file = $dir . $upload_record['file_name'];
        try {
            // file_put_contents($file, file_get_contents(self::getRelationTableUrl($upload_record['file_key'])));
            $data = PhpSpreadsheet::read($file);
        } catch (ErrorException $e) {

        }
        if (!is_file($file)) {
            $this->recordErrorInfo($upload_record['id'], '创建临时文件失败');
            return;
        }
        //删除文件
        // try {
        //     unlink($file);
        //     WQiniu::delete(self::getRemoteRelationTableBucket(), $upload_record['file_key']);
        // } catch (ErrorException $e) {
 
        // }
        //验证
        if ($data === false) {
            $this->recordErrorInfo($upload_record['id'], '解析文件出错');
            return;
        }
        if (!$data || !isset($data[0][0])) {
            $this->recordErrorInfo($upload_record['id'], '空数据文件');
            return;
        }
        $title_row = $data[0];
        try {
            if ($title_row[0] !== '文件' || $title_row[1] !== '方向' || $title_row[2] !== '标题' || $title_row[3] !== '主题'|| $title_row[4] !== '用户ID') {
                $this->recordErrorInfo($upload_record['id'], '首行标题格式不正确');
                return;
            }
        } catch (\Exception $e) {
            $this->recordErrorInfo($upload_record['id'], '首行标题格式不正确');
            return;
        }
        unset($data[0]);
        //数据错误行   0缺少文件名  1文件不存在   2重复上传   3方向错误  4用户错误
        $data_error_row_array = [
            0 => [],
            1 => [],
            2 => [],
            3 => [],
            4 => [],
        ];
        $data_error_info = '';
        $original_file_md5_array = [];
        foreach ($data as $key => $value) {
            $row = $key + 1;
            $original_file = $value[0];
            $direction = $value[1];
            $user_id = $value[4];
            if (!$original_file) {
                $data_error_row_array[0][] = $row;
                continue;
            }
            // 验证方向
            if (!in_array($direction,['横', '竖'], true)) {
                $data_error_row_array[3][] = $row;
                continue;
            }
            // 验证文件是否存在
            $original_file_md5 = \app\admin\model\VideoPutPlan::getOriginalVideoMd5($original_file);
            if (!$original_file_md5) {
                $data_error_row_array[1][] = $row;
                continue;
            }
            // 验证文件唯一
            $exist_original_file_md5 = Db::name('video_put_plan')->where('original_file_md5', $original_file_md5)->count();
            if ($exist_original_file_md5 || in_array($original_file_md5, $original_file_md5_array)){
                $data_error_row_array[2][] = $row;
                continue;
            }
            $data[$key]['original_file_md5'] = $original_file_md5;
            $original_file_md5_array[] = $original_file_md5;
            // 验证用户id
            if ($user_id) {
                if (!is_numeric($user_id)) {
                    $data_error_row_array[4][] = $row;
                    continue;
                } else {
                    $user_find = Db::name('User')->where(['status' => UserModel::STATUS['NORMAL'], 'id' => $user_id])->count();
                    if (!$user_find) {
                        $data_error_row_array[4][] = $row;
                        continue;
                    }
                }
            }
        }
        unset($original_file_md5_array);
        foreach ($data_error_row_array as $key => $value) {
            if (count($value)) {
                switch ($key) {
                    case 0:
                        $data_error_info .= '行号' . implode(',', $value) . '缺少文件名,';
                        break;
                    case 1:
                        $data_error_info .= '行号' . implode(',', $value) . '文件不存在,';
                        break;
                    case 2:
                        $data_error_info .= '行号' . implode(',', $value) . '重复上传,';
                        break;
                    case 3:
                        $data_error_info .= '行号' . implode(',', $value) . '方向错误,';
                        break;
                    case 4:
                        $data_error_info .= '行号' . implode(',', $value) . '用户错误,';
                        break;
                }
            }
        }
        if ($data_error_info) {
            $this->recordErrorInfo($upload_record['id'], trim($data_error_info, ','));
            return;
        }
        // 组装数据
        $bucket = \app\admin\model\VideoPutPlan::getRemoteVideoBucket();
        $param = \app\admin\model\VideoPutPlan::getParam();
        $plan_time = $param['start_time'];
        $all_insert_data = [];
        foreach ($data as  $key => $value) {
            $original_file = $value[0];
            $original_file_md5 = $value['original_file_md5'];
            $key = WQiniu::createKey();
            $ret = WQiniu::move($bucket,$original_file,$bucket,$key);
            if (!$ret) {
                $key = '';
            }
            $direction = ($value[1] === '横') ? 3 : 1;
            $title = $value[2] ? $value[2] : '';
            $subject = $value[3] ? $value[3] : '';
            $user_id = $value[4] ? $value[4] : 0;
            $all_insert_data[] = [
                'original_file' => $original_file,
                'original_file_md5' => $original_file_md5,
                'key' => $key,
                'direction' => $direction,
                'title' => $title,
                'subject' => $subject,
                'user_id' => $user_id,
                'status' => \app\admin\model\VideoPutPlan::STATUS['SET'],
                'create_time' => time(),
                'plan_time' => $plan_time,
            ];
            $interval_time = mt_rand($param['interval_time_min'], $param['interval_time_max']);
            $plan_time += $interval_time;
        }
        // 保存数据
        Db::name('video_put_plan')->insertAll($all_insert_data, false, 50);
        // 设置完成
        Db::name('video_put_plan_upload_record')->where('id', $upload_record['id'])->update([
            'status' => self::STATUS['DONE'],
        ]);
    }

    



    // public function getCreateTimeTextAttr($value, $data)
    // {
    //     $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
    //     return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    // }

    // protected function setCreateTimeAttr($value)
    // {
    //     return $value && !is_numeric($value) ? strtotime($value) : $value;
    // }


}
