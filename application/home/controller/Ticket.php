<?php
/**
 * 跟团游
 */
namespace app\home\controller;
use app\common\controller\HomeBase;

/**
 * 门票
 */
class Ticket extends HomeBase
{
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

    //add show option 统一处理
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
        $goodsField = "a.code,a.show_title,a.check_type,a.price_type,a.sales,a.plat_price,a.on_time,a.off_time,a.online_type";
        $ticketField = "b.goods_class,b.place_name,b.ticket_type";
        $allField = $goodsField.','.$ticketField;

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
            //上线时间显示处理
            if(empty($k["on_time"])){
                if($k["online_type"] == 1){
                    $k["on_time"] = "上线时间为审核通过当天";
                }else if($k["online_type"] == 3){
                    $k["on_time"] = "上线时间为手动上线当天";
                }
            }else{
                if($k["check_type"] == 5){
                    //显示 0是预上线  1是上线  2不显示
                    if(time() > $k["on_time"]){
                        $k["checkOnlie"] = 1;
                    }else{
                        $k["checkOnlie"] = 0;
                    }
                }else{
                    $k["checkOnlie"] = 2;
                }
                $k["on_time"] = date("Y-m-d",$k["on_time"]);
            }
            $k["off_time"] = date("Y-m-d",$k["off_time"]);
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
        if($res["check_type"] != 1 && $res["check_type"] != 4 && $res["check_type"] != 6){
            return json(array("code" => 405,"msg" => "商品只有在保存状态，驳回状态，下线状态才能提交审核"));
        }
        $output = db('goods')->where($where)->update(array("check_type"=>2));
        if($output){
            return json(array("code" => 200,"msg" => "提交审核成功"));
        }else{
            return json(array("code" => 403,"msg" => "提交审核失败，请再试一次"));
        }
    }

    //上线
    public function online(){
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return json(array("code" => 404,"msg" => "参数错误404"));
        }

        $where = [
            "code"        =>  $goodsCode,
            "is_del"      =>  ["<>","1"],          //未删除
            "goods_type" =>   '2'                  //门票
        ];
        $res = db('goods')->field("check_type,online_type")->where($where)->find();
        if(empty($res)){
            return json(array("code" => 405,"msg" => "商品号找不到"));
        }
        if($res["check_type"] != 3){
            return json(array("code" => 405,"msg" => "商品只有通过审核才能上线"));
        }
        if($res["online_type"] == 3){                           //手动上线处理时间
            $data["on_time"] = strtotime(date('Y-m-d',time())); //上线时间
        }
        $data["check_type"] = 5;    //上线
        $output = db('goods')->where($where)->update($data);
        if($output){
            return json(array("code" => 200,"msg" => "上线成功"));
        }else{
            return json(array("code" => 403,"msg" => "上线失败，请再试一次"));
        }
    }

    //下线
    public function offline(){
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
        if($res["check_type"] != 5){
            return json(array("code" => 405,"msg" => "商品只有上线状态才能下线"));
        }
        $output = db('goods')->where($where)->update(array("check_type"=>6));
        if($output){
            return json(array("code" => 200,"msg" => "下线成功"));
        }else{
            return json(array("code" => 403,"msg" => "下线失败，请再试一次"));
        }
    }

}
