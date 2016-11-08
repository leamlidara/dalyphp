<?php
class IMAGE{
    /**
     * Create thumbnail
     * @param String $srcFile image source name
     * @param String $destFile image destination name
     * @param int $width Thumbnail width
     * @param int $height Thumnail height
     * @param int $quality compression quality
     * @return String thumbnail name on success or blank on fail
     */
    function createThumbnail($srcFile, $destFile, $width=null, $height=null){
        $thumbnail = '';
        try{
            if (file_exists($srcFile)  && isset($destFile)){
                $size = @getimagesize($srcFile);
                if ($width !== null && $height === null){
                    $w = number_format($width, 0, ',', '');
                    $h = number_format(($size[1] / $size[0]) * $width, 0, ',', '');
                }else if ($width === null && $height !== null){
                    $h = number_format($height, 0, ',', '');
                    $w = number_format(($size[0] / $size[1]) * $height, 0, ',', '');
                }else{
                    $w = number_format($width, 0, ',', '');
                    $h = number_format($height, 0, ',', '');
                }
                $thumbnail =  $this->copyImage($srcFile, $destFile, $w, $h);
            }
        }  catch (Exception $ex){}
        return basename($thumbnail);
    }

    /**
     * Copy an image to a destination file. The destination image size will be $w X $h pixels
     * @param String $srcFile image source name
     * @param String $destFile image destination name
     * @param Int $width Image width
     * @param Int $height Image height
     * @param boolean $crop if TRUE crop the image using width and height or FALSE resize only
     * @return Boolean TRUE on success or error string on fail
     */
    function copyImage($srcFile, $destFile, $width, $height, $crop = false){
        if (file_exists($srcFile) == false) return 'File not found!';
        if(!list($w, $h) = getimagesize($srcFile)) return 'Unsupported picture type!';

        $type = $this->imagemime($srcFile);
        if($type == 'image/bmp') $type = 'bmp';
        else if($type == 'image/gif') $type = 'gif';
        else if ($type == 'image/jpeg' || $type == 'image/jpg') $type = 'jpg';
        else if ($type == 'image/png') $type = 'png';
        else return false;

        switch($type){
            case 'bmp': $img = imagecreatefromwbmp($srcFile); break;
            case 'gif': $img = imagecreatefromgif($srcFile); break;
            case 'jpeg':
            case 'jpg': $img = imagecreatefromjpeg($srcFile); break;
            case 'png': $img = imagecreatefrompng($srcFile); break;
            default : return 'Unsupported picture type!';
        }

        // resize
        if($crop){
            if($w < $width or $h < $height) return 'Picture is too small!';
            $ratio = max($width/$w, $height/$h);
            $h = $height / $ratio;
            $x = ($w - $width / $ratio) / 2;
            $w = $width / $ratio;
        }else $x = 0;
        $new = imagecreatetruecolor($width, $height);

        // preserve transparency
        if($type == 'gif' or $type == 'png'){
            imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
            imagealphablending($new, false);
            imagesavealpha($new, true);
        }

        imagecopyresampled($new, $img, 0, 0, $x, 0, $width, $height, $w, $h);

        if (is_dir(dirname($destFile)) == false) mkdir(dirname($destFile), 0777, true);

        switch($type){
            case 'bmp': imagewbmp($new, $destFile); break;
            case 'gif': imagegif($new, $destFile); break;
            case 'jpeg':
            case 'jpg': imagejpeg($new, $destFile); break;
            case 'png': imagepng($new, $destFile); break;
        }
        return true;
    }

    /**
     * Optimize image
     * @param String $file file name
     * @param Int $quality integer value between 0 to 100
     * @return boolean TRUE on success or FALSE on fail
     */
    function optimizeImage($file, $quality=70){
        if (is_file($file) == true){
            try{
                $extension = $this->imagemime($file);
                if($extension == 'image/bmp') $extension = 'bmp';
                else if($extension == 'image/gif') $extension = 'gif';
                else if ($extension == 'image/jpeg' || $extension == 'image/jpg') $extension = 'jpg';
                else if ($extension == 'image/png') $extension = 'png';
                else return false;
            
                $images = array('jpg', 'jpeg', 'png');
                if (in_array($extension, $images) == false) return false;
                list($w,$h) = @getimagesize($file);
                if(empty($w) || empty($h)) return false;
                $tmp = imagecreatetruecolor($w,$h);
                $tmpFile = "{$file}_ctmp";
                if ($quality > 100) $quality = 80;
                $outputFunction = '';
                if ($extension == 'png'){
                    $outputFunction = 'imagepng';
                    $quality = round(10 - ($quality / 10));
                    $src = imagecreatefrompng($file);
                    imagecolortransparent($tmp, imagecolorallocatealpha($tmp, 0, 0, 0, 127));
                    imagealphablending($tmp, false);
                    imagesavealpha($tmp, true);
                    imagecopyresampled($tmp, $src, 0, 0, 0, 0, $w, $h, $w, $h);
                    $this->_sharpenImage($tmp, $w, $w);
                    // imagetruecolortopalette($tmp, false, 256);
                    // imagecolormatch($src, $tmp);
                    imagepng($tmp, $tmpFile, $quality);
                }else{
                    $outputFunction = 'imagejpeg';
                    $src = imagecreatefromjpeg($file);
                    imagecopyresampled($tmp, $src, 0, 0, 0, 0, $w, $h, $w, $h);
                    imagejpeg($tmp, $tmpFile, $quality);
                }

                @imagedestroy($tmp);
                @imagedestroy($src);
                if (filesize($file) > filesize($tmpFile)){
                    @unlink($file);
                    @rename($tmpFile, $file);
                }else @unlink($tmpFile);
                return true;
            } catch (Exception $ex){}
        }
        if (isset($tmpFile) && is_file($tmpFile)) @rename($tmpFile, $file);
        return false;
    }

    protected function _sharpenImage(&$image, $width, $targetWidth) {
        if (function_exists('imageconvolution')) {
            $intFinal = $targetWidth * (750.0 / $width);
            $intA = 52;
            $intB = -0.27810650887573124;
            $intC = .00047337278106508946;
            $intRes = $intA + $intB * $intFinal + $intC * $intFinal * $intFinal;
            $intSharpness = max(round($intRes), 0);
            $arrMatrix = array(
                    array(-1, -2, -1),
                    array(-2, $intSharpness + 12, -2),
                    array(-1, -2, -1)
            );
            imageconvolution($image, $arrMatrix, $intSharpness, 0);
        }
    }
    
    private function imagemime($image){
        $r = getimagesize($image); 
        if(isset($r['mime'])) return $r['mime'];
        return NULL;
    }


}
?>