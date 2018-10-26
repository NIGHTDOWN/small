<?php
namespace app\common\command\video;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class Stat extends Command
{
    protected function configure()
    {
        $this->setName('Stat')->setDescription('统计');
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