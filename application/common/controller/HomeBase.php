<?php
namespace app\common\controller;
/**
 * home模块基类
 */
class HomeBase extends Base
{
    public function __construct()
    {
        parent::__construct();
//        if(!IS_WIN){
            $origin = isset($_SERVER['HTTP_ORIGIN'])? $_SERVER['HTTP_ORIGIN'] : '';
            $allow_origin = array( 
                'http://localhost:8080', 
                'http://qq.kris1945.com' 
            );
            if(in_array($origin, $allow_origin)){ 
                header('Access-Control-Allow-Origin:'.$origin); 
                header("Access-Control-Allow-Methods", "GET,POST");
                header('Access-Control-Allow-Headers:x-requested-with,content-type'); 
                header('Access-Control-Allow-Credentials:true');
                cookie(['domain' => $origin]);
            }
            // header("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, Access-Token");//没什么用
//        }
        // 权限控制
        $controller = strtolower( request()->controller() );

        $sp = array("id" => '1' , "code" => '1234567');
        session('sp',$sp);
//        $sp = session('sp');
//        if(!empty($sp)){
//            if( $controller == 'login' ) return false;
//
//            $auth =  \think\Loader::model('Auth','logic');
//            $res = $auth->checkAuth($sp['code'],$controller);
//
//            if(!$res) return false;
//        }

    }
}
