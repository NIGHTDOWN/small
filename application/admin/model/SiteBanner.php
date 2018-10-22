<?php

namespace app\admin\model;
use WSJ\WQiniu;
use think\Model;
use think\Db;

class SiteBanner extends Model
{
    // 表名
    protected $name = 'site_banner';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'create_time_text',
        'update_time_text'
    ];

    const STATUS=[
        'DISABLED'=>0,
        'ENABLED'=>1,
        'DELETE'=>2
    ];

    const STATUS_TXET=[
        0=>'已关闭',
        1=>'已启用',
        2=>'已删除'
    ];

    const TYPE_STATUS=[
        'DISABLED'=>0,
        'ENABLED'=>1,
        'DELETE'=>2
    ];

    const TYPE_STATUS_TXET=[
        0=>'已关闭',
        1=>'已启用',
        2=>'已删除'
    ];

    const CLIENT_TYPE=[
        'MOBILE'=>0,
        'PC'=>1,
    ];

    const CLIENT_TYPE_TEXT=[
        0=>'手机',
        1=>'PC',
    ];
    



    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function items() { //建立一对多关联
        return $this->belongsTo('SiteBannerType', 'id', 'type_id'); //关联的模型，外键，当前模型的主键
    }


    public function getUpdateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['update_time']) ? $data['update_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
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
     * 获取远程图片存储空间
     * @return mixed
     */
    public  static function getRemoteImgBucket()
    {
        return config('qiniu.site_banner_bkt');
    }

    /**
     * 获取远程图片访问域名
     */
    public  static function getRemoteImgDomain()
    {
        return config('qiniu.site_banner_domain');
    }

    /**
     * 获取远程图片访问协议
     * @return mixed
     */
    public static function getRemoteImgProtocol()
    {
        return config('qiniu.site_banner_protocol');
    }

    /**
     * 获取封面图url
     * @param $key
     * @return string
     */
    public static function getCoverImgUrl($key)
    {
        if ($key){
            // $WQiniuConfig=WQiniu::getConfig();
            $key=self::getRemoteImgProtocol().'://'.self::getRemoteImgDomain().'/'.$key;
        }
        return $key;
    }

    /**
     * 删除远程活动图片资源
     * @param $image
     */
    public function deleteRemoteActivityImageFile($image)
    {
        if ($image){
            $bucket=self::getRemoteImgBucket();
            WQiniu::delete($bucket,$image);
        }
    }

    public function SiteBannerType()
    {
        return $this->belongsTo('SiteBannerType', 'type_id', 'id', [], 'LEFT')->setEagerlyType(1);
    }

    public function add($data)
    {
        $db = Db::name('site_banner');
        $now=time();
        return $db->insertGetId([
            'type_id'=>$data['type_id'],
            'image'=>$data['image'],
            'order_sort'=>$data['order_sort'],
            'client_type'=>$data['client_type'],
            'update_time'=>$now,
            'create_time'=>$now,
            'status'=>$data['status']
        ]);
    }

    public function edit($data)
    {
        $db = Db::name('site_banner');
        $now = time();
        return  $db->where('id','eq',$data['id'])->update([
            'image' => $data['image'],
            'order_sort' => $data['order_sort'],
            'client_type' => $data['client_type'],
            'update_time' => $now,
            'status' => $data['status'],
            'type_id'=>$data['type_id'],
        ]);
    }
}
