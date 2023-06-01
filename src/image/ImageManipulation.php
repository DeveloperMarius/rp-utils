<?php

namespace utils\image;

use GdImage;
use JetBrains\PhpStorm\ExpectedValues;
use lsolesen\pel\PelEntryUserComment;
use lsolesen\pel\PelTag;
use utils\image\exceptions\ImageDimensionsException;
use utils\image\exceptions\ImageExifReadDataFailedException;
use utils\image\exceptions\ImageExportFailedException;
use utils\image\exceptions\ImageFilterException;
use utils\image\exceptions\ImageFilterNotFoundException;
use utils\image\exceptions\ImageNotReadableException;
use utils\image\exceptions\ImageNotWritableException;
use utils\image\exceptions\ImageOutputNotFoundException;
use utils\image\exceptions\ImageResizeException;
use utils\image\exceptions\ImageResourceCreationFailedException;
use utils\image\exceptions\ImageRotateException;
use utils\image\exceptions\ImageSourceNotFoundException;
use utils\image\exceptions\ImageTypeNotSupportedException;
use utils\Util;

/**
 * Class ImageManipulation
 * @package utils\image
 *
 * Heavily inspired by https://github.com/stefangabos/Zebra_Image
 *
 * TODO:
 * inspire by:
 * - https://github.com/Intervention/image
 * - https://github.com/meyfa/php-svg
 */
class ImageManipulation{

    const
        IMAGE_BOXED = 0,
        IMAGE_NOT_BOXED = 1,
        IMAGE_CROP_TOPLEFT = 2,
        IMAGE_CROP_TOPCENTER = 3,
        IMAGE_CROP_TOPRIGHT = 4,
        IMAGE_CROP_MIDDLELEFT = 5,
        IMAGE_CROP_CENTER = 6,
        IMAGE_CROP_MIDDLERIGHT = 7,
        IMAGE_CROP_BOTTOMLEFT = 8,
        IMAGE_CROP_BOTTOMCENTER = 9,
        IMAGE_CROP_BOTTOMRIGHT = 10,

        ORIENTATION_HORIZONTAL = 0,
        ORIENTATION_VERTICAL = 1,
        ORIENTATION_BOTH = 2;

    /**
     * @var GdImage|resource $image_resource
     */
    private $image_resource;


    private ?int $source_transparent_color_index = null;
    private ?array $source_transparent_color = null;
    private ?int $source_image_last_edit_time = null;
    private int $source_type;
    private ?\lsolesen\pel\PelExif $source_exif = null;
    private ?\lsolesen\pel\PelJpegContent $source_icc = null;

    private int $source_width;
    private int $source_height;

    private string $output_path = '';
    private int $output_type;

    private bool $preserve_aspect_ratio = true;
    /**
     * @var bool $preserve_image_last_edit_time
     * Set the timestamp of last changes to the source file to the output file
     */
    private bool $preserve_image_last_edit_time = true;
    private bool $sharpen_images = false;
    private bool $auto_handle_exif_orientation = false;
    private bool $enlarge_smaller_images = true;
    private bool $jpeg_interlace = false;
    private bool $keep_exif_data = true;

    private int $chmod_value = 0755;
    private int $jpeg_quality = 85;
    private int $png_compression = 9;
    private int $webp_quality = 80;

    /**
     * ImageManipulation constructor.
     * @param string $source_path
     * @throws ImageDimensionsException
     * @throws ImageExifReadDataFailedException
     * @throws ImageNotReadableException
     * @throws ImageResourceCreationFailedException
     * @throws ImageRotateException
     * @throws ImageSourceNotFoundException
     * @throws ImageTypeNotSupportedException
     */
    public function __construct(private string $source_path){
        $this->_createFromSource();
    }

    public function rotate(int $angle, int|string $background_color = -1){
        // there is a bug in GD when angle is 90, 180, 270
        // transparency is not preserved
        if ($angle % 90 === 0) $angle += 0.001;

        // angles are given clockwise but imagerotate works counterclockwise so we need to negate our value
        $angle = -$angle;

        if($background_color === -1){
            if($this->getOutputType() === IMAGETYPE_PNG || $this->getOutputType() === IMAGETYPE_WEBP){
                // allocate a transparent color
                $background_color = imagecolorallocatealpha($this->getImageResource(), 0, 0, 0, 127);
            }else if($this->getOutputType() === IMAGETYPE_GIF){
                // if source image was a GIF and a transparent color existed
                if($this->getSourceType() === IMAGETYPE_GIF && $this->getSourceTransparentColorIndex() >= 0){
                    // use that color
                    $background_color = imagecolorallocate(
                        $this->getImageResource(),
                        $this->source_transparent_color['red'],
                        $this->source_transparent_color['green'],
                        $this->source_transparent_color['blue']
                    );
                }else{
                    // allocate a transparent color
                    $background_color = imagecolorallocate($this->getImageResource(), 255, 255, 255);

                    // make color transparent
                    imagecolortransparent($this->getImageResource(), $background_color);
                }
            }else{

                // use white as the color of uncovered zone after the rotation
                $background_color = imagecolorallocate($this->getImageResource(), 255, 255, 255);
            }
        }else{
            // convert the color to RGB values
            $background_color = Util::hex2rgb($background_color);

            // allocate the color to the image identifier
            $background_color = imagecolorallocate(
                $this->getImageResource(),
                $background_color['r'],
                $background_color['g'],
                $background_color['b']
            );
        }
        $rotate_image = imagerotate($this->getImageResource(), $angle, $background_color);
        if($rotate_image === false){
            throw new ImageRotateException('Failed to rotate Image');
        }

        $this->_updateImage($rotate_image);
        return $this;
    }

