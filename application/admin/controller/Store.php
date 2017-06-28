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
 * Date: 2015-10-09
 */

namespace app\admin\controller;

use think\db;
use think\Cache;
class Store extends Base{
	
	/*
	 * 配置入口
	 */
	public function index()
	{          
		//return $this->fetch();
	}
	
	public function store_list(){
	    
	    $list = M('store')->order('id desc')->select();
	    $this->assign('list',$list);
	    return $this->fetch();
	}
	
	public function store_add(){
	    $post = I('post.');
	    
	    if(!empty($post)){
	        $user_id = M('users')
	        ->where(['mobile|email'=>$post['user_name']])
	        ->getField('user_id');
	        $res = M('store')->data([
	            'store_name'=>$post['store_name'],
	            'user_id'=>$user_id,
	            'seller_name'=>$post['seller_name'],
	            'password'=>md5(md5($post['password'])),
	            'create_time'=>time(),
	            'is_owner'=>0,
	        ])->save();
	        if($res){
	            $this->success('新增成功');
	        }else{
	            $this->error('新增失败');
	        }
	    }else{
	        return $this->fetch();
	    }
	}
	
}