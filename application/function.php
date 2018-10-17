<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

// 为方便系统核心升级，二次开发中需要用到的公共函数请写在这个文件，不要去修改common.php文件


//取数组每个键值
if(!function_exists('array_v')){
    function array_v($key,$arr=array())
    {
        return $arr?(array_key_exists($key,$arr)?$arr[$key]:$arr[0]):'';

    }
}

//静态文本选择器
if(!function_exists('staticText')){
    function staticText($data='',$type='view',$pp='Y-m-d H:i'){
        if($type=='view'){
            return "<div>{$data}</div>";
        }elseif($type=='url'){
            if(!$data)  return no_font('暂无');
            return "<a href='{$data}' target='_bank' title='点击打开'>{$data}</a>";
        }elseif($type=='pic'){
            if(!$data)  return no_font('暂无');
            return "<style>img.dj_img{max-width:300px;max-height:300px;}</style><a href='".get_file_path($data)."' target='_bank' title='点击查看原图'><img src='".get_file_path($data)."' class='dj_img'></a>";
        }elseif ($type=='pics') {
            if(!$data)  return no_font('暂无');
            $html="";
            if(!is_array($data)){
                $data=explode(',',$data);
            }
            foreach ($data as $k => $v) {
                if(!$data)  return no_font('暂无');
                $html.="<style>img.dj_img{max-width:300px;max-height:300px;}</style><a href='".get_file_path($v)."' target='_bank' title='点击查看原图'><img src='".get_file_path($v)."' class='dj_img'></a>";
            }
            return $html;
        }elseif($type=='file'){
            if(!$data)  return no_font('暂无');
            return "<a href='".get_file_path($data)."' title='".get_file_name($data)."'>".get_file_name($data)."</a>";
        }elseif ($type=='files') {
            if(!$data)  return no_font('暂无');
            $html="";
            if(!is_array($data)){
                $data=explode(',',$data);
            }
            foreach ($data as $k => $v) {
                $html.="<li><a href='".get_file_path($v)."' title='".get_file_name($v)."'>".get_file_name($v)."</a></li>";
            }
            return $html;
        }elseif($type=='time'){
            if(!$data)  return no_font('未知');
            return date($pp,$data);
        }elseif($type=='admin_username'){
            if(!$data)  return no_font('未知');
            return admin_username($data);
        }elseif($type=='img'){
            if(!$data)  return no_font('暂无');
            if(!preg_match('/^http/i', $data)){
                $data=config('mall.public_url').$data;
            }
            return "<style>img.dj_img{max-width:300px;max-height:300px;}</style><a href='".$data."' target='_bank' title='点击查看原图'><img src='".$data."' class='dj_img'></a>";
        }elseif ($type=='imgs') {
            if(!$data)  return no_font('暂无');
            $html="";
            if(!is_array($data)){
                $data=explode(',',$data);
            }
            foreach ($data as $k => $v) {
                if(!preg_match('/^http/i', $v)){
                    $v=config('mall.public_url').$v;
                }
                $html.="<style>img.dj_img{max-width:300px;max-height:300px;}</style><a href='".$v."' target='_bank' title='点击查看原图'><img src='".$v."' class='dj_img'></a>";
            }
            return $html;
        }else{
            if(!$data)  return no_font('暂无');
            return $data;
        }
    }
} 

//判断是否有此菜单权限
if(!function_exists('if_menu_auth')){
    function if_menu_auth($menu_id,$admin_id=UID){
        $role=db('admin_user')->where('id',$admin_id)->value('role');
        if($role=='1') return true;
        $menu_auth=db('admin_role')->where('id',$role)->value('menu_auth');
        if(strpos($menu_auth,'"'.$menu_id.'"')===false){
            return false;
        }else{
            return true;
        }
    }
}
//树形数组结构
if(!function_exists('get_arr_tree')){
    function get_arr_tree($items,$pid ="parent_id") {
        $map  = [];
        $tree = [];    
        foreach ($items as &$it){ //数据的ID名生成新的引用索引树
            $map[$it['id']] = &$it;
        }  
        foreach ($items as &$it){
            $parent = &$map[$it[$pid]];
            if($parent) {
                $parent['son'][] = &$it;
            }else{
                $tree[] = &$it;
            }
        }
        return $tree;
    }
}
//普通数组结构
if(!function_exists('get_tree_ids')){
    function get_tree_ids($arr,$pid='0',$pix=0){
        global $tree_ids;
        foreach($arr as $key=>$value) {
            if($value['parent_id'] == $pid) {
                $tree_ids[$value['id']]= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $value['level']-$pix).$value['name'];
                get_tree_ids($arr , $value['id'] );
            }
        }
        return $tree_ids;
    }
}

