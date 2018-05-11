<?php
namespace app\home\validate;
use think\Validate;
class Ticket extends Validate
{
    //定义规则
    protected $rule = [
        //基本信息添加
        'contact_code'      =>   'require|max:11',       //合同编码  （主）必须
        'goods_class'       =>   'require|max:16',       //商品种类  （副）必须
        'city'               =>   'require|max:24',       //城市      （副）必须
        'place_name'        =>   'require|max:256',       //景点名称   （副）必须
        'ticket_type'       =>   'require|max:16',       //门票票种  （副）必须
        'show_title'        =>   'require|max:150',      //产品名称(外部标题) （主）必须
        'include_cost'      =>   'require',               //费用包含 （副）必须
        'include_cost_info' =>  'max:600',               //费用包含补充说明（副）
        'not_include_info'  =>  'max:600',               //费用不包含（副）
        'safe_server'       =>   'require|max:1|number',//保险服务 1 0 （副）必须
        'safe_server_info' =>   'max:30',                //保险说明  (副)
        'service_tel'       =>   'require|max:128',     //客服电话 （副）必须

        //购买使用说明
        'advance_time'      =>  'require|max:11|number',    //提前预定时间   （主）必须
        'advance_time_type'=>  'require|max:1|number',     //提前预定类型   （主）必须
        'contact_need'      =>  'require|max:1|number',     //联系人信息      (副)必须
        'player_info'       =>  'require|max:256',          //游玩人限制信息  (副)必须
        'min_buy_num'       =>  'require|between:0,999',    //最少购买人数   (副)必须
        'max_buy_num'       =>  'require|between:0,999',    //最多购买人数   (副)必须
        'mobile_limit'      =>  'max:38',                     //手机号限制    (副)
        'identity_limit'    =>  'max:38',                     //身份证限制    (副)
        'entrance_time'     =>  'require',                    //入园时间      (副)必须
        'entrance_place'    =>  'require',                     //入园地址     (副)必须

        //价格模式
        'price_type'        =>  'require|max:1|number',                 //价格类型  (主)必须
        'begin_date'        =>  'require|number|max:11',                //  有效期开始时间 (indate)必须 --价格日历没有
        'end_date'          =>  'require|number|max:11',                //`有效期结束时间 (indate)必须 --价格日历没有
        'effective_days'    =>  'number|between:1,999',                 //有效天数  (副)            --有效期模式没有
        'usable_date'       =>  'require|max:128',                      //可用日期  (副)必须        --价格日历没有
        'stock_type'        =>  'require|max:1|number',                 //库存模式  (主)必须
        'stock_num'         =>  'require|number|between:0,999999',   //库存 必须 （indate）有效期  （价-主）总库存
        'stock_num_day'     =>  'require|number|between:0,999999',  //日库存 （价格日历）        --有效期模式没有
        'plat_price'        =>  'require|number|between:0,999999',    //价格       （indate）必须   --价格日历没有（分接口）
        'settle_price'      =>  'require|number|between:0,999999',    //结算价格   （indate）必须   --价格日历没有（分接口）
        'market_price'      =>  'require|number|between:0,999999',    //市场价格   （indate）必须   --价格日历没有（分接口）
        'refund'            =>  'require|max:1|number',                //退款设置    (副)必须
        'refund_info'       =>  'max:128',                              //退款设置     (副)
        'online_type'       =>  'require|max:1|number',                //上线类型    (主)
        'offline_type'      =>  'require|max:1|number',                //下线类型    (主)
        'on_time'           =>  'number|max:11',                        //上线时间     (主)
        'off_time'          =>  'number|max:11',                        //下线时间     (主)




    ];