    public function crop(int $start_x, int $start_y, int $end_x, int $end_y, int|string $background_color = -1, $resource = null){
        // compute width and height
        $width = $end_x - $start_x;
        $height = $end_y - $start_y;

        // prepare the target image
        $crop_image = $this->_prepare_image($width, $height, $background_color);

        $dest_x = 0;
        $dest_y = 0;

        // if starting x is negative
        if ($start_x < 0) {

            // we are adjusting the position where we place the cropped image on the target image
            $dest_x = abs($start_x);

            // and crop starting from 0
            $start_x = 0;

        }

        // if ending x is larger than the image's width, adjust the width we're showing
        if($resource !== null){
            $resource_x = imagesx($resource);
            if($end_x > $resource_x)
                $width = $resource_x - $start_x;
        }else{
            if($end_x > $this->getSourceWidth())
                $width = $this->getSourceWidth() - $start_x;
        }

        // if starting y is negative
        if ($start_y < 0) {

            // we are adjusting the position where we place the cropped image on the target image
            $dest_y = abs($start_y);

            // and crop starting from 0
            $start_y = 0;

        }

        // if ending y is larger than the image's height, adjust the height we're showing
        if($resource !== null){
            $resource_y = imagesy($resource);
            if($end_y > $resource_y)
                $height = $resource_y - $start_y;
        }else{
            if($end_y > $this->getSourceHeight())
                $height = $this->getSourceHeight() - $start_y;
        }

        // crop the image
        imagecopyresampled(
            $crop_image,
            $resource !== null ? $resource : $this->getImageResource(),
            $dest_x,
            $dest_y,
            $start_x,
            $start_y,
            $width,
            $height,
            $width,
            $height
        );

        if($resource !== null){
            return $resource;
        }

        $this->_updateImage($crop_image);
        return $this;
    }

