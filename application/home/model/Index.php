<?php
namespace app\home\model;

use think\Model;

class Index extends Model
{
    //设置主键
    protected $pk = 'uid';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'think_user';
    //只读字段用来保护某些特殊的字段值不被更改，这个字段的值一旦写入，就无法更改
    protected $readonly = ['name','email'];
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名  字段名默认创建时间字段为create_time，更新时间字段为update_time，支持的字段类型包括timestamp/datetime/int。
    protected $createTime = 'create_at';
    protected $updateTime = 'update_at';

//    // 关闭自动写入时间戳  'auto_timestamp' => true,开启情况下才要关闭
//    protected $autoWriteTimestamp = false;
//    // 关闭自动写入update_time字段
//    protected $updateTime = false;

    // 设置当前模型的数据库连接
    protected $connection = [
        // 数据库类型
        'type'        => 'mysql',
        // 服务器地址
        'hostname'    => '127.0.0.1',
        // 数据库名
        'database'    => 'thinkphp',
        // 数据库用户名
        'username'    => 'root',
        // 数据库密码
        'password'    => '',
        // 数据库编码默认采用utf8
        'charset'     => 'utf8',
        // 数据库表前缀
        'prefix'      => 'think_',
        // 数据库调试模式
        'debug'       => false,
    ];

    //自定义初始化
    protected function initialize()
    {
        //需要调用`Model`的`initialize`方法
        parent::initialize();
        //TODO:自定义的初始化

    }

    //自定义初始化
    protected static function init()
    {
        //TODO:自定义的初始化
    }
}