<?php
namespace app\mall\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder;


class Salesmen extends Admin
{
	public function index($status='-1'){  
        $list_tab = [
            '-1' => ['title' => '已审核', 'url' => url('index', ['status' => '-1'])],
            '2' => ['title' => '待审核', 'url' => url('index', ['status' => '2'])],
            '3' => ['title' => '驳回', 'url' => url('index', ['status' => '3'])],
        ];
        switch ($status) {
            case '-1':
                $order = $this->getOrder();
                if($order===''){
                    $order='id desc';
                }
                $map = $this->getMap();
                $data_list = Db::name('salesmen')->where('status','in','0,1')->where($map)->order($order)->paginate();
                $page = $data_list->render();
                return ZBuilder::make('table')
                    ->setPageTitle('') // 设置页面标题
                    ->setPageTips('') // 设置页面提示信息
                    ->setTabNav($list_tab,  $status)//分组
                    ->hideCheckbox() //隐藏第一列多选框
                    ->setTableName('salesmen') // 指定数据表名
                    ->addOrder('id,created_at') // 添加排序
                    ->addTimeFilter('created_at') // 添加时间段筛选
                    ->setSearch(['id' => 'ID', 'truename' => '姓名','mobile'=>'手机'], '', '', '搜索') // 设置搜索参数
                    ->addTopSelect('level', '区域类型', ['1'=>'一级','2'=>'二级']) //添加顶部下拉筛选
                    ->addColumns([
                            ['id', 'ID'], 
                            ['truename', '姓名'],
                            ['mobile', '手机号码'],
                            ['level', '区域类型','callback','array_v',['1'=>'一级','2'=>'二级']], 
                            ['region', '负责区域','callback',function($value,$data){
                                $province=db('regions')->where('id',$data['province_id'])->value('name');
                                $city=db('regions')->where('id',$data['city_id'])->value('name');
                                $area=db('regions')->where('id',$data['area_id'])->value('name');
                                return $province.' '.$city.' '.$area;
                            }, '__data__'], 
                            ['status', '状态', 'status', '', ['禁用', '正常']],
                            ['created_at', '注册时间','datetime', '未知','Y-m-d H:i'], 
                            ['right_button', '操作', 'btn'],
                        ]) //添加多列数据
                    ->addRightButton('edit',['title'=>'修改'])
                    ->setRowList($data_list) // 设置表格数据
                    ->setPages($page) // 设置分页数据
                    ->fetch();
                break;
            case '2':
                $order = $this->getOrder();
                if($order===''){
                    $order='id desc';
                }
                $map = $this->getMap();
                $data_list = Db::name('salesmen')->where('status','2')->where($map)->order($order)->paginate();
                $page = $data_list->render();
                return ZBuilder::make('table')
                    ->setPageTitle('') // 设置页面标题
                    ->setPageTips('') // 设置页面提示信息
                    ->setTabNav($list_tab,  $status)//分组
                    ->hideCheckbox() //隐藏第一列多选框
                    ->setTableName('salesmen') // 指定数据表名
                    ->addOrder('id,created_at') // 添加排序
                    ->addTimeFilter('created_at') // 添加时间段筛选
                    ->setSearch(['id' => 'ID', 'truename' => '姓名','mobile'=>'手机'], '', '', '搜索') // 设置搜索参数
                    ->addTopSelect('level', '区域类型', ['1'=>'一级','2'=>'二级']) //添加顶部下拉筛选
                    ->addColumns([
                            ['id', 'ID'], 
                            ['truename', '姓名'],
                            ['mobile', '手机号码'],
                            ['level', '区域类型','callback','array_v',['1'=>'一级','2'=>'二级']], 
                            ['region', '负责区域','callback',function($value,$data){
                                $province=db('regions')->where('id',$data['province_id'])->value('name');
                                $city=db('regions')->where('id',$data['city_id'])->value('name');
                                $area=db('regions')->where('id',$data['area_id'])->value('name');
                                return $province.' '.$city.' '.$area;
                            }, '__data__'], 
                            ['created_at', '注册时间','datetime', '未知','Y-m-d H:i'], 
                            ['right_button', '操作', 'btn'],
                        ]) //添加多列数据
                    ->addRightButton('custom',['title'=>'审核','href'=>url('audit',['id'=>'__ID__'])]) 
                    ->setRowList($data_list) // 设置表格数据
                    ->setPages($page) // 设置分页数据
                    ->fetch();
                break;
            case '3':
                $order = $this->getOrder();
                if($order===''){
                    $order='id desc';
                }
                $map = $this->getMap();
                $data_list = Db::name('salesmen')->where('status','3')->where($map)->order($order)->paginate();
                $page = $data_list->render();
                return ZBuilder::make('table')
                    ->setPageTitle('') // 设置页面标题
                    ->setPageTips('') // 设置页面提示信息
                    ->setTabNav($list_tab,  $status)//分组
                    ->hideCheckbox() //隐藏第一列多选框
                    ->setTableName('salesmen') // 指定数据表名
                    ->addOrder('id,created_at') // 添加排序
                    ->addTimeFilter('created_at') // 添加时间段筛选
                    ->setSearch(['id' => 'ID', 'truename' => '姓名','mobile'=>'手机'], '', '', '搜索') // 设置搜索参数
                    ->addTopSelect('level', '区域类型', ['1'=>'一级','2'=>'二级']) //添加顶部下拉筛选
                    ->addColumns([
                            ['id', 'ID'], 
                            ['truename', '姓名'],
                            ['mobile', '手机号码'],
                            ['level', '区域类型','callback','array_v',['1'=>'一级','2'=>'二级']], 
                            ['region', '负责区域','callback',function($value,$data){
                                $province=db('regions')->where('id',$data['province_id'])->value('name');
                                $city=db('regions')->where('id',$data['city_id'])->value('name');
                                $area=db('regions')->where('id',$data['area_id'])->value('name');
                                return $province.' '.$city.' '.$area;
                            }, '__data__'], 
                            ['created_at', '注册时间','datetime', '未知','Y-m-d H:i'], 
                            ['auditmsg','上次驳回原因','callback',function($value,$data){
                                return db('salesman_audits')->where('salesman_id',$data['id'])->order('audit_at desc')->value('auditmsg');
                            },'__data__']
                            //['right_button', '操作', 'btn'],
                        ]) //添加多列数据
                    //->addRightButton('custom',['title'=>'查看详情','href'=>url('look',['id'=>'__ID__'])],true) 
                    ->setRowList($data_list) // 设置表格数据
                    ->setPages($page) // 设置分页数据
                    ->fetch();
                break;
            default:
                break;
        }
    		
	}
    public function audit($id=''){
        //判断是否为post请求
        if (Request::instance()->isPost()) {
            $now=time();
            //获取请求的post数据
            $data=input('post.');
            // 查处数据
            $salesman=Db::name("salesmen")->where('status','2')->where('id',$data['id'])->find();
            if(!$salesman){
                return $this->error('请求错误');
            }
            //数据输入验证
            $validate = new Validate([
                'status|审核'  => 'require|in:1,3',
            ]);
            if (!$validate->check($data)) {
                return $this->error($validate->getError());
            }
            if($data['status']=='3'){
                $validate = new Validate([
                    'auditmsg|驳回理由'  => 'require|length:1,20',
                ]);
                if (!$validate->check($data)) {
                    return $this->error($validate->getError());
                }
                Db::name('salesman_audits')->insertGetId(['salesman_id'=>$data['id'],'auditmsg'=>$data['auditmsg'],'audit_at'=>$now]);
            }
            //数据处理
            $update=array();
            $update['id']=$data['id'];
            $update['status']=$data['status'];
            $update['updated_at']=$now;
            //数据更新
            $rt=Db::name("salesmen")->update($update);
            //跳转
            if($rt!==false){
                //正式发短信
                if($salesman['mobile']!=''){
                    import('aliyun_sms.SmsDemo');
                    if($data['status']=='1'){
                        $response = \SmsDemo::sendSms(
                                "五金商城", // 短信签名
                                "SMS_125022257", // 短信模板编号
                                $salesman['mobile'], // 短信接收者
                                Array(),
                                "001"   // 流水号,选填
                        );
                    }else{
                        $response = \SmsDemo::sendSms(
                                "五金商城", // 短信签名
                                "SMS_125027328", // 短信模板编号
                                $salesman['mobile'], // 短信接收者
                                Array(
                                    "auditmsg"=>trim(trim($data['auditmsg'],'。'),'.'),
                                ),
                                "001"   // 流水号,选填
                        );
                    }    
                }
                return $this->success('审核成功',url('index',['status'=>$data['status']=='3'?'3':'-1']));
            } else {
                return $this->error('审核失败');
            }
        }
        // 接收id
        if ($id>0) {
            // 查处数据
            $salesman=Db::name("salesmen")->where('status','2')->where('id',$id)->find();
            if(!$salesman){
                return $this->error('请求错误');
            }
            $province=db('regions')->where('id',$salesman['province_id'])->value('name');
            $city=db('regions')->where('id',$salesman['city_id'])->value('name');
            $area=db('regions')->where('id',$salesman['area_id'])->value('name');
            $region=$province.' '.$city.' '.$area;
            switch ($salesman['level']) {
                case '1':
                    $level='大区代理';
                    break;
                case '2':
                    $level='县级代理';
                    break;
                default:
                    $level='';
                    break;
            }
            // 使用ZBuilder快速创建表单
            return ZBuilder::make('form')
                ->setPageTitle('代理商审核') // 设置页面标题
                ->setPageTips('请认真审核相关信息') // 设置页面提示信息
                ->setBtnTitle('submit', '确定') //修改默认按钮标题
                //->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
                ->addStatic('truename', '姓名','',$salesman['truename'])
                ->addStatic('mobile', '手机号码','',$salesman['mobile'])
                ->addStatic('idcard_pic', '照片','应为手持身份证照片，否则无效',staticText($salesman['idcard_pic'],'pic'))
                ->addStatic('idcard', '身份证号码','',$salesman['idcard'])
                ->addStatic('level', '代理类型','',$level)
                ->addStatic('region', '负责地区','',$region)
                ->addRadio('status', '审核', '必选', ['1' => '通过', '3' => '驳回'])
                ->addText('auditmsg', '驳回理由','必填，限制在20字以内')
                ->addHidden('id',$salesman['id'])
                ->setTrigger('status', '3', 'auditmsg',false)
                //->isAjax(false) //默认为ajax的post提交
                ->fetch();
        }
    }
	public function edit($id=''){
        //判断是否为post请求
        if (Request::instance()->isPost()) {
            $now=time();
            //获取请求的post数据
            $data=input('post.');
            // 查处数据
            $salesman=Db::name("salesmen")->where('status','in','0,1')->where('id',$data['id'])->find();
            if(!$salesman){
                return $this->error('请求错误');
            }
            //数据输入验证
            $validate = new Validate([
                'level|代理类型'  => 'require|in:1,2',
                'status|代理状态'  => 'require|in:0,1',
            ]);
            if (!$validate->check($data)) {
                return $this->error($validate->getError());
            }
            $update=array();
            if($data['level']=='1'){
                $validate = new Validate([
                    'region1|负责地区'  => 'require',
                ]);
                if (!$validate->check($data)) {
                    return $this->error($validate->getError());
                }
                $update['area_id']='0';
                $update['city_id']=$data['region1'];
                $update['province_id']=db('regions')->where('id',$data['region1'])->value('parent_id');
            }
            if($data['level']=='2'){
                $validate = new Validate([
                    'region2|负责地区'  => 'require',
                ]);
                if (!$validate->check($data)) {
                    return $this->error($validate->getError());
                }
                $update['area_id']=$data['region2'];
                $update['city_id']=db('regions')->where('id',$data['region2'])->value('parent_id');
                $update['province_id']=db('regions')->where('id',$update['city_id'])->value('parent_id');
            }
            if($data['status']=='0'){
                $validate = new Validate([
                    'disablemsg|禁用理由'  => 'require|length:1,20',
                ]);
                if (!$validate->check($data)) {
                    return $this->error($validate->getError());
                }
                Db::name('salesman_disables')->insertGetId(['salesman_id'=>$data['id'],'disablemsg'=>$data['disablemsg'],'audit_at'=>$now]);
            }
            //数据处理
            $update['id']=$data['id'];
            $update['status']=$data['status'];
            $update['level']=$data['level'];
            $update['updated_at']=$now;
            //数据更新
            $rt=Db::name("salesmen")->update($update);
            //跳转
            if($rt!==false){
                return $this->success('修改成功',url('index',['status'=>$data['status']=='3'?'3':'-1']));
            } else {
                return $this->error('修改失败');
            }
        }
        // 接收id
        if ($id>0) {
            // 查处数据
            $salesman=Db::name("salesmen")->where('status','in','0,1')->where('id',$id)->find();
            if(!$salesman){
                return $this->error('请求错误');
            }
            $disable=db('salesman_disables')->where('salesman_id',$id)->order('disable_at desc')->find();
            if($disable){
                $disablemsg=$disable['disablemsg'];
            }else{
                $disablemsg='';
            }
            if($salesman['level']=='1' && $salesman['area_id']=='0') {
                $salesman['area_id']=db('regions')->where('parent_id',$salesman['city_id'])->value('id');
                $user_num=db('users')->where('city_id',$salesman['city_id'])->count('id');
                $order_num_1=db('orders a')->join('users b','a.user_id=b.id','LEFT')->where('b.city_id',$salesman['city_id'])->where('a.state','in','1,3,4,5,8')->where('a.refund_state','in','-1,0,1,2,3,6,9,10,11')->count('a.id');
                $order_num_2=db('collage_orders a')->join('users b','a.user_id=b.id','LEFT')->where('b.city_id',$salesman['city_id'])->where('a.state','in','1,4')->count('a.id');
                $order_num=$order_num_1+$order_num_2;
                $amount_num=db('trades a')->join('users b','a.user_id=b.id','LEFT')->where('b.city_id',$salesman['city_id'])->where('a.state','1')->count('a.id');
            }else{
                $user_num=db('users')->where('area_id',$salesman['area_id'])->count('id');
                $order_num_1=db('orders a')->join('users b','a.user_id=b.id','LEFT')->where('b.area_id',$salesman['area_id'])->where('a.state','in','1,3,4,5,8')->where('a.refund_state','in','-1,0,1,2,3,6,9,10,11')->count('a.id');
                $order_num_2=db('collage_orders a')->join('users b','a.user_id=b.id','LEFT')->where('b.area_id',$salesman['area_id'])->where('a.state','in','1,4')->count('a.id');
                $order_num=$order_num_1+$order_num_2;
                $amount_num=db('trades a')->join('users b','a.user_id=b.id','LEFT')->where('b.area_id',$salesman['area_id'])->where('a.state','1')->count('a.id');
            }

            // 使用ZBuilder快速创建表单
            return ZBuilder::make('form')
                ->setPageTitle('修改代理商') // 设置页面标题
                ->setPageTips('请认真修改相关信息') // 设置页面提示信息
                ->setBtnTitle('submit', '确定') //修改默认按钮标题
                ->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
                ->addStatic('truename', '姓名','',$salesman['truename'])
                ->addStatic('mobile', '手机号码','',$salesman['mobile'])
                ->addStatic('idcard_pic', '照片','应为手持身份证照片，否则无效',staticText($salesman['idcard_pic'],'pic'))
                ->addStatic('idcard', '身份证号码','',$salesman['idcard'])
                ->addStatic('survey_num', '调研信息数量','',db('surveys')->where('salesman_id',$salesman['id'])->count('id'))
                ->addStatic('user_num', '地区用户数量','',$user_num)
                ->addStatic('order_num', '地区订单数量','',$order_num)
                ->addStatic('amount_num', '地区交易额','',number_format($amount_num/100,2,'.','').'元')
                ->addStatic('complaint_num', '被投诉次数','',db('feedbacks')->where(['type_id'=>'3','salesman_id'=>$salesman['id']])->count('id'))
                ->addRadio('level', '代理类型', '必选', ['1' => '大区代理','2' => '县级代理'],$salesman['level'])
                ->addLinkages('region1', '负责地区', '必选', 'regions', 3, $salesman['city_id'], 'id,name,parent_id')
                ->addLinkages('region2', '负责地区', '必选', 'regions', 4, $salesman['area_id'], 'id,name,parent_id')
                ->addRadio('status', '代理状态', '必选', ['1' => '正常','0' => '禁用'],$salesman['status'])
                ->addText('disablemsg', '禁用理由','必填，限制在20字以内',$disablemsg)
                ->addHidden('id',$salesman['id'])
                ->setTrigger('level', '1', 'region1',false)
                ->setTrigger('level', '2', 'region2',false)
                ->setTrigger('status', '0', 'disablemsg',false)
                //->isAjax(false) //默认为ajax的post提交
                ->fetch();
        }
    }
}