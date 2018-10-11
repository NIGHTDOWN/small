<?php
namespace wsj\ali;

use Elasticsearch\ClientBuilder;

/**
 * Class ElasticSearch
 * @package WSJ\ali
 * @link https://www.elastic.co/guide/cn/elasticsearch/php/cn/index.html
 */
class ElasticSearch
{
    /** @var null 连接 */
    protected $client=null;

    /** @var null 索引名称 */
    protected $index_name=null;

    /** @var null 类型名称 */
    protected $type_name='data';

    /** @var array 搜索条件 */
    protected $query_where=[];

    public function __construct()
    {
        //设置连接
        if (is_null($this->client)){
            $config=config('ali.elastic_search.hosts');
            $this->client=ClientBuilder::create()->setHosts($config)->build();
        }
    }

    /**
     * 创建索引
     * @param $name
     * @return array
     */
    public function createIndex($name)
    {
        $params=[
            'index'=>$name,
        ];
        return $this->client->indices()->create($params);
    }

    /**
     * 删除索引
     * @param $name
     * @return array
     */
    public function deleteIndex($name)
    {
        $params=[
            'index'=>$name,
        ];
        return $this->client->indices()->delete($params);
    }

    /**
     * 设置索引名称
     * @param $name
     * @return $this
     */
    public function name($name)
    {
        $this->index_name=config('ali.elastic_search.indices.'.$name);
        return $this;
    }

    /**
     * 写入
     * @param $data
     * @return int|bool
     */
    public function insert($data)
    {
        if (!is_array($data)){
            return false;
        }
        if (!isset($data['id'])){
            return false;
        }
        $params = [
            'index' => $this->index_name,
            'type' => $this->type_name,
            'id'=>$data['id'],
            'body' => $data,
        ];
        try{
            $rs=$this->client->index($params);
            return $rs['_id'];
        }catch (\Exception $e){
            return false;
        }
    }

    /**
     * 写入多个
     * @param $data
     * @return bool
     */
    public function insertAll($data)
    {
        if (!$data){
            return false;
        }
        if (!is_array($data)){
            return false;
        }
        $params=[];
        foreach ($data as $value){
            if (!is_array($value)){
                return false;
            }
            if (!isset($value['id'])){
                return false;
            }
            $params['body'][] = [
                'index' => [
                    '_index' => $this->index_name,
                    '_type' => $this->type_name,
                    '_id' => $value['id'],
                ]
            ];

            $params['body'][] = $value;
        }
        $rs = $this->client->bulk($params);
        if (isset($rs['errors'])&&$rs['errors']===false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 更新
     * @param $id
     * @param $data
     * @return array|bool
     */
    public function update($id,$data)
    {
        if (!$id||!$data||!is_array($data)){
            return false;
        }
        if (isset($data['id'])){
            unset($data['id']);
        }
        $params = [
            'index' => $this->index_name,
            'type' => $this->type_name,
            'id'=>$id,
            'body' => [
                'doc'=>$data,
            ],
        ];
        try{
            $rs=$this->client->update($params);
            return $rs['result']=='updated'?1:0;
        }catch (\Exception $e){
            return false;
        }
    }

    /**
     * 删除
     * @param $id
     * @return bool|int
     */
    public function delete($id)
    {
        if (!$id){
            return false;
        }
        $params = [
            'index' => $this->index_name,
            'type' => $this->type_name,
            'id'=>$id,
        ];
        try{
            $rs=$this->client->delete($params);
            return $rs['result']=='deleted'?1:0;
        }catch (\Exception $e){
            return false;
        }
    }

    /**
     * 查找单个
     * @param $id
     * @return array
     */
    public function find($id)
    {
        $params = [
            'index' => $this->index_name,
            'type' => $this->type_name,
            'id'=>$id,
        ];
        try{
            $rs=$this->client->get($params);
            return $rs['_source'];
        }catch (\Exception $e){
            return null;
        }
    }

    /**
     * 查找
     * @param array $where  条件
     * @param int $size  返回的结果数量
     * @param int $from  跳过的初始结果数量
     * @return array
     */
    public function select($where=[],$size=0,$from=0)
    {
        $params = [
            'index' => $this->index_name,
            'type' => $this->type_name,
        ];
        if ($where){
            $params['body']['query']=$where;
        }
        $params['body']['size']=$size;
        $params['body']['from']=$from;
        $rs=$this->client->search($params);
        $data=[
            'total'=>0,
            'data'=>[],
        ];
        if (isset($rs['hits']['total'])&&isset($rs['hits']['hits'])){
            $data['total']=$rs['hits']['total'];
            foreach ($rs['hits']['hits'] as $key=>$value){
                if ($value['_source']['id']!=$value['_id']){
                    $value['_source']['id']=$value['_id'];
                }
                $data['data'][]=$value['_source'];
            }
        }
        return $data;
    }

    /**
     * 文本分词
     * @param $text
     * @param string $analyzer
     * @return array|bool
     */
    public function analyze($text,$analyzer='ik_smart')
    {
        if (!$text||!is_string($text)){
            return false;
        }
        $params=[
            'body'=>[
                'analyzer'=>$analyzer,
                'text'=>$text,
            ],
        ];
        if ($this->index_name){
            $params['index']=$this->index_name;
        }
        try{
            $ret=$this->client->indices()->analyze($params);
            $data=[];
            if (isset($ret['tokens'])){
                foreach ($ret['tokens'] as $value){
                    $data[]=$value['token'];
                }
            }
            return $data;
        }catch (\Exception $e){
            return false;
        }
    }
}