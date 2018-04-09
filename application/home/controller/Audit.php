<?php
namespace app\home\controller;
use app\common\controller\HomeBase;

class Audit extends HomeBase
{
    public function __construct()
    {
        parent::__construct();
//        if(!getSpType()){
//            echo json_encode(array("code"=>405,"msg"=>"只有超级管理员才有权限"));
//            die;
//        }
    }

    public function index()
    {
        echo "home/audit/index";
    }

    //审核产品列表显示
    public function showList(){
        $page = input("post.page");        //页码
        if(empty($page)){
            $page = 1;
        }
        $where = [
            "check_type"    =>  "2",        //审核中
            'is_del'        =>  ['<>', "1"]  //未删除
        ];
        $field = "code,sp_code,show_title,on_time,off_time,price_type,goods_type,plat_price,settle_price,stock_type,online_type";
        $count = db('goods')->where($where)->order("last_edit_time desc")->count('id');
        if(!$count){
            return json(array("code" => 200,"data" => array("count"=>0)));
        }
        $res = db('goods')->field($field)->where($where)->order("last_edit_time desc")->page($page,10)->select();
        foreach ($res as &$k){
            $k["off_time"] = date("Y-m-d",$k["off_time"]);
            if($k["online_type"] == 1){
                $k["on_time"] = "上线时间为审核通过当天";
            }else if($k["online_type"] == 3){
                $k["on_time"] = "上线时间为手动上线当天";
            }else{
                $k["on_time"] = date("Y-m-d",$k["on_time"]);
            }
        }
        $output["list"]  =  $res;
        $output["count"]  =  $count;
        return json(array("code" => 200,"data" => $output));
    }

    //审核通过
    public function pass(){
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return json(array("code" => 404,"msg" => "参数错误404"));
        }
        $where = [
            "code"        =>  $goodsCode,
            "is_del"      =>  ["<>","1"],          //未删除
        ];
        $res = db('goods')->field("check_type,online_type")->where($where)->find();
        if(empty($res)){
            return json(array("code" => 405,"msg" => "商品号找不到或者被删除"));
        }
        if($res["check_type"] != 2){
            return json(array("code" => 405,"msg" => "商品不在审核状态"));
        }
        if($res["online_type"] == 1){                       //审核通过上线
            $data["check_type"] = 5;                            //直接上线
            $data["on_time"] = strtotime(date('Y-m-d',time())); //上线时间
        }else{
            $data["check_type"] = 3;                            //改为审核通过
        }
        $output = db('goods')->where(array("code" => $goodsCode))->update($data);
        if($output){
            return json(array("code" => 200,"msg" => "审核成功"));
        }else{
            return json(array("code" => 403,"msg" => "审核失败，请再试一次"));
        }
    }

    //审核不通过 驳回
    public function rejected(){
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return json(array("code" => 404,"msg" => "参数错误404"));
        }
        $where = [
            "code"        =>  $goodsCode,
            "is_del"      =>  ["<>","1"],          //未删除
        ];
        $res = db('goods')->field("id")->where($where)->find();
        if(empty($res)){
            return json(array("code" => 405,"msg" => "商品号找不到或者被删除"));
        }
        $output = db('goods')->where(array("code" => $goodsCode))->update(array("check_type"=>4));
        if($output){
            return json(array("code" => 200,"msg" => "驳回成功"));
        }else{
            return json(array("code" => 403,"msg" => "操作失败，请再试一次"));
        }
    }




}