    public function resize(int $width = 0, int $height = 0, int $method = self::IMAGE_CROP_CENTER, int|string $background_color = -1){
        // if either width or height is to be adjusted automatically
        // set a flag telling the script that, even if $preserve_aspect_ratio is set to false
        // treat everything as if it was set to true
        $auto_preserve_aspect_ratio = ($width == 0 || $height == 0);

        if($this->isPreserveAspectRatio() || $auto_preserve_aspect_ratio){
            if ($width == 0 && $height > 0) {// if height is given and width is to be computed accordingly
                // get the original image's aspect ratio
                $aspect_ratio = $this->getSourceWidth() / $this->getSourceHeight();

                // the target image's height is as given as argument to the method
                $target_height = $height;

                // compute the target image's width, preserving the aspect ratio
                $target_width = round($height * $aspect_ratio);

            }else if ($width > 0 && $height == 0) {// if width is given and height is to be computed accordingly
                // get the original image's aspect ratio
                $aspect_ratio = $this->getSourceWidth() / $this->getSourceHeight();

                // the target image's width is as given as argument to the method
                $target_width = $width;

                // compute the target image's height, preserving the aspect ratio
                $target_height = round($width * $aspect_ratio);
            } elseif ($width > 0 && $height > 0 && ($method == 0 || $method == 1)) {// if both width and height are given and IMAGE_BOXED or IMAGE_NOT_BOXED methods are to be used

                // compute the horizontal and vertical aspect ratios
                $vertical_aspect_ratio = $height / $this->getSourceHeight();
                $horizontal_aspect_ratio = $width / $this->getSourceWidth();

                if (round($horizontal_aspect_ratio * $this->getSourceHeight() < $height)) {// if the image's newly computed height would be inside the bounding box

                    // the target image's width is as given as argument to the method
                    $target_width = $width;

                    // compute the target image's height so that the image will stay inside the bounding box
                    $target_height = round($horizontal_aspect_ratio * $this->getSourceHeight());

                } else {

                    // the target image's height is as given as argument to the method
                    $target_height = $height;

                    // compute the target image's width so that the image will stay inside the bounding box
                    $target_width = round($vertical_aspect_ratio * $this->getSourceWidth());

                }
            } else if ($width > 0 && $height > 0 && $method > 1 && $method < 11) {// if both width and height are given and image is to be cropped in order to get to the required size
                // compute the horizontal and vertical aspect ratios
                $vertical_aspect_ratio = $this->getSourceHeight() / $height;
                $horizontal_aspect_ratio = $this->getSourceWidth() /  $width;

                // we'll use one of the two
                $aspect_ratio =

                    $vertical_aspect_ratio < $horizontal_aspect_ratio ?

                        $vertical_aspect_ratio :

                        $horizontal_aspect_ratio;

                // compute the target image's width, preserving the aspect ratio
                $target_width = round($this->getSourceWidth() / $aspect_ratio);

                // compute the target image's height, preserving the aspect ratio
                $target_height = round($this->getSourceHeight() / $aspect_ratio);
            } else {
                // we will create a copy of the source image
                $target_width = $this->getSourceWidth();
                $target_height = $this->getSourceHeight();
            }
        }else{
            // compute the target image's width
            $target_width = ($width > 0 ? $width : $this->getSourceWidth());

            // compute the target image's height
            $target_height = ($height > 0 ? $height : $this->getSourceHeight());
        }

        if(
            $this->isEnlargeSmallerImages() ||

            // smaller images than the given width/height are to be left untouched
            // but current image has at leas one side that is larger than the required width/height
            ($width > 0 && $height > 0 ?
                ($this->getSourceWidth() > $width || $this->getSourceHeight() > $height) :
                ($this->getSourceWidth() > $target_width || $this->getSourceHeight() > $target_height)
            )
        ){
            if(
                ($this->isPreserveAspectRatio() || $auto_preserve_aspect_ratio) &&

                // both width and height are given
                ($width > 0 && $height > 0) &&

                // images are to be cropped
                ($method > 1 && $method < 11)
            ){
                // prepare the target image
                $crop_image = $this->_prepare_image($target_width, $target_height, $background_color);

                imagecopyresampled(
                    $crop_image,
                    $this->getImageResource(),
                    0,
                    0,
                    0,
                    0,
                    $target_width,
                    $target_height,
                    $this->source_width,
                    $this->source_height
                );

                // do the crop according to the required method
                switch ($method) {
                    case self::IMAGE_CROP_TOPLEFT:// if image needs to be cropped from the top-left corner
                        // crop accordingly
                        return $this->crop(
                            0,
                            0,
                            $width,
                            $height,
                            $background_color,
                            $crop_image // crop this resource instead
                        );
                        break;
                    case self::IMAGE_CROP_TOPCENTER:// if image needs to be cropped from the top-center
                        // crop accordingly
                        return $this->crop(
                            floor(($target_width - $width) / 2),
                            0,
                            floor(($target_width - $width) / 2) + $width,
                            $height,
                            $background_color,
                            $crop_image // crop this resource instead
                        );
                        break;
                    case self::IMAGE_CROP_TOPRIGHT:// if image needs to be cropped from the top-right corner
                        // crop accordingly
                        return $this->crop(
                            $target_width - $width,
                            0,
                            $target_width,
                            $height,
                            $background_color,
                            $crop_image // crop this resource instead
                        );
                        break;
                    case self::IMAGE_CROP_MIDDLELEFT:// if image needs to be cropped from the middle-left
                        // crop accordingly
                        return $this->crop(
                            0,
                            floor(($target_height - $height) / 2),
                            $width,
                            floor(($target_height - $height) / 2) + $height,
                            $background_color,
                            $crop_image // crop this resource instead
                        );
                        break;
                    case self::IMAGE_CROP_CENTER:// if image needs to be cropped from the center of the image
                        // crop accordingly
                        return $this->crop(
                            floor(($target_width - $width) / 2),
                            floor(($target_height - $height) / 2),
                            floor(($target_width - $width) / 2) + $width,
                            floor(($target_height - $height) / 2) + $height,
                            $background_color,
                            $crop_image // crop this resource instead
                        );
                        break;
                    case self::IMAGE_CROP_MIDDLERIGHT:// if image needs to be cropped from the middle-right
                        // crop accordingly
                        return $this->crop(
                            $target_width - $width,
                            floor(($target_height - $height) / 2),
                            $target_width,
                            floor(($target_height - $height) / 2) + $height,
                            $background_color,
                            $crop_image // crop this resource instead
                        );
                        break;
                    case self::IMAGE_CROP_BOTTOMLEFT:// if image needs to be cropped from the bottom-left corner
                        // crop accordingly
                        return $this->crop(
                            0,
                            $target_height - $height,
                            $width,
                            $target_height,
                            $background_color,
                            $crop_image // crop this resource instead
                        );
                        break;
                    case self::IMAGE_CROP_BOTTOMCENTER:// if image needs to be cropped from the bottom-center
                        // crop accordingly
                        return $this->crop(
                            floor(($target_width - $width) / 2),
                            $target_height - $height,
                            floor(($target_width - $width) / 2) + $width,
                            $target_height,
                            $background_color,
                            $crop_image // crop this resource instead
                        );
                        break;
                    case self::IMAGE_CROP_BOTTOMRIGHT:// if image needs to be cropped from the bottom-right corner
                        // crop accordingly
                        return $this->crop(
                            $target_width - $width,
                            $target_height - $height,
                            $target_width,
                            $target_height,
                            $background_color,
                            $crop_image // crop this resource instead
                        );
                        break;
                }
            }else{// if aspect ratio doesn't need to be preserved or it needs to be preserved and method is IMAGE_BOXED or IMAGE_NOT_BOXED
                // prepare the target image
                $crop_image = $this->_prepare_image(
                    ($width > 0 && $height > 0 && $method !== self::IMAGE_NOT_BOXED ? $width : $target_width),
                    ($width > 0 && $height > 0 && $method !== self::IMAGE_NOT_BOXED ? $height : $target_height),
                    $background_color
                );

                imagecopyresampled(
                    $crop_image,
                    $this->getImageResource(),
                    ($width > 0 && $height > 0 && $method !== self::IMAGE_NOT_BOXED ? ($width - $target_width) / 2 : 0),
                    ($width > 0 && $height > 0 && $method !== self::IMAGE_NOT_BOXED ? ($height - $target_height) / 2 : 0),
                    0,
                    0,
                    $target_width,
                    $target_height,
                    $this->source_width,
                    $this->source_height
                );

                // if script gets this far, write the image to disk
                $this->_updateImage($crop_image);
                return $this;
            }
        }else{
            // prepare the target image
            $crop_image = $this->_prepare_image($this->source_width, $this->source_height, $background_color);

            imagecopyresampled(
                $crop_image,
                $this->getImageResource(),
                0,
                0,
                0,
                0,
                $this->source_width,
                $this->source_height,
                $this->source_width,
                $this->source_height
            );

            // previously to 2.2.7 I was simply calling the _write_images() method without the code from above this
            // comment and therefore, when resizing transparent images to a format which doesn't support transparency
            // and the "enlarge_smaller_images" property being set to FALSE, the "background_color" argument was not
            // applied and lead to unexpected background colors for the resulting images
            $this->_updateImage($crop_image);
            return true;
        }
        throw new ImageResizeException('Failed to resize image');
    }

