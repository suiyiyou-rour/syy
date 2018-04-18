<?php
namespace app\weixin\controller;
use app\common\controller\WeixinBase;
class Order extends WeixinBase
{
    public function index()
    {
//        $res = is_Date("2018-01-021");
//        var_dump($res);
        $data = array("identity_array"=>array("k"=>1));
        if($data["remark"]){
            echo 1;
        }
//        $data['man_num'] = empty($data['man_num']) ? 0 : (int)$data['man_num'];
//
//
//        $data['identity_array'] = empty($data['identity_array']) ? "[]" : json_encode($data["identity_array"]); //身份数组
//        var_dump($data);

//        $model = model("");
//        $model->startTrans();   // 开启事务
//        try{
//            $model->table("order")->insert(array("num"=>"1"));
//            $model->table("syy_test2")->insert(array("name"=>112));
//            // 提交事务
//            $model->commit();
//            return 1;
//        } catch (\Exception $e) {
//            // 回滚事务
//            $model->rollBack();
//            return 2;
//        }
    }

    //添加订单
    public function add(){
        $goodsCode = "t0020002";
        $goTime = "2018-04-19";
        if(empty($goodsCode) || empty($goTime) || !is_Date($goTime)){
            return json(array("code" => 404,"msg" => "商品号或者出发日期出错"));
        }
        $goDate = strtotime($goTime);//出发日期时间戳
        //判断商品是不是上线
        $date = strtotime(date("Y-m-d", time()));
        $where = [
            "check_type"   =>  "5",             //上线
            "is_del"       =>  ["<>","1"],      //未删除
            "on_time"      =>  ["<=",$date],
            "off_time"     =>  [">=",$date],
            "code"         =>   $goodsCode
        ];
        $res = db('goods')->field("sp_code,show_title,advance_time,goods_type,price_type,stock_type,stock_num,rate")->where($where)->find();
        if(!$res){
            return json(array("code" => 405,"msg" => "商品找不到或者没有上线"));
        }

        //判断购买的时间是不是小于当天
        if($goDate < $date){
            return json(array("code" => 405,"msg" => "购买时间错误"));
        }

        //判断提前预定时间
        if($res["advance_time"] != 0 ){
            $goCheckTime = $goDate - $res["advance_time"];//出发日期 - 预定时间戳
            if($date > $goCheckTime){
                return json(array("code" => 405,"msg" => "商品设置了预定时间，请再详情中查看"));
            }
        }

        //三种产品分开处理
        switch ($res["goods_type"]){
            case 1:     //1跟团
                $info = \think\Loader::model('GroupOrder','logic')->add($res);
                break;
            case 2:     //2门票
                $info = \think\Loader::model('TicketOrder','logic')->add($res);
                break;
            case 3:     //3酒景
                $info = \think\Loader::model('SceneryOrder','logic')->add($res);
                break;
            default:
                return json(array("code" => 404, "msg" => "查询出错，请联系随意游小游"));
        }
        return json($info);
    }








}
