<?php
namespace app\weixin\logic;
class Order
{
    //景酒
    public function scenery($array){

    }

    //生成订单号
    protected function createOrderSn()
    {
        //生成24位唯一订单号码，格式：YYYY-MMDD-HHII-SS-NNNN,NNNN-CC，其中：YYYY=年份，MM=月份，DD=日期，HH=24格式小时，II=分，SS=秒，NNNNNNNN=随机数，CC=检查码
        @date_default_timezone_set("PRC");
        //订购日期
        $order_date = date('Y-m-d');
        //订单号码主体（YYYYMMDDHHIISSNNNNNNNN）
        $order_id_main = date('YmdHis') . rand(10000000, 99999999);
        //订单号码主体长度
        $order_id_len = strlen($order_id_main);
        $order_id_sum = 0;
        for ($i = 0; $i < $order_id_len; $i++) {
            $order_id_sum += (int)(substr($order_id_main, $i, 1));
        }
        //唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
        $order_id = $order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT);
        return $order_id;
    }

    //返佣
    protected function rebateInfo($goodsCode,$retailCode){
        $where = [
            "goods_code"   =>  $goodsCode,
            "retail_code"  =>  $retailCode,
            "open"          =>  1
        ];
        $res = db('goods_rebate')->field("money,child_money")->where($where)->find();
        if(!$res){
            $res["money"] = 0;
            $res["child_money"] = 0;
            $res["type"] = 0;
        }else{
            $res["type"] = 1;
        }
        return $res;
    }


}