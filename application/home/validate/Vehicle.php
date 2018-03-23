<?php
namespace app\home\validate;
use think\Validate;
class Vehicle extends Validate
{
    //定义规则
    protected $rule = [
        'name'          =>  'require|max:24',           //车辆名称
        'type'          =>  'require|max:1|number',     //车辆类型 0接驳车 1接送机 2租车 3包车（含司机）
        'instruction'  =>  'require',                   //使用说明
        'image'         =>  'require',                  //车辆图片
    ];


    //反馈信息
    protected $message = [
        'name.require'              => '车辆代号是必须的',
        'name.max'                  => '车辆代号不能超过24个字符',
        'type.require'              => '车辆类型格式错误',
        'type.max'                  => '车辆类型格式错误',
        'type.number'               => '车辆类型格式错误',
        'instruction.require'      => '使用说明是必须填写的',
        'image.require'             => '酒店图片是必须填写的',
    ];

    //定义场景
    protected $scene = [
        'add'   =>  ['type','instruction',"image"],
    ];
}