    public function flipBoth(): self{
        return $this->_flip(self::ORIENTATION_BOTH);
    }

    public function flipHorizontal(): self{
        return $this->_flip(self::ORIENTATION_HORIZONTAL);
    }

    public function flipVertical(): self{
        return $this->_flip(self::ORIENTATION_VERTICAL);
    }

    public function applyFilter(array|string $filter, ...$args){
        // prepare the target image
        $filter_image = $this->_prepare_image($this->getSourceWidth(), $this->getSourceHeight(), -1);

        // copy the original image
        imagecopyresampled(
            $filter_image,
            $this->getImageResource(),
            0,
            0,
            0,
            0,
            $this->source_width,
            $this->source_height,
            $this->source_width,
            $this->source_height
        );

        if (is_array($filter)) {
            foreach ($filter as $filter_args){
                $filter_name = strtoupper($filter_args[0]);
                array_shift($filter_args);
                // if filter exists
                if(defined('IMG_FILTER_' . $filter_name)){
                    if(!imagefilter($filter_image, $filter_name, ...$filter_args)){
                        throw new ImageFilterException('Failed applying Filter "' . $filter_name . '" with args ' . json_encode($filter_args));
                    }
                } else
                    throw new ImageFilterNotFoundException('Cannot find Filter "' . $filter_name . '"');
            }
        } else{
            $filter_name = strtoupper($filter);
            if(defined('IMG_FILTER_' . $filter_name)){
                if(!imagefilter($filter_image, $filter_name, ...$args)){
                    throw new ImageFilterException('Failed applying Filter "' . $filter_name . '" with args ' . json_encode($args));
                }
            } else
                throw new ImageFilterNotFoundException('Cannot find Filter "' . $filter_name . '"');
        }
        $this->_updateImage($filter_image);
        return $this;
    }

    /**
     * Strip Exif meta tags {@link https://www.php.net/manual/en/imagick.stripimage.php#120380}
     */
    public function clearExif(){
        //TODO
    }

    /**
     * @param string|null $source_path
     * @param int $source_type - only used if source_path is specified
     * @return ImageManipulationExifData
     * @throws ImageExifReadDataFailedException
     */
    public function getExifData(?string $source_path = null, int $source_type = IMAGETYPE_JPEG): ImageManipulationExifData{
        return $source_path === null ? new ImageManipulationExifData($this->getSourcePath(), $this->getSourceType()) : new ImageManipulationExifData($source_path, $source_type);
    }

    public function updateExifData(ImageManipulationExifData $exifData){
        if($exifData->isPelInstalled()){
            $this->_saveImageExifData($exifData);
            //$exif_image = imagecreatefromstring($exifData->getPelJpeg()->getBytes());
            //$this->_updateImage($exif_image);
        }
    }

