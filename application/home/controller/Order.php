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
        $goods_type = input("post.goods_type");           //订单编号
        if($goods_type){
            $where["goods_type"] = $goods_type;
        }else{
            $where["goods_type"]  = "1"; //跟团游
        }

        $where['is_del']      = array("<>", "1");   //未删除
        if(!getSpType()){                              //超级管理
            $where["sp_code"] = getSpCode();         //供应商
        }

        $order_sn = input("post.order_sn");           //订单编号
        if($order_sn){
            $where["order_sn"] = $order_sn;
        }

        $goods_code = input("post.goods_code");       //商品编号
        if($goods_code){
            $where["goods_code"] = $goods_code;
        }

        $goods_name = input("post.goods_name");       //产品名称
        if($goods_name){
            $where["goods_name"] = ['like',"%".$goods_name."%"];
        }

        $user_name = input("post.user_name");         //联系人姓名
        if($user_name){
            $where["user_name"] = ['like',"%".$user_name."%"];
        }

        $mobile = input("post.mobile");               //联系人手机
        if($mobile){
            $where["mobile"] = $mobile;
        }

        $order_type = input("post.order_type");      //订单状态
        if($order_type){
            $where["order_type"] = $order_type;
        }else{
            $where["order_type"] = ["in","2,3,4"];
        }

        $page = input("post.page");                   //页码
        if(empty($page)){
            $page = 1;
        }

        $count = db('order')->where($where)->count('id');
        if(!$count){
            return json(array("code" => 200,"data" => array("count" =>0)));
        }

        $Field   = "order_sn,goods_code,goods_name,go_time,pay_time,user_name,mobile,total_price,total_settle_price,total_num,order_type";
        if(getSpType()){    //超级管理
            $Field   .= ",rebate_type";
        }
        $res = db('order')->field($Field)->where($where)->order('id desc')->page($page,10)->select();
        foreach ($res as &$k){
            $k["go_time"]           = date("Y-m-d", $k["go_time"]);
            if($k["pay_time"]){
                $k["pay_time"]      = date("Y-m-d", $k["pay_time"]);
            }else{
                $k["pay_time"]      = "";
            }
            $k["total_price"]            = (float)$k["total_price"];
            $k["total_settle_price"]    =  (float)$k["total_settle_price"];

        }
        $output["list"]  =  $res;
        $output["count"]  =  $count;
        return json(array("code" => 200,"data"=>$output));

    }

    //单个显示
    public function show(){
        $orderSn = input("order_sn");
        if(empty($orderSn)){
            return json(array("code" => 404,"msg" => "订单号不能为空"));
        }

        $spType = getSpType();  //true 超级

        $where["a.is_del"]       =  0;  //未删除
        $where["a.order_sn"]     = $orderSn;

        $join   =  [['sp b','a.sp_code = b.code']];
        $field  =  "a.order_sn,a.goods_code,a.sp_code,a.goods_type,a.create_time,a.pay_time,a.go_time,a.sure_time,a.order_type,a.goods_name,a.total_price,a.total_settle_price,a.total_num,a.mobile,a.user_name,a.identification,a.remark";
        if($spType){
            $join[] = ['retail c','a.retail_code = c.code'];
            $field  .= ",a.retail_code,a.user_type,a.user_code,a.rebate_type,a.rebate_total_money,c.name as retail_name";
        }

        $res = db("order")->alias("a")->join($join)->field($field)->where($where)->find();
        if(!$res){
            return json(array("code" => 403,"msg" => "找不到订单或者已经被删除"));
        }
        //字段转换
        $res["create_time"]         = date("Y-m-d",$res["create_time"]);
        $res["go_time"]             = date("Y-m-d",$res["go_time"]);
        $res["sure_time"]           = date("Y-m-d",$res["sure_time"]);
        if($res["pay_time"])  $res["pay_time"] = date("Y-m-d",$res["pay_time"]);
        $res["total_price"]            = (float)$res["total_price"];
        $res["total_settle_price"]    =  (float)$res["total_settle_price"];
        if($spType){    //超级
            $res["rebate_total_money"]    =  (float)$res["rebate_total_money"];
        }

        //三种产品分开处理
        switch ($res["goods_type"]){
            case 1:     //1跟团
                $subField = $spType ? ['id','order_sn'] : ['id','order_sn','rebate_man_money','rebate_child_money'];
                $body = model("GroupOrder")->field($subField,true)->where(array("order_sn"=>$res["order_sn"]))->find()->toArray();
                break;
            case 2:     //2门票
                $subField = $spType ? ['id','order_sn'] : ['id','order_sn','rebate_man_money'];
                $body = model("TicketOrder")->field($subField,true)->where(array("order_sn"=>$res["order_sn"]))->find()->toArray();
                break;
            case 3:     //3酒景
                $subField = $spType ? ['id','order_sn'] : ['id','order_sn','rebate_man_money'];
                $body = model("SceneryOrder")->field($subField,true)->where(array("order_sn"=>$res["order_sn"]))->find()->toArray();
                break;
            default:
                $body = array();
        }
        $output = array_merge($res,$body);
        return json(array("code" => 200,"data" => $output));

    }

    //确定订单
    public function sure(){
        $orderSn = input("order_sn");
        if(empty($orderSn)){
            return json(array("code" => 404,"msg" => "订单号不能为空"));
        }

        $where["is_del"]         =  0;  //未删除
        $where["order_sn"]       =  $orderSn;

        $orderInfo = db("order")->field("order_type")->where($where)->find();
        if(!$orderInfo){
            return json(array("code" => 403,"msg" => "订单被删除或者不存在"));
        }
        if($orderInfo["order_type"] != 3){
            return json(array("code" => 403,"msg" => "不是未确认订单状态"));
        }

        try{
            db("order")->field("order_type")->where($where)->update(array("order_type"=>2));
        } catch (\Exception $e) {
            return json(array("code" => 403, "msg" => "保存出错，请再保存一次"));
        }
        return json(array("code" => 200,"msg" => "订单已确认"));
    }

    //订单出行
    public function trip(){
        $orderSn = input("order_sn");
        if(empty($orderSn)){
            return json(array("code" => 404,"msg" => "订单号不能为空"));
        }

        $where["is_del"]         =  0;  //未删除
        $where["order_sn"]       =  $orderSn;

        $orderInfo = db("order")->field("order_type")->where($where)->find();
        if(!$orderInfo){
            return json(array("code" => 403,"msg" => "订单被删除或者不存在"));
        }
        if($orderInfo["order_type"] != 2){
            return json(array("code" => 403,"msg" => "不是已确认订单状态"));
        }

        try{
            db("order")->field("order_type")->where($where)->update(array("order_type"=>4));
        } catch (\Exception $e) {
            return json(array("code" => 403, "msg" => "保存出错，请再保存一次"));
        }
        return json(array("code" => 200,"msg" => "订单状态已出行"));
    }
}
