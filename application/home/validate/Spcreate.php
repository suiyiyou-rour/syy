<?php
namespace app\home\validate;
use think\Validate;
class Spcreate extends Validate
{
    //定义规则
    protected $rule = [
        //基本信息添加
        'account'           =>   'require|between:6,15',   //账号  （主）必须
        'password'          =>   'require|between:6,15',   //密码    （主）
        'name'              =>   'require',//内部显示标题   （主）必须
        'mobile'            =>   'require',//商品副标题     （主）
        'company'           =>   'require',//服务保障      （副）
        'address'           =>   'require',//路线类型     （副）
        'y_image_path'      =>   'require',//游玩类型     （副）
        'z_image_path'      =>  'require',//出发地     （副）必须
        's_image_path'      =>  'require',//目的地     （副）必须
        'email_address'     =>  'email'//主要景点     （副）必须
    ];

    //反馈信息
    protected $message = [
        //基本信息添加
        'account.require'       => '账号是必须的',
        'account.between'       => '账号长度限制为6-15个字符',
        'password.require'      => '密码是必须的',
        'password.between'      => '密码长度限制为6-15个字符',

        'name.require'      => '名字是必须的',
        'mobile.require'      => '手机是必须的',
        'address.require'      => '密码是必须的',
        'y_image_path.require'      => '密码是必须的',
        'z_image_path.require'      => '密码是必须的',
        's_image_path.require'      => '密码是必须的',
        'email_address.email'      => '密码是必须的',

    ];

    //定义场景
    protected $scene = [
        'addBasicInfo'   =>  ['contact_code', 'inside_code', 'inside_title', 'subtitle', 'service_type', 'line_type', 'play_type', 'begin_address', 'end_address', 'main_place', 'advance_time', 'online_type', 'on_time', 'off_time' , 'service_tel', 'refund_type', 'refund_info', 'rate'],
    ];


}