    public function exportImage(){
        $this->_canOutputImage();

        $this->_sharpenImage();

        if($this->getOutputType() === IMAGETYPE_JPEG && $this->isJpegInterlace())
            imageinterlace($this->getImageResource(), true);

        $output_path = $this->getOutputPath();

        if($this->isKeepExifData() && $this->_canSetImageExifData()){
            $output_path_split = explode('/', $output_path);
            $output_path_split[sizeof($output_path_split)-1] = '_' . $output_path_split[sizeof($output_path_split)-1];
            $output_path = join('/', $output_path_split);
        }

        switch($this->getOutputType()){
            case IMAGETYPE_GIF:
                if(!imagegif($this->getImageResource(), $output_path)){
                    throw new ImageExportFailedException('Failed to export file with type: "GIF"');
                }
                break;
            case IMAGETYPE_JPEG:
                if(!imagejpeg($this->getImageResource(), $output_path, $this->getJpegQuality())){
                    throw new ImageExportFailedException('Failed to export file with type: "JPEG"');
                }
                break;
            case IMAGETYPE_PNG:
                if(!imagepng($this->getImageResource(), $output_path, $this->getPngCompression())){
                    throw new ImageExportFailedException('Failed to export file with type: "PNG"');
                }
                break;
            case IMAGETYPE_WEBP:
                if(!imagewebp($this->getImageResource(), $output_path, $this->getWebpQuality())){
                    throw new ImageExportFailedException('Failed to export file with type: "WEBP"');
                }
                break;
        }

        if($this->isKeepExifData() && $this->_canSetImageExifData()){
            $this->_setImageExifData($output_path, $this->getOutputPath(), true);
        }

        if($this->isPreserveImageLastEditTime()){
            touch($this->getOutputPath(), $this->getSourceImageLastEditTime());
        }

        imagedestroy($this->getImageResource());
    }

    /**
     * @throws ImageDimensionsException
     * @throws ImageExifReadDataFailedException
     * @throws ImageNotReadableException
     * @throws ImageResourceCreationFailedException
     * @throws ImageRotateException
     * @throws ImageSourceNotFoundException
     * @throws ImageTypeNotSupportedException
     */
    private function _createFromSource(){
        if($this->getSourcePath() === null || !is_file($this->getSourcePath())){
            throw new ImageSourceNotFoundException('The Source Path is not defined or the Source image was not found');
        }else if(!is_readable($this->getSourcePath())){
            throw new ImageNotReadableException('This image is not readable');
        }else{
            $source_path = explode('/', $this->getSourcePath());
            $filename_split = explode('.', end($source_path));
            $source_type_extension = end($filename_split);

            $this->output_path = str_replace('.' . $source_type_extension, '', end($source_path)) . '-' . Util::getTimestamp() . '.' . $source_type_extension;

            $source_type = $this->getImageType($this->getSourcePath());

            if($source_type === null)
                throw new ImageTypeNotSupportedException('This image type is not supported');

            $this->source_type = $this->output_type = $source_type;

            $image_resource = null;

            switch($source_type){
                case IMAGETYPE_GIF:
                    $image_resource = imagecreatefromgif($this->getSourcePath());
                    if($image_resource !== false){
                        $this->source_transparent_color_index = imagecolortransparent($image_resource);

                        // get the index of the transparent color (if any)
                        if($this->getSourceTransparentColorIndex() >= 0){
                            // get the transparent color's RGB values
                            // we have to mute errors because there are GIF images which *are* transparent and everything
                            // works as expected, but imagecolortransparent() returns a color that is outside the range of
                            // colors in the image's pallette...
                            $this->source_transparent_color = @imagecolorsforindex($image_resource, $this->getSourceTransparentColorIndex());
                        }
                    }
                    break;
                case IMAGETYPE_JPEG:
                    $image_resource = imagecreatefromjpeg($this->getSourcePath());
                    break;
                case IMAGETYPE_PNG:
                    $image_resource = imagecreatefrompng($this->getSourcePath());
                    if($image_resource !== false){

                        // disable blending
                        imagealphablending($image_resource, false);

                        // save full alpha channel information
                        imagesavealpha($image_resource, true);
                    }
                    break;
                case IMAGETYPE_WEBP:
                    $image_resource = imagecreatefromwebp($this->getSourcePath());
                    if($image_resource !== false){

                        // disable blending
                        imagealphablending($image_resource, false);

                        // save full alpha channel information
                        imagesavealpha($image_resource, true);
                    }
                    break;
                default:
                    throw new ImageTypeNotSupportedException('This Image type is not supported');
            }
        }

        if($image_resource === false){
            throw new ImageResourceCreationFailedException('Failed to create image resource from source path using file type "' . $this->getOutputType() . '"');
        }
        $this->image_resource = $image_resource;

        $this->_setImageDimensions();

        $last_edit_time = filemtime($this->getSourcePath());
        if($last_edit_time !== false){
            $this->source_image_last_edit_time = $last_edit_time;
        }

        if($this->isAutoHandleExifOrientation() && $this->getSourceType() === IMAGETYPE_JPEG){
            $this->_fixOrientation();
        }

        $this->_saveImageExifData();
    }

