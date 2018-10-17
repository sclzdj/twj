<?php
namespace app\mall\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder;


class Feedback extends Admin
{
	public function index($status='0'){  
        $list_tab = [
            '0' => ['title' => '合理化建议', 'url' => url('index', ['status' => '0'])],
            '1' => ['title' => '投诉', 'url' => url('index', ['status' => '1'])],
        ];
        switch ($status) {
            case '0':
                $order = $this->getOrder();
                if($order===''){
                    $order='id desc';
                }
                $map = $this->getMap();
                $data_list = Db::name('feedbacks')->where('type_id','2')->where($map)->order($order)->paginate();
                $page = $data_list->render();
                return ZBuilder::make('table')
                    ->setPageTitle('') // 设置页面标题
                    ->setPageTips('') // 设置页面提示信息
                    ->setTabNav($list_tab,  $status)//分组
                    ->hideCheckbox() //隐藏第一列多选框
                    ->setTableName('feedbacks') // 指定数据表名
                    ->addOrder('id,created_at') // 添加排序
                    ->addTopSelect('status', '全部状态', ['0'=>'未回复','1'=>'已回复']) //添加顶部下拉筛选
                    ->setSearch(['id' => 'ID', 'content' => '建议内容'], '', '', '搜索') // 设置搜索参数
                    ->addTimeFilter('created_at') // 添加时间段筛选
                    ->addColumns([
                            ['id', 'ID'], 
                            ['name', '反馈者','callback',function($value,$data){
                                $arr=[];
                                if($user=db('users')->where('id',$data['user_id'])->find()){
                                    $arr[]='用户：'.$user['name'];
                                }
                                if($salesman=db('salesmen')->where('id',$data['salesman_id'])->find()){
                                    $arr[]='代理商：'.$salesman['truename'];
                                }
                                if($supplier=db('suppliers')->where('id',$data['supplier_id'])->find()){
                                    $arr[]='供应商：'.$supplier['name'];
                                }
                                return implode('<br>', $arr);
                            },'__data__'],
                            ['content', '建议内容','callback',function($value){
                                return "<p style='max-width:400px;margin:0;'>{$value}</p>";
                            }],
                            ['created_at', '建议时间','datetime', '未知','Y-m-d H:i'], 
                            ['status', '回复状态','status','',['0'=>'未回复','1'=>'已回复']],
                            ['run', '操作','callback',function($value,$data){
                                if($data['status']){
                                    $json=json_encode(['area'=>['800px','450px'],'title'=>'查看回复']);
                                    return '<div class="btn-group"><a title="查看回复" icon="fa fa-smile-o" class="btn btn-xs btn-default ajax-get pop" href="'.url('look',['id'=>$data['id'],'',false]).'?_pop=1" data-layer=\''.$json.'\' _tag="custom">查看回复</a></div>';
                                }else{
                                    $json=json_encode(['area'=>['800px','450px'],'title'=>'开始回复']);
                                    return '<div class="btn-group"><a title="点击回复" icon="fa fa-smile-o" class="btn btn-xs btn-default ajax-get pop" href="'.url('handle',['id'=>$data['id'],'',false]).'?_pop=1" data-layer=\''.$json.'\' _tag="custom">点击回复</a></div>';
                                }
                            },'__data__'], 
                        ]) //添加多列数据
                    ->setRowList($data_list) // 设置表格数据
                    ->setPages($page) // 设置分页数据
                    ->fetch();
                break;
            case '1':
                $order = $this->getOrder();
                if($order===''){
                    $order='id desc';
                }
                $map = $this->getMap();
                $data_list = Db::name('feedbacks')->where('type_id','in','1,3')->where($map)->order($order)->paginate();
                $page = $data_list->render();
                return ZBuilder::make('table')
                    ->setPageTitle('') // 设置页面标题
                    ->setPageTips('') // 设置页面提示信息
                    ->setTabNav($list_tab,  $status)//分组
                    ->hideCheckbox() //隐藏第一列多选框
                    ->setTableName('feedbacks') // 指定数据表名
                    ->addOrder('id,created_at') // 添加排序
                    ->addTopSelect('status', '全部状态', ['0'=>'未处理','1'=>'已处理']) //添加顶部下拉筛选
                    ->setSearch(['id' => 'ID', 'content' => '投诉内容'], '', '', '搜索') // 设置搜索参数
                    ->addTimeFilter('created_at') // 添加时间段筛选
                    ->addColumns([
                            ['id', 'ID'], 
                            ['name', '投诉者','callback',function($value,$data){
                                $arr=[];
                                if($user=db('users')->where('id',$data['user_id'])->find()){
                                    $arr[]='用户：'.$user['name'];
                                }
                                if($salesman=db('salesmen')->where('id',$data['salesman_id'])->find()){
                                    $arr[]='代理商：'.$salesman['truename'];
                                }
                                if($supplier=db('suppliers')->where('id',$data['supplier_id'])->find()){
                                    $arr[]='供应商：'.$supplier['name'];
                                }
                                return implode('<br>', $arr);
                            },'__data__'],
                            ['type_id', '投诉对象','callback',function($value,$data){
                                if($value=='1'){
                                    return '商城';
                                }elseif($value=='3'){
                                    $arr=[];
                                    if($user=db('users')->where('id',$data['user_id'])->find()){
                                        $arr[]='代理商';
                                    }
                                    if($salesman=db('salesmen')->where('id',$data['salesman_id'])->find()){
                                        $arr[]='供应商';
                                    }
                                    return implode('<br>',$arr);;
                                }else{
                                    return '';
                                }
                                
                            },'__data__'],
                            ['content', '投诉内容','callback',function($value){
                                return "<p style='max-width:400px;margin:0;'>{$value}</p>";
                            }],
                            ['created_at', '投诉时间','datetime', '未知','Y-m-d H:i'],
                            ['status', '处理状态','status','',['0'=>'未处理','1'=>'已处理']],
                            ['run', '操作','callback',function($value,$data){
                                if($data['status']){
                                    $json=json_encode(['area'=>['800px','450px'],'title'=>'查看处理']);
                                    return '<div class="btn-group"><a title="查看处理" icon="fa fa-smile-o" class="btn btn-xs btn-default ajax-get pop" href="'.url('look',['id'=>$data['id'],'',false]).'?_pop=1" data-layer=\''.$json.'\' _tag="custom">查看处理</a></div>';
                                }else{
                                    $json=json_encode(['area'=>['800px','450px'],'title'=>'开始处理']);
                                    return '<div class="btn-group"><a title="点击处理" icon="fa fa-smile-o" class="btn btn-xs btn-default ajax-get pop" href="'.url('handle',['id'=>$data['id'],'',false]).'?_pop=1" data-layer=\''.$json.'\' _tag="custom">点击处理</a></div>';
                                }
                                
                            },'__data__'], 
                        ]) //添加多列数据
                    ->setRowList($data_list) // 设置表格数据
                    ->setPages($page) // 设置分页数据
                    ->fetch();
                break;
            default:
                break;
        } 
	}
    public function look($id=''){
        $feedback=Db::name("feedbacks")->where('status','1')->where('id',$id)->find();
        if(!$feedback){
            return $this->error('请求错误');
        }
        // 使用ZBuilder快速创建表单
        return '<div style="padding:20px 2%">'.$feedback['handle'].'</div>';
    }
    public function handle($id=''){
        //判断是否为post请求
        if (Request::instance()->isPost()) {
            //获取请求的post数据
            $data=input('post.');
            //数据输入验证
            $feedback=Db::name("feedbacks")->where('id',$data['id'])->find();
            if($feedback['type_id']=='2'){
                $tips='回复';
            }else{
                $tips='处理';
            }
            $validate = new Validate([
                'handle|'.$tips=> 'require|length:1,500',
            ]);
            if (!$validate->check($data)) {
                return $this->error($validate->getError());
            }
            //数据处理
            $update=array();
            $update['id']=$data['id'];
            $update['handle']=$data['handle'];
            $update['status']='1';
            $update['updated_at']=time();
            //数据更新
            $rt=Db::name("feedbacks")->update($update);
            //跳转
            if($rt!==false){
                return $this->success($tips.'成功',null,['_parent_reload' => 1]);
            } else {
                return $this->error($tips.'失败');
            }
        }
        // 接收id
        if ($id>0) {
            // 查处数据
            $feedback=Db::name("feedbacks")->where('status','0')->where('id',$id)->find();
            if(!$feedback){
                return $this->error('请求错误');
            }
            if($feedback['type_id']=='2'){
                $tips='回复';
            }else{
                $tips='处理';
            }
            // 使用ZBuilder快速创建表单
            return ZBuilder::make('form')
                ->setPageTitle('') // 设置页面标题
                ->setPageTips('') // 设置页面提示信息
                ->hideBtn([ 'back'])//隐藏按钮
                ->setBtnTitle('submit', '确定') //修改默认按钮标题
                ->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
                ->addTextarea('handle', $tips,'必填，限制在500个字以内')
                ->addHidden('id',$feedback['id'])
                //->isAjax(false) //默认为ajax的post提交
                ->fetch();
        }
    }
}