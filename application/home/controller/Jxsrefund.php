<?php
namespace app\home\controller;
use app\common\controller\HomeBase;

class Jxsrefund extends HomeBase
{
    public function __construct()
    {
        parent::__construct();
    }


    //经销商提现
    public function showList(){
        $where = array();
        $page = input("post.page");                   //页码
        if(empty($page)) $page = 1;
        $retail_code = input("post.retail_code");
        if($retail_code) $where["a.retail_code"] = $retail_code;    //经销商编号
        $refund_type = input("post.refund_type");
        if($refund_type) $where["a.refund_type"] = $refund_type;    //请求状态


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
//        $id = 1;
        if(!$id){
            return json(array("code" => 404,"msg" => "参数不能为空"));
        }
        $msg = input("remark_info");
        $data["remark_info"] = $msg ? $msg : "";
        $data["refund_type"] = 1;
        $data["sure_time"]   = time();


        $info  = db("retail_refund")->where(array("id"=>$id))->find();
        if(!$info){
            return json(array("code" => 403,"msg" => "找不到这条记录"));
        }
        if($info["refund_type"] != 0){
            return json(array("code" => 403,"msg" => "不在请求状态"));
        }
        //改变提现状态
        $billData["retail_code"]        = $info["retail_code"];
        $billData["bill_type"]          =  1;
        $billData["bill_balance"]       =  1;//账户余额
        $billData["bill_money"]          =  $info["money"];//金额
        $billData["bill_time"]          = time();

        $res = db("retail_refund")->where(array("id"=>$id))->update($data);
//        db("retail_bill")->where(array(""));
//        db("retail_bill")->insert();
        if($res !== false){
            return json(array("code" => 200,"msg" => "成功"));
        }
        return json(array("code" => 200,"msg" => "失败，请再试一次"));


//        DROP TABLE IF EXISTS `syy_retail_bill`;
//CREATE TABLE `syy_retail_bill` (
//        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
//  `retail_code` varchar(24) NOT NULL COMMENT '经销商标识',
//  `order_sn` varchar(24) DEFAULT NULL COMMENT '订单号 进账才有',
//  `bill_type` enum('1','2','3') DEFAULT '1' COMMENT '状态 1进账 2提现 3退款 ',
//  `bill_balance` decimal(10,2) NOT NULL COMMENT '账户余额',
//  `bill_money` decimal(9,2) NOT NULL COMMENT '金额',
//  `bill_time` int(11) NOT NULL COMMENT '时间',
//  PRIMARY KEY (`id`),
//  UNIQUE KEY `order_sn` (`order_sn`)
//) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='经销商账单记录表';
//
//
//DROP TABLE IF EXISTS `syy_retail_money`;
//CREATE TABLE `syy_retail_money` (
//        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
//  `retail_code` varchar(24) NOT NULL COMMENT '经销商编码',
//  `total_money` decimal(11,2) DEFAULT '0.00' COMMENT '总金额',
//  `no_money` decimal(11,2) DEFAULT '0.00' COMMENT '未提现金额',
//  `already_money` decimal(11,2) DEFAULT '0.00' COMMENT '已提现金额',
//  `deal_money` decimal(11,2) DEFAULT '0.00' COMMENT '处理中金额',
//  PRIMARY KEY (`id`),
//  UNIQUE KEY `retail_code` (`retail_code`)
//) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='经销商账户表';
    }

    //提现拒绝
    public function refuse(){
        $id = input("post.id");
        if(!$id){
            return json(array("code" => 404,"msg" => "参数不能为空"));
        }

        $msg = input("remark_info");
        $data["remark_info"] = $msg ? $msg : "";
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
