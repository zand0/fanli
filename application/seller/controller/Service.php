<?php
namespace app\seller\controller;
use app\admin\logic\OrderLogic;
use think\AjaxPage;
use think\Page;
use think\Db;
class Service extends Base
{
    public function index()
    {
        return ;
    }
    
    public function return_list(){
        // 搜索条件
        $order_sn =  trim(I('order_sn'));
        $order_by = I('order_by') ? I('order_by') : 'id';
        $sort_order = I('sort_order') ? I('sort_order') : 'desc';
        $status =  I('status');
        
        $where = " 1 = 1 ";
        $order_sn && $where.= " and order_sn like '%$order_sn%' ";
        empty($order_sn)&& !empty($status) && $where.= " and status = '$status' ";
        $count = M('return_goods')->alias('rg')->join('order o','o.order_id=rg.order_id and find_in_set('.session('seller_id').',o.store_ids)')->where($where)->count();
        $Page  = new AjaxPage($count,13);
        $show = $Page->show();
        $list = M('return_goods')->alias('rg')->join('order o','o.order_id=rg.order_id and find_in_set('.session('seller_id').',o.store_ids)')->where($where)->order("$order_by $sort_order")->limit("{$Page->firstRow},{$Page->listRows}")->select();
        $goods_id_arr = get_arr_column($list, 'goods_id');
        if(!empty($goods_id_arr)){
            $goods_list = M('goods')->where("goods_id in (".implode(',', $goods_id_arr).")")->getField('goods_id,goods_name');
        }
        $state = C('REFUND_STATUS');
        $this->assign('state',$state);
        $this->assign('goods_list',$goods_list);
        $this->assign('list',$list);
        $this->assign('pager',$Page);
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }
    
    public function return_info(){
        $return_id = I('id');
        $return_goods = M('return_goods')->where("id= $return_id")->find();
        if(!$return_goods)
        {
            $this->error('非法操作!');
            exit;
        }
        $user = M('users')->where("user_id = {$return_goods[user_id]}")->find();
        $goods = M('goods')->where("goods_id = {$return_goods[goods_id]}")->find();
        $type_msg = array('退换','换货');
        $status_msg = array('未处理','处理中','已完成');
        if(IS_POST)
        {
            $data = I('post.');
            $data['seller_delivery']['express_time'] = date('Y-m-d H:i:s');
            $data['seller_delivery'] = serialize($data['seller_delivery']); //换货的物流信息
            $note ="退换货:{$type_msg[$data['type']]}, 状态:{$status_msg[$data['status']]},处理备注：{$data['remark']}";
            $result = M('return_goods')->where("id= $return_id")->save($data);
            if($result)
            {
                $orderLogic = new OrderLogic();
                //审核通过才更改订单商品状态，进行退货，退款时要改对应商品修改库存
                if($data['status']==1) {
                    $type = empty($data['type']) ? 2 : 3;
                    M('order_goods')
                    ->where(['order_id' =>$return_goods['order_id'],'goods_id'=>$return_goods['goods_id']])
                    ->save(array('is_send' => $type));//更改商品状态
                    $orderLogic->alterReturnGoodsInventory($return_goods['order_id'],$return_goods['goods_id']); //审核通过，恢复原来库存
                }
        
                $log = $orderLogic->orderActionLog($return_goods['order_id'],'refund',$note);
                $this->success('修改成功!');
                exit;
            }
        }
        $return_goods['seller_delivery'] = unserialize($return_goods['seller_delivery']);  //订单的物流信息，服务类型为换货会显示
        if($return_goods['imgs'])
            $return_goods['imgs'] = explode(',', $return_goods['imgs']);
            $this->assign('id',$return_id); // 用户
            $this->assign('user',$user); // 用户
            $this->assign('goods',$goods);// 商品
            $this->assign('return_goods',$return_goods);// 退换货
            return $this->fetch();
    }
}
