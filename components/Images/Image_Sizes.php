<?php

namespace hiweb\components\Images;


use hiweb\core\Cache\CacheFactory;
use hiweb\core\hidden_methods;
use hiweb\core\Paths\PathsFactory;
use stdClass;


/**
 * Class control Image_Sizes
 * @package hiweb\components\Images
 * @version 1.1
 */
class Image_Sizes {

    use hidden_methods;


    protected $Image;
    protected $sizes;


    public function __construct(Image $Image) {
        $this->Image = $Image;
    }


    /**
     * Return file name like 'my_image-640x480.jpg' by size 640px * 480px
     * @param       $width
     * @param       $height
     * @param false $crop
     * @param null  $extension
     * @return string
     */
    protected function get_fileName_bySizeName($width, $height, $crop = false, $extension = null): string {
        $main_file = $this->Image->path()->file();
        $file_path = [];
        //$file_path[] = $main_file->get_dirname() . '/';
        $file_path[] = $main_file->get_filename();
        $file_path[] = '-' . $width . 'x' . $height;
        $file_path[] = ($crop === 0 || $crop === false) ? 'c' : '';
        $file_path[] = !is_string($extension) ? '.' . $main_file->get_extension() : '.' . ltrim('.', $extension);
        return join('', $file_path);
    }


    /**
     * Return calculate dimension by current image file
     * @param string|array $sizeOrName
     * @return stdClass
     */
    public function get_calculate_size($sizeOrName = 'thumbnail'): stdClass {
        if (is_string($sizeOrName)) {
            if (strtolower($sizeOrName) == 'original' || strtolower($sizeOrName) == 'full') {
                $sizeOrName = [
                    'width' => $this->Image->get_width(),
                    'height' => $this->Image->get_height(),
                    'crop' => - 1
                ];
            } else {
                $sizeOrName = get_dimension_from_wp_register_size($sizeOrName);
            }
        }
        if (is_array($sizeOrName) || is_object($sizeOrName)) {
            $sizeOrName = (array)$sizeOrName;
            if (isset($sizeOrName[0])) $sizeOrName['width'] = $sizeOrName[0];
            if (isset($sizeOrName[1])) $sizeOrName['height'] = $sizeOrName[1];
            if (isset($sizeOrName[2])) $sizeOrName['resize_mode'] = $sizeOrName[2];
            if (isset($sizeOrName['crop'])) $sizeOrName['resize_mode'] = $sizeOrName['crop'] === true ? 0 : - 1;
            if (isset($sizeOrName['resize_mode'])) $sizeOrName['crop'] = $sizeOrName['resize_mode'] == 0;
            if ( !isset($sizeOrName['resize_mode'])) $sizeOrName['resize_mode'] = - 1;
        }
        if ($this->Image->get_width() == 0 || $this->Image->get_height() == 0) {
            return (object)$sizeOrName;
        }
        return get_image_calculate_size_from_dimension($sizeOrName['width'], $sizeOrName['height'], $this->Image->get_width(), $this->Image->get_height(), $sizeOrName['resize_mode']);
    }


    /**
     * @return Image_Size[]
     */
    public function get_sizes(): array {
        if ( !is_array($this->sizes)) {
            $originalSize = new Image_Size($this->Image, $this->Image->path()->file()->get_basename());
            $originalSize->set_dimension($this->Image->get_width(), $this->Image->get_height(), false);
            $originalSize->set_name('original');
            $this->sizes = [
                'original' => $originalSize
            ];
            if (property_exists($this->Image->get_attachment_meta(), 'sizes') && is_array($this->Image->get_attachment_meta()->sizes)) {
                foreach ($this->Image->get_attachment_meta()->sizes as $sizeName => $sizeRawData) {
                    if ($sizeName == 'original') continue;
                    if (isset($sizeRawData['file'])) {
                        $crop = false;
                        if (preg_match('/^[\d]{1,}x[\d]{1,}c?$/i', $sizeName) > 0) {
                            $crop = preg_match('/c$/i', $sizeName) > 0;
                        } elseif (function_exists('wp_get_registered_image_subsizes') && array_key_exists($sizeName, wp_get_registered_image_subsizes())) {
                            $crop = wp_get_registered_image_subsizes()[$sizeName]['crop'];
                        }
                        $Image_Size = new Image_Size($this->Image, $sizeRawData['file']);
                        if (isset($sizeRawData['width'])) $Image_Size->set_dimension($sizeRawData['width'], $sizeRawData['height'], $crop);
                        $Image_Size->set_name($sizeName);
                        $this->sizes[$sizeName] = $Image_Size;
                    }
                }
            }
        }
        return $this->sizes;
    }


