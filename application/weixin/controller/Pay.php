<?php
namespace app\weixin\controller;
use app\common\controller\WeixinBase;
class Pay extends WeixinBase
{
    public function index()
    {

    }

    public function go(){
        //todo 需要orderSn code 两个参数
        $orderSn = input('order_sn');//订单号
        $code = input('wxcode');
        if(!$orderSn || !$code){
            return json(array("code" => 404 , "msg" => "订单号不能为空或者微信code不能为空"));
        }

        $where = [
            "order_sn"     => $orderSn ,
            "is_del"       => 0 ,          //未删除
            "order_type"   => 1           //待付款
        ];
        $orderInfo = db("order")->field("goods_name,total_price")->where($where)->find();
        if(!$orderInfo){
            return json(array("code" => 405 , "msg" => "找不到待付款订单"));
        }
        $wxpay = cookie("jsApiParameters");
        if($wxpay && $wxpay["orderSn"] == $orderSn){
            $jsApiParameters = json_decode($wxpay["code"],true);
            return json(array("code"=>200,"data"=>$jsApiParameters));
        }


        $orderName = $orderInfo["goods_name"];
        $orderPrice = $orderInfo["total_price"];
        $orderAttach = "syy";
        $orderGoods_tag = "随意游";

//        $orderName = "asdsadsadsa";
//        $orderSn = "201804171527066638859999";
//        $orderAttach = "syy";
//        $orderPrice = "1";
//        $orderGoods_tag = "1232132";
        $tal_fee = $orderPrice * 100; // 支付价格


        //支付必须文件
        Vendor('Wxpay.WxPay#Api');
        Vendor('Wxpay.WxPay#JsApiPay');

        //①、获取用户openid
        $tools = new \JsApiPay();

        #todo 用前端code换取openid
        $wxInfoApi      =   \think\Loader::model('WxInfoApi','service');;
        $openId          =   $wxInfoApi->getOpenid($code);
        if(!$openId){
            return json(array("code"=>403,"msg"=>"微信授权失败，请重新进入订单页面"));
        }
//        $openId = $tools->GetOpenid();

        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($orderName);//设置商品或支付单简要描述
        $input->SetAttach($orderAttach);//商户携带订单的自定义数据

        $input->SetOut_trade_no($orderSn);//设置商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号

        $input->SetTotal_fee($tal_fee);//订单总金额，只能为整数
        $input->SetTime_start(date("YmdHis"));//设置订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010。其他详见时间规则
        $input->SetTime_expire(date("YmdHis", time() + 600));//设置订单失效时间
        $input->SetGoods_tag($orderGoods_tag);//设置商品标记

        $input->SetNotify_url("http://admin.suiyiyou.net/index.php/weixin/Paynotify/home");//设置接收微信支付异步通知回调地址
        $input->SetTrade_type("JSAPI");//

        $input->SetOpenid($openId);
        $order = \WxPayApi::unifiedOrder($input);

        $jsApiParameters = $tools->GetJsApiParameters($order);
        //把订单号和前面存起来
        $wxpaysdk["orderSn"] = $orderSn;
        $wxpaysdk["code"] = $jsApiParameters;
        cookie("jsApiParameters",$wxpaysdk,599);


        $jsApiParameters = json_decode($jsApiParameters,true);
        return json(array("code"=>200,"data"=>$jsApiParameters));
    }

}
