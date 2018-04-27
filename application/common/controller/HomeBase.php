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
        $origin = isset($_SERVER['HTTP_ORIGIN'])? $_SERVER['HTTP_ORIGIN'] : '';
        $allow_origin = array(
            'http://qq.suiyiyou.net',
            'http://localhost:8080'
        );
        if(in_array($origin, $allow_origin)){
            header('Access-Control-Allow-Origin:'.$origin);                          // 指定允许其他域名访问
            header("Access-Control-Allow-Methods", "GET,POST");                     // 响应类型
            header('Access-Control-Allow-Headers:x-requested-with,content-type'); // 响应头设置
            header('Access-Control-Allow-Credentials:true');                        // 是否允许请求带有验证信息
//            cookie(['domain' => '.suiyiyou.net']);                                    // 初始化有效域名
        }
        // 权限控制
        $controller = strtolower( request()->controller() );
        if( $controller !== 'login' ){
            $sp = session('sp', '', 'home');
//            if(!$sp){
//                echo json_encode(array("code"=>499,"data"=>"登陆状态失效，请重新登陆"));
//                die;
//            }
//           if($sp['type']==1) return false;
//
//           $auth =  \think\Loader::model('Auth','logic');
//           $res = $auth->checkAuth($sp['code'],$controller);
//
//           if(!$res) return false;
        }

    }
}
