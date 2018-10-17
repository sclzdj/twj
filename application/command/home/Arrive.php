<?php
namespace app\command\home;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;
use app\common\controller\Common;
use think\Db;
class Arrive extends Command
{
    protected function configure()
    {
        //设置参数
        //$this->addArgument('timed', Argument::REQUIRED);//可选参数
        $this->setName('arrive')->setDescription('商品到达用户所在地时间');
    }
    protected function execute(Input $input, Output $output)
    {
        //放执行代码
        //$args = $input->getArguments();
        //print_r($args);
        //$timed=(int)$args['timed'];
        
        //换货商品到达用户所在地时间
        $orders=db('orders')->field('id,barter_express_id,barter_express_sn')->where('state','in','4,5')->where('refund_state','6')->where('refund_product_state','in','4,5,6')->where('barter_refund_user_arrived_at','0')->select();
        foreach ($orders as $key => $value) {
            if($value['barter_express_sn']=='') continue;
            $arr=express_query($value['barter_express_sn']);
            if($arr['status']=='0'){
                if(isset($arr['result']['list'][0]['time'])){
                    $time=strtotime($arr['result']['list']['0']['time']);
                    $rows=db('orders')->where('state','in','4,5')->where('refund_state','6')->where('refund_product_state','in','4,5,6')->where('id',$value['id'])->update(['refund_product_state'=>'5','barter_refund_user_arrived_at'=>$time,'updated_at'=>$time]);
                    if($rows>0){
                        db('order_logs')->insertGetId(['order_id'=>$value['id'],'content'=>'新货已送达','created_at'=>$time,'updated_at'=>$time]);
                    }
                }
            }else{
                $code=db('expresses')->where('id','barter_express_id')->value('code');
                if($code!==''){
                    $arr=express_query($value['barter_express_sn'],$code);
                    if($arr['status']=='0'){
                        if(isset($arr['result']['list'][0]['time'])){
                            $time=strtotime($arr['result']['list']['0']['time']);
                            $rows=db('orders')->where('state','in','4,5')->where('refund_state','6')->where('refund_product_state','in','4,5,6')->where('id',$value['id'])->update(['refund_product_state'=>'5','barter_refund_user_arrived_at'=>$time,'updated_at'=>$time]);
                            if($rows>0){
                                db('order_logs')->insertGetId(['order_id'=>$value['id'],'content'=>'新货已送达','created_at'=>$time,'updated_at'=>$time]);
                            }
                        }
                    }
                }
            }
        }

        //拼单商品到达用户所在地时间
        $orders=db('collage_orders')->field('id,express_id,express_sn')->where('state','in','2,5')->where('deliver_state','in','1,2,3')->where('arrived_at','0')->select();
        foreach ($orders as $key => $value) {
            if($value['express_sn']=='') continue;
            $arr=express_query($value['express_sn']);
            $express_msg=json_encode($arr);
            if($arr['status']=='0'){
                db('collage_orders')->where('id',$value['id'])->update(['express_msg'=>$express_msg]);
                if(isset($arr['result']['list'][0]['time'])){
                    $time=strtotime($arr['result']['list']['0']['time']);
                    $rows=db('collage_orders')->where('state','in','2,5')->where('deliver_state','in','1,2,3')->where('id',$value['id'])->update(['deliver_state'=>'2','arrived_at'=>$time,'updated_at'=>$time]);
                    if($rows>0){
                        db('collage_order_events')->insertGetId(['collage_order_id'=>$value['id'],'event'=>'商品已送达','created_at'=>$time,'updated_at'=>$time]);
                    }
                }
            }else{
                $code=db('expresses')->where('id','express_id')->value('code');
                if($code!==''){
                    $arr=express_query($value['express_sn'],$code);
                    if($arr['status']=='0'){
                        db('collage_orders')->where('id',$value['id'])->update(['express_msg'=>$express_msg]);
                        if(isset($arr['result']['list'][0]['time'])){
                            $time=strtotime($arr['result']['list']['0']['time']);
                            $rows=db('collage_orders')->where('state','in','2,5')->where('deliver_state','in','1,2,3')->where('id',$value['id'])->update(['deliver_state'=>'2','arrived_at'=>$time,'updated_at'=>$time]);
                            if($rows>0){
                                db('collage_order_events')->insertGetId(['collage_order_id'=>$value['id'],'event'=>'商品已送达','created_at'=>$time,'updated_at'=>$time]);
                            }
                        }
                    }
                }
            }
        }
        //print_r(['status'=>'1']);
    }
}