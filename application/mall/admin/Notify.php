<?php
namespace app\mall\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder;


class Notify extends Admin
{
	public function index(){  
		$order = $this->getOrder();
        if($order===''){
            $order='id desc';
        }
        $map = $this->getMap();
		$data_list = Db::name('notifys')->where($map)->order($order)->paginate();
		$page = $data_list->render();
        return ZBuilder::make('table')
        	->setPageTitle('') // 设置页面标题
        	->setPageTips('') // 设置页面提示信息
        	->hideCheckbox() //隐藏第一列多选框
        	->setTableName('notifys') // 指定数据表名
        	->addOrder('id,send_at') // 添加排序
        	->addTimeFilter('send_at') // 添加时间段筛选
        	->addTopSelect('type', '全部发送对象', ['1'=>'用户','2'=>'供应商','3'=>'代理商']) //添加顶部下拉筛选
        	->setSearch(['id' => 'ID', 'title' => '标题'], '', '', '搜索') // 设置搜索参数
        	->addColumns([
        			['id', 'ID'], 
        			['title', '标题'],
        			['admin_attachment_id', '图片','picture','暂无图片'],
        			['type', '发送对象','callback','array_v',['1'=>'用户','2'=>'供应商','3'=>'代理商']], 
        			['send_at', '发送时间','datetime', '未知','Y-m-d H:i'], 
        			['right_button', '操作', 'btn'],
        		]) //添加多列数据
        	->addRightButton('custom',['title'=>'查看通知内容','href'=>url('look',['id'=>'__ID__'])],true) 
        	->addTopButton('custom',['title'=>'新建通知','href'=>url('send'),'icon'=>'fa fa-fw fa-send']) 
        	->setRowList($data_list) // 设置表格数据
        	->setPages($page) // 设置分页数据
        	->fetch();
	}
	public function look($id=''){
		$notify=Db::name("notifys")->where('id',$id)->find();
		if(!$notify){
			return $this->error('请求错误');
		}
		// 使用ZBuilder快速创建表单
		return '<div style="padding:20px 2%">'.$notify['content'].'</div>';
	}
	public function send(){
		//判断是否为post请求
		if (Request::instance()->isPost()) {
			$now=time();
			//获取请求的post数据
			$data=input('post.');
			//数据输入验证
			$validate = new Validate([
				'title|标题'=> 'require|length:1,30',
			    'admin_attachment_id|图片'  => 'require',
			    'type|发送对象'  => 'require|in:1,2,3',
			    'content|内容' => 'require'
			]);
			if (!$validate->check($data)) {
			    return $this->error($validate->getError());
			}
			//数据处理
			$insert=array();
			$insert['title']=$data['title'];
			$insert['admin_attachment_id']=$data['admin_attachment_id'];
			$insert['type']=$data['type'];
			$insert['content']=$_POST['content'];
			$insert['send_at']=$now;
			$insert['created_at']=$now;
			$insert['updated_at']=$now;
			//数据更新
			$insert_id=Db::name("notifys")->insertGetId($insert);
			//跳转
			if($insert_id>0){
				return $this->success('发送成功',url('index'));
	        } else {
	            return $this->error('发送失败');
	        }
		}
		
		// 使用ZBuilder快速创建表单
		return ZBuilder::make('form')
			->setPageTitle('新建通知') // 设置页面标题
			->setPageTips('请认真编辑相关信息') // 设置页面提示信息
			->setBtnTitle('submit', '发送通知') //修改默认按钮标题
			->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
			->addText('title', '标题','必填，限制在30个字以内')
			->addImage('admin_attachment_id', '图片','必传')
			->addSelect('type', '发送对象','必选',['1'=>'用户','2'=>'供应商','3'=>'代理商'])
			->addUeditor('content', '内容','建议在1200个字以内')
			//->isAjax(false) //默认为ajax的post提交
			->fetch();
	}
}