    /**
     * Code taken from {@link https://www.php.net/manual/de/function.imageconvolution.php#104006}
     */
    private function _sharpenImage(){
        if($this->isSharpenImages()){
            // the convolution matrix as an array of three arrays of three floats
            $matrix = array(
                array(-1.2, -1, -1.2),
                array(-1, 20, -1),
                array(-1.2, -1, -1.2),
            );

            // the divisor of the matrix
            $divisor = array_sum(array_map('array_sum', $matrix));

            // color offset
            $offset = 0;

            // sharpen image
            imageconvolution($this->getImageResource(), $matrix, $divisor, $offset);
        }
        return $this;
    }

    /**
     * @throws ImageExifReadDataFailedException
     * @throws ImageRotateException
     */
    private function _fixOrientation(){
        switch($this->getExifData()->getOrientation()){
            case ImageManipulationExifData::ORIENTATION_BOTTOMRIGHT:
                $this->rotate(180, -1);
                break;
            case ImageManipulationExifData::ORIENTATION_RIGHTTOP:
                $this->rotate(90, -1);
                break;
            case ImageManipulationExifData::ORIENTATION_LEFTBOTTOM:
                $this->rotate(-90, -1);
                break;
        }
    }

    /**
     * @throws ImageExifReadDataFailedException
     *
     * TODO:
     * "DateTime": "2021:03:08 12:21:17" -> "DateTime": "2021:03:08 12:21:17\u0000"
     * "DateTimeOriginal": "2021:03:04 17:40:58" -> "DateTimeOriginal": "2021:03:04 17:40:58\u0000",
     * "DateTimeDigitized": "2021:03:04 17:40:58" -> "DateTimeDigitized": "2021:03:04 17:40:58\u0000",
     * remove comment?
     *      null -> "COMMENT": [ "CREATOR: gd-jpeg v1.0 (using IJG JPEG v90), quality = 100\n" ],
     *      "SectionsFound": "ANY_TAG, IFD0, THUMBNAIL, EXIF" -> "SectionsFound": "ANY_TAG, IFD0, THUMBNAIL, COMMENT, EXIF",
     *
     * UNDEFINED TAGS:
     *      "UndefinedTag:0xA431": "XHL1708100137",
     *      "UndefinedTag:0xA434": "SIGMA 30mm F1.4 DC DN | C 016",
     *      "UndefinedTag:0xA435": "51670738"
     *
     * WHAT HAPPENS TO THUMBNAIL?:
     *      "THUMBNAIL"."JPEGInterchangeFormat": 994 -> "JPEGInterchangeFormat": 899
     */
    private function _saveImageExifData(?ImageManipulationExifData $exif = null){
        if($exif === null)
            $exif = $this->getExifData();
        if($exif->isPelInstalled()){
            $this->source_exif = $exif->getPelExif();
            $this->source_icc = $exif->getPelICC();
        }
    }

    private function _canSetImageExifData(){
        return $this->getOutputType() === IMAGETYPE_JPEG && ImageManipulationExifData::isPelInstalled();
    }

    //https://github.com/pel/pel/issues
    private function _setImageExifData(string $source_path, ?string $output_path = null, bool $destroy_source = false){
        $exif = $this->getExifData($source_path);
        if($this->source_exif !== null)
            $exif->getPelJpeg()->setExif($this->source_exif);
        if($this->source_icc !== null)
            $exif->getPelJpeg()->setICC($this->source_icc);
        $exif->getPelJpeg()->saveFile($output_path !== null ? $output_path : $this->getOutputPath());
        if($destroy_source)
            unlink($source_path);
    }

    /**
     * @throws ImageDimensionsException
     */
    private function _setImageDimensions(){
        $source_width = imagesx($this->getImageResource());
        if($source_width === false){
            throw new ImageDimensionsException('Failed to read image width');
        }
        $this->source_width = $source_width;
        $source_height = imagesy($this->getImageResource());
        if($source_height === false){
            throw new ImageDimensionsException('Failed to read image height');
        }
        $this->source_height = $source_height;
    }

