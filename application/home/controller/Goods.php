<?php
namespace app\home\controller;
use app\common\controller\HomeBase;

class Goods extends HomeBase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        echo "home/Goods/index";
    }

    //已经上线的商品列表
    public function goodsList(){
        $goodsCode = input("post.goodsCode");         //产品编号
        if($goodsCode){
            $where["code"] = $goodsCode;
        }
        $page = input("post.page");                    //页码
        if(empty($page)){
            $page = 1;
        }
        $date = strtotime(date("Y-m-d", time()));

        $where = [
            "check_type"   =>  "5",             //上线
            "is_del"       =>  ["<>","1"],      //未删除
            "on_time"      =>  ["<=",$date],
            "off_time"     =>  [">=",$date]
        ];
        $count = db('goods')->where($where)->count('id');

        if(!$count){
            return json(array("code" => 200,"data" => array("count"=>0)));
        }
        $res = db('goods')->field("code,show_title,sales")->where($where)->order("last_edit_time desc")->page($page,10)->select();
        $output["list"]  =  $res;
        $output["count"]  =  $count;
        return json(array("code" => 200,"data" => $output));


    }

    //更改产品销量
    public function alterSale(){
        $goodsCode = input("post.goodsCode");         //产品编号
        $sales     = (int)input("post.sales");         //销量
        if(empty($goodsCode) || empty($sales)){
            return json(array("code" => 404,"msg" => "参数错误404"));
        }

        $date = strtotime(date("Y-m-d", time()));
        $where = [
            "check_type"   =>  "5",             //上线
            "is_del"       =>  ["<>","1"],      //未删除
            "on_time"      =>  ["<=",$date],
            "off_time"     =>  [">=",$date],
            "code"         =>  $goodsCode
        ];
        $goods = db('goods')->field("id")->where($where)->find();
        if(empty($goods)){
            return json(array("code" => 405,"msg" => "不在线上或者没有这个商品"));
        }
        $res = db('goods')->where($where)->update(array("sales"=>$sales));
        if($res !== false){
            return json(array("code" => 200,"msg" => "保存成功"));
        }
        return json(array("code" => 403,"msg" => "保存失败请再试一次"));
    }
}
