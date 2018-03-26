<?php
/**
 * 跟团游
 */
namespace app\home\controller;
use app\common\controller\HomeBase;
//use think\Request;
//use \think\Validate;
class Login extends HomeBase
{   
    /**
     * 后台登录 /etc/nginx/conf.d/
     */ 
    public function login(){
        $account = input('post.account');
        $password = md5(input('post.password'));
        //$remember = input('remember');
        // 参数 合法性校验
        if($account == '' || $account == null || $password == '' || $password == null){
            return json(array('code' => 404,'msg' => '账号或密码不能为空'));
        }
        // 密码账号的正确性  -- 超管 分销商
        $data = db('sp')
                ->field(['id','code','type','account_num','com_name','open'])
                ->where(array('account_num' => $account,'pwd' => $password))
                ->where('type','in','0,1')
                ->find();
        if(empty($data)){
            return json(array('code' => 404,'msg' => '账号或密码错误'));
        }
        // 检查账户是否开启登陆
        if($data['open'] == 0){
            return json(array('code' => 404,'msg' => '您被禁止登陆，请联系管理员'));
        }
        // -- 获取 超管 分销商 菜单
        $auth =  \think\Loader::model('Auth','logic');
        $menu = $auth->menuList($data['code'],$data['type']);
        if($menu['code'] == 404){
            return json($menu);
        }
        // 记录
        session_expire('sp',array('id' => $data['id'], 'code' => $data['code']),60*60*24*7);
        cookie('menu',$menu);
        return json($menu);
    }

    /**
     * 用户注销
     */
    public function logout(){
        session('sp',null);
        cookie('menu',null);
        return json(array('code' => 200,'msg' => '注销成功'));
    }    

    /**
     * 测试 菜单获取
     */
    // public function get(){
    //     $auth =  \think\Loader::model('Auth','logic');
    //     $menu = $auth->checkAuth('login');
        
    // }
}

 