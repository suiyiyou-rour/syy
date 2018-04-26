<?php
namespace app\home\controller;
use app\common\controller\HomeBase;

class Retailbill extends HomeBase
{
    public function __construct()
    {
        parent::__construct();
    }

    //经销商进出账记录
    public function showList(){
        $where = array();
        $bill_type = input("post.bill_type");
        if($bill_type) $where["a.bill_type"] = $bill_type;    //账单状态
        $retail_code = input("post.retail_code");
        if($retail_code) $where["a.retail_code"] = $retail_code;    //经销商编号
        $page = input("post.page");                   //页码
        if(empty($page)) $page = 1;

        $join = [['retail b','a.retail_code = b.code']];

        $count = db('retail_bill')->alias("a")->join($join)->where($where)->count('a.id');
        if(!$count){
            return json(array("code" => 200,"data" => array("count" =>0)));
        }

        $field = "a.retail_code,a.order_sn,a.bill_type,a.bill_balance,a.bill_money,a.bill_time,b.com_name";
        $res = db('retail_bill')->alias("a")->field($field)->join($join)->where($where)->order('a.id desc')->page($page,10)->select();
        foreach ($res as &$k){
            $k["bill_time"] = date("Y-m-d H:i:s",$k["bill_time"]);
        }
        $output["list"]  =  $res;
        $output["count"]  =  $count;
        return json(array("code" => 200,"data"=>$output));
    }

}
