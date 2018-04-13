<?php
namespace app\home\model;

use think\Model;

class Rebate extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'syy_goods_rebate';
    //只读字段用来保护某些特殊的字段值不被更改，这个字段的值一旦写入，就无法更改
    protected $readonly = ['goods_code','retail_code','sp_code'];
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

}