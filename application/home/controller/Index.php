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
        echo "common/homeBase/index";
        echo "home/index/index";
    }
}
