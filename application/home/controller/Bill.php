<?php
namespace app\home\controller;
use app\common\controller\HomeBase;

class Bill extends HomeBase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        echo "common/homeBase/index";
        echo "home/index/index";
    }

    //显示对账列表
    public function showList(){
        $where = array();

        $begin_date = input("begin_date");
        if($begin_date) $where["begin_date"] = [ ">=" , strtotime($begin_date) ];
        $end_date = input("end_date");
        if($end_date) $where["end_date"] = [ "<=" , strtotime($end_date) ];
        $goods_type = input("goods_type");
        $where["goods_type"] = $goods_type ?  $goods_type : 1 ;//默认跟团

        $page = input("post.page");                   //页码
        if(empty($page)) $page = 1;

        if(!getSpType()){                              //超级管理
            $where["sp_code"] = getSpCode();         //供应商
        }

        $join   = [['sp b','a.sp_code = b.code']];

        $count = db('sp_bill')->alias("a")->join($join)->where($where)->count('a.id');
        if(!$count){
            return json(array("code" => 200,"data" => array("count" =>0)));
        }

        $res = db("sp_bill")->field("a.*,b.com_name")->alias("a")->join($join)->where($where)->order('a.id desc')->page($page,10)->select();
        foreach ($res as &$k){
            $k["begin_date"]         = date("Y-m-d", $k["begin_date"]);
            $k["end_date"]           = date("Y-m-d", $k["end_date"]);
        }
        $output["list"]  =  $res;
        $output["count"]  =  $count;
        return json(array("code" => 200,"data"=>$output));
    }

    //对账结算
    public function sure(){
        if(!getSpType()){                              //超级管理
            return json(array("code"=>405,"msg"=>"只有超级管理才能点击结算"));
        }
        $id = input("post.id");
        if(!$id){
            return json(array("code"=>404,"msg"=>"参数不能为空"));
        }

        try{
            db("sp_bill")->where(array("id" => $id))->update(array("is_settle"=>1));
            return json(array("code" => 200,"msg" => "账单已经结算"));
        } catch (\Exception $e) {
            return json(array("code" => 403, "msg" => "确定订单出错，请联系管理员"));
        }

    }
}