    //反馈信息
    protected $message = [
        //基本信息添加
        'contact_code.require'      =>  '合同编码是必须的',
        'contact_code.max'          =>  '合同编码不能超过11个字符',
        'goods_class.require'       =>  '商品种类是必须的',
        'goods_class.max'           =>  '商品种类不能超过16个字符',
        'city.require'              =>  '城市填写是必须的',
        'city.max'                  =>  '城市填写不能超过24个字符',
        'place_name.require'        =>  '景点名称是必须的',
        'place_name.max'            =>  '景点名称长度错误',
        'ticket_type.require'       =>  '门票票种是必须的',
        'ticket_type.max'           =>  '门票票种不能超过16个字符',
        'show_title.require'        =>  '产品名称是必须的',
        'show_title.max'            =>  '产品名称不能超过150个字符',
        'include_cost.require'      =>  '费用包含是必须的',
        'include_cost_info.max'     =>  '产品名称不能超过600个字符',
        'not_include_info.max'      =>  '产品名称不能超过600个字符',
        'safe_server.require'       =>  '保险服务是必须的',
        'safe_server.max'           =>  '保险服务格式错误',
        'safe_server.number'        =>  '保险服务格式错误',
        'safe_server_info.max'      =>  '产品名称不能超过30个字符',
        'service_tel.require'       =>  '客服电话是必须的',
        'service_tel.max'           =>  '客服电话不能超过128个字符',

        //购买使用说明
        'advance_time.require'      =>  '提前预定时间是必须的',
        'advance_time.max'          =>  '提前预定时间格式错误1',
        'advance_time.number'       =>  '提前预定时间格式错误2',
        'advance_time_type.require'=>  '提前预定时间格式错误3',
        'advance_time_type.max'    =>  '提前预定时间格式错误4',
        'advance_time_type.number' =>  '提前预定时间格式错误5',
        'contact_need.require'      =>  '联系人信息是必须的',
        'contact_need.max'          =>  '联系人信息格式错误',
        'contact_need.number'       =>  '联系人信息格式错误',
        'player_info.require'       =>  '游玩人限制信息是必须的',
        'player_info.max'           =>  '游玩人限制信息格式错误',
        'min_buy_num.require'       =>  '最少购买人数是必须的',
        'min_buy_num.max'           =>  '最少购买人数格式错误',
        'max_buy_num.require'       =>  '最多购买人数是必须的',
        'max_buy_num.max'           =>  '最多购买人数格式错误',
        'mobile_limit.max'          =>  '手机号限制不能超过38个字符',
        'identity_limit.max'        =>  '身份证限制不能超过38个字符',
        'entrance_time.require'     =>  '入园时间是必须的',
        'entrance_place.require'    =>  '入园地址是必须的',

        //价格模式
        'price_type.require'        =>  '价格类型是必须的',
        'price_type.max'            =>  '价格类型格式错误',
        'price_type.number'         =>  '价格类型格式错误',
        'begin_date.require'        =>  '有效期时间段格式错误',
        'begin_date.max'            =>  '有效期时间段格式错误',
        'begin_date.number'         =>  '有效期时间段格式错误',
        'end_date.require'          =>  '有效期时间段格式错误',
        'end_date.max'              =>  '有效期时间段格式错误',
        'end_date.number'           =>  '有效期时间段格式错误',
        'effective_days.number'     =>  '有效天数必须是数字',
        'effective_days.between'    =>  '有效天数不能超过999',
        'usable_date.require'       =>  '可用日期是必须的',
        'usable_date.max'           =>  '身份证限制不能超过128个字符',
        'stock_type.require'        =>  '库存模式是必须的',
        'stock_type.max'            =>  '库存模式格式错误',
        'stock_type.number'         =>  '库存模式格式错误',
        'stock_num.require'         =>  '库存是必须的',
        'stock_num.number'          =>  '库存必须是数字',
        'stock_num.between'         =>  '库存不能超过999999',
        'stock_num_day.require'     =>  '日库存是必须的',
        'stock_num_day.number'      =>  '日库存必须是数字',
        'stock_num_day.between'     =>  '日库存不能超过999999',
        'plat_price.require'        =>  '平台价格是必须的',
        'plat_price.number'         =>  '平台价格必须是数字',
        'plat_price.between'        =>  '平台价格不能超过999999',
        'settle_price.require'      =>  '结算价格是必须的',
        'settle_price.number'       =>  '结算价格必须是数字',
        'settle_price.between'      =>  '结算价格不能超过999999',
        'market_price.require'      =>  '市场价格是必须的',
        'market_price.number'       =>  '市场价格必须是数字',
        'market_price.between'      =>  '市场价格不能超过999999',
        'refund.require'            =>  '退款设置是必须的',
        'refund.max'                =>  '退款设置格式错误',
        'refund.number'             =>  '退款设置格式错误',
        'refund_info.max'           =>  '身份证限制不能超过128个字符',
        'online_type.require'       =>  '上线类型是必须的',
        'online_type.max'           =>  '上线类型格式错误',
        'online_type.number'        =>  '上线类型格式错误',
        'offline_type.require'      =>  '下线类型是必须的',
        'offline_type.max'          =>  '下线类型格式错误',
        'offline_type.number'       =>  '下线类型格式错误',
        'on_time.number'            =>  '上线时间格式错误',
        'on_time.max'               =>  '上线时间格式错误',
        'off_time.number'           =>  '下线时间格式错误',
        'off_time.max'              =>  '下线时间格式错误',

    ];

    //定义场景
    protected $scene = [
        //基本信息添加
        'addBasicInfo'   =>  ['contact_code', 'goods_class', 'city', 'place_name', 'ticket_type', 'show_title', 'include_cost', 'include_cost_info', 'not_include_info', 'safe_server', 'service_tel'],
        //购买使用说明
        'addBuyUsed'     =>  ['advance_time', 'advance_time_type','contact_need', 'player_info', 'min_buy_num', 'max_buy_num', 'mobile_limit', 'identity_limit', 'entrance_time', 'entrance_place'],
        //价格模式(有效)
        'AddRulesSetIndate'     =>  ['price_type', 'begin_date','end_date','usable_date', 'stock_type','stock_num', 'plat_price', 'settle_price','market_price','refund','refund_info','online_type','offline_type','on_time','off_time'],
        //价格模式(日历)
        'AddRulesSetCalendar'   =>  ['price_type','effective_days','stock_type','stock_num','stock_num_day','plat_price','settle_price','market_price','refund','refund_info','online_type','offline_type','on_time','off_time'],
    ];






}