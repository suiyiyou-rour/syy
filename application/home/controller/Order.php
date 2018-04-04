<?php
namespace app\home\controller;
use app\common\controller\HomeBase;

class Order extends HomeBase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        echo "home/Order/index";
    }

    //列表显示
    public function showList(){
        $where["a.goods_type"]  = "1";                 //跟团游
        $where['a.is_del']      = array("<>", "1");   //未删除
        if(!getSpType()){                               //超级管理
            $where["a.sp_code"] = getSpCode();         //供应商
        }

        $order_sn = input("post.order_sn");           //订单编号
        if($order_sn){
            $where["a.order_sn"] = $order_sn;
        }

        $goods_code = input("post.goods_code");       //商品编号
        if($goods_code){
            $where["a.goods_code"] = ['like',"%".$goods_code."%"];
        }

        $goods_name = input("post.goods_name");       //产品名称
        if($goods_name){
            $where["a.goods_name"] = $goods_name;
        }

        $user_name = input("post.user_name");         //联系人姓名
        if($user_name){
            $where["a.user_name"] = $user_name;
        }

        $mobile = input("post.mobile");               //联系人手机
        if($mobile){
            $where["a.mobile"] = $mobile;
        }

        $order_type = input("post.order_type");      //订单状态
        if($order_type){
            $where["a.order_type"] = $order_type;
        }

        $page = input("post.page");                   //页码
        if(empty($page)){
            $page = 1;
        }

        $aField   = "a.order_sn,a.goods_code,goods_name,a.go_time,a.create_time,a.user_name,a.mobile,a.total_price,a.total_num";
        $bField   = "b.plat_price,b.man_num,b.plat_child_price,b.child_num";
        $allField = $aField.",".$bField;

        $join = [['group_order b','a.order_sn = b.order_sn']];
        $count = db('order')->alias("a")->where($where)->join($join)->count('a.id');
        if(!$count){
            return json(array("code" => 200,"data" => array("count" =>0)));
        }
        $res = db('order')->alias("a")->join($join)->field($allField)->where($where)->order('a.id desc')->page($page,10)->select();
        $output["list"]  =  $res;
        $output["count"]  =  $count;
        return json(array("code" => 200,"data"=>$output));
    }

    //单个显示
    public function show(){
        $order_sn = input("post.order_sn");           //订单编号
        if(empty($order_sn)){
            return json(array("code" => 404,"data"=>"订单编号不能为空"));
        }

        $where = [
            "a.order_sn"    =>  $order_sn,
            'a.is_del'      =>  ['<>', "1"]  //未删除
        ];

        $join = [['group_order b','a.order_sn = b.order_sn']];
        $res  = db('order')->alias("a")->join($join)->where($where)->find();
        if(!$res){
            return json(array("code" => 404,"data"=>"找不到这条订单"));
        }
        $res["charged_item"]    =  json_decode($res["charged_item"],true);
        $res["identity_array"]  =  json_decode($res["identity_array"],true);

        return json(array("code" => 200,"data"=>$res));

    }
}