    private function _flip(#[ExpectedValues([self::ORIENTATION_HORIZONTAL, self::ORIENTATION_VERTICAL, self::ORIENTATION_BOTH])] string $orientation){
        $flip_image = $this->_prepare_image($this->getSourceWidth(), $this->getSourceHeight(), -1);
        switch($orientation){
            case self::ORIENTATION_HORIZONTAL:
                imagecopyresampled(
                    $flip_image,
                    $this->getImageResource(),
                    0,
                    0,
                    ($this->source_width - 1),
                    0,
                    $this->source_width,
                    $this->source_height,
                    -$this->source_width,
                    $this->source_height
                );
                break;
            case self::ORIENTATION_VERTICAL:
                imagecopyresampled(
                    $flip_image,
                    $this->getImageResource(),
                    0,
                    0,
                    0,
                    ($this->source_height - 1),
                    $this->source_width,
                    $this->source_height,
                    $this->source_width,
                    -$this->source_height
                );
                break;
            case self::ORIENTATION_BOTH:
                imagecopyresampled(
                    $flip_image,
                    $this->getImageResource(),
                    0,
                    0,
                    ($this->source_width - 1),
                    ($this->source_height - 1),
                    $this->source_width,
                    $this->source_height,
                    -$this->source_width,
                    -$this->source_height

                );
                break;
        }
        $this->_updateImage($flip_image);
        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     * @param string|int $background_color
     * @return GdImage|resource
     * @throws ImageResourceCreationFailedException
     */
    private function _prepare_image(int $width, int $height, string|int $background_color = '#FFFFFF'){
        // create a blank image
        $prepared_resource = imagecreatetruecolor($width <= 0 ? 1 : $width, $height <= 0 ? 1 : $height);

        if($prepared_resource === false)
            throw new ImageResourceCreationFailedException('Failed to create colored image');

        // if we are creating a transparent image, and image type supports transparency
        if ($background_color === -1 && $this->getOutputType() !== IMAGETYPE_JPEG) {
            // disable blending
            imagealphablending($prepared_resource, false);

            // allocate a transparent color
            $background_color = imagecolorallocatealpha($prepared_resource, 0, 0, 0, 127);

            // we also need to set this for saving gifs
            imagecolortransparent($prepared_resource, $background_color);

            // save full alpha channel information
            imagesavealpha($prepared_resource, true);
        } else {
            // convert hex color to rgb
            $background_color = Util::hex2rgb($background_color);

            // prepare the background color
            $background_color = imagecolorallocate($prepared_resource, $background_color['r'], $background_color['g'], $background_color['b']);
        }

        // fill the image with the background color
        imagefill($prepared_resource, 0, 0, $background_color);

        // return the image's identifier
        return $prepared_resource;
    }

    /**
     * @param GdImage|resource $resource
     * @throws ImageDimensionsException
     */
    private function _updateImage($resource){
        imagedestroy($this->image_resource);
        $this->image_resource = $resource;
        $this->_setImageDimensions();
    }

    /**
     * @return bool
     * @throws ImageNotWritableException
     * @throws ImageOutputNotFoundException
     */
    private function _canOutputImage(): bool{
        if($this->getOutputPath() === null){
            throw new ImageOutputNotFoundException('The Output Path is not defined');
        }else if(($this->getSourcePath() === $this->getOutputPath()) && !is_writable($this->getSourcePath())){
            throw new ImageNotWritableException('Cannot write into this directory');
        }
        return true;
    }

    private function getImageType(string $file_path): ?int{
        $file_path_split = explode('/', $file_path);
        $filename_split = explode('.', end($file_path_split));
        $output_type_extension = end($filename_split);
        return match ($output_type_extension){
            'gif' => IMAGETYPE_GIF,
            'jpg','jpeg' => IMAGETYPE_JPEG,
            'png' => IMAGETYPE_PNG,
            'webp' => IMAGETYPE_WEBP,
            default => null
        };
    }

    /* Getters and Setters */

    /**
     * @return string|null
     */
    public function getSourcePath(): ?string{
        return $this->source_path;
    }

    /**
     * @return int|null
     */
    #[ExpectedValues([IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, null])]
    public function getSourceType(): ?int{
        return $this->source_type;
    }

    /**
     * @return int
     */
    public function getSourceWidth(): int{
        return $this->source_width;
    }

    /**
     * @return int
     */
    public function getSourceHeight(): int{
        return $this->source_height;
    }

    /**
     * @return GdImage|resource
     */
    public function getImageResource(): GdImage{
        return $this->image_resource;
    }

    /**
     * @return int|null
     */
    public function getSourceTransparentColorIndex(): ?int{
        return $this->source_transparent_color_index;
    }

    /**
     * @return int|null
     */
    public function getSourceImageLastEditTime(): ?int{
        return $this->source_image_last_edit_time;
    }

    /**
     * @return string
     */
    public function getOutputPath(): string{
        return $this->output_path;
    }

    /**
     * @param string $output_path
     * @throws ImageTypeNotSupportedException
     */
    public function setOutputPath(string $output_path): void{
        $this->output_path = $output_path;
        $output_type = $this->getImageType($this->getOutputPath());
        if($output_type === null)
            throw new ImageTypeNotSupportedException('This image type is not supported');
        $this->setOutputType($output_type);
    }

    /**
     * @return int
     */
    #[ExpectedValues([IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP])]
    public function getOutputType(): int{
        return $this->output_type;
    }

    /**
     * @param int $output_type
     */
    private function setOutputType(#[ExpectedValues([IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP])] int $output_type): void{
        $this->output_type = $output_type;
    }

    /**
     * @return bool
     */
    public function isAutoHandleExifOrientation(): bool{
        return $this->auto_handle_exif_orientation;
    }

    /**
     *  If set to TRUE, JPEG images will be auto-rotated according to the {@link http://keyj.emphy.de/exif-orientation-rant/ Exif Orientation Tag}
     *  so that they are always shown correctly.
     *
     *  <samp>If you set this to TRUE you must also enable exif-support with --enable-exif. Windows users must enable both
     *  the php_mbstring.dll and php_exif.dll DLL's in php.ini. The php_mbstring.dll DLL must be loaded before the
     *  php_exif.dll DLL so adjust your php.ini accordingly. See {@link http://php.net/manual/en/exif.installation.php the PHP manual}</samp>
     *
     * @param bool $auto_handle_exif_orientation
     */
    public function setAutoHandleExifOrientation(bool $auto_handle_exif_orientation): void{
        $this->auto_handle_exif_orientation = $auto_handle_exif_orientation;
    }

    /**
     * @return bool
     */
    public function isKeepExifData(): bool{
        return $this->keep_exif_data;
    }

    /**
     * @param bool $keep_exif_data
     */
    public function setKeepExifData(bool $keep_exif_data): void{
        $this->keep_exif_data = $keep_exif_data;
    }

    /**
     * @return bool
     */
    public function isJpegInterlace(): bool{
        return $this->jpeg_interlace;
    }

    /**
     *  Indicates whether the created image should be saved as a progressive JPEG.
     *
     * @param bool $jpeg_interlace
     */
    public function setJpegInterlace(bool $jpeg_interlace): void{
        $this->jpeg_interlace = $jpeg_interlace;
    }

    /**
     * @return bool
     */
    public function isSharpenImages(): bool{
        return $this->sharpen_images;
    }

    /**
     *
     *  Indicates whether the target image should have a "sharpen" filter applied to it.
     *
     *  Can be very useful when creating thumbnails and should be used only when creating thumbnails.
     *
     * @param bool $sharpen_images
     *
     */
    public function setSharpenImages(bool $sharpen_images): void{
        $this->sharpen_images = $sharpen_images;
    }

    /**
     * @return bool
     */
    public function isPreserveAspectRatio(): bool{
        return $this->preserve_aspect_ratio;
    }

    /**
     *  Specifies whether, upon resizing, images should preserve their aspect ratio.
     *
     * @param bool $preserve_aspect_ratio
     */
    public function setPreserveAspectRatio(bool $preserve_aspect_ratio): void{
        $this->preserve_aspect_ratio = $preserve_aspect_ratio;
    }

    /**
     * @return bool
     */
    public function isPreserveImageLastEditTime(): bool{
        return $this->preserve_image_last_edit_time;
    }

    /**
     * @param bool $preserve_image_last_edit_time
     */
    public function setPreserveImageLastEditTime(bool $preserve_image_last_edit_time): void{
        $this->preserve_image_last_edit_time = $preserve_image_last_edit_time;
    }

    /**
     * @return bool
     */
    public function isEnlargeSmallerImages(): bool{
        return $this->enlarge_smaller_images;
    }

    /**
     *  If set to FALSE, images having both width and height smaller than the required width and height, will be left
     *  untouched ({@link jpeg_quality} and {@link png_compression} will still apply).
     *
     * @param bool $enlarge_smaller_images
     */
    public function setEnlargeSmallerImages(bool $enlarge_smaller_images): void{
        $this->enlarge_smaller_images = $enlarge_smaller_images;
    }

    /**
     * @return int
     */
    public function getChmodValue(): int{
        return $this->chmod_value;
    }

    /**
     * @param int $chmod_value
     */
    public function setChmodValue(int $chmod_value): void{
        $this->chmod_value = $chmod_value;
    }

    /**
     * @return int
     */
    public function getJpegQuality(): int{
        return $this->jpeg_quality;
    }

    /**
     *  Indicates the quality of the output image (better quality means bigger file size).
     *
     *  Range is 0 - 100
     *
     *  Default is 85
     *
     * @param int $jpeg_quality
     */
    public function setJpegQuality(int $jpeg_quality): void{
        $this->jpeg_quality = $jpeg_quality;
    }

    /**
     * @return int
     */
    public function getPngCompression(): int{
        return $this->png_compression;
    }

    /**
     *  Indicates the compression level of the output image (lower compression means bigger file size).
     *
     *  Range is 0 - 9
     *
     *  Default is 9
     *
     * @param int $png_compression
     */
    public function setPngCompression(int $png_compression): void{
        $this->png_compression = $png_compression;
    }

    /**
     * @return int
     */
    public function getWebpQuality(): int{
        return $this->webp_quality;
    }

    /**
     * Indicates the quality level of the output image.
     *
     * Range is 0 - 100
     *
     *  Default is 80
     *
     * @param int $webp_quality
     */
    public function setWebpQuality(int $webp_quality): void{
        $this->webp_quality = $webp_quality;
    }
}