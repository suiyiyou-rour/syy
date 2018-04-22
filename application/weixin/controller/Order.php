<?php
namespace app\weixin\controller;
use app\common\controller\WeixinBase;
class Order extends WeixinBase
{
    public function index()
    {

//        $db = db("");
//        $db->startTrans();   // 开启事务
//        try{
//            $db->table("order")->insert(array("num"=>"1"));
//            $db->table("syy_test2")->insert(array("name"=>112));
//            // 提交事务
//            $db->commit();
//            return 1;
//        } catch (\Exception $e) {
//            // 回滚事务
//            $db->rollBack();
//            return 2;
//        }
    }

    //添加订单
    public function add(){
//        $goodsCode = "t0020002";
//        $goTime = "2018-04-19";
        $goodsCode = input("post.goodsCode");
        $goTime = input("post.go_time");
        if(empty($goodsCode) || empty($goTime) || !is_Date($goTime)){
            return json(array("code" => 404,"msg" => "商品号或者出发日期出错"));
        }
        $goDate = strtotime($goTime);//出发日期时间戳
        //判断商品是不是上线
        $date = strtotime(date("Y-m-d", time()));
        $where = [
            "check_type"   =>  "5",             //上线
            "is_del"       =>  ["<>","1"],      //未删除
            "on_time"      =>  ["<=",$date],
            "off_time"     =>  [">=",$date],
            "code"         =>   $goodsCode
        ];
        $res = db('goods')->field("sp_code,show_title,advance_time,goods_type,price_type,stock_type,stock_num,rate")->where($where)->find();
        if(!$res){
            return json(array("code" => 405,"msg" => "商品找不到或者没有上线"));
        }

        //判断购买的时间是不是小于当天
        if($goDate < $date){
            return json(array("code" => 405,"msg" => "购买时间错误"));
        }

        //判断提前预定时间
        if($res["advance_time"] != 0 ){
            $goCheckTime = $goDate - $res["advance_time"];//出发日期 - 预定时间戳
            if($date > $goCheckTime){
                return json(array("code" => 405,"msg" => "商品设置了预定时间，请再详情中查看"));
            }
        }

        //三种产品分开处理
        switch ($res["goods_type"]){
            case 1:     //1跟团
                $info = \think\Loader::model('GroupOrder','logic')->add($res);
                break;
            case 2:     //2门票
                $info = \think\Loader::model('TicketOrder','logic')->add($res);
                break;
            case 3:     //3酒景
                $info = \think\Loader::model('SceneryOrder','logic')->add($res);
                break;
            default:
                return json(array("code" => 404, "msg" => "查询出错，请联系随意游小游"));
        }
        return json($info);
    }

    //订单删除
    public function del(){

    }

    //订单列表
    public function showList(){
        $userCode       = input("user_code");
        $page           = input("post.page");              //页码
        $goodsType      = input("post.goods_type");       //商品类型
        $order_type     = input("post.goods_type");       //订单状态
//        `order_type` tinyint(2) DEFAULT '1' COMMENT '订单状态 1待付款 2已付款，未出行 3待确认订单 4已出行 5取消订单 6退款中 7退款成功 8拒绝退款',

        if(empty($page)) $page = 1;

        $userCode = "100001";           //todo  用户类型

        $where["a.is_del"]       =  0;  //未删除
        $where["a.user_code"]    =  $userCode;
        $where["a.user_type"]    =  "1";    //todo 用户用户类型
        if($goodsType) $where["a.goods_type"] = $goodsType;
        if($order_type) $where["a.goods_type"] = $goodsType;

        $join           =  [['goods b','a.goods_code = b.code']];
        $count = db('order')->alias("a")->join($join)->where($where)->count('a.id');
        if(!$count){
            return json(array("code" => 200,"data" => array("count" => 0,"list" => array())));
        }

        $field = "a.goods_code,a.goods_name,a.total_price,a.total_num,a.order_type,b.head_img";
        $res = db("order")->alias("a")->join($join)->field($field)->where($where)->order("a.id desc")->page($page,10)->select();
        foreach ($res as &$k){
            $k["head_img"] = config("img_url").$k["head_img"];
        }
        $output["list"]  =  $res;
        $output["count"]  =  $count;
        return json(array("code" => 200,"data" => $output));
    }


    //
    public function show(){
        $orderSn = input("order_sn");
        $orderSn = "201804211548376959591604";
        $user_type = 2;//用户类型

        $where["a.is_del"]       =  0;  //未删除
        $where["a.order_sn"]     = $orderSn;

        $join   =  [['goods b','a.goods_code = b.code']];
        $field  =  "a.order_sn,a.goods_code,a.user_code,a.user_type,a.retail_code,a.create_time,a.pay_time,a.goods_type,a.go_time,a.order_type,a.goods_name,a.total_price,a.total_num,a.mobile,a.user_name,a.identification,a.remark,b.head_img";
        if($user_type == 2){    //分销商
            $field.= ",a.rebate_type,a.rebate_total_money,a.total_settle_price";
        }

        $res = db("order")->alias("a")->join($join)->field($field)->where($where)->find();
        if(!$res){
            return json(array("code" => 403,"msg" => "找不到订单或者已经被删除"));
        }
        $res["create_time"]         = date("Y-m-d",$res["create_time"]);
        if($res["pay_time"]){
            $res["pay_time"]            = date("Y-m-d",$res["pay_time"]);
        }
        $res["go_time"]             = date("Y-m-d",$res["go_time"]);
        $res["head_img"]            = config("img_url").$res["head_img"];

        if($user_type == 2){       //分销商
            $res["total_price"]        = (float)$res["total_price"];
            $res["total_settle_price"] = (float)$res["total_settle_price"];
            $res["rebate_total_money"] = (float)$res["rebate_total_money"];
        }


        //三种产品分开处理
        switch ($res["goods_type"]){
            case 1:     //1跟团
                $subField = $user_type == 2 ? ['id','order_sn'] : ['id','order_sn','rebate_man_money','rebate_child_money','settle_price','settle_child_price'];
                $body = model("GroupOrder")->field($subField,true)->where(array("order_sn"=>$res["order_sn"]))->find()->toArray();
                break;
            case 2:     //2门票
                $subField = $user_type == 2 ? ['id','order_sn'] : ['id','order_sn','rebate_man_money','settle_price'];
                $body = model("TicketOrder")->field($subField,true)->where(array("order_sn"=>$res["order_sn"]))->find()->toArray();
                break;
            case 3:     //3酒景
                $subField = $user_type == 2 ? ['id','order_sn'] : ['id','order_sn','rebate_man_money','settle_price'];
                $body = model("SceneryOrder")->field($subField,true)->where(array("order_sn"=>$res["order_sn"]))->find()->toArray();
                break;
            default:
                $body = array();
        }
        $output["head"]  =  $res;
        $output["body"]  =  $body;
        return json(array("code" => 200,"data" => $output));
    }


}
