<?php

class ImageMagician {
    private   $fileName;
    private   $image;
    protected $imageResized;
    private   $widthOriginal;     # Always be the original width
    private   $heightOriginal;
    private   $width;         # Current width (width after resize)
    private   $height;
    private   $imageSize;
    private   $fileExtension;

    private $debug      = true;
    private $errorArray = array();

    private $forceStretch        = true;
    private $aggresiveSharpening = false;

    private $transparentArray = ['.png', '.gif'];
    private $keepTransparency = true;
    private $fillColorArray   = ['r' => 255, 'g' => 255, 'b' => 255];

    private $sharpenArray = ['jpg'];

    private $psdReaderPath = null;
    private $filterOverlayPath = null;

    private $isInterlace = false;

    private $captionBoxPositionArray = [];

    private $fontDir = 'fonts';

    private $cropFromTopPercent = 10;

    public function __construct($fileName)
    {
        if (!$this->check_GDInstalled()) {
            throw new RuntimeException('The GD Library is not installed.');
        }

        $this->fileName = $fileName;
        $this->fileExtension = mb_strtolower(strrchr($fileName, '.'));

        $this->image = $this->openImage($fileName);
        $this->imageResized = $this->image;

        if ($this->is_image($this->image)) {
            $this->width = imagesx($this->image);
            $this->widthOriginal = imagesx($this->image);
            $this->height = imagesy($this->image);
            $this->heightOriginal = imagesy($this->image);
            $this->imageSize = getimagesize($this->fileName);
        } else {
            $this->errorArray[] = 'File is not an image';
        }
    }

    /**
     * @param $newWidth
     * @param $newHeight
     * @param $option
     * @param $sharpen
     * @param $autoRotate
     * @return void
     * $option:
     *          0 / exact = defined size;
     *          1 / portrait = keep aspect set height;
     *          2 / landscape = keep aspect set width;
     *          3 / auto = auto;
     *          4 / crop= resize and crop;
     *          $option can also be an array containing options for cropping. E.G., array('crop', 'r')
     * @throws Exception
     */
    public function resizeImage($newWidth, $newHeight, $option = 0, $sharpen = false, $autoRotate = false)
    {
        // *** We can pass in an array of options to change the crop position
        $cropPos = 'm';
        if (is_array($option) && fix_strtolower($option[0]) == 'crop') {
            $cropPos = $option[1];         # get the crop option
        } else {
            if (str_contains($option, '-')) {
                // *** Or pass in a hyphen seperated option
                $optionPiecesArray = explode('-', $option);
                $cropPos = end($optionPiecesArray);
            }
        }
        $option = $this->prepOption($option);

        if (!$this->is_image($this->image)) {
            throw new RuntimeException('file ' . $this->getFileName() . ' is missing or invalid');
        }

        $dimensionsArray = $this->getDimensions($newWidth, $newHeight, $option);
        $optimalWidth = $dimensionsArray['optimalWidth'];
        $optimalHeight = $dimensionsArray['optimalHeight'];

        $this->imageResized = imagecreatetruecolor($optimalWidth, $optimalHeight);
        $this->keepTransparancy($optimalWidth, $optimalHeight, $this->imageResized);
        imagecopyresampled($this->imageResized, $this->image, 0, 0, 0, 0, $optimalWidth, $optimalHeight, $this->width, $this->height);

        // *** If '4', then crop too
        if ($option == 4 || $option == 'crop') {
            if (($optimalWidth >= $newWidth && $optimalHeight >= $newHeight)) {
                $this->crop($optimalWidth, $optimalHeight, $newWidth, $newHeight, $cropPos);
            }
        }

        if ($autoRotate) {
            $exifData = $this->getExif();
            if (count($exifData) > 0) {
                switch ($exifData['orientation'])
                {
                    case 8:
                        $this->imageResized = imagerotate($this->imageResized, 90, 0);
                        break;
                    case 3:
                        $this->imageResized = imagerotate($this->imageResized, 180, 0);
                        break;
                    case 6:
                        $this->imageResized = imagerotate($this->imageResized, -90, 0);
                        break;
                }
            }
        }

        // *** Sharpen image (if jpg and the user wishes to do so)
        if ($sharpen && in_array($this->fileExtension, $this->sharpenArray))
        {
            $this->sharpen();
        }

    }