//生成供应商编号
if(!function_exists('get_supplier_serial_num')){
    function get_supplier_serial_num(){
        $serial_num=mt_rand(0,99999999999);
        $serial_num=str_pad($serial_num,11,"0",STR_PAD_LEFT); 
        $supplier=db('suppliers')->field('id')->where('serial_num',$serial_num)->find();
        if($supplier){
            get_supplier_serial_num(); 
        }else{
            return $serial_num;
        }
    }
}

//平台流水号随机生成
if(!function_exists('get_platform_water_sn')){
    function get_platform_water_sn(){
        $sn=mt_rand(0,9999999999);
        $sn=str_pad($sn,10,"0",STR_PAD_LEFT);
        $sn=date('YmdHis').$sn; 
        $platform_warte=db('platform_waters')->field('id')->where('sn',$sn)->find();
        if($platform_warte){
            get_platform_water_sn(); 
        }else{
            return $sn;
        }
    }
}

/**
 * 清空/删除 文件夹
 * @param string $dirname 文件夹路径
 * @param bool $self 是否删除当前文件夹
 * @return bool
 */
if(!function_exists('do_rmdir')){
    function do_rmdir($dirname, $self = true) {
        if (!file_exists($dirname)) {
            return false;
        }
        if (is_file($dirname) || is_link($dirname)) {
            return @unlink($dirname);
        }
        $dir = dir($dirname);
        if ($dir) {
            while (false !== $entry = $dir->read()) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }
                do_rmdir($dirname . '/' . $entry);
            }
        }
        $dir->close();
        $self && @rmdir($dirname);
    }
}

//返回某种字体
if(!function_exists('no_font')){
    function no_font($font,$tag="span",$size="12",$color="#ccc"){
        return "<{$tag} style='font-size:{$size}px;color:{$color};'>{$font}</{$tag}>";
    }
}

/**
 * 对查询结果集进行排序
 * http://www.onethink.cn
 * /Application/Common/Common/function.php
 *
 * @access public
 * @param array $list 查询结果
 * @param string $field 排序的字段名
 * @param string $sortby 排序类型 （asc正向排序 desc逆向排序 nat自然排序）
 * @return array
 */
if (! function_exists('list_sort_by'))
{
    function list_sort_by($list, $field, $sortby = 'asc')
    {
        if (is_array($list))
        {
            $refer = $resultSet = array();
            foreach ($list as $i => $data)
            {
                $refer[$i] = &$data[$field];
            }
            switch ($sortby)
            {
                case 'asc': // 正向排序
                    asort($refer);
                    break;
                case 'desc': // 逆向排序
                    arsort($refer);
                    break;
                case 'nat': // 自然排序
                    natcasesort($refer);
                    break;
            }
            foreach ($refer as $key => $val)
            {
                $resultSet[] = &$list[$key];
            }
            return $resultSet;
        }
        return false;
    }
}
if (! function_exists('express_query'))
{
    function express_query($sn, $type='auto')
    {
        $host = "http://jisukdcx.market.alicloudapi.com";
        $path = "/express/query";
        $method = "GET";
        $appcode = config('express.AppCode');
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $querys = "number=".$sn."&type=".$type;
        $bodys = "";
        $url = $host . $path . "?" . $querys;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        if (1 == strpos("$".$host, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        return json_decode(curl_exec($curl),TRUE);
    }
}