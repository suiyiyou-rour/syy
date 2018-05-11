<?php
namespace app\weixin\controller;
use app\common\controller\WeixinBase;
class Detail extends WeixinBase
{
    public function index(){
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return array("code" => 412,"msg" => "商品号不能为空");
        }
//        $goodsCode = 's0020003';
        $date = strtotime(date("Y-m-d", time()));
        $where = [
            "a.check_type"   =>  "5",             //上线
            "a.is_del"       =>  ["<>","1"],      //未删除
            "a.on_time"      =>  ["<=",$date],
            "a.off_time"     =>  [">=",$date],
            "a.code"         =>   $goodsCode
        ];

        $join           =  [['sp b','a.sp_code = b.code']];
        $goodsField     =  "a.code,a.show_title,a.advance_time,a.goods_type,a.price_type,stock_type,stock_num";
        $sceneryField   =  "b.com_name";
        $allField       =  $goodsField.','.$sceneryField;

        $res = db('goods')->alias("a")->field($allField)->where($where)->join($join)->find();
        if(!$res){
            return json(array("code" => 405,"msg" => "商品找不到或者没有上线"));
        }
        switch ($res["goods_type"]){
            case 1:     //1跟团
                $info = $this->groupInfo($res);
                break;
            case 2:     //2门票
                $info = $this->ticketInfo($res);
                break;
            case 3:     //3酒景
                $info = $this->sceneryInfo($res);
                break;
            default:
                return json(array("code" => 404, "msg" => "查询出错，请联系随意游小游"));
        }
        $output["head"] = $res;
        $output["info"] = $info;
        return json(array("code" => 200, "data" => $output));
    }

    //跟团游
    private function groupInfo($goods){
        //副表 补充表
        $join           =  [['goods_supply b','a.goods_code = b.goods_code']];
        $where          =  array("a.goods_code" => $goods["code"]);
        $info = db('goods_group')->alias("a")->field("a.*,b.image")->where($where)->join($join)->find();
        $info["service_type"]       =  json_decode($info["service_type"],true);
        $info["main_place"]         =  json_decode($info["main_place"],true);
        $info["service_tel"]        =  json_decode($info["service_tel"],true);
        $info["refund_info"]        =  json_decode($info["refund_info"],true);
        $info["gather_place"]       =  json_decode($info["gather_place"],true);
        $info["route_info"]         =  json_decode($info["route_info"],true);
        $info["feature_reasons"]   =  json_decode($info["feature_reasons"],true);
        $info["charged_item"]       =  json_decode($info["charged_item"],true);
        $info["child_price_info"]   =  json_decode($info["child_price_info"],true);
        $info["cost_not_include"]   =  json_decode($info["cost_not_include"],true);
        $info["crowd_limit"]        =  json_decode($info["crowd_limit"],true);
        $info["book_notice"]        =  json_decode($info["book_notice"],true);
        $info["image"]              =  json_decode($info["image"],true);
        foreach ($info["image"] as &$k){
            $k = config("img_url").$k;
        }

        //价格日历
        $date = strtotime(date("Y-m-d", time()));
        $calendarWhere = [
            "goods_code"     =>  $goods["code"],
            "date"           =>  [">=",$date],
            "stock_is_open" =>  1,
        ];
        $calendar = db('group_calendar')
            ->field(['id','goods_code'],true)
            ->where($calendarWhere)
            ->where('stock_num <> 0 OR need_stock_num <> 0')
            ->order("date asc")
            ->select();
        if($calendar){
            foreach ($calendar as &$k){
                $k["plat_price"]            = (float)$k["plat_price"];
                $k["market_price"]          = (float)$k["market_price"];
                $k["settle_price"]          = (float)$k["settle_price"];
                $k["market_child_price"]    = (float)$k["market_child_price"];
                $k["plat_child_price"]      = (float)$k["plat_child_price"];
                $k["settle_child_price"]    = (float)$k["settle_child_price"];
                $k["plat_house_price"]      = (float)$k["plat_house_price"];
                $k["date"]                   =  date("Y-m-d",$k["date"]);
            }
        }
        $output["info"] = $info;
        $output["date"] = $calendar;
        return $output;
    }

    //门票
    private function ticketInfo($goods){
        $join           =  [['goods_supply b','a.goods_code = b.goods_code']];
        $where          =  array("a.goods_code" => $goods["code"]);
        $info = db('goods_ticket')->alias("a")->field("a.*,b.image")->where($where)->join($join)->find();
        $info["place_name"]         =  json_decode($info["place_name"],true);
        $info["include_cost"]       =  json_decode($info["include_cost"],true);
        $info["service_tel"]        =  json_decode($info["service_tel"],true);
        $info["player_info"]        =  json_decode($info["player_info"],true);
        $info["mobile_limit"]       =  json_decode($info["mobile_limit"],true);
        $info["identity_limit"]     =  json_decode($info["identity_limit"],true);
        $info["entrance_time"]      =  json_decode($info["entrance_time"],true);
        $info["entrance_place"]     =  json_decode($info["entrance_place"],true);
        $info["refund_info"]        =  json_decode($info["refund_info"],true);
        $info["usable_date"]        =  json_decode($info["usable_date"],true);
        $info["disabled_date"]      =  json_decode($info["disabled_date"],true);
        $info["image"]              =  json_decode($info["image"],true);
        foreach ($info["image"] as &$k){
            $k = config("img_url").$k;
        }
        $date = strtotime(date("Y-m-d", time()));
        if ($goods["price_type"] == 1){
            //价格日历
            $calendarWhere = [
                "goods_code"     =>  $goods["code"],
                "date"           =>  [">=",$date],
                "stock_is_open" =>  1
            ];
            $calendar = db('ticket_calendar')->field(['id','goods_code'],true)->where($calendarWhere)->order("date asc")->select();
            if($calendar){
                foreach ($calendar as &$k){
                    $k["plat_price"]    = (float)$k["plat_price"];
                    $k["settle_price"]  = (float)$k["settle_price"];
                    $k["market_price"]  = (float)$k["market_price"];
                    $k["date"]           =  date("Y-m-d",$k["date"]);
                    if($goods["stock_type"] == 1){//1无限库存 2总库存 3日库存
                        $k["stock_num"] = -1;
                    }else if($goods["stock_type"] == 2){
                        $k["stock_num"] = $goods["stock_num"];
                    }
                }
            }
        }else{
            //有效期
            $indate = db('ticket_indate')->field(['id','goods_code'],true)->where(array("goods_code" => $goods["code"]))->find();
            $newIndate["plat_price"]      =   (float)$indate["plat_price"];
            $newIndate["settle_price"]    =   (float)$indate["settle_price"];
            $newIndate["market_price"]    =   (float)$indate["market_price"];
            $newIndate["stock_is_open"]   =   $indate["stock_is_open"];
            $newIndate["stock_num"]       =   $indate["stock_num"];
            $newIndate["sales_num"]       =   $indate["sales_num"];
            $calendar    =   array();//日期数组
            if($indate["begin_date"] <= $date){
                $indate["begin_date"] = $date;
            }
            for($i = $indate["begin_date"]; $i <= $indate["end_date"];$i += 86400)  //一天86400
            {
                $newIndate["date"]    =  date('Y-m-d',$i);         //每天
                array_push($calendar,$newIndate);
            }
            $info["begin_date"]     =  $indate["begin_date"];
            $info["end_date"]       =  $indate["end_date"];

        };
        $output["info"] = $info;
        $output["date"] = $calendar;
        return $output;

    }

    //景酒
    private function sceneryInfo($goods){
        $join           =  [['goods_supply b','a.goods_code = b.goods_code']];
        $where          =  array("a.goods_code"=>$goods["code"]);
        $info = db('goods_scenery')->alias("a")->field("a.*,b.image")->where($where)->join($join)->find();
        $info["hotel_code"]             =  json_decode($info["hotel_code"],true);
        $info["view_code"]              =  json_decode($info["view_code"],true);
        $info["meal_code"]              =  json_decode($info["meal_code"],true);
        $info["vehicle_code"]           =  json_decode($info["vehicle_code"],true);
        $info["trip_info"]              =  json_decode($info["trip_info"],true);
        $info["stock_confirm_time"]    =  json_decode($info["stock_confirm_time"],true);
        $info["contact_info"]           =  json_decode($info["contact_info"],true);
        $info["play_people_info"]       =  json_decode($info["play_people_info"],true);
        $info["class_label"]             =  json_decode($info["class_label"],true);
        $info["image"]              =  json_decode($info["image"],true);
        foreach ($info["image"] as &$k){
            $k = config("img_url").$k;
        }
        $date = strtotime(date("Y-m-d", time()));
        $calendarWhere = [
            "goods_code"     =>  $goods["code"],
            "date"           =>  [">=",$date],
            "stock_is_open" =>  1,
        ];
        $calendar = db('scenery_calendar')
            ->field(['id','goods_code'],true)
            ->where($calendarWhere)
            ->where('stock_num <> 0 OR reserve_num <> 0')
            ->order("date asc")
            ->select();
        if($calendar){
            foreach ($calendar as &$k){
                $k["plat_price"] = (float)$k["plat_price"];
                $k["settle_price"] = (float)$k["settle_price"];
                $k["date"] = date("Y-m-d",$k["date"]);
            }
        }
        $output["info"] = $info;
        $output["date"] = $calendar;
        return $output;
    }

}
