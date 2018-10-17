<?php
namespace app\mall\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder;


class Supplierwithdraw extends Admin
{
	public function index($status='0'){  
        $list_tab = [
            '0' => ['title' => '提现列表', 'url' => url('index', ['status' => '0'])],
            '1' => ['title' => '提现费率设置', 'url' => url('index', ['status' => '1'])],
        ];

        switch ($status) {
            case '0':
                $order = $this->getOrder();
                if($order===''){
                    $order='id desc';
                }
                $map = $this->getMap();
                $data_list = Db::name('supplier_capital_extracts')->where($map)->order($order)->paginate();
                $page = $data_list->render();
                $suppliers=db('suppliers')->field('id,name')->select();
                $select_suppliers=[];
                foreach ($suppliers as $key => $value) {
                    $select_suppliers[$value['id']]=$value['name'];
                }
                return ZBuilder::make('table')
                    ->setPageTitle('') // 设置页面标题
                    ->setPageTips('供应商提现审核通过时即扣除相应手续费') // 设置页面提示信息
                    ->setTabNav($list_tab,  $status)//分组
                    ->hideCheckbox() //隐藏第一列多选框
                    ->setTableName('supplier_capital_extracts') // 指定数据表名
                    ->addOrder('id,created_at') // 添加排序
                    ->addTopSelect('supplier_id', '全部供应商', $select_suppliers) //添加顶部下拉筛选
                    ->addTopSelect('progress', '全部状态', ['0'=>'待审核','1'=>'通过','2'=>'驳回']) //添加顶部下拉筛选
                    ->setSearch(['id' => 'ID','bank_account'=>'银行账号'], '', '', '搜索') // 设置搜索参数
                    ->addTimeFilter('created_at') // 添加时间段筛选
                    ->addColumns([
                            ['id', 'ID'], 
                            ['supplier_id', '供应商','callback',function($value){
                                return db('suppliers')->where('id',$value)->value('name');
                            }],
                            ['amount', '提现金额','callback',function($value){
                                return number_format($value/100,2,'.','').'元';
                            }],
                            ['balance', '账户余额','callback',function($value,$data){
                                $balance=db('supplier_capitals')->where('supplier_id',$data['supplier_id'])->value('money_available');
                                return number_format($balance/100,2,'.','').'元';
                            },'__data__'],
                            ['info', '提现银行账户信息','callback',function($value,$data){
                                return "开户行名称：{$data['bank_found']}<br>持卡人姓名：{$data['bank_user']}<br>银行卡账号：{$data['bank_account']}</div>";
                            },'__data__'],
                            ['created_at', '申请时间','datetime', '未知','Y-m-d H:i'], 
                            ['progress', '审核状态','status','',['0'=>'待审核','1'=>'通过','2'=>'驳回:danger']],
                            ['run', '操作','callback',function($value,$data){
                                if($data['progress']=='0'){
                                    $json=json_encode(['area'=>['800px','450px'],'title'=>'开始审核']);
                                    return '<div class="btn-group"><a title="点击审核" icon="fa fa-smile-o" class="btn btn-xs btn-default ajax-get pop" href="'.url('audit',['id'=>$data['id'],'',false]).'?_pop=1" data-layer=\''.$json.'\' _tag="custom">点击审核</a></div>';
                                }elseif($data['progress']=='2'){
                                    $json=json_encode(['area'=>['800px','450px'],'title'=>'驳回理由']);
                                    return '<div class="btn-group"><a title="点击查看驳回理由" icon="fa fa-smile-o" class="btn btn-xs btn-default ajax-get pop" href="'.url('look',['id'=>$data['id'],'',false]).'?_pop=1" data-layer=\''.$json.'\' _tag="custom">点击查看驳回理由</a></div>';
                                }else{
                                    return '';
                                }
                            },'__data__'], 
                        ]) //添加多列数据
                    ->setRowList($data_list) // 设置表格数据
                    ->setPages($page) // 设置分页数据
                    ->fetch();
                break;
            case '1':
                //判断是否为post请求
                if (Request::instance()->isPost()) {
                    //获取请求的post数据
                    $data=input('post.');
                    //数据输入验证
                    $supplier_withdraw_rate=Db::name("config")->where('name','supplier_withdraw_rate')->find();
                    $validate = new Validate([
                        'supplier_withdraw_rate|'.$supplier_withdraw_rate['title']=> [
                            'require',
                            'egt'=>'0',
                            'lt'=>'100',
                            'regex'=>'/^((\d|[1-9]\d)(\.\d)?)$/',
                        ],
                    ]);
                    if (!$validate->check($data)) {
                        return $this->error($validate->getError());
                    }
                    //数据处理
                    $update=array();
                    $update['value']=$data['supplier_withdraw_rate'];
                    $update['updated_at']=time();
                    //数据更新
                    $rt=Db::name("config")->where('name',$supplier_withdraw_rate['name'])->update($update);
                    //跳转
                    if($rt!==false){
                        return $this->success('提现费率设置成功',url('index',['status'=>'1']));
                    } else {
                        return $this->error('提现费率设置失败');
                    }
                }
                // 查处数据
                $supplier_withdraw_rate=Db::name("config")->where('name','supplier_withdraw_rate')->find();
                if(!$supplier_withdraw_rate){
                    return $this->error('请求错误');
                }
                // 使用ZBuilder快速创建表单
                return ZBuilder::make('form')
                    ->setPageTitle('') // 设置页面标题
                    ->setPageTips('供应商提现审核通过时即扣除相应手续费') // 设置页面提示信息
                    ->setTabNav($list_tab,  $status)//分组
                    ->hideBtn([ 'back'])//隐藏按钮
                    ->setBtnTitle('submit', '确定') //修改默认按钮标题
                    ->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
                    ->addText('supplier_withdraw_rate',$supplier_withdraw_rate['title'],$supplier_withdraw_rate['tips'],$supplier_withdraw_rate['value'], ['','%'])
                    //->isAjax(false) //默认为ajax的post提交
                    ->fetch();
                break;
            default:
                break;
        } 
	}
    public function look($id=''){
        $supplier_capital_extract=Db::name("supplier_capital_extracts")->where('progress','2')->where('id',$id)->find();
        if(!$supplier_capital_extract){
            return $this->error('请求错误');
        }
        // 使用ZBuilder快速创建表单
        return '<div style="padding:20px 2%">'.$supplier_capital_extract['result'].'</div>';
    }
    public function audit($id=''){
        //判断是否为post请求
        if (Request::instance()->isPost()) {
            $now=time();
            //获取请求的post数据
            $data=input('post.');
            // 查处数据
            $supplier_capital_extract=Db::name("supplier_capital_extracts")->where('progress','0')->where('id',$data['id'])->find();
            if(!$supplier_capital_extract){
                return $this->error('请求错误');
            }
            //数据输入验证
            $validate = new Validate([
                'progress|审核'=> 'require|in:1,2',
            ]);
            if (!$validate->check($data)) {
                return $this->error($validate->getError());
            }
            $update=array();
            if($data['progress']=='2'){
                $validate = new Validate([
                    'result|驳回理由'=> 'require|length:1,20',
                ]);
                if (!$validate->check($data)) {
                    return $this->error($validate->getError());
                }
                $update['result']=$data['result'];
            }
            //数据处理
            $update['id']=$data['id'];
            $update['progress']=$data['progress'];
            $update['admin_user_id']=UID;
            $update['updated_at']=$now;
            //数据更新
            $rt=Db::name("supplier_capital_extracts")->update($update);
            //跳转
            if($rt!==false){
                $supplier_capital_extract=Db::name("supplier_capital_extracts")->where('id',$data['id'])->find();
                if($data['progress']=='1'){
                    //扣除手续费
                    $rate=db('config')->where('name','supplier_withdraw_rate')->value('value');
                    $sx_m=ceil($rate*$supplier_capital_extract['amount']/100);//手续费
                    $sj_m=$supplier_capital_extract['amount']-$sx_m;//实际提现费用
                    db('supplier_capitals')->where('supplier_id',$supplier_capital_extract['supplier_id'])->setDec('money_lock',$supplier_capital_extract['amount']);
                    db('supplier_capitals')->where('supplier_id',$supplier_capital_extract['supplier_id'])->setInc('money_obtained',$sj_m);
                    db('supplier_capitals')->where('supplier_id',$supplier_capital_extract['supplier_id'])->setInc('money_rate',$sx_m);
                    db('supplier_capitals')->where('supplier_id',$supplier_capital_extract['supplier_id'])->update(['updated_at'=>$now]);
                    db('supplier_capital_extracts')->where('id',$supplier_capital_extract['id'])->update(['money_rate'=>$sx_m]);
                    db('supplier_capital_flows')->insertGetId(['table_id'=>$data['id'],'supplier_id'=>$supplier_capital_extract['supplier_id'],'amount'=>-$sj_m,'reason'=>'提现到账金额','created_at'=>$now,'updated_at'=>$now]);
                    db('supplier_capital_flows')->insertGetId(['table_id'=>$data['id'],'supplier_id'=>$supplier_capital_extract['supplier_id'],'amount'=>-$sx_m,'reason'=>'提现手续费','created_at'=>$now,'updated_at'=>$now]);
                    //手续费计入平台流水
                    $insert=[
                        'sn'=>get_platform_water_sn(),
                        'amount'=>$sx_m,
                        'channel'=>'线下',
                        'source'=>'供应商提现手续费',
                        'relate_order'=>'提现记录ID：'.$supplier_capital_extract['id'],
                        'created_at'=>$now,
                        'updated_at'=>$now
                    ];
                    db('platform_waters')->insertGetId($insert);
                    //系统消息
                    db("letters")->insertGetId(['order_id'=>$data['id'],'type'=>'3','supplier_id'=>$supplier_capital_extract['supplier_id'],'title'=>'到帐提示','content'=>'您的提现已经到帐！','created_at'=>$now,'updated_at'=>$now]);
                    //发短信
                    $supplier=db('suppliers')->find($supplier_capital_extract['supplier_id']);
                    //$content="尊敬的供应商（{$supplier['name']}），你的提现{$supplier_capital_extract['amount']}元申请已通过，其中手续费比例为{$rate}%，已扣除手续费{$sx_m}元，实际提现{$sj_m}元，已转账至你的提现银行卡{$supplier_capital_extract['bank_account']}，敬请查收。";//短信内容
                    //正式发短信
                    if($supplier['mobile']!=''){
                        import('aliyun_sms.SmsDemo');
                        $response = \SmsDemo::sendSms(
                                "五金商城", // 短信签名
                                "SMS_125026898", // 短信模板编号
                                $supplier['mobile'], // 短信接收者
                                Array(  // 短信模板中字段的值
                                        "amount"=>number_format($supplier_capital_extract['amount']/100,2,'.',''),
                                        "poundage"=>number_format($sx_m/100,2,'.',''),
                                ),
                                "001"   // 流水号,选填
                        );
                    }
                }else{
                    //返还金额
                    db('supplier_capitals')->where('supplier_id',$supplier_capital_extract['supplier_id'])->setDec('money_lock',$supplier_capital_extract['amount']);
                    db('supplier_capitals')->where('supplier_id',$supplier_capital_extract['supplier_id'])->setInc('money_available',$supplier_capital_extract['amount']);
                    db('supplier_capitals')->where('supplier_id',$supplier_capital_extract['supplier_id'])->update(['updated_at'=>$now]);
                    //发短信
                    $supplier=db('suppliers')->find($supplier_capital_extract['supplier_id']);
                    //$content="尊敬的供应商（{$supplier['name']}），你的提现%s元申请已被驳回，驳回理由：{$data['result']}。";
                    //$content=trim($content,'。');//短信内容
                    //正式发短信
                    if($supplier['mobile']!=''){
                        import('aliyun_sms.SmsDemo');
                        $response = \SmsDemo::sendSms(
                                "五金商城", // 短信签名
                                "SMS_125021871", // 短信模板编号
                                $supplier['mobile'], // 短信接收者
                                Array(  // 短信模板中字段的值
                                        "amount"=>number_format($supplier_capital_extract['amount']/100,2,'.',''),
                                        "auditmsg"=>trim(trim($data['result'],'。'),'.'),
                                ),
                                "001"   // 流水号,选填
                        );
                    }
                }
                return $this->success('审核成功',null,['_parent_reload' => 1]);
            } else {
                return $this->error('审核失败');
            }
        }
        // 接收id
        if ($id>0) {
            // 查处数据
            $supplier_capital_extract=Db::name("supplier_capital_extracts")->where('progress','0')->where('id',$id)->find();
            if(!$supplier_capital_extract){
                return $this->error('请求错误');
            }
            // 使用ZBuilder快速创建表单
            return ZBuilder::make('form')
                ->setPageTitle('') // 设置页面标题
                ->setPageTips('') // 设置页面提示信息
                ->hideBtn([ 'back'])//隐藏按钮
                ->setBtnTitle('submit', '确定') //修改默认按钮标题
                ->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
                ->addRadio('progress', '审核', '必选', ['1' => '通过','2' => '驳回'])
                ->addText('result', '驳回理由','必填，限制在20字以内')
                ->addHidden('id',$supplier_capital_extract['id'])
                ->setTrigger('progress', '2', 'result',false)
                //->isAjax(false) //默认为ajax的post提交
                ->fetch();
        }
    }
}