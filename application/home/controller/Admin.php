<?php
namespace app\home\controller;
use app\common\controller\HomeBase;

/**
 * 需要继承的超管类
 */
class Admin extends HomeBase
{
    public function __construct()
    {
        parent::__construct();
        if(!getSpType()){
            echo json_encode(array("code"=>405,"msg"=>"只有超级管理员才有权限"));
            die;
        }
    }
}
