<?php
namespace app\home\validate;
use think\Validate;
class Meal extends Validate
{
    //定义规则
    protected $rule = [
        'city'          =>  'require|max:64',       //餐厅城市
        'name'          =>  'require|max:64',       //餐厅名称
        'is_hotel'      =>  'require|max:1|number',//酒店内餐厅 0没选 1选
        'address'       =>  'require|max:128',      //餐厅地址
        'instruction'  =>  'require',              //使用说明
        'image'         =>  'require',              //酒店图片
    ];



    //反馈信息
    protected $message = [
        'city.require'          =>  '餐厅城市是必须填写的',
        'city.max'              =>  '餐厅城市不能超过64个字符',
        'name.require'          =>  '餐厅名称是必须填写的',
        'name.max'              =>  '餐厅名称不能超过64个字符',
        'is_hotel.require'      => '酒店内餐厅格式错误',
        'is_hotel.max'          => '酒店内餐厅格式错误',
        'is_hotel.number'       => '酒店内餐厅格式错误',
        'address.require'       =>  '餐厅地址是必须填写的',
        'address.max'           =>  '餐厅地址不能超过128个字符',
        'instruction.require' =>  '使用说明是必须填写的',
        'image.require'         =>  '酒店图片必须选择的',
    ];

    //定义场景
    protected $scene = [
        'add'   =>  ['city','name','is_hotel',"address","introduction","image"],
    ];
}