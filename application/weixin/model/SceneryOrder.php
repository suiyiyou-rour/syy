<?php
namespace app\weixin\model;
use think\Model;
class SceneryOrder extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'syy_scenery_order';
    //只读字段用来保护某些特殊的字段值不被更改，这个字段的值一旦写入，就无法更改
    protected $readonly = ['order_sn'];
    //类型转换
    protected $type = [
        'plat_price'             =>  'float',
        'settle_price'           =>  'float',
        'rebate_man_money'      =>  'float',
        'man_num'                =>  'integer',
        'hotel_code'         =>  'array',
        'view_code'         =>  'array',
        'meal_code'         =>  'array',
        'vehicle_code'         =>  'array',
        'identity_array'         =>  'array'
    ];




}