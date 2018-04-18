<?php
namespace app\weixin\model;
use think\Model;

class Order extends Model
{
    //只读字段用来保护某些特殊的字段值不被更改，这个字段的值一旦写入，就无法更改
    protected $readonly = ['order_sn', 'goods_code', 'sp_code', 'user_code', 'retail_code', 'goods_type', 'create_time'];
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    // 关闭自动写入update_time字段
    protected $updateTime = false;
    //类型转换
    protected $type = [
        'total_price'           =>  'float',
        'total_settle_price'    =>  'float',
        'rate'                   =>  'float',
        'rebate_total_money'    =>  'float',
        'total_num'             =>  'integer'
    ];
}