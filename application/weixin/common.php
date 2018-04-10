<?php

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

