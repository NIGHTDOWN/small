<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/6
 * Time: 14:29
 */
namespace wsj;

use Aliyun\OTS\OTSClient;
use Aliyun\OTS\RowExistenceExpectationConst;

class Tablestore{

        public static $INF_MAX = 'INF_MAX';

        public static $INF_MIN = 'INF_MIN';

        public static $instance ;

        public static $config;

        public function __construct($config = [])
        {
               if(self::$instance) return $this;
               self::$config = $config ? $config :  config('tablestore.');
               self::$instance = new OTSClient(
                   array(
                       'EndPoint' => self::$config['end_point'],
                       'AccessKeyID' => self::$config['access_key'],
                       'AccessKeySecret' => self::$config['access_secret'],
                       'InstanceName' => self::$config['instance_name'],
                       'debugLogHandler' => self::$config['debug_log_handler']
                   )
               );
               return $this;
        }

        private function getTableName($table_name){
            return self::$config['table_prefix'] . $table_name;
        }

        public function putRow($primary_key=[],$attr_column = [],$condition = 'IGNORE'){
             $this->mergeParams();
             $request = [
                 'table_name' => $this->getTableName($this->queryParams['table_name']),
                 'primary_key' => $primary_key,
                 'condition' => $condition,
             ];
             if($attr_column) $request['attribute_columns'] = $attr_column;
             return self::$instance->putRow($request);
        }

        public function deleteRow($primary_key,$condition = []){
             $this->mergeParams();
             $request = [
                 'table_name' => $this->getTableName($this->queryParams['table_name']),
                 'primary_key' => $primary_key
             ];
             if(!$condition) $request['condition'] = RowExistenceExpectationConst::CONST_IGNORE;
             return self::$instance->deleteRow($request);
        }

        public function batchDeleteRow($primary_keys = [],$condition = 'IGNORE'){
            $this->mergeParams();
            $map = [];
            $key_md5_list = [];
            foreach($primary_keys as $v){
                $md5_key = md5(strtolower(serialize($v)));
                if(in_array($md5_key,$key_md5_list)) continue;
                $key_md5_list[] = $md5_key;
                $map[] = [
                       'primary_key' => $v,
                       'condition' => $condition
                   ];
            }
            $request = [
                'tables' => [
                    [
                        'table_name' => $this->getTableName($this->queryParams['table_name']),
                        'delete_rows' => $map,
                    ]
                ]
            ];
            return self::$instance->batchWriteRow($request);
        }

        public function batchPutRow($put_data = [],$condition = 'IGNORE'){
            $this->mergeParams();
            $data = [];
            $key_md5_list[] = [];
            foreach($put_data as $k => $v){
                $md5_key = md5(strtolower(serialize($v)));
                if(in_array($md5_key,$key_md5_list)) continue;
                $key_md5_list[] = $md5_key;
                $temp = [
                        'condition' => $condition
                    ];
                    if(isset($v['primary_key'])) $temp['primary_key'] = $v['primary_key'];
                    if(isset($v['attribute_columns'])) $temp['attribute_columns'] = $v['primary_key'];
                    $data[] = $temp;
            }
            $request = [
                'tables' => [
                    [
                        'table_name' => $this->getTableName($this->queryParams['table_name']),
                        'put_rows' => $data
                    ]
                ]
            ];
            return self::$instance->batchWriteRow($request);
        }


        public function batchGetRow($put_data = [],$condition = 'IGNORE'){

        }


        private $defaultParams = [
            'direction' => 'FORWARD', //BACKWARD
        ];

        private $queryParams = [];

        public function order($direction = 'BACKWARD'){
               $direction = strtoupper($direction);
               if(in_array($direction,['FORWARD','BACKWARD'])){
                   $this->queryParams['direction'] = $direction;
               }
               return $this;
        }

        public function table($table_name){
               $this->queryParams['table_name'] = $table_name;
               return $this;
        }

        public function setStartKey($data){
               $this->queryParams['start_key']  = $data;
               return $this;
        }

        public function setEndKey($data){
               $this->queryParams['end_key'] = $data;
               return $this;
        }

        public function field($fields){
                if(is_string($fields) && strpos(',',$fields) !== false){
                    $fields = explode(',',$fields);
                }
                $this->queryParams['field'] = $fields;
                return $this;
        }

        public function limit($limit){
                $this->queryParams['limit'] = $limit;
                return $this;
        }

        public function filter($column_filter){
                $this->queryParams['column_filter'] = $column_filter;
                return $this;
        }

        public function mergeParams(){
                return $this->queryParams += $this->defaultParams;
        }


        public function getRow($primary_key){
               $this->mergeParams();
               $request = [
                    'table_name' => $this->getTableName($this->queryParams['table_name']),
                    'primary_key' => $primary_key,
               ];
               return self::$instance->getRow($request);
        }

        public function getRange(){
                  $this->mergeParams();
                  $request = [
                      'table_name' => $this->getTableName($this->queryParams['table_name']),
                      'direction' => $this->queryParams['direction'],
                      'inclusive_start_primary_key' => $this->queryParams['start_key'],
                      'exclusive_end_primary_key' => $this->queryParams['end_key'],
                  ];
                  if(isset($this->queryParams['limit'])) $request['limit']  = $this->queryParams['limit'];
                  if(isset($this->queryParams['column_filter'])) $request['column_filter'] = $this->queryParams['column_filter'];
                  if(isset($this->queryParams['field'])) $request['columns_to_get'] = $this->queryParams['field'];
                  return self::$instance->getRange($request);
        }

}