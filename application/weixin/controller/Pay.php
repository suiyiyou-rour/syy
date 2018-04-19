<?php
namespace app\weixin\controller;
use app\common\controller\WeixinBase;
class Pay extends WeixinBase
{
    public function index()
    {
        $outputdata["order_sn"]         =      "201804171527066610168424";        //订单号
        $outputdata["openid"]           =       "oojM_wqpt3w-GOcjAnWbLmZUWEhY";   //openid
        $outputdata["price"]            =       (500) / 100;    //价格
        $outputdata["tag"]              =       "syy";               //标记
        $outputdata["create_time"]     =       time();                         //添加时间
        $outputdata["xml"]              =       "xxx";                           //微信返回的整个xml

        //记录添加
        $res = db('pay_record')->insert($outputdata);
        var_dump($res);

    }

    public function go(){
        //todo 需要orderSn code 两个参数
        $orderSn = input('orderSn');//订单号
        if(empty($orderSn)){
            return json(array("code" => 404 , "msg" => "订单号不能为空"));
        }
        $where = [
            "order_sn"     => $orderSn ,
            "is_del"       => 0 ,          //未删除
            "order_type"   => 1           //待付款
        ];
        $orderInfo = db("order")->field("goods_name,total_price")->where($where)->find();
        if($orderInfo){
            return json(array("code" => 405 , "msg" => "找不到待付款订单"));
        }
        $orderName = $orderInfo["goods_name"];
        $orderPrice = $orderInfo["total_price"];
        $orderAttach = "syy";
        $orderGoods_tag = "随意游";


//        $orderName = "asdsadsadsa";
//        $orderSn = "201804171527066610168424";
//        $orderAttach = "syy";
//        $orderPrice = "1";
//        $orderGoods_tag = "1232132";
        $tal_fee = $orderPrice * 100; // 支付价格


        //支付必须文件
        Vendor('Wxpay.WxPay#Api');
        Vendor('Wxpay.WxPay#JsApiPay');

        //①、获取用户openid
        $tools = new \JsApiPay();
        $openId = $tools->GetOpenid();
//        $openId = $tools->GetOpenidFromMp($code); #todo 用前端code换取openid

        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($orderName);//设置商品或支付单简要描述
        $input->SetAttach($orderAttach);//商户携带订单的自定义数据

        $input->SetOut_trade_no($orderSn);//设置商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号

        $input->SetTotal_fee($tal_fee);//订单总金额，只能为整数
        $input->SetTime_start(date("YmdHis"));//设置订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010。其他详见时间规则
        $input->SetTime_expire(date("YmdHis", time() + 600));//设置订单失效时间
        $input->SetGoods_tag($orderGoods_tag);//设置商品标记

        $input->SetNotify_url("http://admin.suiyiyou.net/index.php/Weixin/Paynotify/home");//设置接收微信支付异步通知回调地址
        $input->SetTrade_type("JSAPI");//

        $input->SetOpenid($openId);
        $order = \WxPayApi::unifiedOrder($input);

        $jsApiParameters = $tools->GetJsApiParameters($order);
        return json(array("code"=>200,"data"=>$jsApiParameters));
    }

}
