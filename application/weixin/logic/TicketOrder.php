<?php
namespace app\weixin\logic;
use think\Request;
class TicketOrder extends Order
{
    //门票
    public function add($info){
        //数据接收
        $data = $this->data();
        //数据验证
        $validate =$this->validate($data);
        if($validate !== true){
            return array("code" => 405,"msg" => $validate);
        }

        //副表
        $ticketInfo = db("goods_ticket")->field("min_buy_num,max_buy_num")->where(array("goods_code"=>$data['goodsCode']))->find();
        if(!$ticketInfo){
            return array("code" => 403,"msg" => "产品查询出错，请联系小游");
        }

        if($ticketInfo["max_buy_num"] != 0){
            if($data['man_num'] > $ticketInfo["max_buy_num"]){
                return array("code" => 403,"msg" => "每单最多购买人数,不能超过" . $ticketInfo["max_buy_num"] . "人");
            }
        }

        if ($data['man_num'] < $ticketInfo["min_buy_num"]) {
            return array('code' => 403, "msg" => "每单最少购买人数,不能小于" . $ticketInfo["min_buy_num"] . "人");
        }

//        return $data;
        //价格查询
        if($info["price_type"] == 1){
            //价格日历
            $price = db("ticket_calendar")->where(array("goods_code"=>$data['goodsCode'],"date"=> $data['go_time']))->find();
        }else{
            //有效期
            $price = db("ticket_indate")->where(array("goods_code"=>$data['goodsCode'], "begin_date"=>["<=",$data['go_time']],"end_date"=>[">=",$data['go_time']]))->find();
        }
        if(!$price) return array("code" => 403,"msg" => "没有出发当天的价格数据");

        //库存判断
        if($info["stock_type"] == 2){   //库存类型为总库存
            if($info["price_type"] == 1){   //价格日历
                if ($data['man_num'] > $info["stock_num"]) {    //主表判断
                    return (array('code' => 403, "msg" => "库存已经不够，目前最多还剩" . $info["stock_num"] . "人"));
                }
            }else{                          //有效期
                if ($data['man_num'] > $price["stock_num"]) {    //有效期表判断
                    return (array('code' => 403, "msg" => "库存已经不够，目前最多还剩" . $info["stock_num"] . "人"));
                }
            }
        }else if($info["stock_type"] == 3 && $info["price_type"] == 1){     //日库存 价格日历才有
            if ($data['man_num'] > $price["stock_num"]) {    //日历表
                return (array('code' => 403, "msg" => "库存已经不够，目前最多还剩" . $price["stock_num"] . "人"));
            }
        }

        //订单总价 = 数量 * 价格
        $totalPrice = $data['man_num'] * $price["plat_price"];
        //结算总价 = 数量 * 结算价格
        $totalSettlePrice = $data['man_num'] * $price["settle_price"];
        //查看返佣设置
        $rebate = $this->rebateInfo($data['goodsCode'],$data['retail_code']);
        //返佣总价
        $totalRebatePrice = $data['man_num'] * $rebate["money"];

        //判断前端传过来的价格和计算的对比
        if ((int)($totalPrice * 100) !== (int)($data['ticket_price'] * 100)) {
            return array('code' => 403, "msg" => "金额计算异常");
        }

        $orderSn = $this->createOrderSn();  //订单编号

        //主表数据
        $order["order_sn"]              =   $orderSn;                       //订单号
        $order["goods_code"]            =   $data['goodsCode'];            //商品编号
        $order["sp_code"]               =   $info["sp_code"];              //供应商编号
        $order["user_code"]             =   $data['user_code'];            //下单用户
        $order["retail_code"]           =   $data['retail_code'];          //经销商code
        $order["goods_type"]            =   "2";                            //门票
        $order["create_time"]           =   time();                         //订单创建时间
        $order["go_time"]               =   $data['go_time'];               //出游时间
        $order["goods_name"]            =   $info["show_title"];           //产品名称
        $order["total_price"]           =   $totalPrice;                    //订单总价
        $order["total_settle_price"]   =   $totalSettlePrice;              //结算总价
        $order["total_num"]             =   $data['man_num'];              //总人数
        $order["mobile"]                =   $data['mobile'];                //联系方式 手机号
        $order["user_name"]             =   $data['user_name'];             //联系人用户名称
        $order["identification"]        =   $data["identification"];       //主要联系人身份证
        $order["remark"]                =   $data["remark"];                //备注
        $order["rate"]                  =   $info["rate"];                   //费率
        $order["rebate_type"]           =   $rebate["type"];                 //返利类型 1有 0没
        $order["rebate_total_money"]   =   $totalRebatePrice;                //返利总金额

        //副表数据
        $subOrder["order_sn"]                   =   $orderSn;//订单号
        $subOrder["market_price"]               =   $price["market_price"];          //市场价 - 成人'
        $subOrder["plat_price"]                 =   $price["plat_price"];            //平台价 - 成人
        $subOrder["settle_price"]               =   $price["settle_price"];          //结算价 - 成人
        $subOrder["rebate_man_money"]           =   $rebate["money"];                //返利金额 - 成人
        $subOrder["man_num"]                     =   $data['man_num'];                //成人数量
        $subOrder["identity_array"]              =   $data["identity_array"];       //身份证数组

        $db = db("");
        $db->startTrans();   // 开启事务
        try{
            $db->table("syy_order")->insert($order);
            $db->table("syy_ticket_order")->insert($subOrder);
            // 提交事务
            $db->commit();
            return array("code" => 200 , 'data' => array('orderSn'=> $orderSn),'msg' => '下单成功');
        } catch (\Exception $e) {
            // 回滚事务
            $db->rollBack();
            return array("code" => 403 , 'msg' => '下单失败请稍后再试');
        }
    }

