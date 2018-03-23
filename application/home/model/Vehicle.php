<?php
namespace app\home\model;
use think\Model;
class Vehicle extends Model
{
    protected $readonly = ['id','sp_code','code','create_time'];    //字段保护
    protected $autoWriteTimestamp = true; // 开启自动写入时间戳字段



}