<?php
namespace app\mall\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder;


class Record extends Admin
{
	public function index($status='0'){  
        $list_tab = [
            '0' => ['title' => '每月新增用户', 'url' => url('index', ['status' => '0'])],
            '1' => ['title' => '用户地区排行', 'url' => url('index', ['status' => '1'])],
            '2' => ['title' => '用户订单排行', 'url' => url('index', ['status' => '2'])],
        ];
        $user_count=db('users')->count('id');
        $year=date('Y');
        $month=date('m');
        $st=strtotime($year.'-'.$month.'-01 00:00:00');
        if($month=='12'){
            $en_year=$year+1;
            $en_month=1;
        }else{
            $en_year=$year;
            $en_month=$month+1;
        }
        $en=strtotime($en_year.'-'.$en_month.'-01 00:00:00')-1;
        $user_month_count=db('users')->where('created_at','between',$st.','.$en)->count('id');
        $tips="当前总用户数量：<b>{$user_count}</b>人，本月新增用户：<b>{$user_month_count}</b>人。";
        switch ($status) {
            case '0':
                if($year==2018){
                    $default_year='';
                }else{
                    $default_year=$year;
                }
                if($month==1){
                    $default_month='';
                }else{
                    $default_month=$month;
                }
                $request_url=$_SERVER["REQUEST_URI"];
                $url_arr=parse_url($request_url);
                if(isset($url_arr['query'])){
                    parse_str($url_arr['query'], $query_arr);
                    if(isset($query_arr['status']) && $query_arr['status']==0){
                        $pix=true;
                    }else{
                        $pix=false;
                    }
                }else{
                    $pix=false;
                }
                $map = $this->getMap();
                if (empty($map) || (!isset($map['year']) && !isset($map['month']))) {
                    if($pix){
                        $s_year=2018;
                        $s_month=1; 
                    }else{
                        $s_year=$year;
                        $s_month=$month; 
                    }
                }elseif(!isset($map['year']) && isset($map['month'])){
                    $s_year=2018;
                    $s_month=$map['month'];
                }elseif(!isset($map['month'])  && isset($map['year'])){
                    $s_year=$map['year'];
                    $s_month=1;
                }else{
                    $s_year=$map['year'];
                    $s_month=$map['month'];
                }
                if($s_month=='12'){
                    $en_year=$s_year+1;
                    $en_month=1;
                }else{
                    $en_year=$s_year;
                    $en_month=$s_month+1;
                }
                $st=strtotime($s_year.'-'.$s_month.'-01 00:00:00');
                $en=strtotime($en_year.'-'.$en_month.'-01 00:00:00')-1;
                $for=ceil(($en-$st)/(24*60*60));
                $data_list=[];
                for ($i=1; $i <= $for; $i++) { 
                    $st_d=strtotime($s_year.'-'.$s_month.'-'.$i.' 00:00:00');
                    $en_d=strtotime($s_year.'-'.$s_month.'-'.$i.' 23:59:59');
                    $data_list[]=[
                        'day'=>'第'.$i.'日',
                        'count'=>db('users')->where('created_at','between',$st_d.','.$en_d)->count('id')
                    ];
                }
                session('user_add_excel_year',$s_year);
                session('user_add_excel_month',$s_month);
                session('user_add_excel_data',$data_list);
                $months=['2'=>'二月','3'=>'三月','4'=>'四月','5'=>'五月','6'=>'六月','7'=>'七月','8'=>'八月','9'=>'九月','10'=>'十月','11'=>'十一月','12'=>'十二月'];
                $years=[];
                for ($i=2019; $i <= 2118; $i++) { 
                    $years[$i]=$i.'年';
                }
                return ZBuilder::make('table')
                    ->setPageTitle('') // 设置页面标题
                    ->setPageTips($tips) // 设置页面提示信息
                    ->setTabNav($list_tab,  $status)//分组
                    ->hideCheckbox() //隐藏第一列多选框
                    ->addTopSelect('year', '2018年', $years, $default_year) //添加顶部下拉筛选
                    ->addTopSelect('month', '一月', $months, $default_month) //添加顶部下拉筛选
                    ->addColumns([
                            ['day', '第几日'], 
                            ['count', '新增用户数'],
                        ]) //添加多列数据
                    ->addTopButton('custom',['title'=>'导出excel文件','href'=>url('excel',['status'=>0])])
                    ->setRowList($data_list) // 设置表格数据
                    ->fetch();
                break;
            case '1':
                $map = $this->getMap();
                session('user_region_rank_excel_map',$map);
                if(isset($map['created_at'][0]) && isset($map['created_at'][1]) && $map['created_at'][0]=='between time'){
                    $where=" WHERE created_at<=".strtotime($map['created_at'][1][1])." && created_at>=".strtotime($map['created_at'][1][0]);
                }else{
                    $where="";
                }
                $data=Db::query("SELECT area_id,COUNT(id) AS counts from ".config('database.prefix')."users {$where} GROUP BY area_id ORDER BY counts DESC LIMIT 100");
                $data_list=[];
                foreach ($data as $key => $value) {
                    $area=db('regions')->where('id',$value['area_id'])->find();
                    $city=db('regions')->where('id',$area['parent_id'])->find();
                    $province=db('regions')->where('id',$city['parent_id'])->find();
                    $region=$province['name'].' '.$city['name'].' '.$area['name'];
                    if($key==0){
                        $rank=$key+1;
                    }else{
                        if($value['counts']!=$data[$key-1]['counts']){
                            $rank=$key+1;
                        }
                    }
                    $data_list[]=['rank'=>$rank,'region'=>$region,'counts'=>$value['counts']];
                }
                session('user_region_rank_excel_data',$data_list);
                return ZBuilder::make('table')
                    ->setPageTitle('') // 设置页面标题
                    ->setPageTips($tips) // 设置页面提示信息
                    ->setTabNav($list_tab,  $status)//分组
                    ->hideCheckbox() //隐藏第一列多选框
                    ->addTimeFilter('created_at') // 添加时间段筛选
                    ->addColumns([
                            ['rank', '排名'], 
                            ['region', '地区'],
                            ['counts', '数量'],
                        ]) //添加多列数据
                    ->addTopButton('custom',['title'=>'导出excel文件','href'=>url('excel',['status'=>1])])
                    ->setRowList($data_list) // 设置表格数据
                    ->fetch();
                break;
            case '2':
                $map = $this->getMap();
                session('user_order_rank_excel_map',$map);
                if(isset($map['created_at'][0]) && isset($map['created_at'][1]) && $map['created_at'][0]=='between time'){
                    $where=" WHERE paid_at<=".strtotime($map['created_at'][1][1])." && paid_at>=".strtotime($map['created_at'][1][0])." && state=1";
                    $wh="paid_at<=".strtotime($map['created_at'][1][1])." && paid_at>=".strtotime($map['created_at'][1][0]);
                    $wh2="a. paid_at<=".strtotime($map['created_at'][1][1])." && a.paid_at>=".strtotime($map['created_at'][1][0]);
                    $wh3="paid_at<=".strtotime($map['created_at'][1][1])." && paid_at>=".strtotime($map['created_at'][1][0]);
                }else{
                    $where=" WHERE state=1";
                    $wh="";
                    $wh2="";
                    $wh3="";
                }
                $data=Db::query("SELECT user_id,SUM(pay_amount) AS sums from ".config('database.prefix')."trades{$where} GROUP BY user_id ORDER BY sums DESC LIMIT 100");
                $data_list=[];
                foreach ($data as $key => $value) {
                    if($key==0){
                        $rank=$key+1;
                    }else{
                        if($value['sums']!=$data[$key-1]['sums']){
                            $rank=$key+1;
                        }
                    }
                    $pix=['rank'=>$rank];
                    $user=db('users')->where('id',$value['user_id'])->find();
                    if($user){
                        $pix['name']=$user['name'];
                        $pix['mobile']=$user['mobile'];
                    }else{
                        $pix['name']='';
                        $pix['mobile']='';
                    }
                    $pix['sums']=number_format($value['sums']/100,2,'.','');
                    $count1=db('orders')->where('user_id',$value['user_id'])->where($wh)->where('state','in','1,3,4,5,8')->where('refund_state','in','-1,-2,-3,0,1,2,3,6,9,10,11')->count('id');
                    $count2=db('collage_orders')->where('user_id',$value['user_id'])->where($wh)->where('state','in','1,2,5')->count('id');
                    $pix['order_counts']=$count1+$count2;
                    $sum1=db('orders a')->join('order_products b','a.id=b.order_id','LEFT')->where($wh2)->where('a.user_id',$value['user_id'])->where('a.state','in','1,3,4,5,8')->where('a.refund_state','in','-1,-2,-3,0,1,2,3,6,9,10,11')->sum('b.num');
                    $sum2=db('collage_orders')->where('user_id',$value['user_id'])->where($wh3)->where('state','in','1,2,5')->sum('num');
                    $pix['product_sums']=$sum1+$sum2;
                    $data_list[]=$pix;
                }
                session('user_order_rank_excel_data',$data_list);
                return ZBuilder::make('table')
                    ->setPageTitle('') // 设置页面标题
                    ->setPageTips($tips) // 设置页面提示信息
                    ->setTabNav($list_tab,  $status)//分组
                    ->hideCheckbox() //隐藏第一列多选框
                    ->addTimeFilter('created_at') // 添加时间段筛选
                    ->addColumns([
                            ['rank', '排名'], 
                            ['name', '用户名'],
                            ['mobile', '联系电话'],
                            ['sums', '订单总金额'],
                            ['order_counts', '订单总数量'],
                            ['product_sums', '包含商品总数量'],
                        ]) //添加多列数据
                    ->addTopButton('custom',['title'=>'导出excel文件','href'=>url('excel',['status'=>2])])
                    ->setRowList($data_list) // 设置表格数据
                    ->fetch();
                break;
            default:
                break;
        } 
	}
    public function excel($status){
        switch ($status) {
            case '0':
                $header = array(
                  '第几日'=>'string',//text
                  '新增用户数'=>'string',//text
                );
                $data_list = session('user_add_excel_data');
                $year=session('user_add_excel_year');
                $month=session('user_add_excel_month');
                $file_name = $year.'年'.$month.'月每日新增用户数量-'.mt_rand(1000,9999).'.xlsx';     //下载文件名    
                $file_dir = "excels/users/adds/";        //下载文件存放目录    
                do_rmdir($file_dir,false);//先清空文件夹
                break;
            case '1':
                $header = array(
                  '排名'=>'string',//text
                  '地区'=>'string',//text
                  '数量'=>'string',//text
                );
                $map = session('user_region_rank_excel_map');
                if(isset($map['created_at'][0]) && isset($map['created_at'][1]) && $map['created_at'][0]=='between time'){
                    $date='（'.explode(' ',$map['created_at'][1][0])[0].'至'.explode(' ',$map['created_at'][1][1])[0].'）-';
                }else{
                    $date='';
                }
                $data_list = session('user_region_rank_excel_data');
                $file_name = '用户地区排行-'.$date.mt_rand(1000,9999).'.xlsx';     //下载文件名    
                $file_dir = "excels/users/regions/ranks/";        //下载文件存放目录    
                do_rmdir($file_dir,false);//先清空文件夹
                break;
            case '2':
                $header = array(
                  '排名'=>'string',//text
                  '用户名'=>'string',//text
                  '联系电话'=>'string',//text
                  '订单总金额'=>'string',//text
                  '订单总数量'=>'string',//text
                  '包含商品总数量'=>'string',//text
                );
                $map = session('user_order_rank_excel_map');
                if(isset($map['created_at'][0]) && isset($map['created_at'][1]) && $map['created_at'][0]=='between time'){
                    $date='（'.explode(' ',$map['created_at'][1][0])[0].'至'.explode(' ',$map['created_at'][1][1])[0].'）-';
                }else{
                    $date='';
                }
                $data_list = session('user_order_rank_excel_data');
                $file_name = '用户订单排行-'.$date.mt_rand(1000,9999).'.xlsx';     //下载文件名    
                $file_dir = "excels/users/regions/ranks/";        //下载文件存放目录    
                do_rmdir($file_dir,false);//先清空文件夹
                break;
            default:
                $this->error('请求错误');
                break;
        }
        
        $rows = $data_list;
        import('PHP_XLSXWriter-master.xlsxwriter', EXTEND_PATH,'.class.php');
        $writer = new \XLSXWriter();
        $writer->writeSheetHeader('Sheet1', $header);
        foreach($rows as $row){
            $row=array_values($row);
            $writer->writeSheetRow('Sheet1', $row);
        }
        $writer->writeToFile($file_dir.$file_name);
        //检查文件是否存在    
        if (! file_exists ( $file_dir . $file_name )) {    
            $this->error('文件未生成成功，请重试');
        } else {    
            header('Location:'.config('mall.public_url').$file_dir.$file_name);
            die;
        } 
    }
    public function supplier($status='0'){  
        $list_tab = [
            '0' => ['title' => '交易额排行', 'url' => url('supplier', ['status' => '0'])],
            '1' => ['title' => '订单数量排行', 'url' => url('supplier', ['status' => '1'])],
            '2' => ['title' => '交易商品数量排行', 'url' => url('supplier', ['status' => '2'])],
        ];
        switch ($status) {
            case '0':
                $map = $this->getMap();
                session('supplier_trade_rank_excel_map',$map);
                if(isset($map['created_at'][0]) && isset($map['created_at'][1]) && $map['created_at'][0]=='between time'){
                    $where=" WHERE a.created_at<=".strtotime($map['created_at'][1][1])." && a.created_at>=".strtotime($map['created_at'][1][0])." && a.amount>0";
                }else{
                    $where=" WHERE a.amount>0";
                }
                $data=Db::query("SELECT b.serial_num,b.name,SUM(a.amount) AS sums from ".config('database.prefix')."supplier_capital_flows a LEFT JOIN ".config('database.prefix')."suppliers b ON a.supplier_id=b.id{$where} GROUP BY a.supplier_id ORDER BY sums DESC LIMIT 100");
                $data_list=[];
                foreach ($data as $key => $value) {
                    if($key==0){
                        $rank=$key+1;
                    }else{
                        if($value['sums']!=$data[$key-1]['sums']){
                            $rank=$key+1;
                        }
                    }
                    $data_list[]=['rank'=>$rank,'name'=>$value['name'],'serial_num'=>$value['serial_num'],'sums'=>number_format($value['sums']/100,2,'.','')];
                }
                session('supplier_trade_rank_excel_data',$data_list);
                return ZBuilder::make('table')
                    ->setPageTitle('') // 设置页面标题
                    ->setPageTips('') // 设置页面提示信息
                    ->setTabNav($list_tab,  $status)//分组
                    ->hideCheckbox() //隐藏第一列多选框
                    ->addTimeFilter('created_at') // 添加时间段筛选
                    ->addColumns([
                            ['rank', '排名'], 
                            ['name', '供应商名称'],
                            ['serial_num', '供应商编号'],
                            ['sums', '交易额'],
                        ]) //添加多列数据
                    ->addTopButton('custom',['title'=>'导出excel文件','href'=>url('excelsupplier',['status'=>0])])
                    ->setRowList($data_list) // 设置表格数据
                    ->fetch();
                break;
            case '1':
                $map = $this->getMap();
                session('supplier_order_rank_excel_map',$map);
                if(isset($map['created_at'][0]) && isset($map['created_at'][1]) && $map['created_at'][0]=='between time'){
                    $where=" WHERE a.paid_at<=".strtotime($map['created_at'][1][1])." && a.paid_at>=".strtotime($map['created_at'][1][0])." && a.state in (1,3,4,5,8) && a.refund_state in (-1,-2,-3,0,1,2,3,6,9,10,11) && a.is_purchase=1 && a.supplier_product_state=1";
                }else{
                    $where=" WHERE a.state in (1,3,4,5,8) && a.refund_state in (-1,-2,-3,0,1,2,3,6,9,10,11) && a.is_purchase=1 && a.supplier_product_state=1";
                }
                $data=Db::query("SELECT b.name,COUNT(a.id) AS counts from ".config('database.prefix')."orders a LEFT JOIN ".config('database.prefix')."suppliers b ON a.supplier_id=b.id{$where} GROUP BY a.supplier_id ORDER BY counts DESC LIMIT 100");
                $data_list=[];
                foreach ($data as $key => $value) {
                    if($key==0){
                        $rank=$key+1;
                    }else{
                        if($value['counts']!=$data[$key-1]['counts']){
                            $rank=$key+1;
                        }
                    }
                    $data_list[]=['rank'=>$rank,'name'=>$value['name'],'counts'=>$value['counts']];
                }
                session('supplier_order_rank_excel_data',$data_list);
                return ZBuilder::make('table')
                    ->setPageTitle('') // 设置页面标题
                    ->setPageTips('') // 设置页面提示信息
                    ->setTabNav($list_tab,  $status)//分组
                    ->hideCheckbox() //隐藏第一列多选框
                    ->addTimeFilter('created_at') // 添加时间段筛选
                    ->addColumns([
                            ['rank', '排名'], 
                            ['name', '供应商'],
                            ['counts', '订单数量'],
                        ]) //添加多列数据
                    ->addTopButton('custom',['title'=>'导出excel文件','href'=>url('excelsupplier',['status'=>1])])
                    ->setRowList($data_list) // 设置表格数据
                    ->fetch();
                break;
            case '2':
                $map = $this->getMap();
                session('supplier_product_rank_excel_map',$map);
                if(isset($map['created_at'][0]) && isset($map['created_at'][1]) && $map['created_at'][0]=='between time'){
                    $where=" WHERE a.paid_at<=".strtotime($map['created_at'][1][1])." && a.paid_at>=".strtotime($map['created_at'][1][0])." && a.state in (1,3,4,5,8) && a.refund_state in (-1,-2,-3,0,1,2,3,6,9,10,11) && a.is_purchase=1 && a.supplier_product_state=1";
                }else{
                    $where=" WHERE a.state in (1,3,4,5,8) && a.refund_state in (-1,-2,-3,0,1,2,3,6,9,10,11) && a.is_purchase=1 && a.supplier_product_state=1";
                }
                $data=Db::query("SELECT b.name,SUM(c.num) AS nums from ".config('database.prefix')."orders a LEFT JOIN ".config('database.prefix')."suppliers b ON a.supplier_id=b.id LEFT JOIN ".config('database.prefix')."order_products c ON a.id=c.order_id{$where} GROUP BY a.supplier_id ORDER BY nums DESC LIMIT 100");
                $data_list=[];
                foreach ($data as $key => $value) {
                    if($key==0){
                        $rank=$key+1;
                    }else{
                        if($value['nums']!=$data[$key-1]['nums']){
                            $rank=$key+1;
                        }
                    }
                    $data_list[]=['rank'=>$rank,'name'=>$value['name'],'nums'=>$value['nums']];
                }
                session('supplier_product_rank_excel_data',$data_list);
                return ZBuilder::make('table')
                    ->setPageTitle('') // 设置页面标题
                    ->setPageTips('') // 设置页面提示信息
                    ->setTabNav($list_tab,  $status)//分组
                    ->hideCheckbox() //隐藏第一列多选框
                    ->addTimeFilter('created_at') // 添加时间段筛选
                    ->addColumns([
                            ['rank', '排名'], 
                            ['name', '供应商'],
                            ['nums', '交易商品数量'],
                        ]) //添加多列数据
                    ->addTopButton('custom',['title'=>'导出excel文件','href'=>url('excelsupplier',['status'=>2])])
                    ->setRowList($data_list) // 设置表格数据
                    ->fetch();
                break;
            default:
                break;
        } 
    }
    public function excelsupplier($status){
        switch ($status) {
            case '0':
                $header = array(
                  '排名'=>'string',//text
                  '供应商名称'=>'string',//text
                  '供应商编号'=>'string',//text
                  '交易额'=>'string',//text
                );
                $map = session('supplier_trade_rank_excel_map');
                if(isset($map['created_at'][0]) && isset($map['created_at'][1]) && $map['created_at'][0]=='between time'){
                    $date='（'.explode(' ',$map['created_at'][1][0])[0].'至'.explode(' ',$map['created_at'][1][1])[0].'）-';
                }else{
                    $date='';
                }
                $data_list=session('supplier_trade_rank_excel_data');
                $file_name = '供应商交易额排行-'.$date.mt_rand(1000,9999).'.xlsx';     //下载文件名    
                $file_dir = "excels/suppliers/trades/ranks/";        //下载文件存放目录    
                do_rmdir($file_dir,false);//先清空文件夹
                break;
            case '1':
                $header = array(
                  '排名'=>'string',//text
                  '供应商'=>'string',//text
                  '订单数量'=>'string',//text
                );
                $map = session('supplier_order_rank_excel_map');
                if(isset($map['created_at'][0]) && isset($map['created_at'][1]) && $map['created_at'][0]=='between time'){
                    $date='（'.explode(' ',$map['created_at'][1][0])[0].'至'.explode(' ',$map['created_at'][1][1])[0].'）-';
                }else{
                    $date='';
                }
                $data_list=session('supplier_order_rank_excel_data');
                $file_name = '供应商订单数量排行-'.$date.mt_rand(1000,9999).'.xlsx';     //下载文件名    
                $file_dir = "excels/suppliers/orders/ranks/";        //下载文件存放目录    
                do_rmdir($file_dir,false);//先清空文件夹
                break;
            case '2':
                $header = array(
                  '排名'=>'string',//text
                  '供应商'=>'string',//text
                  '交易商品数量'=>'string',//text
                );
                $map = session('supplier_product_rank_excel_map');
                if(isset($map['created_at'][0]) && isset($map['created_at'][1]) && $map['created_at'][0]=='between time'){
                    $date='（'.explode(' ',$map['created_at'][1][0])[0].'至'.explode(' ',$map['created_at'][1][1])[0].'）-';
                }else{
                    $date='';
                }
                $data_list=session('supplier_product_rank_excel_data');
                $file_name = '供应商交易商品数量排行-'.$date.mt_rand(1000,9999).'.xlsx';     //下载文件名    
                $file_dir = "excels/suppliers/products/ranks/";        //下载文件存放目录    
                do_rmdir($file_dir,false);//先清空文件夹
                break;
            default:
                $this->error('请求错误');
                break;
        }
        
        $rows = $data_list;
        import('PHP_XLSXWriter-master.xlsxwriter', EXTEND_PATH,'.class.php');
        $writer = new \XLSXWriter();
        $writer->writeSheetHeader('Sheet1', $header);
        foreach($rows as $row){
            $row=array_values($row);
            $writer->writeSheetRow('Sheet1', $row);
        }
        $writer->writeToFile($file_dir.$file_name);
        //检查文件是否存在    
        if (! file_exists ( $file_dir . $file_name )) {    
            $this->error('文件未生成成功，请重试');
        } else {    
            header('Location:'.config('mall.public_url').$file_dir.$file_name);
            die;
        } 
    }
    public function trade($status='0'){  
        $list_tab = [
            '0' => ['title' => '每月商城交易额', 'url' => url('trade', ['status' => '0'])],
            '1' => ['title' => '每月地区交易数据排行', 'url' => url('trade', ['status' => '1'])],
        ];
        $year=date('Y');
        $month=date('m');
        $st=strtotime($year.'-'.$month.'-01 00:00:00');
        if($month=='12'){
            $en_year=$year+1;
            $en_month=1;
        }else{
            $en_year=$year;
            $en_month=$month+1;
        }
        $en=strtotime($en_year.'-'.$en_month.'-01 00:00:00')-1;
        $platform_water_count=db('platform_waters')->where('created_at','between',$st.','.$en)->sum('amount');
        $tips="本月商城交易额：<b>".number_format($platform_water_count/100,2,'.','')."</b>元。";
        switch ($status) {
            case '0':
                if($year==2018){
                    $default_year='';
                }else{
                    $default_year=$year;
                }
                if($month==1){
                    $default_month='';
                }else{
                    $default_month=$month;
                }
                $request_url=$_SERVER["REQUEST_URI"];
                $url_arr=parse_url($request_url);
                if(isset($url_arr['query'])){
                    parse_str($url_arr['query'], $query_arr);
                    if(isset($query_arr['status']) && $query_arr['status']==0){
                        $pix=true;
                    }else{
                        $pix=false;
                    }
                }else{
                    $pix=false;
                }
                $map = $this->getMap();
                if (empty($map) || (!isset($map['year']) && !isset($map['month']))) {
                    if($pix){
                        $s_year=2018;
                        $s_month=1; 
                    }else{
                        $s_year=$year;
                        $s_month=$month; 
                    }
                }elseif(!isset($map['year']) && isset($map['month'])){
                    $s_year=2018;
                    $s_month=$map['month'];
                }elseif(!isset($map['month'])  && isset($map['year'])){
                    $s_year=$map['year'];
                    $s_month=1;
                }else{
                    $s_year=$map['year'];
                    $s_month=$map['month'];
                }
                if($s_month=='12'){
                    $en_year=$s_year+1;
                    $en_month=1;
                }else{
                    $en_year=$s_year;
                    $en_month=$s_month+1;
                }
                $st=strtotime($s_year.'-'.$s_month.'-01 00:00:00');
                $en=strtotime($en_year.'-'.$en_month.'-01 00:00:00')-1;
                $for=ceil(($en-$st)/(24*60*60));
                $data_list=[];
                for ($i=1; $i <= $for; $i++) { 
                    $st_d=strtotime($s_year.'-'.$s_month.'-'.$i.' 00:00:00');
                    $en_d=strtotime($s_year.'-'.$s_month.'-'.$i.' 23:59:59');
                    $amount=db('platform_waters')->where('created_at','between',$st_d.','.$en_d)->sum('amount');
                    $data_list[]=[
                        'day'=>'第'.$i.'日',
                        'sum'=>number_format($amount/100,2,'.','')
                    ];
                }
                session('platform_water_excel_year',$s_year);
                session('platform_water_excel_month',$s_month);
                session('platform_water_excel_data',$data_list);
                $months=['2'=>'二月','3'=>'三月','4'=>'四月','5'=>'五月','6'=>'六月','7'=>'七月','8'=>'八月','9'=>'九月','10'=>'十月','11'=>'十一月','12'=>'十二月'];
                $years=[];
                for ($i=2019; $i <= 2118; $i++) { 
                    $years[$i]=$i.'年';
                }
                return ZBuilder::make('table')
                    ->setPageTitle('') // 设置页面标题
                    ->setPageTips($tips) // 设置页面提示信息
                    ->setTabNav($list_tab,  $status)//分组
                    ->hideCheckbox() //隐藏第一列多选框
                    ->addTopSelect('year', '2018年', $years, $default_year) //添加顶部下拉筛选
                    ->addTopSelect('month', '一月', $months, $default_month) //添加顶部下拉筛选
                    ->addColumns([
                            ['day', '第几日'], 
                            ['sum', '交易额'],
                        ]) //添加多列数据
                    ->addTopButton('custom',['title'=>'导出excel文件','href'=>url('exceltrade',['status'=>0])])
                    ->setRowList($data_list) // 设置表格数据
                    ->fetch();
                break;
            case '1':
                if($year==2018){
                    $default_year='';
                }else{
                    $default_year=$year;
                }
                if($month==1){
                    $default_month='';
                }else{
                    $default_month=$month;
                }
                $request_url=$_SERVER["REQUEST_URI"];
                $url_arr=parse_url($request_url);
                if(isset($url_arr['query'])){
                    parse_str($url_arr['query'], $query_arr);
                    if(isset($query_arr['status']) && $query_arr['status']==1){
                        $pix=true;
                    }else{
                        $pix=false;
                    }
                }else{
                    $pix=false;
                }
                $map = $this->getMap();
                if (empty($map) || (!isset($map['year']) && !isset($map['month']))) {
                    if($pix){
                        $s_year=2018;
                        $s_month=1; 
                    }else{
                        $s_year=$year;
                        $s_month=$month; 
                    }
                }elseif(!isset($map['year']) && isset($map['month'])){
                    $s_year=2018;
                    $s_month=$map['month'];
                }elseif(!isset($map['month'])  && isset($map['year'])){
                    $s_year=$map['year'];
                    $s_month=1;
                }else{
                    $s_year=$map['year'];
                    $s_month=$map['month'];
                }
                if($s_month=='12'){
                    $en_year=$s_year+1;
                    $en_month=1;
                }else{
                    $en_year=$s_year;
                    $en_month=$s_month+1;
                }
                $st=strtotime($s_year.'-'.$s_month.'-01 00:00:00');
                $en=strtotime($en_year.'-'.$en_month.'-01 00:00:00')-1;
                $where=" WHERE a.paid_at<=".$en." && a.paid_at>=".$st." && a.state=1 ";
                $data=Db::query("SELECT b.area_id,sum(pay_amount) AS sums from ".config('database.prefix')."trades a LEFT JOIN ".config('database.prefix')."users b ON a.user_id=b.id {$where} GROUP BY b.area_id ORDER BY sums DESC LIMIT 100");
                $data_list=[];
                foreach ($data as $key => $value) {
                    $area=db('regions')->where('id',$value['area_id'])->find();
                    $city=db('regions')->where('id',$area['parent_id'])->find();
                    $province=db('regions')->where('id',$city['parent_id'])->find();
                    $region=$province['name'].' '.$city['name'].' '.$area['name'];
                    if($key==0){
                        $rank=$key+1;
                    }else{
                        if($value['sums']!=$data[$key-1]['sums']){
                            $rank=$key+1;
                        }
                    }
                    $count1=db('orders a')->join('users b','a.user_id=b.id','LEFT')->where('a.paid_at','between',$st.','.$en)->where('b.area_id',$value['area_id'])->where('a.state','in','1,3,4,5,8')->where('a.refund_state','in','-1,-2,-3,0,1,2,3,6,9,10,11')->count('a.id');
                    $count2=db('collage_orders a')->join('users b','a.user_id=b.id','LEFT')->where('a.paid_at','between',$st.','.$en)->where('b.area_id',$value['area_id'])->where('a.state','in','1,2,5')->count('a.id');
                    $order_counts=$count1+$count2;
                    $data_list[]=['rank'=>$rank,'region'=>$region,'sums'=>number_format($value['sums']/100,2,'.',''),'order_counts'=>$order_counts];
                }
                session('trade_region_excel_year',$s_year);
                session('trade_region_excel_month',$s_month);
                session('trade_region_excel_data',$data_list);
                $months=['2'=>'二月','3'=>'三月','4'=>'四月','5'=>'五月','6'=>'六月','7'=>'七月','8'=>'八月','9'=>'九月','10'=>'十月','11'=>'十一月','12'=>'十二月'];
                $years=[];
                for ($i=2019; $i <= 2118; $i++) { 
                    $years[$i]=$i.'年';
                }
                return ZBuilder::make('table')
                    ->setPageTitle('') // 设置页面标题
                    ->setPageTips($tips) // 设置页面提示信息
                    ->setTabNav($list_tab,  $status)//分组
                    ->hideCheckbox() //隐藏第一列多选框
                    ->addTopSelect('year', '2018年', $years, $default_year) //添加顶部下拉筛选
                    ->addTopSelect('month', '一月', $months, $default_month) //添加顶部下拉筛选
                    ->addColumns([
                            ['rank', '排名'], 
                            ['region', '地区'],
                            ['sums', '交易额'],
                            ['order_counts', '订单数'],
                        ]) //添加多列数据
                    ->addTopButton('custom',['title'=>'导出excel文件','href'=>url('exceltrade',['status'=>1])])
                    ->setRowList($data_list) // 设置表格数据
                    ->fetch();
                break;
            default:
                break;
        } 
    }
    public function exceltrade($status){
        switch ($status) {
            case '0':
                $header = array(
                  '第几日'=>'string',//text
                  '交易额'=>'string',//text
                );
                $data_list = session('platform_water_excel_data');
                $year=session('platform_water_excel_year');
                $month=session('platform_water_excel_month');
                $file_name = $year.'年'.$month.'月商城交易额-'.mt_rand(1000,9999).'.xlsx';     //下载文件名    
                $file_dir = "excels/trades/platforms/";        //下载文件存放目录    
                do_rmdir($file_dir,false);//先清空文件夹
                break;
            case '1':
                $header = array(
                  '排名'=>'string',//text
                  '地区'=>'string',//text
                  '交易额'=>'string',//text
                  '订单数'=>'string',//text
                );
                $data_list = session('trade_region_excel_data');
                $year=session('trade_region_excel_year');
                $month=session('trade_region_excel_month');
                $file_name = $year.'年'.$month.'月地区交易数据排行-'.mt_rand(1000,9999).'.xlsx';     //下载文件名    
                $file_dir = "excels/trades/regions/ranks/";        //下载文件存放目录    
                do_rmdir($file_dir,false);//先清空文件夹
                break;
            default:
                $this->error('请求错误');
                break;
        }
        
        $rows = $data_list;
        import('PHP_XLSXWriter-master.xlsxwriter', EXTEND_PATH,'.class.php');
        $writer = new \XLSXWriter();
        $writer->writeSheetHeader('Sheet1', $header);
        foreach($rows as $row){
            $row=array_values($row);
            $writer->writeSheetRow('Sheet1', $row);
        }
        $writer->writeToFile($file_dir.$file_name);
        //检查文件是否存在    
        if (! file_exists ( $file_dir . $file_name )) {    
            $this->error('文件未生成成功，请重试');
        } else {    
            header('Location:'.config('mall.public_url').$file_dir.$file_name);
            die;
        } 
    }
    public function product(){  
        $map = $this->getMap();
        session('supplier_product_rank_excel_map',$map);
        if(isset($map['created_at'][0]) && isset($map['created_at'][1]) && $map['created_at'][0]=='between time'){
            $where=" WHERE c.paid_at<=".strtotime($map['created_at'][1][1])." && c.paid_at>=".strtotime($map['created_at'][1][0])." && c.state in (1,3,4,5,8) && c.refund_state in (-1,-2,-3,0,1,2,3,6,9,10,11)";
        }else{
            $where=" WHERE c.state in (1,3,4,5,8) && c.refund_state in (-1,-2,-3,0,1,2,3,6,9,10,11)";
        }
        $data=Db::query("SELECT b.name,SUM(a.num) AS nums from ".config('database.prefix')."order_products a LEFT JOIN ".config('database.prefix')."products b ON a.product_id=b.id LEFT JOIN ".config('database.prefix')."orders c ON a.order_id=c.id{$where} GROUP BY a.product_id ORDER BY nums DESC LIMIT 100");
        $data_list=[];
        foreach ($data as $key => $value) {
            if($key==0){
                $rank=$key+1;
            }else{
                if($value['nums']!=$data[$key-1]['nums']){
                    $rank=$key+1;
                }
            }
            $data_list[]=['rank'=>$rank,'name'=>$value['name'],'nums'=>$value['nums']];
        }
        session('supplier_product_rank_excel_data',$data_list);
        return ZBuilder::make('table')
            ->setPageTitle('商品交易数量排行') // 设置页面标题
            ->setPageTips('') // 设置页面提示信息
            ->hideCheckbox() //隐藏第一列多选框
            ->addTimeFilter('created_at') // 添加时间段筛选
            ->addColumns([
                    ['rank', '排名'], 
                    ['name', '商品名称'],
                    ['nums', '交易数量'],
                ]) //添加多列数据
            ->addTopButton('custom',['title'=>'导出excel文件','href'=>url('excelproduct')])
            ->setRowList($data_list) // 设置表格数据
            ->fetch();
    }
    public function excelproduct(){
        $header = array(
          '排名'=>'string',//text
          '商品名称'=>'string',//text
          '交易数量'=>'string',//text
        );
        $map = session('supplier_product_rank_excel_map');
        if(isset($map['created_at'][0]) && isset($map['created_at'][1]) && $map['created_at'][0]=='between time'){
            $date='（'.explode(' ',$map['created_at'][1][0])[0].'至'.explode(' ',$map['created_at'][1][1])[0].'）-';
        }else{
            $date='';
        }
        $data_list=session('supplier_product_rank_excel_data');
        $file_name = '商品交易数量排行-'.$date.mt_rand(1000,9999).'.xlsx';     //下载文件名    
        $file_dir = "excels/products/ranks/";        //下载文件存放目录    
        do_rmdir($file_dir,false);//先清空文件夹
        
        $rows = $data_list;
        import('PHP_XLSXWriter-master.xlsxwriter', EXTEND_PATH,'.class.php');
        $writer = new \XLSXWriter();
        $writer->writeSheetHeader('Sheet1', $header);
        foreach($rows as $row){
            $row=array_values($row);
            $writer->writeSheetRow('Sheet1', $row);
        }
        $writer->writeToFile($file_dir.$file_name);
        //检查文件是否存在    
        if (! file_exists ( $file_dir . $file_name )) {    
            $this->error('文件未生成成功，请重试');
        } else {    
            header('Location:'.config('mall.public_url').$file_dir.$file_name);
            die;
        } 
    }
}