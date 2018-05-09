<?php
namespace app\home\controller;
use app\common\controller\HomeBase;

/**
 * 经销商提现
 */
class Jxsrefund extends HomeBase
{
    public function __construct()
    {
        parent::__construct();
        if(!getSpType()){
            echo json_encode(array("code"=>405,"msg"=>"只有超级管理员才有权限"));
            die;
        }
    }

    //经销商提现
    public function showList(){
        $where = array();
        $page = input("post.page");                   //页码
        if(empty($page)) $page = 1;
        $retail_code = input("post.retail_code");
        if($retail_code) $where["a.retail_code"] = $retail_code;    //经销商编号
        $refund_type = input("post.refund_type");
        if($refund_type !== false) $where["a.refund_type"] = $refund_type;    //请求状态


        $join = [['retail b','a.retail_code = b.code']];
        $count = db('retail_refund')->alias("a")->join($join)->where($where)->count('a.id');
        if(!$count){
            return json(array("code" => 200,"data" => array("count" =>0)));
        }

        $field = "a.*,b.com_name";
        $res = db('retail_refund')->alias("a")->field($field)->join($join)->where($where)->order('a.id desc')->page($page,10)->select();
        foreach ($res as &$k){
            $k["start_time"] = date("Y-m-d H:i:s",$k["start_time"]);
            $k["sure_time"] = $k["sure_time"] ? date("Y-m-d H:i:s",$k["sure_time"]) : "还没有确定";
        }
        $output["list"]  =  $res;
        $output["count"]  =  $count;
        return json(array("code" => 200,"data"=>$output));
    }

    //提现确定
    public function sure(){
        $id = input("post.id");
        if(!$id) return json(array("code" => 404,"msg" => "参数不能为空"));

        //提现请求记录
        $info  = db("retail_refund")->where(array("id" => $id))->find();
        if(!$info) return json(array("code" => 403,"msg" => "找不到这条记录"));
        if($info["refund_type"] != 0) return json(array("code" => 403,"msg" => "不在请求状态"));

        //经销商金额
        $money = db("retail_money")->where(array("retail_code" => $info["retail_code"]))->find();
        if(!$money) return json(array("code" => 403,"msg" => "经销商账户出错，请联系管理员"));

        //提现记录表
        $remark_info  = input("remark_info");
        if($remark_info) $refundData["remark_info"] = $remark_info;
        $refundData["refund_type"]   = 1;           //同意 1
        $refundData["sure_time"]     = time();

        //经销商账单记录表
        $billData["retail_code"]        = $info["retail_code"];
        $billData["bill_type"]          =  1;                        //进账
        $billData["bill_balance"]       =  $money["no_money"];      //账户余额
        $billData["bill_money"]         =  $info["money"];          //金额
        $billData["bill_time"]          = time();

        //经销商账户表
        $moneyData["total_money"]   = $money["total_money"] + $info["money"];
        $moneyData["no_money"]      = $money["no_money"] + $info["money"];


        $db = db("");
        $db->startTrans();   // 开启事务
        try{
            $db->table("syy_retail_refund")->where(array("id" => $id))->update($refundData);
            $db->table("syy_retail_bill")->insert($billData);
            $db->table("syy_retail_money")->where(array("retail_code" => $info["retail_code"]))->update($moneyData);
            // 提交事务
            $db->commit();
            return json(array("code" => 200 ,  'msg' => '操作成功'));
        } catch (\Exception $e) {
            // 回滚事务
            $db->rollBack();
            return json(array("code" => 403 , 'msg' => '操作失败，请联系管理员'));
        }
    }

    //提现拒绝
    public function refuse(){
        $id = input("post.id");
        if(!$id){
            return json(array("code" => 404,"msg" => "参数不能为空"));
        }

        $remark_info = input("remark_info");
        if($remark_info) $data["remark_info"] = $remark_info;
        $data["refund_type"] = 2;
        $data["sure_time"]   = time();

        $info  = db("retail_refund")->field("refund_type")->where(array("id"=>$id))->find();
        if(!$info){
            return json(array("code" => 403,"msg" => "找不到这条记录"));
        }
        if($info["refund_type"] != 0){
            return json(array("code" => 403,"msg" => "不在请求状态"));
        }
        $res = db("retail_refund")->where(array("id"=>$id))->update($data);
        if($res !== false){
            return json(array("code" => 200,"msg" => "拒绝成功"));
        }
        return json(array("code" => 200,"msg" => "失败，请再试一次"));
    }
}
