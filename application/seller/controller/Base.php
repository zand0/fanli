<?php

/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Author: 当燃
 * Date: 2015-09-09
 */

namespace app\seller\controller;
use think\Controller;
use think\Db;
use think\response\Json;
use think\Session;
class Base extends Controller {

    /**
     * 析构函数
     */
    function __construct() 
    {
        //Session::start();
        //header("Cache-control: private");  // history.back返回后输入框值丢失问题 参考文章 http://www.tp-shop.cn/article_id_1465.html  http://blog.csdn.net/qinchaoguang123456/article/details/29852881
        parent::__construct();
              
   }    
    
    /*
     * 初始化操作
     */
    public function _initialize() 
    {
        if(in_array(ACTION_NAME,array('login')) || in_array(CONTROLLER_NAME,array('Login'))){
                    	//return;
        }else{
        	if(session('user_id')){
        		//$this->check_priv();//检查管理员菜单操作权限
        	}else{
        		$this->error('请先登陆',U('/Seller/login'),1);
        	}
        }
        
        //过滤不需要登陆的行为
//         if(in_array(ACTION_NAME,array('login','logout','vertify')) || in_array(CONTROLLER_NAME,array('Ueditor','Uploadify'))){
//         	//return;
//         }else{
//         	if(session('admin_id') > 0 ){
//         		$this->check_priv();//检查管理员菜单操作权限
//         	}else{
//         		$this->error('请先登陆',U('Admin/Admin/login'),1);
//         	}
//         }
//         $this->public_assign();
    }  
    
    public function is_login(){
        $uid = session('user_id');
        $sid = session('seller_id');
        if(!$uid || !$sid){
            $this->error('请先登陆！');
        }
        return true;
    }
}