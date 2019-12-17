<?php

namespace Asmx\Cropper;


/**
 * Class Cropper
 * @package Asmx\Cropper
 */
class Cropper
{

    /**
     * Weight for output file
     * @var int
     */

    protected $width = 0;

    /**
     * Height for output file
     * @var int
     */

    protected $height = 0;

    /**
     * Input file path
     * @var
     */

    protected $source_file;

    /**
     * Output file path
     * @var
     */

    protected $destination_file;

    /**
     * Quality for image
     * @var int
     */
    protected $quality = 90;

    /**
     * Allowed formats to create image
     * @var array
     */

    protected $allowed_formats = ['jpeg', 'jpg', 'gif', 'png', 'bmp', 'webp'];

    /**
     * Set source file.
     *
     * @param string $source_file
     *
     * @return $this
     */

    public function from(string $source_file)
    {
        $this->source_file = $source_file;

        return $this;
    }

    /**
     * Set destination file
     * if empty or not set, construct from sizes and '_fit'
     * @param string $destination_file
     *
     * @return $this
     */

    public function to(string $destination_file = '')
    {
        if ($destination_file != '') {
            $this->destination_file = $destination_file;
        } else {
            $this->destination_file = preg_replace('/\.' . $this->getExtension($this->source_file) . '/i',
                '_' . $this->width . '_' . $this->height . '_fit.' . $this->getExtension($this->source_file),
                $this->source_file);
        }
        return $this;
    }

    /**
     *  Set quality
     * @param int $quality
     * @return $this
     */

    public function quality(int $quality)
    {
        if ($quality <= 100 and $quality > 0)
            $this->quality = $quality;
        return $this;
    }

    /**
     * Set need size
     * @param array $size
     * @return $this
     */
    public function size(array $size)
    {
        isset($size[0]) and is_int($size[0]) ? $this->width = $size[0] : $this->width = 'auto';

        isset($size[1]) and is_int($size[1]) ? $this->height = $size[1] : $this->height = 'auto';

        return $this;
    }

    /**
     * get extension of file for change create image method
     * @param string $filename
     * @return string
     */
    private function getExtension(string $filename): string
    {
        $path_info = pathinfo($filename);

        return strtolower($path_info['extension']);
    }


    /**
     * main method
     * @param bool $overwrite
     * @throws \Exception
     */
    public function fit(bool $overwrite = false)
    {

        if (!$this->destination_file) {
            $this->to();
        }

        if (!$this->checkExistFile($this->destination_file) and
            $overwrite != false and
            $this->checkExistFile($this->source_file)
        ) {

            list($width, $height) = getimagesize($this->source_file);

            $new_width = $this->width;

            $new_height = $this->height;

            if ($this->width > 0 && $this->height > 0) {
                $side_to_cute = ((($height / $width) - ($this->height / $this->width)) < 0) ? 'width' : 'height';
            } else {
                $side_to_cute = ($this->height === 0) ? 'width' : 'height';
            }

            if ($side_to_cute === 'width') {
                $new_height = (int)ceil($this->width * $height / $width);
            } else {
                $new_width = (int)ceil($width * $this->height / $height);
            }

            if ($this->width == 0) {
                $this->width = $new_width;
            }
            if ($this->height == 0) {
                $this->height = $new_height;
            }

            $color_fill = $this->getPrimaryColor();

            $new_img = imagecreatetruecolor($this->width, $this->height);

            $primary_color = imagecolorallocate($new_img, $color_fill[0], $color_fill[1], $color_fill[2]);

            imagefill($new_img, 0, 0, $primary_color);

            $source = $this->imageCreateFrom();

            $tmp = imagecreatetruecolor($new_width, $new_height);

            imagecopyresampled($tmp, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

            $x = ($this->width - $new_width) / 2;

            $y = ($this->height - $new_height) / 2;

            imagecopy($new_img, $tmp, $x, $y, 0, 0, $new_width, $new_height);

            $this->imageSaveTo($new_img);

            // clear all

            imagedestroy($tmp);

            imagedestroy($source);

            imagedestroy($new_img);
        }
    }

    /**
     * Method to calc primary color for input file
     * @return array
     * @throws \Exception
     */
    private function getPrimaryColor()
    {

        $im = $this->imageCreateFrom();

        $total_R = 0;
        $total_G = 0;
        $total_B = 0;

        list($width, $height) = getimagesize($this->source_file);

        for ($x = 0;
             $x < $width;
             $x++) {
            for ($y = 0;
                 $y < $height;
                 $y++) {
                $rgb = imagecolorat($im, $x, $y);
                $total_R += ($rgb >> 16) & 0xFF;
                $total_G += ($rgb >> 8) & 0xFF;
                $total_B += $rgb & 0xFF;
            }
        }

        imagedestroy($im);

        $avg_R = round($total_R / $width / $height);
        $avg_G = round($total_G / $width / $height);
        $avg_B = round($total_B / $width / $height);

        return array($avg_R, $avg_G, $avg_B);
    }


    /**
     * Method create image from format
     * @return false|resource
     * @throws \Exception
     */
    private function imageCreateFrom()
    {

        $ext = $this->getExtension($this->source_file);
        if (!in_array($ext, $this->allowed_formats)) {
            throw new \Exception('Not supported format');
        }
        switch ($ext) {
            case 'jpg' or 'jpeg':
                $image = imagecreatefromjpeg($this->source_file);
                break;
            case 'gif':
                $image = imagecreatefromgif($this->source_file);
                break;
            case 'png':
                $image = imagecreatefrompng($this->source_file);
                break;
            case 'webp':
                $image = imagecreatefromwebp($this->source_file);
                break;
            case 'bmp':
                $image = imagecreatefrombmp($this->source_file);
                break;
        }
        return $image;
    }

    /**
     * Method save image to format
     * @param $resource
     * @throws \Exception
     */
    private function imageSaveTo($resource)
    {
        $ext = $this->getExtension($this->destination_file);
        if (!in_array($ext, $this->allowed_formats)) {
            throw new \Exception('Not supported format');
        }
        switch ($ext) {
            case 'jpg' or 'jpeg':
                imagejpeg($resource, $this->destination_file, $this->quality);
                break;
            case 'gif':
                imagegif($resource, $this->destination_file);
                break;
            case 'png':
                imagepng($resource, $this->destination_file, $this->quality);
                break;
            case 'webp':
                imagewebp($resource, $this->destination_file, $this->quality);
                break;
            case 'bmp':
                imagebmp($resource, $this->destination_file);
                break;
        }
    }

    /**
     * @param $path_file
     * @return bool
     */
    private function checkExistFile($path_file): bool
    {

        try {
            file_exists($path_file);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }


}
