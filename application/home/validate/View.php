<?php
namespace app\home\validate;
use think\Validate;
class View extends Validate
{
    //定义规则
    protected $rule = [
        'city'          =>  'require|max:64',       //景点城市
        'name'          =>  'require|max:64',       //景点名称
        'address'       =>  'require|max:128',      //景点地址
        'introduction'  =>  'max:500',              //景点介绍
        'ticket_type'  =>  'require',               //票种类型
        'image'         =>  'require',              //酒店图片
    ];



    //反馈信息
    protected $message = [
        'city.require'          =>  '景点城市是必须填写的',
        'city.max'              =>  '景点城市不能超过64个字符',
        'name.require'          =>  '景点名称是必须填写的',
        'name.max'              =>  '景点名称不能超过64个字符',
        'address.require'       =>  '景点地址是必须填写的',
        'address.max'           =>  '景点地址不能超过128个字符',
        'introduction.max'      =>  '景点介绍不能超过500个字符',
        'ticket_type.require'   =>  '票种类型是必须填写的',
        'image.require'         =>  '酒店图片必须选择的',
    ];

    //定义场景
    protected $scene = [
        'add'   =>  ['city','name',"address","introduction","ticket_type","image"],
    ];
}