    /**
     * Return original image size
     * @return Image_Size
     */
    public function get_original_size(): Image_Size {
        if (ImagesFactory::$useWebPExtension) $this->get_sizes()['original']->make(false);
        return $this->get_sizes()['original'];
    }


    /**
     * @param string $sizeOrName
     * @return array|Image_Size[]
     */
    public function get_similar_sizes($sizeOrName = 'medium'): array {
        $sizes_by_delta = [];
        ///
        $dimension = $this->get_calculate_size($sizeOrName);
        $desireArea = $dimension->width * $dimension->height;
        $desireAspect = $dimension->width / $dimension->height;
        $more_that = ($dimension->resize_mode > 0);
        $less_that = ($dimension->resize_mode < 0);
        foreach ($this->get_sizes() as $sizeName => $image_Size) {
            if ($sizeName == '' || $image_Size->get_aspect() == 0) continue;
            $delta = ($image_Size->get_area() - $desireArea) + ($image_Size->get_area() - $desireArea) * abs($image_Size->get_aspect() - $desireAspect);
            if (($more_that && $delta >= 0) || ($less_that && $delta <= 0)) {
                $sizes_by_delta[abs($delta)] = $image_Size;
            }
        }
        ksort($sizes_by_delta, SORT_NATURAL);
        return $sizes_by_delta;
    }


    /**
     * @param string|array|stdClass $sizeOrName
     * @param null                  $makeIfNotExist
     * @param int                   $quality_jpg_png_webp
     * @return Image_Size
     * @version 1.1
     */
    public function get($sizeOrName = 'medium', $makeIfNotExist = null, $quality_jpg_png_webp = 75): Image_Size {
        if ( !$this->Image->is_attachment_exists()) return $this->get_original_size();
        if ( !is_bool($makeIfNotExist)) $makeIfNotExist = ImagesFactory::$makeFileIfNotExists;
        $dimension = $this->get_calculate_size($sizeOrName);
        ///check if dimension is exists and find in wp registered sizes...
        if ($dimension->width == $this->Image->get_width() && $dimension->height == $this->Image->get_height()) return $this->get_original_size();
        foreach ($this->get_sizes() as $image_Size) {
            if ($dimension->resize_mode == 0 && $image_Size->path()->file()->is_exists() && $image_Size->get_width() == $dimension->width && $image_Size->get_height() == $dimension->height) {
                if (ImagesFactory::$useWebPExtension) $image_Size->make(false, $quality_jpg_png_webp);
                return $image_Size;
            } elseif ($dimension->resize_mode == - 1 && $image_Size->path()->file()->is_exists() && (($dimension->width == $image_Size->get_width() && $dimension->height >= $image_Size->get_height()) || ($dimension->width >= $image_Size->get_width() && $dimension->height == $image_Size->get_height()))) {
                if (ImagesFactory::$useWebPExtension) $image_Size->make(false, $quality_jpg_png_webp);
                return $image_Size;
            } elseif ($dimension->resize_mode == 1 && $image_Size->path()->file()->is_exists() && (($dimension->width == $image_Size->get_width() && $dimension->height <= $image_Size->get_height()) || ($dimension->width <= $image_Size->get_width() && $dimension->height == $image_Size->get_height()))) {
                if (ImagesFactory::$useWebPExtension) $image_Size->make(false, $quality_jpg_png_webp);
                return $image_Size;
            }
        }
        ///find similar image size
        if ( !$makeIfNotExist) {
            foreach ($this->get_similar_sizes($sizeOrName) as $image_Size) {
                if (ImagesFactory::$makeFileIfNotExists) $image_Size->make();
                return $image_Size;
            }
        } else {
            $dimension = $this->get_calculate_size($sizeOrName);
            $newSizeName = $dimension->width . 'x' . $dimension->height . ($dimension->resize_mode == 0 ? 'c' : '');
            $Image_Size = new Image_Size($this->Image, $this->get_fileName_bySizeName($dimension->width, $dimension->height, $dimension->resize_mode));
            $Image_Size->set_dimension($dimension->width, $dimension->height, $dimension->resize_mode);
            $Image_Size->set_name($newSizeName);
            $Image_Size->make(false, $quality_jpg_png_webp);
            $this->sizes[$newSizeName] = $Image_Size;
            return $Image_Size;
        }
        return $this->get_original_size();
    }

}