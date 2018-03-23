<?php
namespace app\home\controller;
use app\common\controller\HomeBase;

class Index extends HomeBase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
//        echo "home/index/base";
        $k =array("id"=>"k","name"=>"k");
        $v =array("name"=>"v","id"=>"x");
        $kk = array_merge($k,$v);
        echo  1;
        var_dump($kk);

    }



}
