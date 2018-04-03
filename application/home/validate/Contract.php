<?php
namespace app\home\validate;
use think\Validate;
class Contract extends Validate
{
    //定义规则
    protected $rule = [
        //基本信息添加
        'name'              =>   'require|max:32',               //合同名
        'content'           =>   'require',                      //合同内容
        'image'             =>   'max:256',                       //图片
        'rate'              =>   'require|between:0,100|number',//产品费率
    ];

    //反馈信息
    protected $message = [
        //基本信息添加
        'name.require'          =>  '合同名称是必须的',
        'name.max'              =>  '合同名称不能超过32个字符',
        'content.require'       =>  '合同内容是必须的',
        'image.max'             =>  '图片长度非法',
        'rate.require'          =>  '产品费率是必须的',
        'rate.between'          =>  '产品费率格式错误1',
        'rate.number'           =>  '产品费率格式错误2',
    ];

    //定义场景
    protected $scene = [
        //合同添加
        'add'   =>  ['name','content','image','rate'],

    ];







}