<?php
namespace app\home\validate;
use think\Validate;
class Group extends Validate
{
    //定义规则
    protected $rule = [
        //基本信息添加
        'contact_code'      =>   'require|max:11',   //合同编码  （主）必须
        'inside_code'       =>   'max:24',   //供应商产品内部编号    （主）
        'inside_title'      =>   'require|max:50',//内部显示标题   （主）必须
        'subtitle'          =>   'max:150',//商品副标题     （主）
        'service_type'      =>   'max:50',//服务保障      （副）
        'line_type'         =>   'max:1|number',//路线类型     （副）
        'play_type'         =>   'max:1|number',//游玩类型     （副）
        'begin_address'     =>  'require|max:30',//出发地     （副）必须
        'end_address'       =>  'require|max:30',//目的地     （副）必须
        'main_place'        =>  'require',//主要景点     （副）必须
        'advance_time'      =>  'require|max:11|number',//提前预定时间     （主）必须
        'online_type'       =>  'require|max:1|number',//上线类型   (主)必须
        'on_time'           =>  'max:11|number',//上线时间     （主）
        'off_time'          =>  'max:11|number',//下线时间     （主）
        'service_tel'       =>  'max:200',//客服电话     （副）
        'refund_type'       =>  'require|max:1|number',//退款类型     （副）必须
//        'refund_info'       =>  '',//梯度详细退款     （副）
        'rate'               =>  'require|max:3|number',//产品费率     （主）必须

        //行程信息添加
        'play_day'          =>  'require|number|max:3',//行程天数  int长度3  （副）必须
        'go_trans'          =>  'require|number|max:2',//交通方式（去） int长度2  （副）必须
        'back_trans'        =>  'require|number|max:2',//交通方式（回） int长度2  （副）必须
        'go_trans_cost'     =>  'max:256',//交通费用说明（去） （副）varchar长度256
        'back_trans_cost'   =>  'max:256',//交通费用说明（回） （副）varchar长度256
        'gather_place'      =>  'require',//集合地点    （副）必须
        'route_info'        =>  'require',//行程详细  （副）必须


        //费用包含
//        'little_traffic' =>  'require|number|max:3',//景区小交通  int长度1
//        'stay'   ,
//        'food_server',
//        'tick_server',
//        'guide_server',
//        'safe_server',
//        'child_price_type',
//        'child_price_info',
//        'child_price_supply',
//        'give_info'


    ];

    //反馈信息
    protected $message = [
        //基本信息添加
        'contact_code.require'  => '合同编码是必须的',
        'contact_code.max'      => '合同编码不能超过11个字符',
        'inside_code.max'       => '供应商产品内部编号不能超过24个字符',
        'inside_title.require'  => '内部显示标题是必须的',
        'inside_title.max'      => '内部显示标题不能超过50个字符',
        'subtitle.max'          => '商品副标题不能超过150个字符',
        'service_type.max'      => '服务保障不能超过50个字符',
        'line_type.max'         => '路线类型格式错误',
        'line_type.number'      => '路线类型格式错误',
        'play_type.max'         => '游玩类型格式错误',
        'play_type.number'      => '游玩类型格式错误',
        'begin_address.require' => '出发地是必须的',
        'begin_address.max'     => '出发地不能超过30个字符',
        'end_address.require'   => '目的地是必须的',
        'end_address.max'       => '目的地不能超过30个字符',
        'main_place.require'    => '主要景点是必须的',
        'advance_time.require'  => '提前预定时间是必须的',
        'advance_time.max'      => '提前预定时间不能超过11个字符',
        'advance_time.number'   => '提前预定时间格式错误',
        'online_type.require'   => '上线类型是必须的',
        'online_type.max'       => '上线类型不能超过11个字符',
        'online_type.number'    => '上线类型格式错误',
        'on_time.max'           => '上线时间不能超过11个字符',
        'on_time.number'        => '上线时间只能是数字',
        'off_time.max'          => '下线时间不能超过11个字符',
        'off_time.number'       => '下线时间只能是数字',
        'service_tel.max'       => '客服电话不能超过200个字符',
        'refund_type.require'   => '退款类型是必须的',
        'refund_type.max'       => '退款类型格式错误',
        'refund_type.number'    => '退款类型格式错误',
        'rate.require'          => '产品费率是必须的',
        'rate.max'              => '产品费率格式错误',
        'rate.number'           => '产品费率格式错误',

        //行程信息添加
        'play_day.require'          => '行程天数是必须的',
        'play_day.max'              => '行程天数格式错误',
        'play_day.number'           => '行程天数格式错误',
        'go_trans.require'          => '去时交通方式是必须的',
        'go_trans.max'              => '去时交通方式格式错误',
        'go_trans.number'           => '去时交通方式格式错误',
        'back_trans.require'        => '回时交通方式是必须的',
        'back_trans.max'            => '回时交通方式格式错误',
        'back_trans.number'         => '回时交通方式格式错误',
        'go_trans_cost.max'         => '去时交通费用说明不能超过256个字符',
        'back_trans_cost.max'       => '回时交通费用说明不能超过256个字符',
        'gather_place.require'      => '集合地点是必须的',
        'route_info.require'        => '行程详细是必须的',

        //

    ];

    //定义场景
    protected $scene = [
        //基本信息添加
        'addBasicInfo'   =>  ['contact_code', 'inside_code', 'inside_title', 'subtitle', 'service_type', 'line_type', 'play_type', 'begin_address', 'end_address', 'main_place', 'advance_time', 'online_type', 'on_time', 'off_time' , 'service_tel', 'refund_type', 'refund_info', 'rate'],
        //行程信息添加
        'addRouteInfo'   =>  ['play_day','go_trans','back_trans','go_trans_cost','back_trans_cost','gather_place','route_info'],
    ];







}