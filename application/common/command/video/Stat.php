<?php
namespace app\common\command\video;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class Stat extends Command
{
    protected function configure()
    {
        $this->setName('VideoStat')->setDescription('视频统计');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->main();
    }

    public function main()
    {
        /** @var \app\admin\model\VideoStatDay $model */
        $model=model('admin/VideoStatDay');
        $model->add();
    }
}