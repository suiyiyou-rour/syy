<?php
/**
 * 跟团游
 */
namespace app\home\controller;
use app\common\controller\HomeBase;
use think\Request;
//use \think\Validate;
class Group extends HomeBase
{
    public function __construct()
    {
        parent::__construct();
        cookie("name",335);
    }

    public function index()
    {
        $str = '[{"beginPlace":"123","routes":[{"type":"\u706b\u8f66","place":"123"}],"traffic":"123123","meals":{"bf":{"bool":"false","mark":"123"},"lunch":{"bool":"false","mark":"123"},"dinner":{"bool":"false","mark":"123"}},"stay":"\u9732\u8425\/\u5176\u4ed6","trips":[{"time":"123123","des":"12312312312","place":["123"],"free":"0"}]}]';
//        $obj = json_decode($str,true);
//        foreach ($obj as $key){
//            var_dump($key["trips"]);
//        }
//        var_dump($obj[0]["trips"]);
        echo  strtotime(date('Y-m-d', time()));

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

    //商品列表显示
    public function goodsList(){
        $where["a.goods_type"]  = "1";                  //跟团游
        $where["a.is_del"]      =    ["<>","1"];        //未删除

        if(!getSpType()){                                 //超级管理
            $where["a.sp_code"] = getSpCode();           //供应商
        }

        $show_title = input("post.show_title");         //随意游产品名称
        if($show_title){
            $where["a.show_title"] = ['like',"%".$show_title."%"];
        }

        $inside_title = input("post.inside_title");     //供应商产品名称
        if($inside_title){
            $where["a.inside_title"] = ['like',"%".$inside_title."%"];
        }

        $begin_address = input("post.begin_address");   //出发地
        if($begin_address){
            $where["b.begin_address"] = $begin_address;
        }

        $end_address = input("post.end_address");        //目的地
        if($end_address){
            $where["b.end_address"] = $end_address;
        }

        $check_type = input("post.check_type");        //审核状态
        if($check_type){
            $where["a.check_type"] = $check_type;
        }else{
            $where["a.check_type"] = ['<>',0];          //0全查
        }

        $page = input("post.page");        //页码
        if(empty($page)){
            $page = 1;
        }

        $alias = array("syy_goods" => "a","syy_goods_group" => "b");
        $join = [['syy_goods_group','a.code = b.goods_code']];
        $goodsField = "a.code,a.inside_code,a.inside_title,a.show_title,a.check_type,plat_price,deadline_date as date,settle_price";
        $groupField = "b.play_type,b.begin_address,b.child_price_type";
        $allField = $goodsField.','.$groupField;
        $count = db('goods')->alias($alias)->where($where)->join($join)->count('a.id');

        if(!$count){
            return json(array("code" => 200,"data" => array("count"=>0)));
        }

        $res = db('goods')->alias($alias)->field($allField)->where($where)->join($join)->order("a.last_edit_time desc")->page($page,10)->select();
        foreach ($res as &$k){
            $calendarWhere["goods_code"] = $k["code"];
            //儿童价格 单房差
            $calendarField = "MIN(plat_child_price) as plat_child_price,MIN(plat_house_price) as plat_house_price";
            $priceArray = db('group_calendar')->field($calendarField)->where($calendarWhere)->find();
            if($priceArray){
                $k["plat_child_price"]      = $priceArray["plat_child_price"] ;//儿童价格
                $k["plat_house_price"]      = $priceArray["plat_house_price"] ; //单房差
            }

            if($k["child_price_type"] == 0){//无儿童价格
                $k["plat_child_price"] = "--";
            }else if($k["child_price_type"] == 1){//和成人同价
                $k["plat_child_price"] = $k["plat_price"];
            }

            if(empty($k["plat_house_price"])){
                $k["plat_house_price"] = "--";
            }
            $k["date"] = date("Y-m-d",$k["date"]);
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
            "goods_type" =>   '1'                  //跟团
        ];
        $res = db('goods')->field("check_type")->where($where)->find();
        if(empty($res)){
            return json(array("code" => 405,"msg" => "商品号找不到或者这条商品不属于你"));
        }
        if($res["check_type"] == 0){
            db('goods')->where(array("code"=>$goodsCode))->delete();
            db('goods_group')->where(array("goods_code"=>$goodsCode))->delete();
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
            "goods_type" =>   '1'                  //跟团
        ];
        $res = db('goods')->field("check_type")->where($where)->find();
        if(empty($res)){
            return json(array("code" => 405,"msg" => "商品号找不到"));
        }
        if($res["check_type"] != 1){
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
