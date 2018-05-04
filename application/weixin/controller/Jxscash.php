<?php
namespace app\weixin\controller;
use app\common\controller\WeixinBase;

class Jxscash extends WeixinBase
{
    //获取经销商余额
    public function getBalance(){
        $code = "54"; //todo cookie 拿
        $res = db("retail_money")->field("no_money")->where(array("retail_code" => $code))->find();
        if(!$res){
            return json(array("code" => 403,"msg" => "余额查询出错，请联系管理员"));
        }
        return json(array("code" => 200 ,"data" => $res));
    }

    //提现申请
    public function putInCash(){
        $money = input('money');
        $message = input('message');
        if(empty($money)) return json(array("code" => 405,"msg" => "参数错误"));
        if($money < 100) return json(array('code' => 405, "msg" => "提现金额不能小于100"));
        if($money > 100000) return json(array('code' => 405, "msg" => "提现金额不能大于100000"));

        $code = "54";//todo cookie 拿

        $res = db("retail_money")->field("no_money")->where(array("retail_code" => $code))->find();
        if(!$res) return json(array("code" => 403,"msg" => "余额查询出错，请联系管理员"));

        $jxs_money = $res["no_money"];
        if($money > $jxs_money) return json(array("code" => 403,"msg" => "提现金额不能大于账户余额，当前余额为".$jxs_money));

        $jxs_money = $jxs_money - $money;       //账户余额   先扣
        $JJmoney = 0 - $money;                  //提现金额


        $refundData["retail_code"]      = $code;                    //经销商code
        $refundData["refund_type"]      = 0;                        //提现状态
        $refundData["money"]            = $JJmoney;                  //提现金额
        $refundData["bill_balance"]     = $jxs_money;                //账户余额
        $refundData["start_time"]       = time();                    //请求时间
        $refundData["message"]          = $message;                   //经销商备注
        //应该提现的金额
        $procedureFee = $this->computeBrokerage($money);                       //手续费
        $cashMoney = $money - $procedureFee;                                   //提现的金额
        $refundData["remark_info"] = "提现实际金额是".$cashMoney.",手续费为".$procedureFee.",请确认！";  //平台备注


        $db = db("");
        $db->startTrans();   // 开启事务
        try{
            $db->table("syy_retail_money")->where(array("retail_code" => $code))->update(array('no_money'=>$jxs_money));
            $db->table("syy_retail_refund")->insert($refundData);
            // 提交事务
            $db->commit();
            return json(array("code" => 200 ,  'msg' => '操作成功'));
        } catch (\Exception $e) {
            // 回滚事务
            $db->rollBack();
            return json(array("code" => 403 , 'msg' => '操作失败，请联系管理员'));
        }


    }

    //账单详细列表
    public function getBillRecord(){
        $page = (int)input('page');
        $bill_type = input('type');
        if(empty($page)) $page = 1;

        $code = "54";

        $where["retail_code"]   =   $code;
        if($bill_type) $where['bill_type'] = $bill_type;//状态值

        $count = db('retail_bill')->where($where)->count('id');
        if(!$count){
            return json(array("code" => 200,"data" => array("count" => 0,"list" => array())));
        }

        $res = db("retail_bill")->field("order_sn,bill_type,bill_money,bill_time,bill_balance")->order("id desc")->page($page,10)->where($where)->select();
        if($res){
            foreach ($res as &$k){
                $k["bill_time"] = date("Y-m-d H:i:s",$k["bill_time"]);
            }
        }

        $output["list"]  =  $res;
        $output["count"]  =  $count;
        return json(array("code" => 200,"data" => $output));

    }

    //提现请求记录
    public function getRefund(){
        $page = (int)input('page');
        $bill_type = input('type');
        if(empty($page)) $page = 1;

        $code = "54";

        $where["retail_code"]   =   $code;
        if($bill_type) $where['bill_type'] = $bill_type;//状态值

        $count = db('retail_refund')->where($where)->count('id');
        if(!$count){
            return json(array("code" => 200,"data" => array("count" => 0,"list" => array())));
        }

        $res = db("retail_refund")->field("refund_type,refund_type,money,start_time,message,bill_balance")->order("id desc")->page($page,10)->where($where)->select();
        if($res){
            foreach ($res as &$k){
                $k["start_time"] = date("Y-m-d H:i:s",$k["start_time"]);
            }
        }

        $output["list"]  =  $res;
        $output["count"]  =  $count;
        return json(array("code" => 200,"data" => $output));
    }

    //计算佣金
    private function computeBrokerage($money){
        if(100 <= $money && $money < 1000){
            $data = $money * 0.1 ;
            $data = round($data,2);
        }else if(1000 <= $money && $money < 3000){
            $data = $money * 0.08 ;
            $data = round($data,2);
        }else if(3000 <= $money && $money < 5000){
            $data = $money * 0.07 ;
            $data = round($data,2);
        }else if(5000 < $money){
            $data = $money * 0.06 ;
            $data = round($data,2);
        }
        return $data;
    }
}