    /**
     * Add watermark image
     *
     * @param $watermarkImage - $watermark: The watermark image
     * @param $pos - Could be a pre-determined position such as:
     * @param int $padding - If using a pre-determined position you can
    * #         adjust the padding from the edges by passing an amount
    * #         in pixels. If using co-ordinates, this value is ignored.
     * @param int $opacity
     * @return void
     *
     * #       (str) $pos:
    * #           tl = top left,
    * #           t  = top (middle),
    * #           tr = top right,
    * #           l  = left,
    * #           m  = middle,
    * #           r  = right,
    * #           bl = bottom left,
    * #           b  = bottom (middle),
    * #           br = bottom right
    * #         Or, it could be a co-ordinate position such as: 50x100
     */
    public function addWatermark($watermarkImage, $pos, int $padding = 0, int $opacity = 0): void
    {
        $stamp = $this->openImage($watermarkImage);
        $im = $this->imageResized;

        $sx = imagesx($stamp);
        $sy = imagesy($stamp);

        $posArray = $this->calculatePosition($pos, $padding, $sx, $sy);
        $x = $posArray['width'];
        $y = $posArray['height'];

        if (fix_strtolower(strrchr($watermarkImage, '.')) == '.png') {
            $opacity = $this->invertTransparency($opacity, 100);
            $this->filterOpacity($stamp, $opacity);
        }

        imagecopy(
            $im,
            $stamp,
            $x, $y,
            0, 0,
            imagesx($stamp), imagesy($stamp)
        );
    }

    /**
     * @param $savePath - Where to save the image including filename:
     * @param $imageQuality - image quality you want the image saved at 0-100
     * @return void
     * @throws Exception
     *
     * jpg has a quality setting 0-100 (100 being the best)
     * png has a quality setting 0-9 (0 being the best)
     */
    public function saveImage($savePath, $imageQuality = 100): void
    {
        if (!$this->is_image($this->imageResized)) {
            throw new RuntimeException('saveImage: This is not a resource.');
        }

        $fileInfoArray = pathInfo($savePath);
        clearstatcache();

        if ( ! is_writable($fileInfoArray['dirname'])) {
            throw new RuntimeException('The path is not writable. Please check your permissions.');
        }

        $extension = strrchr($savePath, '.');
        $extension = fix_strtolower($extension);

        switch ($extension)
        {
            case '.jpg':
            case '.jpeg':
                $this->checkInterlaceImage($this->isInterlace);
                imagejpeg($this->imageResized, $savePath, $imageQuality);
                break;

            case '.gif':
                $this->checkInterlaceImage($this->isInterlace);
                imagegif($this->imageResized, $savePath);
                break;

            case '.png':
                // *** Scale quality from 0-100 to 0-9
                $scaleQuality = round(($imageQuality / 100) * 9);

                // *** Invert quality setting as 0 is best, not 9
                $invertScaleQuality = 9 - $scaleQuality;

                $this->checkInterlaceImage($this->isInterlace);
                imagepng($this->imageResized, $savePath, $invertScaleQuality);
                break;

            case '.bmp':
                imagebmp($this->imageResized, $savePath);
                break;
            default:
                throw new RuntimeException('This file type (' . $extension . ') is not supported. File not saved.');
                break;
        }
    }


    // ========================================================================================================= //

    private function openImage($file)
    {
        $extension = mime_content_type($file);
        $extension = fix_strtolower($extension);
        $extension = str_replace('image/', '', $extension);

        return match ($extension) {
            'jpg', 'jpeg'   =>  @imagecreatefromjpeg($file),
            'gif'           =>  @imagecreatefromgif($file),
            'png'           =>  @imagecreatefrompng($file),
            'bmp'           =>  @imagecreatefrombmp($file),
            'webp'          =>  @imagecreatefromwebp($file),
            default => false,
        };
    }

    /**
     * @param $image
     * @return bool
     */
    private function is_image($image): bool
    {
        return
            PHP_MAJOR_VERSION > 7
            ? $image instanceof GdImage
            : is_resource($image);
    }


    /**
     * Test to see if GD is installed
     *
     * @return bool
     */
    private function check_GDInstalled(): bool
    {
        return (extension_loaded('gd') && function_exists('gd_info'));
    }

