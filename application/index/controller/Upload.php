<?php


namespace app\index\controller;

use app\admin\model\Attachment as AttachmentModel;
use think\Image;
use think\File;
use think\Db;
use think\Request;
/**
 * 附件控制器
 * @package app\admin\controller
 */
class Upload extends Home
{

    /**
     * 获取上传附件请求地址
     */
    public function getUrl()
    {
        header("Access-Control-Allow-Origin:*" );
        header("Access-Control-Allow-Methods:POST,GET,OPTIONS");
        header("Access-Control-Allow-Headers:x-file-name,x-requested-with,content-type");
        header("Access-control-allow-credentials:true");
        if (Request::instance()->isOptions()){
            return $this->response([],200,'');
        }
        $now=time();
        $token=md5($now.config("mall.upload_code"));
        $url=config('mall.host_url')."/public/index.php/index/upload/index/type/image/time/".$now."/token/".$token;
        return $this->response(['url'=>$url],200,config('mall.upload_minute').'分钟内有效');
    }
    /**
     * 上传附件
     */
    public function index($type = '', $time='' , $token='' , $module = 'index')
    {
        header("Access-Control-Allow-Origin:*" );
        header("Access-Control-Allow-Methods:POST,GET,OPTIONS");
        header("Access-Control-Allow-Headers:x-file-name,x-requested-with,content-type");
        header("Access-control-allow-credentials:true");
        if (Request::instance()->isOptions()){
            return $this->response([],200,'');
        }
        $is_allow_upload=db('config')->where('name','is_allow_upload')->value('value');
        if(!$is_allow_upload){
            return $this->response([],250,'服务器正在清理垃圾文件，请稍后上传');
        }
        // 临时取消执行时间限制
        set_time_limit(0);
        if ($type === '' || $time==='' || $token==='')  return $this->response([],201,'请传入必要‘type’‘time’‘token’参数');
        $cha=time()-$time;
        $upload_time=config('mall.upload_minute')*60;
        if ($cha>$upload_time || $cha<-$upload_time)  return $this->response([],202,'time参数无效，请传入当前时间戳重新上传');
        if (md5($time.config('mall.upload_code'))!=strtolower($token))  return $this->response([],202,'token凭证错误');
        if (!in_array($type, ['image','flash','file','video','voice'])) return $this->response([],203,'上传类型指定错误');
        $dir=$type.'s';
        return $this->saveFile($dir, $module);
    }