    //数据接收
    private function data(){
        $gain = ['goodsCode','man_num','child_num','house_num','mobile','user_name','go_time','retail_code','user_code',"identification","charged_item","ticket_price","identity_array","remark"];
        $data = Request::instance()->only($gain, 'post');//        $data = input('post.');
        $data['man_num']         = empty($data['man_num']) ? 0 : (int)$data['man_num']; //成人数量
        $data['identity_array'] = empty($data['identity_array']) ? "[]" : json_encode($data["identity_array"]); //身份数组
        $data['ticket_price']     = empty($data['ticket_price']) ? 0 : (int)$data['ticket_price']; //总价
        $data['go_time']         =  strtotime($data['go_time']);            //出发时间
        if(empty($data['remark'])) $data['remark'] = "";    //备注信息

        //判断经销商
        if(isRetail()){
            $data['user_type']   =  2;              //订单经销商
            $data['retail_code'] = getUserCode();   //经销商code
        }else{
            $data['user_type']   =  1;              //用户
            if(empty($data['retail_code'])) $data['retail_code'] = "54";    //经销商编码 默认54 小游
        }
        $data['user_code']  = getUserCode();        //用户code

        // 模拟数据
//        $data['goodsCode']      =  "t0020002";           //产品code
//        $data['man_num']        =  1;                     //成人数量
//        $data['mobile']         =  "18060481803";        //主要联系人电话
//        $data['user_name']      =  "刘祖梁";              //主要联系人名称
//        $data['go_time']        =  "2018-04-19";         //出发日期
//        $data['retail_code']    =  "54";                 //经销商编码
//        $data['user_code']      =  "100001";             //下单用户
//        $data["identification"] = "352201199303141637"; //主要联系人身份证
//        $data["identity_array"] = "[]";                   //身份数组 每个人的身份证
//        $data["remark"]          = "";                     //备注信息 100
//
//        $data['go_time'] = strtotime($data['go_time']);

        return $data;
    }

    //数据验证
    private function validate($data){
        if ($data['man_num'] < 1 ) {
            return "最小购买人数要一人";
        }
        if (empty($data['mobile']) || !is_phone($data['mobile'])) {
            return "手机号码错误";
        }
        if(empty($data['user_name'])){
            return "联系人不能为空";
        }
        if (empty($data['user_code'])) {
            return "你还没有登录";
        }
        if (!empty($data['identification'])) {
            if(!is_Identification_card($data['identification'])){
                return "身份证错误";
            }
        }
        if(mb_strlen($data["remark"],"UTF8") > 100){
            return "备注信息不能大于100个字符";
        }
        return true;
    }

}