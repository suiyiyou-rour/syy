<?php

/**
 * 获取商户id
 */
function getSpId(){
    $id = session("sp.id");
    return $id;
}

/**
 * 获取商户code
 */
function getSpCode(){
    $code = session("sp.code");
    return $code;
}

/**
 * 生成随机商品code
 * @return string
 */
function createGoodsCode($type = "x")
{
    //商品表id 小于4位填充
    $goodsId = db("goods")->order("id desc")->value('id');
    $goodsId ? $goodsId++ : $goodsId = 1;
    if( strlen($goodsId) < 4 ){
        $goodsId = str_pad($goodsId,4,"0",STR_PAD_LEFT);
    };

    //供应商id 小于4位填充
    $spId = getSpId();
    if(strlen($spId) < 3){
        $spId = str_pad($spId,3,"0",STR_PAD_LEFT);
    };

    //商品类型 + 供应商id + 商品表id
    $goods_code = $type . $spId . $goodsId;
//    $goods_code = $type . getSpId() .mt_rand(100000,999999);
    return $goods_code;
}

/**
 * 防止表单重复提交hash值
 */
function getFromHash(){
    $str = getSpId() .mt_rand(100000,999999);
    $hash = md5($str);
    $array = array("count" => 0,"code" => $hash);
//    session("hash",$array);
    cookie('hash', $array, 3);
    return $hash;
}

/**
 * 验证hash值
 */
function checkFromHash($data){
    $hash = cookie('hash');
    if(!$hash){
        $array = array("count" => 1,"code" => $data);
        cookie('hash', $array, 3);
        return 1;
    }
    if($hash["code"] != $data || $hash["count"] >= 1){
        return 0;
    }
    $array = array("count" => 1,"code" => $hash["code"]);
    cookie('hash', $array, 3);
    return 1;
}


/**
 * 跟团page0测试参数
 */
function testGroupPage0(){
    //主表添加数据
    $data["contact_code"] = "12345678"; //合同编码  （主）必须
    $data["inside_code"] = "2" ;//内部编号   （主）
    $data["inside_title"] = "3"; //内部显示标题   （主）必须
    $data["subtitle"] = "1"; //商品副标题     （主）
    $data["advance_time"] = 169200; //提前预定时间     （主）必须
    $data["online_type"] = "1"; //上线类型   (主)必须
    $data["on_time"] = ""; //上线时间     （主）
    $data["off_time"] = ""; //下线时间     （主）
    $data["rate"] = "2"; //产品费率     （主）必须

    //副表添加数据
    $data["service_type"] = "json"; //服务保障      （副）
    $data["line_type"] = "1"; //路线类型     （副）
    $data["play_type"] = "1"; //游玩类型     （副）
    $data["begin_address"] = "2"; //出发地     （副）必须
    $data["end_address"] = "2"; //目的地     （副）必须
    $data["main_place"] = "json"; //主要景点     （副）必须
    $data["service_tel"] = "json"; //客服电话     （副）
    $data["refund_type"] = "1"; //退款类型     （副）必须
    $data["refund_info"] = "json";//梯度详细退款     （副）

    return $data;
}

/**
 * 跟团page1测试参数
 */
function testGroupPage1(){
    //副表添加数据
    $data["play_day"] = "2";//行程天数  int长度3  必须
    $data["go_trans"] = "2";//交通方式（去） int长度2  必须
    $data["back_trans"] = "2";//交通方式（回） int长度2  必须
    $data["go_trans_cost"] = "111";//交通费用说明（去） varchar长度256
    $data["back_trans_cost"] = "111";//交通费用说明（回） varchar长度256
    $data["gather_place"] = "json";//集合地点    必须
    $data["route_info"] = "json";//行程详细  必须
    return $data;
}

/**
 * 用cookie设置session有效期
 * @param   string  $session_id SESSIONID
 * @param   value   $val        session值
 * @param   integer $time       有效时间
 * @return  boolean  
 */
function session_expire($session_name,$val,$time=''){
    session($session_name,$val);
    if(session($session_name)){
        $session_id = session_id($session_name);
        cookie('PHPSESSID',$session_id,$time);
    }
}