<?php
namespace app\mall\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder;


class Survey extends Admin
{
	public function index(){  
		$order = $this->getOrder();
        if($order===''){
            $order='id desc';
        }
        $map = $this->getMap();
		$data_list = Db::name('surveys')->where($map)->order($order)->paginate();
		$page = $data_list->render();
        return ZBuilder::make('table')
        	->setPageTitle('') // 设置页面标题
        	->setPageTips('') // 设置页面提示信息
        	->hideCheckbox() //隐藏第一列多选框
        	->setTableName('surveys') // 指定数据表名
        	->addOrder('id') // 添加排序
        	->addColumns([
        			['id', 'ID'], 
        			['title', '名称'],
                    ['contact', '联系人'],
        			['mobile', '手机号码'],
        			['region', '所属地区','callback',function($value,$data){
        				$province=db('regions')->where('id',$data['province_id'])->value('name');
        				$city=db('regions')->where('id',$data['city_id'])->value('name');
        				$area=db('regions')->where('id',$data['area_id'])->value('name');
        				return $province.' '.$city.' '.$area;
        			}, '__data__'],
        			['mainsale_type', '主营产品'], 
                    ['salesman_id', '代理商','callback',function($value){
                        return db('salesmen')->where('id',$value)->value('truename');
                    }],
                    ['right_button', '操作', 'btn'],
        		]) //添加多列数据
        	->addRightButton('custom',['title'=>'查看详情','href'=>url('look',['id'=>'__ID__'])],true) 
        	->setRowList($data_list) // 设置表格数据
        	->setPages($page) // 设置分页数据
        	->fetch();
	}
	public function look($id=''){
		$survey=Db::name("surveys")->where('id',$id)->find();
		if(!$survey){
			return $this->error('请求错误');
		}
		$province=db('regions')->where('id',$survey['province_id'])->value('name');
        $city=db('regions')->where('id',$survey['city_id'])->value('name');
        $area=db('regions')->where('id',$survey['area_id'])->value('name');
        $region=$province.' '.$city.' '.$area;
        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('调研信息详情') // 设置页面标题
            //->setPageTips('') // 设置页面提示信息
            ->hideBtn(['back','submit']) //隐藏默认按钮
            //->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
            ->addStatic('title', '名称','',$survey['title'])
            ->addStatic('salesman', '提交信息代理商','',db('salesmen')->where('id',$survey['salesman_id'])->value('truename'))
            ->addStatic('created_at', '提交时间','',date('Y-m-d H:i',$survey['created_at']))
            ->addStatic('contact', '联系人','',$survey['contact'])
            ->addStatic('mobile', '手机号码','',$survey['mobile'])
            ->addStatic('region', '所在地区','',$region)
            ->addStatic('address', '详细地址','',$survey['address'])
            ->addStatic('mainsale_type', '主营产品','',$survey['mainsale_type'])
            ->addStatic('pic', '照片','',staticText($survey['pic'],'pic'))
            ->addStatic('remark', '其他情况','',$survey['remark'])
            //->isAjax(false) //默认为ajax的post提交
            ->fetch();
	}
}