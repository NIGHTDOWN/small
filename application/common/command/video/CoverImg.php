<?php
namespace app\common\command\video;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class CoverImg extends Command
{
    protected function configure()
    {
        $this->setName('CoverImg')->setDescription('封面数据修改');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->main();
    }

    public function main()
    {
        Db::name('video_extend')
            ->where([
                'cover_imgs'=>['<>','']
            ])
            ->chunk(100,function ($data){
                foreach ($data as $key=>$value){
                    try{
                        $coverImgs=unserialize($value['cover_imgs']);
                        $coverImg=config('site.cover_url').'/'.$coverImgs[0];
                        Db::name('video_extend')->where('video_id',$value['video_id'])->update(['cover_imgs'=>$coverImg]);
                    }catch (\Exception $e){

                    }
                }
            });
    }
}