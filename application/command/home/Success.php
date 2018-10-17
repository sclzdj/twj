<?php
namespace app\command\home;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;
use app\common\controller\Common;
use think\Db;
class Success extends Command
{
    protected function configure()
    {
        //设置参数
        //$this->addArgument('timed', Argument::REQUIRED);//可选参数
        $this->setName('success')->setDescription('订单交易状态置为8，拼单订单确认收货');
    }
    protected function execute(Input $input, Output $output)
    {
        //放执行代码
        //$args = $input->getArguments();
        //print_r($args);
        //$timed=(int)$args['timed'];
        $now=time();
        //订单交易状态置为8
        $orders=db('orders')->where('state','in','4,5')->where('refund_state','-3,-2,-1,0')->where('arrived_at','<',$now-config('crontab.order_success_limit')*24*60*60)->select();
        foreach ($orders as $key => $value) {
            $rows=db('orders')->where('state','in','4,5')->where('refund_state','-3,-2,-1,0')->where('id',$value['id'])->update(['state'=>'8','updated_at'=>$now]);
            if($rows>0){
                if($value['state']=='4'){
                    $rows=db('orders')->where('id',$value['id'])->update(['received_at'=>$now,'updated_at'=>$now]);
                    if($rows>0){
                        db('order_logs')->insertGetId(['order_id'=>$value['id'],'content'=>'已确认收货（系统自动）','created_at'=>$now,'updated_at'=>$now]);
                    }
                }
                db('order_logs')->insertGetId(['order_id'=>$value['id'],'content'=>'交易成功，订单完成','created_at'=>$now,'updated_at'=>$now]);
            }
        }
        //拼单订单确认收货
        $orders=db('collage_orders')->where('state','in','2,5')->where('deliver_state','2')->where('arrived_at','<',$now-config('crontab.order_success_limit')*24*60*60)->select();
        foreach ($orders as $key => $value) {
            $rows=db('collage_orders')->where('state','in','2,5')->where('deliver_state','2')->where('id',$value['id'])->update(['deliver_state'=>'3','updated_at'=>$now]);
            if($rows>0){
                $rows=db('collage_orders')->where('id',$value['id'])->update(['received_at'=>$now,'updated_at'=>$now]);
                if($rows>0){
                    db('collage_order_events')->insertGetId(['collage_order_id'=>$value['id'],'event'=>'已确认收货（系统自动），订单完成','created_at'=>$now,'updated_at'=>$now]);
                }
            }
        }
        //print_r(['status'=>'1']);
    }
}