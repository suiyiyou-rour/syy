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
        $page = input("post.page");                   //页码
        if(empty($page)) $page = 1;

        $join = [['retail_money b','a.code = b.retail_code']];
        // 查找申请的和通过的
        $count = db('retail')->alias("a")->join($join)->where('a.type','in','1,3')->count("a.id");
        if(!$count){
            return \json(array("code" => 200,"data" => array(),"count" => 0 ));
        }

        $field = "a.*,b.total_money,b.no_money,b.already_money";
        $retailData = db('retail')->alias("a")->join($join)->field($field)->where('a.type','in','1,3')->page($page,10)->order('a.reg_time','desc')->select();
        return \json(array('code' => 200 ,'data' => $retailData ,'count' => $count));
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
        $field  =  "a.order_sn,a.goods_code,a.sp_code,a.goods_type,a.create_time,a.pay_time,a.go_time,a.sure_time,a.order_type,a.goods_name,a.total_price,a.total_settle_price,a.total_num,a.mobile,a.user_name,a.identification,a.remark,b.com_name";
        if($spType){
            $join[] = ['retail c','a.retail_code = c.code'];
            $field  .= ",a.retail_code,a.user_type,a.user_code,a.rebate_type,a.rebate_total_money,c.com_name as retail_name";
        }

        $res = db("order")->alias("a")->join($join)->field($field)->where($where)->find();
        if(!$res){
            return json(array("code" => 403,"msg" => "找不到订单或者已经被删除"));
        }
        //字段转换
        $res["create_time"]         = date("Y-m-d H:i:s",$res["create_time"]);
        $res["go_time"]             = date("Y-m-d H:i:s",$res["go_time"]);
        $res["sure_time"] = $res["sure_time"] ? date("Y-m-d H:i:s",$res["sure_time"]) : "未确定出行订单";
        $res["pay_time"] = $res["pay_time"] ? date("Y-m-d H:i:s",$res["pay_time"]) : "未支付";
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
            db("order")->where($where)->update( array("order_type" => 2 ) );
        } catch (\Exception $e) {
            return json(array("code" => 403, "msg" => "保存出错，请再保存一次"));
        }
        return json(array("code" => 200,"msg" => "订单已确认"));
    }

    //订单出行
    public function trip(){
        $orderSn = input("order_sn");
//        $orderSn = "201804171547199857727005";
        if(empty($orderSn)){
            return json(array("code" => 404,"msg" => "订单号不能为空"));
        }

        $where["is_del"]         =  0;  //未删除
        $where["order_sn"]       =  $orderSn;

        $orderInfo = db("order")->field("*")->where($where)->find();
        if(!$orderInfo){
            return json(array("code" => 403,"msg" => "订单被删除或者不存在"));
        }
        if($orderInfo["order_type"] != 2){
            return json(array("code" => 403,"msg" => "不是已确认订单状态"));
        }
        //验证供应商 分销商账户

        //查看分销商是不是返利分销商
        $reJoin   = [['retail_money b','a.code = b.retail_code']];
        $reField  = "a.type,b.total_money,b.no_money";
        $reWhere = array("a.code" => $orderInfo["retail_code"]);
        $retailInfo = db("retail")->alias("a")->join($reJoin)->field($reField)->where($reWhere)->find();
        if(!$retailInfo){
            return json(array("code" => 403,"msg" => "分销商账户出现异常，请联系管理员"));
        }
        if($retailInfo["type"] == 3 && $orderInfo["rebate_type"] == 1){   //返利分销商
            $yj = $orderInfo["total_price"] - $orderInfo["total_settle_price"] + $orderInfo["rebate_total_money"];  //佣金
        }else{
            //计算佣金
            $yj = $orderInfo["total_price"] - $orderInfo["total_settle_price"]; //佣金
        }

        //更改订单状态
        //供应商对账单
        $spbData["sp_code"]                 =   $orderInfo["sp_code"];              //供应商标识
        $spbData["order_sn"]                =   $orderInfo["order_sn"];             //订单编号
        $spbData["goods_type"]              =   $orderInfo["goods_type"];           //商品类型
        $spbData["begin_date"]              =   time();                              //账单开始时间
        $spbData["end_date"]                =   strtotime("+7 day");                //账单结束时间
        $spbData["total_price"]             =   $orderInfo["total_price"];          //订单总价
        $spbData["total_settle_price"]     =   $orderInfo["total_settle_price"];   //结算总价
        //经销商进账记录
        $rebData["retail_code"]             =   $orderInfo["retail_code"];           //经销商标识
        $rebData["order_sn"]                =   $orderInfo["order_sn"];               //订单编号
        $rebData["bill_type"]               =   1;                                     //进账
        $rebData["bill_balance"]            =   $retailInfo["no_money"];              //账户余额
        $rebData["bill_money"]              =   $yj;                                   //金额
        $rebData["bill_time"]               =   time();                                 //进账时间
        //经销商金额添加
        $remData["total_money"]            =   $retailInfo["total_money"] + $yj;        //总金额
        $remData["no_money"]               =   $retailInfo["no_money"] + $yj;           //未提现金额


        $db = db("");
        $db->startTrans();   // 开启事务
        try{
            //更改订单状态
            $db->table("syy_order")->where($where)->update(array("order_type" => 4));
            //供应商对账单
            $db->table("syy_sp_bill")->insert($spbData);
            //经销商账单表
            $db->table("syy_retail_bill")->insert($rebData);
            //经销商金额添加
            $db->table("syy_retail_money")->where(array("retail_code" => $orderInfo["retail_code"]))->update($remData);
            // 提交事务
            $db->commit();
            return json(array("code" => 200,"msg" => "订单状态已出行"));
        } catch (\Exception $e) {
            // 回滚事务
            $db->rollBack();
            return json(array("code" => 403, "msg" => "确定订单出错，请联系管理员"));
        }


    }

    //退款
    public function refund(){
        if(!getSpType()){                              //超级管理
            return json(array("code"=>405,"msg"=>"只有超级管理才能点击结算"));
        }
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

        if($orderInfo["order_type"] != 2 && $orderInfo["order_type"] != 3){
            return json(array("code" => 403,"msg" => "订单不在未出行状态或者不在待确认订单状态"));
        }

        try{
            db("order")->where($where)->update( array("order_type" => 7 ) );
        } catch (\Exception $e) {
            return json(array("code" => 403, "msg" => "保存出错，请再保存一次"));
        }
        return json(array("code" => 200,"msg" => "订单已改成退款状态"));

    }
}
