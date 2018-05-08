<?php
namespace app\weixin\controller;
use app\common\controller\Base;
class Paynotify extends Base
{
    public function index(){
        
    }

    public function home(){
        if(!isset($GLOBALS['HTTP_RAW_POST_DATA'])) return;
        $xml = $GLOBALS['HTTP_RAW_POST_DATA']; //返回的xml
        if(empty($xml)) return;
        libxml_disable_entity_loader(true);
        $postObj = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)));
//        $postStr = file_get_contents("php://input");
//        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

        $xmldata["order_sn"]         =       $postObj->out_trade_no;        //订单号
        $xmldata["openid"]           =       $postObj->openid;               //openid
        $xmldata["price"]            =       ($postObj->total_fee) / 100;    //价格
        $xmldata["tag"]              =       $postObj->attach;               //标记
        $xmldata["create_time"]     =       time();                         //添加时间
        $xmldata["xml"]              =       $xml;                           //微信返回的整个xml
//        $xmldata["order_sn"]         =       "201805081446433607291620";        //订单号
//        $xmldata["openid"]           =       "oojM_wqpt3w-GOcjAnWbLmZUWEhY";               //openid
//        $xmldata["price"]            =       "0.02";    //价格
//        $xmldata["tag"]              =       "syy";               //标记
//        $xmldata["create_time"]     =       time();                         //添加时间
//        $xmldata["xml"]              =       "xxx";                           //微信返回的整个xml

        //记录添加
        db('pay_record')->insert($xmldata);
        $record = db('pay_record')->getLastInsID();
//        if(!$record) return;

        $where = [
            "order_sn"     => $xmldata["order_sn"] ,
            "is_del"       => 0 ,          //未删除
            "order_type"   => 1           //待付款
        ];
        $orderInfo = db("order")->where($where)->find();
        //order_sn goods_code go_time total_num 跟团需要数据
        if(!$orderInfo) return;

        //三种产品分开处理
        switch ($orderInfo["goods_type"]){
            case 1:     //1跟团
                $resdata = $this->group($orderInfo);
                break;
            case 2:     //2门票
                $resdata = $this->ticket($orderInfo);
                break;
            case 3:     //3酒景
                $resdata = $this->scenery($orderInfo);
                break;
            default:
                $resdata = array("bool" => 0,"error_info" => "产品类型异常");
        }


        db('pay_record')->where(array("id" => $record))->update($resdata);

        /* 如果不回复这个xml  微信会给我们发送三次xml */
        $su = '<xml> <return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
