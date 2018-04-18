<?php
namespace app\weixin\validate;
use think\Validate;
class Index extends Validate
{
    protected $rule = [
        'man_num'           =>  'require|max:3|number',  //成人数量
        'child_num'         =>  'max:3|number',           //儿童数量
        'house_num'         =>  'max:3|number',           //单房差数量
        'mobile'            =>  'require|max:11|number', //主要联系人电话
        'user_name'         =>  'require',                //主要联系人名称
        'go_time'           =>  'require',                //出发日期
        'user_code'         =>  'require',                //下单用户
        'remark'            =>  'max:100',                //备注信息 100
    ];

//$data['goodsCode']      =  "g0020001";           //产品code
//$data['man_num']        =  2;                   //成人数量
//$data['child_num']      =  1;                   //儿童数量
//$data['house_num']      =  1;                   //单房差数量
//$data['mobile']         =  "18060481803";        //主要联系人电话
//$data['user_name']      =  "刘祖梁";              //主要联系人名称
//$data['go_time']        =  "2018-04-18";         //出发日期
//$data['retail_code']    =  "54";                 //经销商编码
//$data['user_code']      =  "100001";             //下单用户
//$data["identification"] = "352201199303141637"; //主要联系人身份证
//$data["charged_item"]   =   "";                   //自费项目
//$data["zfprice"]        =   0;                     //自费价格
//$data["identity_array"] = "[]";                     //身份数组 每个人的身份证
//$data["remark"]          = "[]";                     //备注信息 100
    protected $message = [
        'name.require'  =>  '用户名必须',
        'email' =>  '邮箱格式错误',
    ];

    protected $scene = [
        'add'   =>  ['name','email'],
        'edit'  =>  ['email'],
    ];
}