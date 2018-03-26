<?php
/**
 * 权限管理
 */
namespace app\home\logic;

class Auth
{

    /**
     * 提取菜单列表
     */
    public function menuList($userCode,$type){
        if($type == 0){
            $data = $this->adminData();
        }else{
            $data = $this->supplyData($userCode,$type); 
        }

        if(empty($data)){
            return array('code'=>404,'msg'=>'菜单获取失败');
        }
        
        $data = $this->iconData($data); 
       
        // 菜单数据重构
        $data = $this->recombinData($data);
        return  array('code' => 200,'msg' => '登录成功','data' => $data);
    }

    /**
     * 菜单数据重构
     */
    public function recombinData($data){
        foreach($data as $key=>$val){
            if($val['pid'] == 0){
                $arr= array(
                    "name" => $val['name'],
                    "hash" => $val['hash'],
                    "second" => []
                );   
                foreach($data as $k => $v){
                    if($v['pid'] == $val['id']){
                        $arr1= array(
                            "name" => $v['name'],
                            "hash" => $v['hash'],
                            'icon' => $v['icon'],
                            "third" => []
                        ); 
                        foreach($data as $i => $j){
                            if($j['pid'] == $v['id']){
                                $arr2= array(
                                    "name" => $j['name'],
                                    "hash" => $j['hash'],
                                    'icon' => $j['icon']
                                );
                                $arr1['third'][] = $arr2; 
                            }
                        }
                        $arr['second'][] = $arr1;
                    }
                }
                $array[] = $arr;
            }
        }
        return $array;
    }

    /**
     * 菜单数据icon
     * @return array    $data   菜单数据
     */
    private function iconData($data){
        foreach($data as $key => $val){
            if($val['icon']==null){
                $data[$key]['icon']='';
            }
        }
        return $data;
       
    }

    /**
     * 超管 权限
     * @return array    $data   菜单数据
     */
    private function adminData(){
        $data = db('syy_function')
                ->order('pid asc,id asc')
                ->select();
        return $data;
    }

    /**
     * 供应商 权限
     * @param string    $userCode   用户code
     * @param integer   $type       用户type
     * @return array    $data       菜单数据
     */
    private function supplyData($userCode,$type){
        $where = 'a.user_code='.$userCode;
        $data = db('auth')
                ->alias('a')
                ->field('a.user_code,b.*')
                ->join('syy_function b','a.fid=b.id')
                ->where($where)
                ->order('b.pid asc,b.id asc')
                ->select();
        return $data;
    }

    /**
     * 权限校验
     * @param   string  $action 访问的控制器
     * @param   string
     * @return  boolean 
     */
    public function checkAuth($userCode,$action='login'){
        //$is_login = session('sp');
        // if(empty($is_login)) return false;
        $where = 'a.user_code='.$userCode.' and b.controller!=""';
        $data = db('auth')
                ->alias('a')
                ->field('b.controller')
                ->join('syy_function b','a.fid=b.id')
                ->where($where)
                ->order('b.pid asc,b.id asc')   
                ->select();
        
        foreach($data as $k=>$v){
            $indexArr[] = $v['controller'];
        }

        $res = in_array($action,$indexArr);
        return $res;
    }
}