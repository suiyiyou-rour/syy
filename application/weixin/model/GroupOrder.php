<?php
namespace app\weixin\model;
use think\Model;
class GroupOrder extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'syy_group_order';
    //只读字段用来保护某些特殊的字段值不被更改，这个字段的值一旦写入，就无法更改
    protected $readonly = ['order_sn'];
    //类型转换
    protected $type = [
        'market_price'           =>  'float',
        'plat_price'             =>  'float',
        'settle_price'           =>  'float',
        'market_child_price'    =>  'float',
        'plat_child_price'      =>  'float',
        'settle_child_price'    =>  'float',
        'rebate_man_money'      =>  'float',
        'rebate_child_money'    =>  'float',
        'plat_house_price'      =>  'float',
        'man_num'                =>  'integer',
        'child_num'              =>  'integer',
        'house_num'              =>  'integer',
        'charged_item'           =>  'array',
        'identity_array'         =>  'array'
    ];


}