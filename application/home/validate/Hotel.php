<?php
namespace app\home\validate;
use think\Validate;
class Hotel extends Validate
{
    //定义规则
    protected $rule = [
        'city'          =>  'require|max:64',       //酒店所在城市
        'name'          =>  'require|max:64',       //酒店名称
        'address'       =>  'require|max:128',      //酒店具体地址
        'begin_time'    =>  'require|max:24',       //入住时间
        'end_time'      =>  'require|max:24',       //退房时间
        'prove'         =>  'require|max:2|number', //入住凭证
        'introduction'  =>  'max:500',              //酒店简介
        'other_info'    =>  'max:500',              //其他说明
        'image'         =>  'require',              //酒店图片
    ];



    //反馈信息
    protected $message = [
        'city.require'          =>  '酒店所在城市是必须填写的',
        'city.max'              =>  '酒店所在城市不能超过64个字符',
        'name.require'          =>  '酒店名称是必须填写的',
        'name.max'              =>  '酒店名称不能超过64个字符',
        'address.require'       =>  '酒店具体地址是必须填写的',
        'address.max'           =>  '酒店具体地址不能超过128个字符',
        'begin_time.require'    =>  '入住时间是必须填写的',
        'begin_time.max'        =>  '入住时间格式错误',
        'end_time.require'      =>  '退房时间是必须填写的',
        'end_time.max'          =>  '退房时间格式错误',
        'prove.require'         =>  '入住凭证是必须填写的',
        'prove.max'             =>  '入住凭证格式错误',
        'prove.number'          =>  '入住凭证格式错误',
        'introduction.max'      =>  '酒店简介不能超过500个字符',
        'other_info.max'        =>  '其他说明不能超过500个字符',
        'image.require'         =>  '酒店图片是必须填写的',

    ];

    //定义场景
    protected $scene = [
        'add'   =>  ['city','name',"address","begin_time","end_time","prove","introduction","other_info","image"],
    ];
}