<?php
/**
 * Created by PhpStorm.
 * User: ShenYao
 * Date: 2016/7/22
 * Time: 10:29
 */


/**
 * 处理上传图片，只能处理单文件
 * 多文件时可将数据构造成多个单文件的格式循环调用
 * @param array $upload_file 上传文件的信息$_FILES['XX']
 * @param string $name 保存文件名
 * @param string $save_path 保存路径
 * @param int $width    图片宽度
 * @param int $height   图片高度
 * @return array ['status' => 1/-1, 'msg' => 'XXX']
 */
function upload_img($upload_file, $name, $save_path, $width = 0, $height = 0) {
    //上传错误提示
    $upload_error = array(
        0 => '上传成功',
        1 => '文件大小超出服务器限制',
        2 => '文件大小超出表单限制',
        3 => '网络错误',
        4 => '没有文件被上传',
        6 => '找不到临时文件夹',
        7 => '文件写入失败',
    );
    $res = array(
        'status' => -1,
        'msg' => $upload_error[$upload_file['error']],
    );

    if ($upload_file['error'] === 0) {
        $tmp_name = $upload_file['tmp_name'];
        $ext = pathinfo($upload_file['name'])['extension'];
        if (in_array($ext, array('gif', 'jpg', 'jpeg', 'png'))) {
            $filename = $name . '.png';
            $save_file = $save_path . '/' . $filename;
            //裁切并上传
            $up = resize_and_save_image($tmp_name, $save_file, $width, $height);
            if ($up) {
                $res['status'] = 1;
                $res['msg'] = $save_file;
            } else {
                $res['status'] = -1;
                $res['msg'] = '无法移动文件！';
            }
        } else {
            $res['status'] = -1;
            $res['msg'] = "未知的图片格式[ {$ext} ]。";
        }
    }
    return $res;
}

/**
 * 裁切图片并保存
 * @param string $source_name  原文件名
 * @param string $save_file    保存到的文件，需包含完整路径、文件名、文件后缀
 * @param int $width    新图宽度，为零取原值
 * @param int $height   新图高度，为零取原值
 * @param int $save_type 图片类型常量，参考：http://php.net/manual/zh/function.exif-imagetype.php
 * @return bool
 * @throws Exception
 */
function resize_and_save_image($source_name, $save_file, $width = 0, $height = 0, $save_type = IMAGETYPE_PNG){
    //不裁切
    if (empty($width) && empty($height)) {
        return move_uploaded_file($source_name, $save_file);
    }

    //原图参数
    list($s_width, $s_height, $ext, $attr) = getimagesize($source_name);

    //打开原图
    switch ($ext) {
        case IMAGETYPE_GIF: //gif
            $source = imagecreatefromgif($source_name);
            break;
        case IMAGETYPE_JPEG: //jpg
            $source = imagecreatefromjpeg($source_name);
            break;
        case IMAGETYPE_PNG: //png
            $source = imagecreatefrompng($source_name);
            break;
        default:
            throw new Exception('not support file type.');
    }

    //新图尺寸
    if (empty($width)) {
        $width = $s_width;
    }
    if (empty($height)) {
        $height = $s_height;
    }
    //创建一张新图
    $new_img = imagecreatetruecolor($width, $height);
    //保持透明度
    if($ext == IMAGETYPE_GIF or $ext == IMAGETYPE_PNG){
        imagecolortransparent($new_img, imagecolorallocatealpha($new_img, 0, 0, 0, 127));
        imagealphablending($new_img, false);
        imagesavealpha($new_img, true);
    }
    //裁切
    imagecopyresampled($new_img, $source, 0, 0, 0, 0, $width, $height, $s_width, $s_height);
    //输出到文件
    switch ($save_type) {
        case IMAGETYPE_PNG:
            $res = imagepng($new_img, $save_file);
            break;
        case IMAGETYPE_JPEG:
            $res = imagejpeg($new_img, $save_file);
            break;
        case IMAGETYPE_GIF:
            $res = imagegif($new_img, $save_file);
            break;
        default:
            return false;
    }
    return $res;
}


//处理：上传多图片，将其转换为单文件上传时的格式
function multi_file_trans($files) {
    $shots_files = array();
    if (isset($files) && $files['error'][0] !== 4) {
        foreach ($files['name'] as $_k => $_name) {
            $shots_files[] = [
                'name' => $_name,
                'type' => $files['type'][$_k],
                'tmp_name' => $files['tmp_name'][$_k],
                'error' => $files['error'][$_k],
                'size' => $files['size'][$_k],
            ];
        }
    }
    return $shots_files;
}
