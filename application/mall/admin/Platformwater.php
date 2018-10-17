<?php
namespace app\mall\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder;


class Platformwater extends Admin
{
	public function index(){  
		$order = $this->getOrder();
        if($order===''){
            $order='id desc';
        }
        $map = $this->getMap();
        session('platform_water_excel_order',$order);
        session('platform_water_excel_map',$map);
		$data_list = Db::name('platform_waters')->where($map)->order($order)->paginate();
		$page = $data_list->render();
        return ZBuilder::make('table')
        	->setPageTitle('') // 设置页面标题
        	->setPageTips('') // 设置页面提示信息
        	->hideCheckbox() //隐藏第一列多选框
        	->setTableName('platform_waters') // 指定数据表名
        	->addOrder('id,amount,created_at') // 添加排序
            ->addTimeFilter('created_at') // 添加时间段筛选
        	->setSearch(['id' => 'ID', 'sn' => '流水号'], '', '', '搜索') // 设置搜索参数
        	->addColumns([
        			['id', 'ID'], 
        			['sn', '流水号'],
        			['amount', '金额','callback',function($value){
                        return number_format($value/100,2,'.','').'元';
                    }],
        			['channel', '渠道'],
        			['source', '来源'], 
                    ['created_at', '时间','datetime', '未知','Y-m-d H:i'],
                    ['relate_order', '相关编号'], 
        		]) //添加多列数据 
            ->addTopButton('custom',['title'=>'导出excel文件','href'=>url('excel')])
        	->setRowList($data_list) // 设置表格数据
        	->setPages($page) // 设置分页数据
        	->fetch();
	}
    public function excel(){
        $header = array(
          'ID'=>'string',//text
          '流水号'=>'string',//text
          '金额(元)'=>'string',//text
          '渠道'=>'string',//text
          '来源'=>'string',//text
          '时间'=>'string',//text
          '相关编号'=>'string',//text
        );

        $data_list = Db::name('platform_waters')->field("id,sn,amount,channel,source,created_at,relate_order")->where(session('platform_water_excel_map'))->order(session('platform_water_excel_order'))->select();
        $rows = $data_list;

        import('PHP_XLSXWriter-master.xlsxwriter', EXTEND_PATH,'.class.php');
        $writer = new \XLSXWriter();
        $writer->writeSheetHeader('Sheet1', $header);
        foreach($rows as $row){
            $row['created_at']=date('Y-m-d H:i',$row['created_at']);
            $row['amount']=number_format($row['amount']/100,2,'.','');
            $row=array_values($row);
            $writer->writeSheetRow('Sheet1', $row);
        }

        $file_name = '平台流水-'.date('YmdHis').'-'.mt_rand(1000,9999).'.xlsx';     //下载文件名    
        $file_dir = "excels/platformwaters/";        //下载文件存放目录   
        
        do_rmdir($file_dir,false);//先清空文件夹

        $writer->writeToFile($file_dir.$file_name);
        
        //检查文件是否存在    
        if (! file_exists ( $file_dir . $file_name )) {    
            $this->error('流水文件未生成成功，请重试');
        } else {    
            header('Location:'.config('mall.public_url').$file_dir.$file_name);
            die;
        } 
    }
}