    /**
     * Check if a string starts with a specific pattern
     *
     * @param $needle
     * @param $haystack
     * @return bool
     */
    private function checkStringStartsWith($needle, $haystack): bool
    {
		return str_starts_with($haystack, $needle);
	}

    public function getFileName()
    {
        return $this->fileName;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getErrors()
    {
        return $this->errorArray;
    }

    public function setFile($fileName)
    {
        self::__construct($fileName);
    }

    public function setTransparency($bool)
    {
        $this->keepTransparency = $bool;
    }

    public function setFillColor($value)
    {
        $colorArray = $this->formatColor($value);
        $this->fillColorArray = $colorArray;
    }

    /**
     * (mixed) $value: (array) Could be an array of RGB
     * (str) Could be hex #ffffff or #fff, fff, ffffff
     *
     * @param $value
     * @return array|int[]
     */
    protected function formatColor($value)
    {
        $rgbArray = [];

        // *** If it's an array it should be R, G, B
        if (is_array($value))
        {
            if (key($value) == 0 && count($value) == 3)
            {

                $rgbArray['r'] = $value[0];
                $rgbArray['g'] = $value[1];
                $rgbArray['b'] = $value[2];

            }
            else
            {
                $rgbArray = $value;
            }
        }
        else
        {
            if (mb_strtolower($value) == 'transparent') {
                $rgbArray = [
                    'r' => 255,
                    'g' => 255,
                    'b' => 255,
                    'a' => 127
                ];
            } else {
                $rgbArray = $this->hex2dec($value);
            }
        }

        return $rgbArray;
    }

    /**
     *  Convert #hex color to RGB
     * @param $hex
     * @return array
     */
    public function hex2dec($hex)
    {
        $color = str_replace('#', '', $hex);

        if (strlen($color) == 3) {
            $color = $color . $color;
        }

        return array(
            'r' => hexdec(substr($color, 0, 2)),
            'g' => hexdec(substr($color, 2, 2)),
            'b' => hexdec(substr($color, 4, 2)),
            'a' => 0
        );
    }

    /**
     * Prep option like change the passed in option to lowercase
     * (str/int) $option: eg. 'exact', 'crop'. 0, 4
     *
     * @param $option
     * @return array|false|string|string[]|null
     * @throws Exception
     */
    private function prepOption($option):mixed
    {
        if (is_array($option))
        {
            if (mb_strtolower($option[0]) == 'crop' && count($option) == 2) {
                return 'crop';
            } else {
                throw new Exception('Crop resize option array is badly formatted.');
            }
        }
        else {
            if (str_contains($option, 'crop')) {
                return 'crop';
            }
        }

        if (is_string($option)) {
            return mb_strtolower($option);
        }

        return $option;
    }

    private function getDimensions($newWidth, $newHeight, $option)
        # Author:     Jarrod Oberto
        # Date:       17-11-09
        # Purpose:    Get new image dimensions based on user specificaions
        # Param in:   $newWidth:
        #             $newHeight:
        # Param out:  Array of new width and height values
        # Reference:
        # Notes:    If $option = 3 then this function is call recursivly
        #
        #       To clarify the $option input:
        #               0 = The exact height and width dimensions you set.
        #               1 = Whatever height is passed in will be the height that
        #                   is set. The width will be calculated and set automatically
        #                   to a the value that keeps the original aspect ratio.
        #               2 = The same but based on the width.
        #               3 = Depending whether the image is landscape or portrait, this
        #                   will automatically determine whether to resize via
        #                   dimension 1,2 or 0.
        #               4 = Resize the image as much as possible, then crop the
        #         remainder.
    {

        switch (strval($option))
        {
            case '0':
            case 'exact':
                $optimalWidth = $newWidth;
                $optimalHeight = $newHeight;
                break;
            case '1':
            case 'portrait':
                $dimensionsArray = $this->getSizeByFixedHeight($newWidth, $newHeight);
                $optimalWidth = $dimensionsArray['optimalWidth'];
                $optimalHeight = $dimensionsArray['optimalHeight'];
                break;
            case '2':
            case 'landscape':
                $dimensionsArray = $this->getSizeByFixedWidth($newWidth, $newHeight);
                $optimalWidth = $dimensionsArray['optimalWidth'];
                $optimalHeight = $dimensionsArray['optimalHeight'];
                break;
            case '3':
            case 'auto':
                $dimensionsArray = $this->getSizeByAuto($newWidth, $newHeight);
                $optimalWidth = $dimensionsArray['optimalWidth'];
                $optimalHeight = $dimensionsArray['optimalHeight'];
                break;
            case '4':
            case 'crop':
                $dimensionsArray = $this->getOptimalCrop($newWidth, $newHeight);
                $optimalWidth = $dimensionsArray['optimalWidth'];
                $optimalHeight = $dimensionsArray['optimalHeight'];
                break;
        }

        return array( 'optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight );
    }

    private function getSizeByFixedHeight($newWidth, $newHeight): array
    {
        if ( ! $this->forceStretch)
        {
            if ($this->height < $newHeight) {
                return array( 'optimalWidth' => $this->width, 'optimalHeight' => $this->height );
            }
        }

        $ratio = $this->width / $this->height;

        $newWidth = $newHeight * $ratio;

        return array( 'optimalWidth' => $newWidth, 'optimalHeight' => $newHeight );
    }

    private function getSizeByFixedWidth($newWidth, $newHeight): array
    {
        if ( ! $this->forceStretch) {
            if ($this->width < $newWidth) {
                return array( 'optimalWidth' => $this->width, 'optimalHeight' => $this->height );
            }
        }

        $ratio = $this->height / $this->width;

        $newHeight = $newWidth * $ratio;

        return array( 'optimalWidth' => $newWidth, 'optimalHeight' => $newHeight );
    }

    /**
     * Depending on the height, choose to resize by 0, 1, or 2
     *
     * @param $newWidth
     * @param $newHeight
     * @return array
     */
    private function getSizeByAuto($newWidth, $newHeight)
    {
        if ( ! $this->forceStretch) {
            if ($this->width < $newWidth && $this->height < $newHeight) {
                return array( 'optimalWidth' => $this->width, 'optimalHeight' => $this->height );
            }
        }

        // *** Image to be resized is wider (landscape)
        if ($this->height < $this->width) {
            $dimensionsArray = $this->getSizeByFixedWidth($newWidth, $newHeight);
            $optimalWidth = $dimensionsArray['optimalWidth'];
            $optimalHeight = $dimensionsArray['optimalHeight'];
        } elseif ($this->height > $this->width){
            // *** Image to be resized is taller (portrait)
            $dimensionsArray = $this->getSizeByFixedHeight($newWidth, $newHeight);
            $optimalWidth = $dimensionsArray['optimalWidth'];
            $optimalHeight = $dimensionsArray['optimalHeight'];
        } else {
            // *** Image to be resizerd is a square
            if ($newHeight < $newWidth) {
                $dimensionsArray = $this->getSizeByFixedWidth($newWidth, $newHeight);
                $optimalWidth = $dimensionsArray['optimalWidth'];
                $optimalHeight = $dimensionsArray['optimalHeight'];
            } else {
                if ($newHeight > $newWidth) {
                    $dimensionsArray = $this->getSizeByFixedHeight($newWidth, $newHeight);
                    $optimalWidth = $dimensionsArray['optimalWidth'];
                    $optimalHeight = $dimensionsArray['optimalHeight'];
                } else {
                    // *** Sqaure being resized to a square
                    $optimalWidth = $newWidth;
                    $optimalHeight = $newHeight;
                }
            }
        }

        return array( 'optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight );
    }

    /**
     * @param $newWidth
     * @param $newHeight
     * @return array
        # Notes:      The optimal width and height return are not the same as the
        #       same as the width and height passed in. For example:
        #
        #
        #   |-----------------|       |------------|       |-------|
        #   |                 |   =>  |**|      |**|   =>  |       |
        #   |                 |       |**|      |**|       |       |
        #   |                 |       |------------|       |-------|
        #   |-----------------|
        #        original                optimal             crop
        #          size                   size               size
        #  Fig      1                      2                  3
        #
        #       300 x 250             150 x 125          150 x 100
        #
        #    The optimal size is the smallest size (that is closest to the crop size)
        #    while retaining proportion/ratio.
        #
        #  The crop size is the optimal size that has been cropped on one axis to
        #  make the image the exact size specified by the user.
        #
        #               * represent cropped area
        #
     */
    private function getOptimalCrop($newWidth, $newHeight): array
    {
        if ( ! $this->forceStretch) {
            if ($this->width < $newWidth && $this->height < $newHeight){
                return array( 'optimalWidth' => $this->width, 'optimalHeight' => $this->height );
            }
        }

        $heightRatio = $this->height / $newHeight;
        $widthRatio = $this->width / $newWidth;

        $optimalRatio = min($heightRatio, $widthRatio);

        $optimalHeight = round($this->height / $optimalRatio);
        $optimalWidth = round($this->width / $optimalRatio);

        return array( 'optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight );
    }

    /**
     * Keep transparency for png and gif image
     *
     * @param $width
     * @param $height
     * @param $im
     * @return void
     */
    private function keepTransparancy($width, $height, $im)
    {
        // *** If PNG, perform some transparency retention actions (gif untested)
        if (in_array($this->fileExtension, $this->transparentArray) && $this->keepTransparency) {
            imagealphablending($im, false);
            imagesavealpha($im, true);
            $transparent = imagecolorallocatealpha($im, 255, 255, 255, 127);
            imagefilledrectangle($im, 0, 0, $width, $height, $transparent);
        } else {
            $color = imagecolorallocate($im, $this->fillColorArray['r'], $this->fillColorArray['g'], $this->fillColorArray['b']);
            imagefilledrectangle($im, 0, 0, $width, $height, $color);
        }
    }

    /**
     * Crops the image
     *
     * @param $optimalWidth
     * @param $optimalHeight
     * @param $newWidth
     * @param $newHeight
     * @param $cropPos
     * @return void
     */
    private function crop($optimalWidth, $optimalHeight, $newWidth, $newHeight, $cropPos)
    {

        // *** Get cropping co-ordinates
        $cropArray = $this->getCropPlacing($optimalWidth, $optimalHeight, $newWidth, $newHeight, $cropPos);
        $cropStartX = $cropArray['x'];
        $cropStartY = $cropArray['y'];

        // *** Crop this bad boy
        $crop = imagecreatetruecolor($newWidth, $newHeight);
        $this->keepTransparancy($optimalWidth, $optimalHeight, $crop);
        imagecopyresampled($crop, $this->imageResized, 0, 0, $cropStartX, $cropStartY, $newWidth, $newHeight, $newWidth, $newHeight);

        $this->imageResized = $crop;

        // *** Set new width and height to our variables
        $this->width = $newWidth;
        $this->height = $newHeight;
    }

    /**
     * Set the cropping area.
     *
     * @param $optimalWidth
     * @param $optimalHeight
     * @param $newWidth
     * @param $newHeight
     * @param $pos
     * @return array
     */
    private function getCropPlacing($optimalWidth, $optimalHeight, $newWidth, $newHeight, $pos = 'm'): array
    {
        $pos = fix_strtolower($pos);
        if (str_contains($pos, 'x')) {
            $pos = str_replace(' ', '', $pos);

            $xyArray = explode('x', $pos);
            list($cropStartX, $cropStartY) = $xyArray;
        } else {
            /*switch ($pos)
            {
                case 'tl':
                    $cropStartX = 0;
                    $cropStartY = 0;
                    break;

                case 't':
                    $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                    $cropStartY = 0;
                    break;

                case 'tr':
                    $cropStartX = $optimalWidth - $newWidth;
                    $cropStartY = 0;
                    break;

                case 'l':
                    $cropStartX = 0;
                    $cropStartY = ($optimalHeight / 2) - ($newHeight / 2);
                    break;

                case 'm':
                    $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                    $cropStartY = ($optimalHeight / 2) - ($newHeight / 2);
                    break;

                case 'r':
                    $cropStartX = $optimalWidth - $newWidth;
                    $cropStartY = ($optimalHeight / 2) - ($newHeight / 2);
                    break;

                case 'bl':
                    $cropStartX = 0;
                    $cropStartY = $optimalHeight - $newHeight;
                    break;

                case 'b':
                    $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                    $cropStartY = $optimalHeight - $newHeight;
                    break;

                case 'br':
                    $cropStartX = $optimalWidth - $newWidth;
                    $cropStartY = $optimalHeight - $newHeight;
                    break;

                case 'auto':
                    // *** If image is a portrait crop from top, not center. v1.5
                    if ($optimalHeight > $optimalWidth)
                    {
                        $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                        $cropStartY = ($this->cropFromTopPercent / 100) * $optimalHeight;
                    }
                    else
                    {

                        // *** Else crop from the center
                        $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                        $cropStartY = ($optimalHeight / 2) - ($newHeight / 2);
                    }
                    break;

                default:
                    // *** Default to center
                    $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                    $cropStartY = ($optimalHeight / 2) - ($newHeight / 2);
                    break;
            }*/
            [$cropStartX, $cropStartY] = match ($pos) {
                'tl' => [0, 0],
                't'  => [($optimalWidth / 2) - ($newWidth / 2), 0],
                'tr' => [$optimalWidth - $newWidth, 0],
                'l'  => [0, ($optimalHeight / 2) - ($newHeight / 2)],
                'm'  => [($optimalWidth / 2) - ($newWidth / 2), ($optimalHeight / 2) - ($newHeight / 2)],
                'r'  => [$optimalWidth - $newWidth, ($optimalHeight / 2) - ($newHeight / 2)],
                'bl' => [0, $optimalHeight - $newHeight],
                'b'  => [($optimalWidth / 2) - ($newWidth / 2), $optimalHeight - $newHeight],
                'br' => [$optimalWidth - $newWidth, $optimalHeight - $newHeight],
                'auto' => $optimalHeight > $optimalWidth
                    ? [($optimalWidth / 2) - ($newWidth / 2), ($this->cropFromTopPercent / 100) * $optimalHeight]
                    : [($optimalWidth / 2) - ($newWidth / 2), ($optimalHeight / 2) - ($newHeight / 2)],
                default => [($optimalWidth / 2) - ($newWidth / 2), ($optimalHeight / 2) - ($newHeight / 2)],
            };
        }

        return array( 'x' => $cropStartX, 'y' => $cropStartY );
    }

    /**
     * Get image EXIF data
     *
     * @return array
     * @throws Exception
     */
    public function getExif(): array
    {
        if ( ! $this->check_EXIFInstalled())
        {
            throw new RuntimeException('The EXIF Library is not installed.');
        }

        if ( ! file_exists($this->fileName))
        {
            throw new RuntimeException('Image not found.');
        }

        if ($this->fileExtension != '.jpg')
        {
            throw new RuntimeException('Metadata not supported for this image type.');
        }

        $exifData = exif_read_data($this->fileName, 'IFD0') ?: [];

        $apertureValue
            = ($ev = $exifData['ApertureValue'] ?? '') && count($ap = explode('/', $ev)) == 2
            ? round($ap[0]/$ap[1], 2, PHP_ROUND_HALF_DOWN).' EV'
            : '';

        // *** Format the focal length
        $focalLength
            = ($fl = $exifData['FocalLength'] ?? '') && count($flp = explode('/', $fl)) == 2
            ? ($flp[0]/$flp[1]).' mm'
            : '';


        $fNumber
            = ($fn = $exifData['FNumber'] ?? '') && count($fnp = explode('/', $fn)) == 2
            ? $fnp[0]/$fnp[1]
            : '';

        return [
            'make'                  => $exifData['Make'] ?? '',
            'model'                 => $exifData['Model'] ?? '',
            'date'                  => $exifData['DateTime'] ?? '',
            'exposure time'         => isset($exifData['ExposureTime']) ? $exifData['ExposureTime'].' sec.' : '',
            'aperture value'        => $apertureValue,
            'f-stop'                => $exifData['COMPUTED']['ApertureFNumber'] ?? '',
            'fnumber'               => $exifData['FNumber'] ?? '',
            'fnumber value'         => $fNumber,
            'iso'                   => $exifData['ISOSpeedRatings'] ?? '',
            'focal length'          => $focalLength,
            'exposure program'      => isset($exifData['ExposureProgram']) ? $this->resolveExposureProgram($exifData['ExposureProgram']) : '',
            'metering mode'         => $this->resolveMeteringMode($exifData['MeteringMode'] ?? 0),
            'flash status'          => $this->resolveFlash($exifData['Flash'] ?? 0),
            'creator'               => $exifData['Artist'] ?? '',
            'copyright'             => $exifData['Copyright'] ?? '',
            'orientation'           => $exifData['Orientation'] ?? ''
        ];
    }

    /**
     * @return bool
     */
    private function check_EXIFInstalled(): bool
    {
        return extension_loaded('exif');
    }

    private function resolveExposureProgram($ep): string
    {
        return match ($ep) {
            1 => 'manual',
            2 => 'normal program',
            3 => 'aperture priority',
            4 => 'shutter priority',
            5 => 'creative program',
            6 => 'action program',
            7 => 'portrait mode',
            8 => 'landscape mode',
            default => '', // Все остальные случаи, включая 0
        };
    }

    private function resolveMeteringMode(int $mm): string
    {
        return match ($mm) {
            0 => 'unknown',
            1 => 'average',
            2 => 'center weighted average',
            3 => 'spot',
            4 => 'multi spot',
            5 => 'pattern',
            6 => 'partial',
            255 => 'other',
            default => (string) $mm,
        };
    }

    private function resolveFlash(int $flash): string
    {
        return match ($flash) {
            0 => 'flash did not fire',
            1 => 'flash fired',
            5 => 'strobe return light not detected',
            7 => 'strobe return light detected',
            9 => 'flash fired, compulsory flash mode',
            13 => 'flash fired, compulsory flash mode, return light not detected',
            15 => 'flash fired, compulsory flash mode, return light detected',
            16 => 'flash did not fire, compulsory flash mode',
            24 => 'flash did not fire, auto mode',
            25 => 'flash fired, auto mode',
            29 => 'flash fired, auto mode, return light not detected',
            31 => 'flash fired, auto mode, return light detected',
            32 => 'no flash function',
            65 => 'flash fired, red-eye reduction mode',
            69 => 'flash fired, red-eye reduction mode, return light not detected',
            71 => 'flash fired, red-eye reduction mode, return light detected',
            73 => 'flash fired, compulsory flash mode, red-eye reduction mode',
            77 => 'flash fired, compulsory flash mode, red-eye reduction mode, return light not detected',
            79 => 'flash fired, compulsory flash mode, red-eye reduction mode, return light detected',
            89 => 'flash fired, auto mode, red-eye reduction mode',
            93 => 'flash fired, auto mode, return light not detected, red-eye reduction mode',
            95 => 'flash fired, auto mode, return light detected, red-eye reduction mode',
            default => 'unknown', // или (string) $flash для числового значения
        };
    }

    /**
     * @return void
     */
    private function sharpen(): void
    {
        // ***
        if ($this->aggresiveSharpening) {
            # A more aggressive sharpening solution

            $sharpenMatrix = [
                [-1, -1, -1],
                [-1, 16, -1],
                [-1, -1, -1]
            ];
            $divisor = 8;

        } else {
            # More subtle and personally more desirable
            $sharpness = $this->findSharp($this->widthOriginal, $this->width);

            $sharpenMatrix = [
                [-1, -2, -1],
                [-2, $sharpness + 12, -2], //Lessen the effect of a filter by increasing the value in the center cell
                [-1, -2, -1]
            ];
            $divisor = $sharpness; // adjusts brightness
        }

        $offset = 0;
        imageconvolution($this->imageResized, $sharpenMatrix, $divisor, $offset);
    }

    /**
     * Find optimal sharpness
     *
     * @param $orig
     * @param $final
     * @return float
     */
    private function findSharp($orig, $final): float
    {
        $final = $final * (750.0 / $orig);
        $a = 52;
        $b = -0.27810650887573124;
        $c = .00047337278106508946;

        $result = $a + $b * $final + $c * $final * $final;

        return max(round($result), 0);
    }

    /**
     * Calculate the x, y pixel coordinates of the asset to place
     *
     * @param $pos
     * @param $padding
     * @param $assetWidth
     * @param $assetHeight
     * @param bool $upperLeft
     * @return array
     *
     * Params in:  (str) $pos: Either something like: "tl", "l", "br" or an
     *         exact position like: "100x50"
     *        (int) $padding: The amount of padding from the edge. Only
     *              used for the predefined $pos.
     *        (int) $assetWidth: The width of the asset to add to the image
     *       (int) $assetHeight: The height of the asset to add to the image
     *       (bol) $upperLeft: if true, the asset will be positioned based
     *         on the upper left x, y coords. If false, it means you're
     *         using the lower left as the basepoint and this will
     *         convert it to the upper left position
     * Params out:
     * NOTE: this is done from the UPPER left corner!! But will convert lower
     *       left basepoints to upper left if $upperleft is set to false
     */
    private function calculatePosition($pos, $padding, $assetWidth, $assetHeight, bool $upperLeft = true): array
    {
        $pos = mb_strtolower($pos);

        if (str_contains($pos, 'x'))
        {
            $pos = str_replace(' ', '', $pos);
            [$width, $height] = [0, 0] + explode('x', $pos);
        }
        else
        {
            switch ($pos) {
                case 'tl': {
                    $width = 0 + $padding;
                    $height = 0 + $padding;
                    break;
                }
                case 't': {
                    $width = ($this->width / 2) - ($assetWidth / 2);
                    $height = 0 + $padding;
                    break;
                }
                case 'tr': {
                    $width = $this->width - $assetWidth - $padding;
                    $height = 0 + $padding;;
                    break;
                }
                case 'l': {
                    $width = 0 + $padding;
                    $height = ($this->height / 2) - ($assetHeight / 2);
                    break;
                }
                case 'm': {
                    $width = ($this->width / 2) - ($assetWidth / 2);
                    $height = ($this->height / 2) - ($assetHeight / 2);
                    break;
                }
                case 'r': {
                    $width = $this->width - $assetWidth - $padding;
                    $height = ($this->height / 2) - ($assetHeight / 2);
                    break;
                }
                case 'bl': {
                    $width = 0 + $padding;
                    $height = $this->height - $assetHeight - $padding;
                    break;
                }
                case 'b': {
                    $width = ($this->width / 2) - ($assetWidth / 2);
                    $height = $this->height - $assetHeight - $padding;
                    break;
                }
                case 'br': {
                    $width = $this->width - $assetWidth - $padding;
                    $height = $this->height - $assetHeight - $padding;
                    break;
                }
                default: {
                    $width = 0;
                    $height = 0;
                    break;
                }
            }
        }

        if (!$upperLeft) {
            $height = $height + $assetHeight;
        }

        return array( 'width' => $width, 'height' => $height );
    }

    /**
     * 1) Convert the range from 0-127 to 0-100
     * 2) Inverts value to 100 is not transparent while 0 is fully transparent (like Photoshop)
     *
     * @param float $value
     * @param float $originalMax
     * @param bool $invert
     * @return float
     */
    private function invertTransparency(float $value, float $originalMax, bool $invert = true): float
    {
        $value = min(max($value, 0), $originalMax);

        return $invert
            ? $originalMax - (($value / 100) * $originalMax)
            : ($value / 100) * $originalMax;
    }

    /**
     * Change opacity of image
     *
     * @param $img
     * @param $opacity
     * @return bool
     */
    private function filterOpacity(&$img, $opacity = 75)
    {
        if (!isset($opacity)) {
            return false;
        }

        if ($opacity == 100) {
            return true;
        }

        $opacity /= 100;

        //get image width and height
        $w = imagesx($img);
        $h = imagesy($img);

        //turn alpha blending off
        imagealphablending($img, false);

        //find the most opaque pixel in the image (the one with the smallest alpha value)
        $minalpha = 127;

        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $alpha = (imagecolorat($img, $x, $y) >> 24) & 0xFF;
                if ($alpha < $minalpha) {
                    $minalpha = $alpha;
                }
            }
        }

        //loop through image pixels and modify alpha for each
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                //get current alpha value (represents the TANSPARENCY!)
                $colorxy = imagecolorat($img, $x, $y);
                $alpha = ($colorxy >> 24) & 0xFF;
                //calculate new alpha
                if ($minalpha !== 127) {
                    $alpha = 127 + 127 * $opacity * ($alpha - 127) / (127 - $minalpha);
                } else {
                    $alpha += 127 * $opacity;
                }

                //get the color index with new alpha
                $alphacolorxy = imagecolorallocatealpha($img, ($colorxy >> 16) & 0xFF, ($colorxy >> 8) & 0xFF, $colorxy & 0xFF, $alpha);
                //set pixel with the new color + opacity
                if ( ! imagesetpixel($img, $x, $y, $alphacolorxy)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * jpg will use progressive (they don't use interace)
     *
     * @param $isEnabled
     * @return void
     */
    private function checkInterlaceImage($isEnabled): void
    {
        if ($isEnabled) {
            imageinterlace($this->imageResized, $isEnabled);
        }
    }

    public function getOriginalHeight()
    {
        return $this->heightOriginal;
    }

    public function getOriginalWidth()
    {
        return $this->widthOriginal;
    }


}
