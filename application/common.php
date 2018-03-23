<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
/**
 * @param $obj
 * 对象转换为数组
 */
function objSetArray($obj){
    if(is_object($obj)){
        return json_decode(json_encode($obj),true);
    }
    return $obj;
}

/**
 * 大小写转换
 * @param string $str 要转换的字符串
 * @param int    $type转换模式 1是首字母转为大写 2是换为小写
 **/
function letterChange($str,$type=2)
{
    if($type == 1){
        return ucfirst(trim($str));
    }else{
        return strtolower(trim($str));
    }
}

/**
 * 将key相同的数组合并为新的数组
 * @param array $arr 接收要组装的二维数组
 **/
function arrAssembly($arr)
{
    $arr_new = array();
    foreach($arr as $item){
        foreach($item as $key=>$val){
            $arr_new[$key][] = $val;
        }
    }
    return $arr_new;
}


/**
 * 生成随机字符串
 * @author 普修米洛斯
 **/
function getRandStr($length=8) {
    $str = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $randString = '';
    $len = strlen($str)-1;
    for($i = 0;$i < $length;$i ++){
        $num = mt_rand(0, $len);
        $randString .= $str[$num];
    }
    return $randString ;
}