////        echo 'SUCCESS';
        echo $su;
        exit;
    }

    //跟团
    private function group($orderInfo){
        $price = db("group_calendar")
            ->field("stock_is_open,stock_num,need_stock_num,sales_num")
            ->where(array("goods_code"=>$orderInfo['goods_code'],"date"=> $orderInfo['go_time']))
            ->find();
        if(!$price){
            return array("bool" => 0, "error_info" => "没有价格日历数据");
        }

        $stockNum     = $price["stock_num"];       //免确认库存
        $needSnum     = $price["need_stock_num"]; //需确认库存
        $salesNum     = $price["sales_num"];      //价格日历销量
        $num          = $orderInfo["total_num"];  //总人数
        
        if($num > ($stockNum + $needSnum)){
            $priceDate["stock_num"]          = 0;
            $priceDate["need_stock_num"]     = 0;
            $orderDate["order_type"]         = 3;    // 待确认订单
            $sell = $stockNum + $needSnum;
        }else{
            if ($num <= $stockNum) {                                            // 总人数小于等于免确认库存
                $priceDate["stock_num"] = $stockNum - $num;                    //免 = 免 - all 需不动
                $orderDate["order_type"] = 2;                               // 2已付款，未出行(正常订单)
            } else if ($num > $stockNum && $num < $needSnum) {                 // 总人数大于免确认库存 小于需要确认库存
                $priceDate["need_stock_num"] = $needSnum - $num;              //需 = 需 - all 免不动
                $orderDate["order_type"] = 3;                               //待确认订单
            } else {                                                           //总人数大于免确认库存 也大于需要确认库存
                $priceDate["stock_num"] = 0;                                  //免 0
                $priceDate["need_stock_num"] = $needSnum - ($num - $stockNum);//需 = 免 - （ all - 免 ）
                $orderDate["order_type"] = 3;                               // 待确认订单
            }
            $sell = $num;                                                       //销量
        }
        $priceDate["sales_num"] = $salesNum + $sell;                          //价格日历销量
        $orderDate["pay_time"]  = time();                                      //支付时间


        try{
            //修改订单
            db('order')->where(array("order_sn" => $orderInfo["order_sn"]))->update($orderDate);
        } catch (\Exception $e) {
            return array("bool" => 2, "error_info" => "订单状态保存失败");
        }
        try{
            db("group_calendar")->where(array("goods_code"=>$orderInfo['goods_code'],"date"=> $orderInfo['go_time']))->update($priceDate);
        } catch (\Exception $e) {
            return array("bool" => 3,"error_info" => "价格日历数据保存异常");
        }
        $goodsSell = $this->addGoodsSales($orderInfo['goods_code'],$sell);
        if(!$goodsSell){
            return array("bool" => 4,"error_info" => "主表销量数据保存异常");
        }
        return array("bool" => 1);
    }

    //门票 todo 待测试
    private function ticket($orderInfo){
        $goods = db('goods')->field("price_type,stock_type,stock_num,sales")->where(array("code"=>$orderInfo['goods_code']))->find();
        if (!$goods) return array("bool" =>0,"error_info"=>"没有商品数据");

        $orderDate["pay_time"]      = time();      //支付时间
        $orderDate["order_type"]    = 2;           // 2已付款
        //修改订单
        try{
            db('order')->where(array("order_sn" => $orderInfo["order_sn"]))->update($orderDate);
        } catch (\Exception $e) {
            return array("bool" => 2, "error_info" => "订单状态保存失败");
        }

        $num          = $orderInfo["total_num"];  //总人数
        //库存
        if($goods["price_type"] == 1){//价格日历
            $calendar = db("ticket_calendar")->field("stock_num,sales_num")->where(array("goods_code"=>$orderInfo['goods_code'],"date"=> $orderInfo['go_time']))->find();
            if (!$calendar) return array("bool" =>0,"error_info"=>"没有价格日历数据");

            $cData["sales_num"] = $calendar["sales_num"] + $num;    //价格日历销量
            if($goods["stock_type"] == 2){//总库存
                $gData["stock_num"] = ($goods["stock_num"] - $num) >= 0 ? $goods["stock_num"] - $num : 0;//主表库存
            }else if($goods["stock_type"] == 3){ //3日库存
                $cData["stock_num"] = ($calendar["stock_num"] - $num) >= 0 ? $calendar["stock_num"] - $num : 0;//价格日历库存
            }
            try{
                db("ticket_calendar")->where(array("goods_code"=>$orderInfo['goods_code'],"date"=> $orderInfo['go_time']))->update($cData);
            } catch (\Exception $e) {
                return array("bool" => 3, "error_info" => "价格日历数据异常");
            }
        }else{//有效期
            $indate = db("ticket_indate")->field("stock_num,sales_num")->where(array("goods_code"=>$orderInfo['goods_code']))->find();
            if (!$indate) return array("bool" =>0,"error_info"=>"有效期没有数据");

            $iData["sales_num"] = $indate["sales_num"] + $num;    //有效期表销量
            if($goods["stock_type"] == 2){ //总库存
                $gData["stock_num"] =  ($goods["stock_num"] - $num) >= 0 ? $goods["stock_num"] - $num : 0;    //主表库存
                $iData["stock_num"] = ($indate["stock_num"] - $num) >= 0 ? $indate["stock_num"] - $num : 0;    //有效期表销量
            }
            try{
                db("ticket_indate")->where(array("goods_code"=>$orderInfo['goods_code']))->update($iData);
            } catch (\Exception $e) {
                return array("bool" => 3, "error_info" => "有效期数据异常");
            }
        }

        //主表数据
        try{
            $gData["sales"] = $goods["sales"] + $num;
            db('goods')->where(array("code"=>$orderInfo['goods_code']))->update($gData);
        } catch (\Exception $e) {
            return array("bool" => 4,"error_info" => "主表库存-销量数据保存异常");
        }
        return array("bool" => 1);
    }

    //景酒 todo 待测试
    private function scenery($orderInfo){
        $price = db("scenery_calendar")
            ->field("stock_is_open,stock_num,reserve_is_open,reserve_time,reserve_num,sales_num")
            ->where(array("goods_code"=>$orderInfo['goods_code'],"date"=> $orderInfo['go_time']))
            ->find();
        if(!$price){
            return array("bool" => 0, "error_info" => "没有价格日历数据");
        }
        $num          = $orderInfo["total_num"];  //总人数
        $stockNum     = $price["stock_num"];       //库存
        if($num <= $stockNum){       //正常库存判断
            $priceDate["stock_num"] = $stockNum - $num;
            $orderDate["order_type"] = 2;                               // 2已付款，未出行(正常订单)
        }else{
            $priceDate["stock_num"] = 0;
            //库存保留房库存判断
            if($price["reserve_is_open"] == 1){ //保留房库存
                $priceDate["reserve_num"] = $price - ($num - $stockNum);

                $todayTime = strtotime(date("Y-m-d"),time());
                if($orderInfo['go_time'] == $todayTime && (time() - $todayTime) > $price["reserve_time"]){  //保留房时间不够
                    $orderDate["order_type"] = 3;                               // 3待确认订单
                }else{
                    $orderDate["order_type"] = 2;                               // 2已付款，未出行(正常订单)
                }
            }else{
                $orderDate["order_type"] = 3;                                    // 3待确认订单
            }
        }

        $priceDate["sales_num"] = $price["sales_num"] + $num;                          //价格日历销量
        $orderDate["pay_time"]  = time();                                      //支付时间

        try{
            //修改订单
            db('order')->where(array("order_sn" => $orderInfo["order_sn"]))->update($orderDate);
        } catch (\Exception $e) {
            return array("bool" => 2, "error_info" => "订单状态保存失败");
        }
        try{
            //价格日历
            db("scenery_calendar")->where(array("goods_code"=>$orderInfo['goods_code'],"date"=> $orderInfo['go_time']))->update($priceDate);
        } catch (\Exception $e) {
            return array("bool" => 3,"error_info" => "价格日历数据保存异常");
        }
        $goodsSell = $this->addGoodsSales($orderInfo['goods_code'],$num);
        if(!$goodsSell){
            return array("bool" => 4,"error_info" => "价格日历数据保存异常");
        }
        return array("bool" => 1);
    }

    //添加商品主表销量
    private function addGoodsSales($goodsCode,$num){
        try{
            db('goods')->where(array("code"=>$goodsCode))->setInc('sales', $num);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }



}
