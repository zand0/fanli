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
class Finance extends Base{
	
	/*
	 * 配置入口
	 */
	public function index()
	{          
		//return $this->fetch();
	}
	//转款列表
	public function store_remittance(){
	    
	    $list = M('remittance_store')->order('id desc')->select();
	    $this->assign('list',$list);
	    return $this->fetch('store_remittance2');
	}
	//提现列表
	public function store_withdrawals(){
	    $post = I('post.');
	    $list = M('finance')->where('status','<>','2')->order('id desc')->select();
	    $this->assign('list',$list);
	    return $this->fetch('store_withdrawals2');
	}
	
	public function store_withdrawals_update(){
	    $user_id = session('user_id');
	    $admin_id = session('admin_id');
	    $post = I('post.');
	    $ids = $post['id'];
	    if(!empty($post)){
	        foreach ($ids as $id){
                $ws = M('finance')->where(['id'=>$id])->find();
                $res1 = Db::table('tp_finance')->where('id',$id)->update(['status'=>$post['status']]);
                if($post['status']==1){
                    $res2 = M('remittance_store')->data([
                        'user_id'=>$ws['user_id'],  //'汇款的用户id' ,
                        'bank_name'=>$ws['bank_name'],  // '收款银行名称' ,
                        'account_bank'=>$ws['bank_card'],  // '银行账号' ,
                        'account_name'=>$ws['realname'],  // '开户人名称' ,
                        'money'=>$ws['money'],  // '汇款金额' ,
                        'status'=>0,  // '0汇款失败 1汇款成功' ,
                        'create_time'=>time(),  ///'汇款时间' ,
                        'remark'=>'',  // '汇款备注' ,
                        'admin_id'=>$admin_id,  //'管理员id' ,
                        'withdrawals_id'=>$ws['id']  // '提现申请表id' ,
                    ])->save();
                }
	        }
	        return json(['status'=>1,'msg'=>'操作成功','data'=>null]);
	    }
	}
}