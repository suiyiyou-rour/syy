<?php
/**
 * 产品添加 状态分发
 */
namespace app\home\logic;

class Goods
{
    /**
     * 产品模块分发
     */
    public function dispatcher($goodsType,$operation,$state){
        $array = array('Group','Ticket','Scenery');
        if(in_array($goodsType,$array)){
            return $this->$goodsType($operation,$state);
        }else{
            return json_encode(array("code" => 404,"msg" => "参数错误"));
        }
    }

    //跟团游 操作分发0
    public function group($operation,$state){
        $array = array('Add','Show','Option');
        if(in_array($operation,$array)){
            return \think\Loader::model($operation.'Group','logic')->dispatcher($state);
        }else{
            return json_encode(array("code" => 404,"msg" => "参数错误"));
        }
    }

    //门票
    public function ticket($operation,$state){
        $array = array('Add','Show','Option');
        if(in_array($operation,$array)){
            return \think\Loader::model($operation.'Ticket','logic')->dispatcher($state);
        }else{
            return json_encode(array("code" => 404,"msg" => "参数错误"));
        }
    }

    //酒景
    public function scenery($operation,$state){
        $array = array('Add','Show','Option');
        if(in_array($operation,$array)){
            return \think\Loader::model($operation.'Scenery','logic')->dispatcher($state);
        }else{
            return json_encode(array("code" => 404,"msg" => "参数错误"));
        }
    }
}