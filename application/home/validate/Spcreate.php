<?php
namespace app\home\validate;
use think\Validate;
class Spcreate extends Validate
{
    //定义规则
    protected $rule = [
        //基本信息添加
        'account'           =>   'require|length:6,15',   //账号  
        'password'          =>   'require|length:6,15',   //密码   
        'name'              =>   'require',//供应商名字   
        'mobile'            =>   'require',//供应商联系方式     
        'company'           =>   'require',//公司名      
        'address'           =>   'require',//地址     
        'auth'              =>   'array',// 权限数组
        'y_image_path'      =>   'require',//营业执照     
        'z_image_path'      =>  'require',//组织机构代码     
        's_image_path'      =>  'require',//税务登记证     
        'email_address'     =>  'email'//邮箱     
    ];

    //反馈信息
    protected $message = [
        //基本信息添加
        'account.require'       => '账号是必须的',
        'account.length'        => '账号长度限制为6-15个字符',
        'password.require'      => '密码是必须的',
        'password.length'       => '密码长度限制为6-15个字符',
        'name.require'          => '名字是必须的',
        'mobile.require'        => '手机是必须的',
        'address.require'       => '地址是必须的',
        'auth.array'            => '请选择权限',
        'y_image_path.require'  => '营业执照是必须的',
        'z_image_path.require'  => '组织机构代码证是必须的',
        's_image_path.require'  => '税务登记证是必须的',
        'email_address.email'   => '邮箱格式错误'
    ];

    //定义场景
    protected $scene = [
        'add' =>array(
                'account'  ,   
                'password',   
                'name'  ,
                'mobile' ,
                'company'  ,
                'address'  ,
                'auth',
                'y_image_path' ,
                'z_image_path',
                's_image_path' ,
                'email_address')
        ];


}