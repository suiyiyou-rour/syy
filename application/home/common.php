<?php

/**
 * 获取商户id
 */
function getSpId(){
//    $id = session('sp.id');
//    return $id;
    $array = cookie('sp');
    return $array["id"];

}

/**
 * 获取商户code
 */
function getSpCode(){
//    $code = session('sp.code');
//    return $code;
    $array = cookie('sp');
    return $array["code"];
}

/**
 * 判断是不是超级管理
 */
function getSpType(){
//    $type = session('sp.type');
    $array = cookie('sp');
    $type = $array["type"];
    if($type == 1){
        return true;
    }
    return false;
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
 * 用cookie设置session有效期
 * @param   string  $session_id SESSIONID
 * @param   value   $val        session值
 * @param   integer $time       有效时间
 * @return  boolean  
 */
function session_expire($session_name,$val,$time=''){
    session($session_name,$val);
//    if(session($session_name)){
//        $session_id = session_id($session_name);
//        cookie('PHPSESSID',$session_id,time()+$time);
//    }
}