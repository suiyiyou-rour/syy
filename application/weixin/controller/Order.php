<?php
namespace app\weixin\controller;
//use app\common\controller\WeixinBase;
class Order extends LoginBase
{
    public function index()
    {
        $str = '[{"name":"\u81ea\u8d39\u9879\u76ee\u540d\u79f0","place":"\u798f\u5efa","price":"20","detail":"dddddddd","num":"4"}]';
        $array = json_decode($str,true);
        var_dump($array);
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
        $page           = input("post.page");              //页码
        $goodsType      = input("post.goods_type");       //商品类型
        $order_type     = input("post.order_type");       //订单状态
        if(empty($page)) $page = 1;
        if($goodsType) $where["a.goods_type"] = $goodsType;
        if($order_type) $where["a.order_type"] = $order_type;

        $isRetail = isRetail(); //判断经销商
        if($isRetail){
            $where["a.retail_code"]   =  getUserCode();     //经销商
        }else{
            $where["a.user_code"]   =  getUserCode();       //用户code
            $where["a.user_type"]   =  1;                   //用户订单
        }
        $where["a.is_del"]       =  0;      //未删除

        $join           =  [['goods b','a.goods_code = b.code']];
        $count = db('order')->alias("a")->join($join)->where($where)->count('a.id');
        if(!$count){
            return json(array("code" => 200,"data" => array("count" => 0,"list" => array())));
        }

        $field = "a.order_sn,a.goods_code,a.goods_name,a.total_price,a.total_num,a.order_type,b.head_img";
        if($isRetail) $field .= ",a.total_settle_price";   //经销商显示结算总价

        $res = db("order")->alias("a")->join($join)->field($field)->where($where)->order("a.id desc")->page($page,10)->select();
        foreach ($res as &$k){
            $k["head_img"] = config("img_url").$k["head_img"];
            $k["total_price"] = (float)$k["total_price"];
            if($isRetail) $k["total_settle_price"] = (float)$k["total_settle_price"]; ;
        }
        $output["list"]  =  $res;
        $output["count"]  =  $count;
        return json(array("code" => 200,"data" => $output));
    }

    //订单详细
    public function show(){
        $orderSn = input("order_sn");
        if(empty($orderSn)){
            return json(array("code" => 404,"msg" => "订单号不能为空"));
        }
//        $orderSn = "201804211548376959591604";
        if(isRetail()){
            $user_type = 2;  //经销商
        }else{
            $user_type = 1;  //用户
        }

        $where["a.is_del"]       =  0;  //未删除
        $where["a.order_sn"]     = $orderSn;

        $join   =  [
            ['goods b','a.goods_code = b.code'],
            ['retail c','a.retail_code = c.code'],
        ];
        $field  =  "a.order_sn,a.goods_code,a.user_code,a.user_type,a.retail_code,a.create_time,a.pay_time,a.goods_type,a.go_time,a.order_type,a.goods_name,a.total_price,a.total_num,a.mobile,a.user_name,a.identification,a.remark,b.head_img,c.name as retail_name,c.mobile as retail_mobile";
        if($user_type == 2){    //分销商
            $field.= ",a.rebate_type,a.rebate_total_money,a.total_settle_price";
        }

        $res = db("order")->alias("a")->join($join)->field($field)->where($where)->find();
        if(!$res){
            return json(array("code" => 403,"msg" => "找不到订单或者已经被删除"));
        }
        $res["create_time"]         = date("Y-m-d H:i:s",$res["create_time"]);
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
        $output = array_merge($res,$body);
        return json(array("code" => 200,"data" => $output));
    }


}
