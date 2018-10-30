<?php
namespace app\common\command\activity;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class UpdateImg extends Command
{
    protected function configure()
    {
        $this->setName('UpdateImg')->setDescription('图片数据修改');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->main();
    }

    public function main()
    {
        $url=config('site.other_url');
        Db::name('activity')
            ->chunk(100,function ($data) use ($url) {
                foreach ($data as $key=>$value){
                    $updateData=[];
                    if ($value['image']){
                        $image=unserialize($value['image']);
                        foreach ($image as $k=>$v){
                            if (strpos($v,$url)===false){
                                $image[$k]=$url.'/'.$v;
                            }
                        }
                        $updateData['image']=serialize($image);
                    }
                    if ($value['cover_image']){
                        if (strpos($value['cover_image'],$url)===false){
                            $updateData['cover_image']=$url.'/'.$value['cover_image'];
                        }
                    }

                    Db::name('activity')->where('id',$value['id'])->update($updateData);
                }
            });
    }
}