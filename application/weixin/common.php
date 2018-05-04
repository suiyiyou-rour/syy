<?php
/**
 * 获取用户code
 */
function getUserCode(){
    $array = cookie('user');
    return $array["code"];
}

/**
 * 获取获取用户type
 */
function getUserTpye(){
    $array = cookie('user');
    return $array["type"];
}

/**
 * 判断是不是经销商
 */
function isRetail(){
    $array = cookie('user');
    if($array["isType"] == 2){
        return true;
    }
    return false;
}

/**
 * 产品上线条件
 */
function goodsOnlineWhere(){
    $date = strtotime(date("Y-m-d", time()));
    $where = [
        "check_type"   =>  "5",             //上线
        "is_del"       =>  ["<>","1"],      //未删除
        "on_time"      =>  ["<=",$date],
        "off_time"     =>  [">=",$date]
    ];
    return $where;
}

/**
 * 判断是不是日期
 */
function is_Date($str,$format="Y-m-d"){
    $unixTime_1 = strtotime($str);
    if ( !is_numeric($unixTime_1) ) return false;
    $checkDate = date($format, $unixTime_1);
    $unixTime_2 = strtotime($checkDate);
    if($unixTime_1 == $unixTime_2){
        return true;
    }else{
        return false;
    }
}

/**
 * 手机判断/^1\d{10}$/
 */
function is_phone($phone){
    if(preg_match('/^1\d{10}$/',$phone)){
        return true;
    }
    return false;
}

/**
 * 身份证判断
 */
function is_Identification_card($identity){
    if(preg_match('/^[1-9]{1}[0-9]{14}$|^[1-9]{1}[0-9]{16}([0-9]|[xX])$/',$identity)){
        return true;
    }
    return false;
}

/**
 * 获取getAccessToken
 */

function getAccessToken(){
    $obj = \think\Loader::model('WxApi','service');
    $token = $obj->getAccessToken();
    return $token;
}

/**
 * curl访问
 * @param string  $url  url连接
 * @param json数组
 * @param headers字符串
 */
function curl_post($url , $data = "", $headers = ""){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if (!empty($data)){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    if($headers){
        curl_setopt( $curl, CURLOPT_HTTPHEADER, $headers );
    }
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}



