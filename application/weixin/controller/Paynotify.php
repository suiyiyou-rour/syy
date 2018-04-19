<?php
namespace app\weixin\controller;
use app\common\controller\Base;
class Paynotify extends Base
{
    public function index(){
        
    }

    public function home(){
//        $xml = $GLOBALS['HTTP_RAW_POST_DATA']; //返回的xml
//        if(empty($xml)){
//            return;
//        }
//        libxml_disable_entity_loader(true);
//        $postObj = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)));
////        $postStr = file_get_contents("php://input");
////        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
//
//        $xmldata["order_sn"]         =       $postObj->out_trade_no;        //订单号
//        $xmldata["openid"]           =       $postObj->openid;               //openid
//        $xmldata["price"]            =       ($postObj->total_fee) / 100;    //价格
//        $xmldata["tag"]              =       $postObj->attach;               //标记
//        $xmldata["create_time"]     =       time();                         //添加时间
//        $xmldata["xml"]              =       $xml;                           //微信返回的整个xml
        $xmldata["order_sn"]         =       "201804181051104658447228";        //订单号
        $xmldata["openid"]           =       "oojM_wqpt3w-GOcjAnWbLmZUWEhY";               //openid
        $xmldata["price"]            =       "12.22";    //价格
        $xmldata["tag"]              =       "syy";               //标记
        $xmldata["create_time"]     =       time();                         //添加时间
        $xmldata["xml"]              =       "xxx";                           //微信返回的整个xml

        //记录添加
        $record = db('pay_record')->insert($xmldata);
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
                $resdate = $this->group($orderInfo);
                break;
            case 2:     //2门票
                $resdate = $this->ticket($orderInfo);
                break;
            case 3:     //3酒景
                $resdate = $this->scenery($orderInfo);
                break;
        }



        db('pay_record')->where(array("id" => $record))->update($resdate);
        die;








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

        //修改订单
        $orderRes = db('order')->where(array("order_sn" => $orderInfo["order_sn"]))->update($orderDate);
        //价格日历
        $price = db("group_calendar")->where(array("goods_code"=>$orderInfo['goods_code'],"date"=> $orderInfo['go_time']))->update($priceDate);
        //主表销量
        $this->addGoodsSales($orderInfo['goods_code'],$sell);
//
        if($orderRes === false){
            return array("bool" => 2, "error_info" => "订单状态保存失败");
        }
        if($price === false){
            return array("bool" => 3,"error_info" => "价格日历数据保存异常");
        }
        return array("bool" => 1);
    }

    //门票
    private function ticket($orderInfo){
        $goods = db('goods')->field("price_type,stock_type,stock_num")->where(array("code"=>$orderInfo['goods_code']))->find();
        if (empty($goods)) {
            return array("bool" =>0,"error_info"=>"没有商品数据");
        }
        //价格查询
        if($goods["price_type"] == 1){
            //价格日历
            $price = db("ticket_calendar")->where(array("goods_code"=>$orderInfo['goods_code'],"date"=> $orderInfo['go_time']))->find();
        }else{
            //有效期
            $price = db("ticket_indate")->where(array("goods_code"=>$orderInfo['goodsCode'], "begin_date"=>["<=",$orderInfo['go_time']],"end_date"=>[">=",$orderInfo['go_time']]))->find();
        }





        $tickInfo = M('tick')
            ->field('t_tick_date,t_tick_sell,t_tick_kc')
            ->where(array('t_code' => $orderInfo['t_tick_code']))
            ->find();
        if (empty($tickInfo)) {
            return array("code" =>0,"msg"=>"没有商品价格数据");
        }

        $o_data['t_pay_time']           =   date("Y-m-d H:i:s", time());            //付款时间
        $o_data['t_tick_order_type']   =    2;                                      //订单状态
        $Model = M();
//        $Model->startTrans(); // 开启事务
        //todo 更新 销量 库存 改变订单状态
        $om = $Model->table('lf_tick_order')->where(array('t_order_sn' => $orderSn))->save($o_data);

        $ck_errorinfo = 1;
        if ($tickInfo['t_tick_date'] == 1 ) {       //有效期
            $ywhere['unix_timestamp(y_b_time)']     =       array('elt', strtotime($orderInfo['t_go_date']));
            $ywhere['unix_timestamp(y_e_time)']     =       array('egt', strtotime($orderInfo['t_go_date']));
            $ywhere['y_code']                         =       $orderInfo['t_tick_code'];
            $ywhere['y_user_code']                   =       $orderInfo['t_tick_id'];
            if (is_numeric($tickInfo['t_tick_kc'])) {
                if($tickInfo['t_tick_kc'] != -1){//库存
                    if(($tickInfo['t_tick_kc'] - $orderInfo['t_tick_num']) >= 0){
                        $tsdata['t_tick_kc'] = $tickInfo['t_tick_kc'] - $orderInfo['t_tick_num'];       //有效期库存
                    }else{
                        $tsdata['t_tick_kc'] = 0;           //有效期库存
                    }
                }
            }else{
                $ck_errorinfo = 0;
            }
            //有效期表跟更新销量
            $ym = $Model->table('lf_tick_y')->where(array($ywhere))->setInc('y_sell_num', $orderInfo['t_tick_num']);
        } else {
            $pwhere['p_code'] = $orderInfo['t_tick_code'];
            $pwhere['unix_timestamp(p_date)'] = array('eq', strtotime($orderInfo['t_go_date']));
            $priceInfo = M('tick_price')->where($pwhere)->find();
            if(!$priceInfo){
                $ck_errorinfo = 0;
            }else{
                if( $priceInfo['p_ck'] !== null && $priceInfo['p_ck'] != -1 ){
                    if(($priceInfo['p_ck'] - $orderInfo['t_tick_num']) >= 0){
                        $data['p_ck'] = $priceInfo['p_ck'] - $orderInfo['t_tick_num'];      //价格日历库存
                    }else{
                        $data['p_ck'] = 0;      //价格日历库存
                    }
                }
            }
            $data['p_sell_num'] = $priceInfo['p_sell_num'] + $orderInfo['t_tick_num'];       //价格日历销量
            //更新价格日历
            $ym = $Model->table('lf_tick_price')->where($pwhere)->save($data);
        }
        //主表销量
        $tsdata['t_tick_sell'] = $tickInfo['t_tick_sell'] + $orderInfo['t_tick_num'];
        $pm = $Model->table('lf_tick')->where(array('t_code' => $orderInfo['t_tick_code'], 't_user_id' => $orderInfo['t_tick_id']))->save($tsdata);

        if(!$om){
            return array("code" => 0, "msg" => "订单状态保存失败" ,"num"=>$orderInfo['t_tick_num']);
        }
        if($ck_errorinfo == 0){
            return array("code" => 2, "msg" => "价格日历或者有效期字段不是数字" ,"num"=>$orderInfo['t_tick_num']);
        }
        if(!$pm){
            return array("code" => 2,"msg" => "总销量库存保存异常","num"=>$orderInfo['t_tick_num']);
        }
        if(!$ym){
            return array("code" => 2,"msg" => "价格日历或者有效期数据保存异常","num"=>$orderInfo['t_tick_num']);
        }
        return array("code" => 1,"msg" => "","num"=>$orderInfo['t_tick_num']);
    }

    //景酒
    private function scenery($orderInfo){
        return 1;
    }

    //添加商品主表销量
    private function addGoodsSales($goodsCode,$num){
        $goodsRes = db('goods')->where(array("code"=>$goodsCode))->setInc('sales', $num);
        if(!$goodsRes){
            return false;
        }
        return true;
    }



}
