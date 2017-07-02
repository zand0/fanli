<?php
namespace app\seller\controller;
use think\Db;
class Index extends Base
{
    public function index()
    {
        $sid = session('seller_id');
        $this->assign('sys_info',$this->get_sys_info());
        //    	$today = strtotime("-1 day");
        $today = strtotime(date("Y-m-d"));
        $store = M('store')->where(['id'=>$sid])->find();
        $count['handle_order'] = M('order')->where("order_status=0 and (pay_status=1 or pay_code='cod')")->count();//待处理订单
        $count['new_order'] = M('order')->where("add_time>=$today")->count();//今天新增订单
        $count['goods'] =  M('goods')->where("store_id={$sid}")->count();//商品总数
        $count['goods_sale'] =  M('goods')->where("store_id={$sid} and is_on_sale=2")->count();//商品总数
        //$count['goods_status0'] =  M('goods')->where("goods_status=0")->count();//待审核商品总数
        $count['pay_status0'] = M('order')->where(['pay_status'=>0])->count();//带付款
        $count['shipping_status0'] = M('order')->where(['shipping_status'=>0])->count();//待发货
        $count['shipping_status2'] = M('order')->where(['shipping_status'=>2])->count();//部分发货
        $count['order_status0'] = M('order')->where(['order_status'=>0])->count();//待确认
        $count['tuihuo'] = M('return_goods')->alias('rg')->join('order o','o.order_id=rg.order_id','right')->where("rg.type=0")->count();//退货申请
        $count['huanhuo'] = M('return_goods')->alias('rg')->join('order o','o.order_id=rg.order_id','right')->where("rg.type=1")->count();//换货申请
        
        //$count['article'] =  M('article')->where("1=1")->count();//文章总数
        //$count['users'] = M('users')->where("1=1")->count();//会员总数
        //$count['today_login'] = M('users')->where("last_login>=$today")->count();//今日访问
        //$count['new_users'] = M('users')->where("reg_time>=$today")->count();//新增会员
        //$count['comment'] = M('comment')->where("is_show=0")->count();//最新评论
        $this->assign('store',$store);
        $this->assign('count',$count);
        return $this->fetch();
    }
    
    public function get_sys_info(){
        $sys_info['os']             = PHP_OS;
        $sys_info['zlib']           = function_exists('gzclose') ? 'YES' : 'NO';//zlib
        $sys_info['safe_mode']      = (boolean) ini_get('safe_mode') ? 'YES' : 'NO';//safe_mode = Off
        $sys_info['timezone']       = function_exists("date_default_timezone_get") ? date_default_timezone_get() : "no_timezone";
        $sys_info['curl']			= function_exists('curl_init') ? 'YES' : 'NO';
        $sys_info['web_server']     = $_SERVER['SERVER_SOFTWARE'];
        $sys_info['phpv']           = phpversion();
        $sys_info['ip'] 			= GetHostByName($_SERVER['SERVER_NAME']);
        $sys_info['fileupload']     = @ini_get('file_uploads') ? ini_get('upload_max_filesize') :'unknown';
        $sys_info['max_ex_time'] 	= @ini_get("max_execution_time").'s'; //脚本最大执行时间
        $sys_info['set_time_limit'] = function_exists("set_time_limit") ? true : false;
        $sys_info['domain'] 		= $_SERVER['HTTP_HOST'];
        $sys_info['memory_limit']   = ini_get('memory_limit');
        $sys_info['version']   	    = file_get_contents(APP_PATH.'admin/conf/version.php');
        $mysqlinfo = Db::query("SELECT VERSION() as version");
        $sys_info['mysql_version']  = $mysqlinfo[0]['version'];
        if(function_exists("gd_info")){
            $gd = gd_info();
            $sys_info['gdinfo'] 	= $gd['GD Version'];
        }else {
            $sys_info['gdinfo'] 	= "未知";
        }
        return $sys_info;
    }
    public function get_category(){
        
        $post = I('get.');
        
        $list = M('goods_category')->where(['parent_id'=>$post['parent_id']])->select();
        $str = '';
        foreach ($list as $v){
            $str .= "<option value='{$v['id']}' rel='0'>{$v['name']}</option>";
        }
        echo $str;
    } 
    
    public function quicklink_add(){
        return  'true';
    }
}
