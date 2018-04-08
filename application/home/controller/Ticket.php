<?php
/**
 * 跟团游
 */
namespace app\home\controller;
use app\common\controller\HomeBase;

class Ticket extends HomeBase
{
    public function __construct()
    {
        parent::__construct();

    }

    public function index()
    {

    }

    //商品添加
    public function add(){
        $this->dispatcher();
    }

    //商品添加显示
    public function show(){
        $this->dispatcher();
    }

    //商品页面选择显示
    public function option(){
        $this->dispatcher();
    }

    //列表显示
    public function goodsList(){
        $where["a.goods_type"]  =    "2";               //门票
        $where["a.is_del"]      =    ["<>","1"];        //未删除

        if(!getSpType()){                                 //超级管理
            $where["a.sp_code"] = getSpCode();           //供应商
        }

        $show_title = input("post.show_title");         //随意游产品名称
        if($show_title){
            $where["a.show_title"] = ['like',"%".$show_title."%"];
        }

        $code = input("post.code");                     //产品编号
        if($code){
            $where["a.code"] = $code;
        }

        $check_type = input("post.check_type");        //审核状态
        if($check_type){
            $where["a.check_type"] = $check_type;
        }else{
            $where["a.check_type"] = ['<>',0];         //0全查
        }

        $page = input("post.page");                     //页码
        if(empty($page)){
            $page = 1;
        }

        $join = [['syy_goods_ticket b','a.code = b.goods_code']];
        $goodsField = "a.code,a.show_title,a.check_type,a.price_type,a.sales,a.plat_price";
        $groupField = "b.goods_class,b.place_name,b.ticket_type";
        $allField = $goodsField.','.$groupField;

        $count = db('goods')->alias("a")->where($where)->join($join)->count('a.id');
        if(!$count){
            return json(array('code' => 200,"data" => array("count"=>0)));
        }
//
        $res = db('goods')->alias("a")->field($allField)->where($where)->join($join)->order('a.last_edit_time desc')->page($page,10)->select();
        foreach ($res as &$k){
            if($k["price_type"] == 1){          //价格日历date
                $dateArray = db('ticket_calendar')->field("MIN(date) as minDate,MAX(date) as maxDate")->where(array("goods_code"=>$k["code"]))->find();
                if($dateArray){
                    //todo 前端不需要区分
                    $k["begin_date"] = date("Y-m-d",$dateArray["minDate"]);
                    $k["end_date"] = date("Y-m-d",$dateArray["maxDate"]);
                }
            }else if($k["price_type"] == 2){    //有效期
                $indateArray = db('ticket_indate')->field("begin_date,end_date")->where(array("goods_code"=>$k["code"]))->find();
                if($indateArray){
                    $k["begin_date"] = date("Y-m-d",$indateArray["begin_date"]);
                    $k["end_date"] = date("Y-m-d",$indateArray["end_date"]);
                }
            }
            $k["place_name"] = json_decode($k["place_name"],true);
        }

        $output["list"]  =  $res;
        $output["count"]  =  $count;
        return json(array("code" => 200,"data" => $output));
    }

    //商品删除
    public function del(){
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return json(array("code" => 404,"msg" => "参数错误404"));
        }
        $where = [
            "code"        =>  $goodsCode,
            "sp_code"     =>  getSpCode(),         // 供应商code
            "is_del"      =>  ["<>","1"],          //未删除
            "goods_type" =>   '2'                  //门票
        ];
        $res = db('goods')->field("check_type")->where($where)->find();
        if(empty($res)){
            return json(array("code" => 405,"msg" => "商品号找不到或者这条商品不属于你"));
        }
        if($res["check_type"] == 0){
            db('goods')->where(array("code"=>$goodsCode))->delete();
            db('goods_ticket')->where(array("goods_code"=>$goodsCode))->delete();
            db('goods_supply')->where(array("goods_code"=>$goodsCode))->delete();
            db('goods_create')->where(array("goods_code"=>$goodsCode))->delete();
        }else{
            $output = db('goods')->where(array("code" => $goodsCode))->update(array("is_del"=>1));
            if($output === false){
                return json(array("code" => 403,"msg" => "删除失败，请稍后再试一次"));
            }
        }
        return json(array('code' => 200,'msg' => '删除成功'));
    }

    //提交审核
    public function goodsAudit(){
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return json(array("code" => 404,"msg" => "参数错误404"));
        }
        $where = [
            "code"        =>  $goodsCode,
            "is_del"      =>  ["<>","1"],          //未删除
            "goods_type" =>   '2'                  //门票
        ];
        $res = db('goods')->field("check_type")->where($where)->find();
        if(empty($res)){
            return json(array("code" => 405,"msg" => "商品号找不到"));
        }
        if($res["check_type"] != 1 ){
            return json(array("code" => 405,"msg" => "商品不在保存状态"));
        }
        $output = db('goods')->where(array("code" => $goodsCode))->update(array("check_type"=>2));
        if($output){
            return json(array("code" => 200,"msg" => "提交审核成功"));
        }else{
            return json(array("code" => 403,"msg" => "提交审核失败，请再试一次"));
        }
    }

    //分配
    private function dispatcher(){
        $request = request();
        $controller  = ucfirst($request->controller());
        $action  = ucfirst($request->action());
        $state = input('state');
        if($state == null || $state == ""){
            echo json_encode(array("code" => 404,"msg" => "参数错误404"));
            return;
        }
        $res = \think\Loader::model('Goods','logic')->dispatcher($controller,$action,$state);
        echo json_encode($res);
        return;

    }

}