    /**
     * 保存附件
     * @param string $dir 附件存放的目录
     * @param string $module 来自哪个模块
     * @return string|\think\response\Json
     */
    private function saveFile($dir = '', $module = '')
    {
        // 附件大小限制
        $size_limit = $dir == 'images' ? config('upload_image_size') : config('upload_file_size');
        $size_limit = $size_limit * 1024;
        // 附件类型限制
        $ext_limit = $dir == 'images' ? config('upload_image_ext') : config('upload_file_ext');
        $ext_limit = $ext_limit != '' ? parse_attr($ext_limit) : '';

        // 获取附件数据
        $callback = '';

        $file_input_name = 'file';

        $file = $this->request->file($file_input_name);
        /*$info=$file->getInfo();
        $ssss['id']='12';
        $ssss['path']='uploads/images/20180130/783b9b3376e436cada3278605e75d2a6.jpg';
        return $this->response($ssss,200,'服务器已得到文件');*/
        // 判断附件是否已存在
        $w=input('post.thumb_max_width','0');
        $h=input('post.thumb_max_height','0');
        if($w!=='0' && $h!=='0'){
            if (preg_match("/^[1-9]\d*$/", $w) && preg_match("/^[1-9]\d*$/", $h)) {

            }else{
                return $this->response([],208,'缩略尺寸必须都为正整数');
            }
        }else{
            if (!(($dir == 'images' && config('upload_thumb_water') == 1 && config('upload_thumb_water_pic') > 0) || ($dir == 'images' && config('upload_image_thumb')) != '')){
                if ($file_exists = AttachmentModel::get(['md5' => $file->hash('md5')])) {
                    if ($file_exists['driver'] == 'local') {
                        $file_path = $file_exists['path'];
                    } else {
                        $file_path = $file_exists['path'];
                    }
                    $thumb_value=db('admin_attachment')->where('id',$file_exists['id'])->value('thumb');
                    if($thumb_value!==''){
                        return $this->response(['id'=>$file_exists['id'],'path'=>$thumb_value],200,'上传成功');
                    }else{
                        return $this->response(['id'=>$file_exists['id'],'path'=>$file_path],200,'上传成功');
                    }
                }
            }
            
        }
        

        // 判断附件大小是否超过限制
        if ($size_limit > 0 && ($file->getInfo('size') > $size_limit)) {

            return $this->response([],204,'文件大小超过限制');
        
        }

        // 判断附件格式是否符合
        $file_name = $file->getInfo('name');
        $file_ext  = strtolower(substr($file_name, strrpos($file_name, '.')+1));
        $error_msg = '';
        if ($ext_limit == '') {
            $error_msg = '获取文件信息失败！';
        }
        if ($file->getMime() == 'text/x-php' || $file->getMime() == 'text/html') {
            $error_msg = '禁止上传非法文件！';
        }
        if (preg_grep("/php/i", $ext_limit)) {
            $error_msg = '禁止上传非法文件！';
        }
        if (!preg_grep("/$file_ext/i", $ext_limit)) {
            $error_msg = '附件类型不正确！';
        }

        if ($error_msg != '') {
            return $this->response([],205,$error_msg);
        }


        // 移动到框架应用根目录/uploads/ 目录下
        $info = $file->move(config('upload_path') . DS . $dir);

        if($info){
            // 水印功能
            if ($dir == 'images' && config('upload_thumb_water') == 1 && config('upload_thumb_water_pic') > 0) {
                $this->create_water($info->getRealPath());
            }

            // 缩略图路径
            $thumb_path_name = '';
            // 生成缩略图
            if($w!=='0' && $h!=='0'){
                $thumb_path_name = $this->create_thumb($info, $info->getPathInfo()->getfileName(), $info->getFilename(),$w,$h);
            }else{
                if ($dir == 'images' && config('upload_image_thumb') != '') {
                    $thumb_path_name = $this->create_thumb($info, $info->getPathInfo()->getfileName(), $info->getFilename());
                }
            }
                
            // 获取附件信息
            $file_info = [
                'uid'    => 0,
                'name'   => $file->getInfo('name'),
                'mime'   => $file->getInfo('type'),
                'path'   => 'uploads/' . $dir . '/' . str_replace('\\', '/', $info->getSaveName()),
                'ext'    => $info->getExtension(),
                'size'   => $info->getSize(),
                'md5'    => $info->hash('md5'),
                'sha1'   => $info->hash('sha1'),
                'thumb'  => $thumb_path_name,
                'module' => $module
            ];

            // 写入数据库
            if ($file_add = AttachmentModel::create($file_info)) {
                $file_path = $file_info['path'];
                if($thumb_path_name!==''){
                    return $this->response(['id'=>$file_add['id'],'path'=>$thumb_path_name],200,'上传成功');
                }else{
                    return $this->response(['id'=>$file_add['id'],'path'=>$file_path],200,'上传成功');
                }
            } else {
                return $this->response([],206,'上传失败，写入数据库失败');
            }
        }else{
            return $this->response([],207,$file->getError());
        }
    }

    /**
     * 创建缩略图
     * @param string $file 目标文件，可以是文件对象或文件路径
     * @param string $dir 保存目录，即目标文件所在的目录名
     * @param string $save_name 缩略图名
     * @return string 缩略图路径
     */
    private function create_thumb($file = '', $dir = '', $save_name = '',$w='0',$h='0')
    {
        if($w!=='0' && $h!=='0'){
            $arr=[$w,$h];
        }else{
            $arr=explode(',', config('upload_image_thumb'));
        }
        
        // 获取要生成的缩略图最大宽度和高度
        list($thumb_max_width, $thumb_max_height) = $arr;
        // 读取图片
        $image = Image::open($file);
        // 生成缩略图
        $image->thumb($thumb_max_width, $thumb_max_height, config('upload_image_thumb_type'));
        // 保存缩略图
        $thumb_path = config('upload_path') . DS . 'images/' . $dir . '/thumb/';
        if (!is_dir($thumb_path)) {
            mkdir($thumb_path, 0777, true);
        }
        $thumb_path_name = $thumb_path. $save_name;
        $image->save($thumb_path_name);
        $thumb_path_name = 'uploads/images/' . $dir . '/thumb/' . $save_name;
        return $thumb_path_name;
    }

    /**
     * 添加水印
     * @param string $file 要添加水印的文件路径
     */
    private function create_water($file = '')
    {
        $path = model('admin/attachment')->getFilePath(config('upload_thumb_water_pic'), 1);
        $thumb_water_pic = realpath(ROOT_PATH . 'public/' . $path);

        // 读取图片
        $image = Image::open($file);
        // 添加水印
        $image->water($thumb_water_pic, config('upload_thumb_water_position'), config('upload_thumb_water_alpha'));
        // 保存水印图片，覆盖原图
        $image->save($file);
    }

    function response($data,$errcode,$msg)
    {
        $res = [
            'data'=>$data,
            'errcode'=>$errcode,
            'msg' =>$msg
        ];
        exit(json_encode($res));
    }

}