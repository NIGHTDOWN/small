<?php
namespace wsj;
use InvalidArgumentException;
/**
 * Class PhpSpreadsheet
 * @package WSJ
 */
class PhpSpreadsheet
{
    /**
     * 读取文件
     * @param $file
     * @return array|bool
     */
    public static function read($file)
    {
        try{
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        }catch (InvalidArgumentException $e){
            return false;
        }
        $relation=$spreadsheet->getActiveSheet()->toArray();
        return $relation;
    }
}