<?php
namespace app\mall\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder;


class Productclass extends Admin
{

    public function index()
    {
        $cates=Db::name('product_types')->order('sort asc')->select();
        $this->assign('cates', $this->get_tree_html($cates));
        $this->assign('product_class_level', config('mall.product_class_level'));
        $this->assign('page_title', db('admin_menu')->where('id','216')->value('title'));
        // 渲染模板输出
        return $this->fetch();
    }
    private function get_tree_html($arr,$pid='0'){
        global $tree;
        foreach($arr as $key=>$value) {
            if($value['parent_id'] == $pid) {
                $tree .= '<div class="dj_box" style="margin-left: '.(($value['level']-1)*2).'%;">'.$value['name'].'&nbsp;&nbsp;&nbsp;<a href="'.url('edit',['id'=>$value['id']]).'">编辑</a>&nbsp;<a href="'.url('add',['parent_id'=>$value['id']]).'">新增子分类</a></div>';
                $this->get_tree_html($arr , $value['id'] );
            }
        }
        return $tree;
    }
    public function add($parent_id='0'){
        //判断是否为post请求
        if (Request::instance()->isPost()) {
            $now=time();
            //获取请求的post数据
            $data=input('post.');
            //数据输入验证
            $validate = new Validate([
                'parent_id|父级分类'  => 'require',
                'name|名称'=> 'require|length:1,10',
                'home|推荐到首页'  => 'require|in:0,1',
                'nav|导航显示'  => 'require|in:0,1',
                'sort|排序' => 'require|regex:^[1-9]\d{0,9}$',
            ]);
            if (!$validate->check($data)) {
                return $this->error($validate->getError());
            }
            if($data['parent_id']>0){
                $level=db('product_types')->where('id',$data['parent_id'])->value('level');
                if($level>0){
                    $level=$level+1;
                }else{
                    return $this->error('父级分类不存在');
                }
            }else{
                $level='1';
            }
            //数据处理
            $insert=array();
            $insert['name']=$data['name'];
            $insert['parent_id']=$data['parent_id'];
            $insert['admin_attachment_id']=$data['admin_attachment_id'];
            $insert['home']=$data['home'];
            $insert['nav']=$data['nav'];
            $insert['alias']=$data['alias'];
            $insert['link']=$data['link'];
            $insert['sort']=$data['sort'];
            $insert['level']=$level;
            $insert['created_at']=$now;
            $insert['updated_at']=$now;
            //数据更新
            $insert_id=Db::name("product_types")->insertGetId($insert);
            //跳转
            if($insert_id>0){
                return $this->success('新增成功',url('index'));
            } else {
                return $this->error('新增失败');
            }
        }
        
        $cates=Db::name('product_types')->field('id,parent_id,level,name')->order('sort asc')->select();
        $cates=get_tree_ids($cates);
        $cate_selects=['0'=>'顶级分类'];
        foreach ($cates as $key => $value) {
            $cate_selects[$key]=$value;
        }
        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('新增商品分类') // 设置页面标题
            ->setPageTips('请认真编辑相关信息') // 设置页面提示信息
            ->setBtnTitle('submit', '确定') //修改默认按钮标题
            ->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
            ->addSelect('parent_id', '父级分类','必选',$cate_selects,$parent_id)
            ->addText('name', '名称','必填，限制在10个字以内')
            ->addImage('admin_attachment_id', '图标','')
            ->addText('alias', '描述','')
            ->addText('link', '跳转地址','请以http://或https://开头')
            ->addRadio('home', '推荐到首页', '必选', ['0' => '否','1' => '是'],'1')
            ->addRadio('nav', '作为导航', '必选', ['0' => '否','1' => '是'],'1')
            ->addText('sort', '排序','必填，请输入一个大于0的整数，前台以此升序取出数据','100')
            //->isAjax(false) //默认为ajax的post提交
            ->fetch();
    }
    public function edit($id=''){
        //判断是否为post请求
        if (Request::instance()->isPost()) {
            $now=time();
            //获取请求的post数据
            $data=input('post.');
            //数据输入验证
            $validate = new Validate([
                'name|名称'=> 'require|length:1,10',
                'home|推荐到首页'  => 'require|in:0,1',
                'nav|导航显示'  => 'require|in:0,1',
                'sort|排序' => 'require|regex:^[1-9]\d{0,9}$',
            ]);
            if (!$validate->check($data)) {
                return $this->error($validate->getError());
            }
            $product_type=Db::name("product_types")->where('id',$data['id'])->find();
            if($product_type['parent_id']>0){
                $level=db('product_types')->where('id',$data['parent_id'])->value('level');
                if($level>0){
                    $level=$level+1;
                }else{
                    return $this->error('父级分类不存在');
                }
            }else{
                $level='1';
            }
            //数据处理
            $update=array();
            $update['id']=$data['id'];
            $update['name']=$data['name'];
            $update['admin_attachment_id']=$data['admin_attachment_id'];
            $update['home']=$data['home'];
            $update['nav']=$data['nav'];
            $update['alias']=$data['alias'];
            $update['link']=$data['link'];
            $update['sort']=$data['sort'];
            $update['level']=$level;
            $update['updated_at']=$now;
            //数据更新
            $rt=Db::name("product_types")->update($update);
            //跳转
            if($rt!==false){
                return $this->success('编辑成功',url('index'));
            } else {
                return $this->error('编辑失败');
            }
        }
        // 接收id
        if ($id>0) {
            // 查处数据
            $product_type=Db::name("product_types")->where('id',$id)->find();
            if(!$product_type){
                return $this->error('请求错误');
            }
            $cates=Db::name('product_types')->field('id,parent_id,level,name')->order('sort asc')->select();
            $cates=get_tree_ids($cates);
            $cate_selects=['0'=>'顶级分类'];
            foreach ($cates as $key => $value) {
                $cate_selects[$key]=$value;
            }
            // 使用ZBuilder快速创建表单
            return ZBuilder::make('form')
                ->setPageTitle('编辑商品分类') // 设置页面标题
                ->setPageTips('请认真编辑相关信息') // 设置页面提示信息
                ->setBtnTitle('submit', '确定') //修改默认按钮标题
                ->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
                ->addText('name', '名称','必填，限制在10个字以内',$product_type['name'])
                ->addImage('admin_attachment_id', '图标','',$product_type['admin_attachment_id'])
                ->addText('alias', '描述','',$product_type['alias'])
                ->addText('link', '跳转地址','请以http://或https://开头',$product_type['link'])
                ->addRadio('home', '推荐到首页', '必选', ['0' => '否','1' => '是'],$product_type['home'])
                ->addRadio('nav', '作为导航', '必选', ['0' => '否','1' => '是'],$product_type['nav'])
                ->addText('sort', '排序','必填，请输入一个大于0的整数，前台以此升序取出数据',$product_type['sort'])
                ->addHidden('id',$product_type['id'])
                //->isAjax(false) //默认为ajax的post提交
                ->fetch();
        }
    }
}
