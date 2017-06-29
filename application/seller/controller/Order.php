<?php
namespace app\seller\controller;
use app\admin\logic\OrderLogic;
use think\AjaxPage;
use think\Page;
use think\Db;
class Order extends Base
{
    public function index()
    {
        $where = [];
        $order_count = M('order')->where($where)->count();
        $Page = new Page($order_count,C('PAGESIZE'));
        $list = M('order')->where($where)->order('order_id desc')->limit($Page->firstRow,$Page->listRows)->select();
        foreach ($list as &$v){
            $v['order_goods'] = M('order_goods')->where(['order_id'=>$v['order_id']])->select();
        }
        unset($v);
        $this->assign('list',$list);
        $this->assign('page',$Page->show());
        return $this->fetch();
    }
    
    public function detail(){
        $order_id = I('get.order_id');
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        $orderGoods = $orderLogic->getOrderGoods($order_id);
        $button = $orderLogic->getOrderButton($order);
        // 获取操作记录
        $action_log = M('order_action')->where(array('order_id'=>$order_id))->order('log_time desc')->select();
        $userIds = array();
        //查找用户昵称
        foreach ($action_log as $k => $v){
            $userIds[$k] = $v['action_user'];
        }
        if($userIds && count($userIds) > 0){
            $users = M("users")->where("user_id in (".implode(",",$userIds).")")->getField("user_id , nickname" , true);
        }
        $this->assign('users',$users);
        $this->assign('order',$order);
        $this->assign('action_log',$action_log);
        $this->assign('orderGoods',$orderGoods);
        $this->assign('shipping_status' , C('SHIPPING_STATUS'));
        $split = count($orderGoods) >1 ? 1 : 0;
        foreach ($orderGoods as $val){
        	if($val['goods_num']>1){
        		$split = 1;
        	}
        }
        $this->assign('split',$split);
        $this->assign('order_status',C('ORDER_STATUS'));
        $this->assign('pay_status',C('PAY_STATUS'));
        $this->assign('button',$button);
        return $this->fetch();
    }
    
    public function edit_order(){
        $order_id = I('order_id');
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        if($order['shipping_status'] != 0){
            $this->error('已发货订单不允许编辑');
            exit;
        }
        
        $orderGoods = $orderLogic->getOrderGoods($order_id);
        
        if(IS_POST)
        {
            $order['consignee'] = I('consignee');// 收货人
            $order['province'] = I('province'); // 省份
            $order['city'] = I('city'); // 城市
            $order['district'] = I('district'); // 县
            $order['address'] = I('address'); // 收货地址
            $order['mobile'] = I('mobile'); // 手机
            $order['invoice_title'] = I('invoice_title');// 发票
            $order['admin_note'] = I('admin_note'); // 管理员备注
            $order['admin_note'] = I('admin_note'); //
            $order['shipping_code'] = I('shipping');// 物流方式
            $order['shipping_name'] = M('plugin')->where(array('status'=>1,'type'=>'shipping','code'=>I('shipping')))->getField('name');
            $order['pay_code'] = I('payment');// 支付方式
            $order['pay_name'] = M('plugin')->where(array('status'=>1,'type'=>'payment','code'=>I('payment')))->getField('name');
            $goods_id_arr = I("goods_id/a");
            $new_goods = $old_goods_arr = array();
            //################################订单添加商品
            if($goods_id_arr){
                $new_goods = $orderLogic->get_spec_goods($goods_id_arr);
                foreach($new_goods as $key => $val)
                {
                    $val['order_id'] = $order_id;
                    $rec_id = M('order_goods')->add($val);//订单添加商品
                    if(!$rec_id)
                        $this->error('添加失败');
                }
            }
        
            //################################订单修改删除商品
            $old_goods = I('old_goods/a');
            foreach ($orderGoods as $val){
                if(empty($old_goods[$val['rec_id']])){
                    M('order_goods')->where("rec_id=".$val['rec_id'])->delete();//删除商品
                }else{
                    //修改商品数量
                    if($old_goods[$val['rec_id']] != $val['goods_num']){
                        $val['goods_num'] = $old_goods[$val['rec_id']];
                        M('order_goods')->where("rec_id=".$val['rec_id'])->save(array('goods_num'=>$val['goods_num']));
                    }
                    $old_goods_arr[] = $val;
                }
            }
        
            $goodsArr = array_merge($old_goods_arr,$new_goods);
            $result = calculate_price($order['user_id'],$goodsArr,$order['shipping_code'],0,$order['province'],$order['city'],$order['district'],0,0,0,0);
            if($result['status'] < 0)
            {
                $this->error($result['msg']);
            }
        
            //################################修改订单费用
            $order['goods_price']    = $result['result']['goods_price']; // 商品总价
            $order['shipping_price'] = $result['result']['shipping_price'];//物流费
            $order['order_amount']   = $result['result']['order_amount']; // 应付金额
            $order['total_amount']   = $result['result']['total_amount']; // 订单总价
            $o = M('order')->where('order_id='.$order_id)->save($order);
        
            $l = $orderLogic->orderActionLog($order_id,'edit','修改订单');//操作日志
            exit;
            if($o && $l){
                $this->success('修改成功',U('Admin/Order/editprice',array('order_id'=>$order_id)));
            }else{
                $this->success('修改失败',U('Admin/Order/detail',array('order_id'=>$order_id)));
            }
            exit;
        }
        // 获取省份
        $province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
        //获取订单城市
        $city =  M('region')->where(array('parent_id'=>$order['province'],'level'=>2))->select();
        //获取订单地区
        $area =  M('region')->where(array('parent_id'=>$order['city'],'level'=>3))->select();
        //获取支付方式
        $payment_list = M('plugin')->where(array('status'=>1,'type'=>'payment'))->select();
        //获取配送方式
        $shipping_list = M('plugin')->where(array('status'=>1,'type'=>'shipping'))->select();
        
        $this->assign('order',$order);
        $this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign('area',$area);
        $this->assign('orderGoods',$orderGoods);
        $this->assign('shipping_list',$shipping_list);
        $this->assign('payment_list',$payment_list);
        return $this->fetch();
    }
    
