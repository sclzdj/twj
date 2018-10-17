<?php
namespace app\mall\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder;


class Index extends Admin
{
	public function index($status='0'){  
        $z1=strtotime(date('Y-m-d').'-1 week Monday');
        $z0=strtotime($z1.'.+7 day')-1;
        $count1=Db::name('suppliers')->where('audit_state','0')->count('id');
        $count2=Db::name('products')->where('audit_state','0')->where('state','in','1,2')->count('id');
        $count3=Db::name('users')->where('created_at','between',$z1.','.$z0)->count('id');
        $count4_1=Db::name('orders')->where('paid_at','between',$z1.','.$z0)->where('state','in','1,3,4,5,8')->where('refund_state','in','-1,-2,-3,0,1,2,3,6,9,10,11')->count('id');
        $count4_2=Db::name('collage_orders')->where('paid_at','between',$z1.','.$z0)->where('state','in','1,2,5')->count('id');
        $count4=$count4_1+$count4_2;
        $data_list=[
            ['channel'=>'待审核供应商','counts'=>$count1],
            ['channel'=>'待审核商品','counts'=>$count2],
            ['channel'=>'本周新增用户数','counts'=>$count3],
            ['channel'=>'本周新增订单数（含拼单）','counts'=>$count4],
        ];
        return ZBuilder::make('table')
            ->setPageTitle('') // 设置页面标题
            ->setPageTips('') // 设置页面提示信息
            ->hideCheckbox() //隐藏第一列多选框
            ->addColumns([
                    ['channel', '栏目'], 
                    ['counts', '数量'],
                ]) //添加多列数据
            ->setRowList($data_list) // 设置表格数据
            ->fetch();
	}
}