    public function search_goods(){
        $brandList =  M("brand")->select();
        $categoryList =  M("goods_category")->select();
        $this->assign('categoryList',$categoryList);
        $this->assign('brandList',$brandList);
        $where = ' is_on_sale = 1 ';//搜索条件
        I('intro')  && $where = "$where and ".I('intro')." = 1";
        if(I('cat_id')){
            $this->assign('cat_id',I('cat_id'));
            $grandson_ids = getCatGrandson(I('cat_id'));
            $where = " $where  and cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
        
        }
        if(I('brand_id')){
            $this->assign('brand_id',I('brand_id'));
            $where = "$where and brand_id = ".I('brand_id');
        }
        if(!empty($_REQUEST['keywords']))
        {
            $this->assign('keywords',I('keywords'));
            $where = "$where and (goods_name like '%".I('keywords')."%' or keywords like '%".I('keywords')."%')" ;
        }
        $goods_count =M('goods')->where($where)->count();
        $Page = new Page($goods_count,C('PAGESIZE'));
        $goodsList = M('goods')->where($where)->order('goods_id DESC')->limit($Page->firstRow,$Page->listRows)->select();
        
        foreach($goodsList as $key => $val)
        {
            $spec_goods = M('spec_goods_price')->where("goods_id = {$val['goods_id']}")->select();
            $goodsList[$key]['spec_goods'] = $spec_goods;
        }
        if($goodsList){
            //计算商品数量
            foreach ($goodsList as $value){
                if($value['spec_goods']){
                    $count += count($value['spec_goods']);
                }else{
                    $count++;
                }
            }
            $this->assign('totalSize',$count);
        }
        
        $this->assign('page',$Page->show());
        $this->assign('goodsList',$goodsList);
        return $this->fetch();
    }
    
    public function delivery_info(){
        $order_id = I('get.order_id',0);
        if(empty($order_id)){
            $this->error("找不到订单");
        }
        $orderLogic = new OrderLogic();
    	$order = $orderLogic->getOrderInfo($order_id);
    	$orderGoods = $orderLogic->getOrderGoods($order_id,2);
        if(!$orderGoods)$this->error('此订单商品已完成退货或换货');//已经完成售后的不能再发货
		$delivery_record = M('delivery_doc')->alias('d')->join('tp_users u','u.user_id = d.user_id')->where('d.order_id='.$order_id)->select();
		if($delivery_record){
			$order['invoice_no'] = $delivery_record[count($delivery_record)-1]['invoice_no'];
		}
		$this->assign('order',$order);
		$this->assign('orderGoods',$orderGoods);
		$this->assign('delivery_record',$delivery_record);//发货记录
    	return $this->fetch();
    }
    
    public function delivery_list(){
        $where = [];
        $where['pay_status']=1;
        $order_count = M('order')->where($where)->count();
        $Page = new Page($order_count,C('PAGESIZE'));
        $list = M('order')->where($where)->order('order_id desc')->limit($Page->firstRow,$Page->listRows)->select();
        foreach ($list as &$v){
            $v['order_goods'] = M('order_goods')->where(['order_id'=>$v['order_id']])->select();
            
        }
        unset($v);
        
        $this->assign('list',$list);
        $this->assign('page',$Page->show());
        return $this->fetch();
    }
    
    public function deliveryHandle(){
        $orderLogic = new \app\seller\logic\OrderLogic();
        $data = I('post.');
        $res = $orderLogic->deliveryHandle($data);
        if($res){
            $this->success('操作成功');
        }else{
            $this->success('操作失败');
        }
    }
}
