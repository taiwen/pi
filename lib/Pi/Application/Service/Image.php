<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt BSD 3-Clause License
 * @package         Service
 */

namespace Pi\Application\Service;

use Pi;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\FontInterface;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Palette\CMYK;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Palette\Grayscale;

/**
 * Image handler service
 *
 * Use {@link Imagaine} as image manipulation library
 *
 *
 * - Size options, applicable to `resize`, `thumbnail`, `crop`;
 *      in `thumbnail`, aspect ratio is always kept; in `crop`, aspect ratio is not applicable
 *
 * ```
 *  // With specified width and height
 *  $size = array(<width>, <height>);
 *
 *  // With specified width
 *  $size = array(<width>, 0);
 *
 *  // With specified height
 *  $size = array(0, <height>);
 *
 *  // Square size with specified integer width (height)
 *  $size = 500; // integer, in pix
 *
 *  // With specified width and height but keep aspect ratio
 *  $size = array(<width>, <height>, true);
 *
 *  // With size ratio (0.0 - 1.0)
 *  $size = 0.5;
 * ```
 *
 * Use cases:
 *
 * - Watermark
 * ```
 *  // Use specific watermark
 *  Pi::service('image')->watermark(
 *      <path/to/source/image>,
 *      <path/to/saved/image>
 *      <path/to/watermark/image>,
 *      <top-left|bottom-right|array(<x>, <y>)>
 *  );
 *
 *  // Use system watermark
 *  Pi::service('image')->watermark(
 *      <path/to/source/image>,
 *      <path/to/saved/image>,
 *      '',
 *      <top-left|bottom-right|array(<x>, <y>)>
 *  );
 *
 *  // Overwrite original image
 *  Pi::service('image')->watermark(
 *      <path/to/source/image>,
 *      '',
 *      <path/to/watermark/image>,
 *      <top-left|bottom-right|array(<x>, <y>)>
 *  );
 *  Pi::service('image')->watermark(
 *      <path/to/source/image>
 *  );
 * ```
 *
 * - Crop
 * ```
 *  // Crop with specified size
 *  Pi::service('image')->crop(
 *      <path/to/source/image>,
 *      array(<X>, <Y>),
 *      array(<width>, <height>),
 *      <path/to/saved/image>
 *  );
 *
 *  // Crop with ratio size
 *  Pi::service('image')->crop(
 *      <path/to/source/image>,
 *      array(<X>, <Y>),
 *      0.5,
 *      <path/to/saved/image>
 *  );
 *
 *  // Overwrite original image
 *  Pi::service('image')->crop(
 *      <path/to/source/image>,
 *      array(<X>, <Y>),
 *      array(<width>, <height>)
 *  );
 * ```
 *
 * - Resize
 * ```
 *  // Resize with specified target and options
 *  Pi::service('image')->resize(
 *      <path/to/source/image>,
 *      <size>,
 *      <path/to/saved/image>,
 *      <filter>,
 *      array(<options>)
 *  );
 *
 *  // Overwrite original image
 *  Pi::service('image')->resize(
 *      <path/to/source/image>,
 *      <size>
 *  );
 * ```
 *
 * - Thumbnail
 * ```
 *  // With specified target and options
 *  Pi::service('image')->thumbnail(
 *      <path/to/source/image>,
 *      <size>,
 *      <path/to/saved/image>,
 *      <mode>,
 *      array(<options>)
 *  );
 *
 *  // Overwrite original image
 *  Pi::service('image')->thumbnail(
 *      <path/to/source/image>,
 *      <size>
 *  );
 * ```
 *
 * - Rotate
 * ```
 *  // Rotate
 *  Pi::service('image')->rotate(
 *      <path/to/source/image>,
 *      <angle>,
 *      <path/to/saved/image>,
 *      <background-color>
 *  );
 *
 *  // Overwrite original image
 *  Pi::service('image')->rotate(
 *      <path/to/source/image>,
 *      <angle>,
 *      <background-color>
 *  );
 * ```
 *
 * - Paste
 * ```
 *  // Paste
 *  Pi::service('image')->paste(
 *      <path/to/source/image>,
 *      <path/to/child/image>,
 *      array(<X>, <Y>),
 *      <path/to/saved/image>
 *  );
 *
 *  // Overwrite original image
 *  Pi::service('image')->paste(
 *      <path/to/source/image>,
 *      <path/to/child/image>,
 *      array(<X>, <Y>)
 *  );
 * ```
 *
 * @author Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 * @see https://github.com/avalanche123/Imagine
 */
class Image extends AbstractService
{
    /** {@inheritDoc} */
    protected $fileIdentifier = 'image';

    /** @var string Image manipulation driver */
    protected $driver;

    /**
     * Get image driver
     *
     * @param string $driver
     * @return ImagineInterface|bool
     */
    public function getDriver($driver = '')
    {
        if (null === $this->driver) {
            $driverName = $driver ?: $this->getOption('driver');
            $driverClass = false;
            switch ($driverName) {
                case 'gd':
                    if (function_exists('gd_info')) {
                        $driverClass = 'Imagine\Gd\Imagine';
                    }
                    break;
                case 'gmagick':
                    if (class_exists('Gmagick')) {
                        $driverClass = 'Imagine\Gmagick\Imagine';
                    }
                    break;
                case 'imagick':
                    if (class_exists('Imagick')) {
                        $driverClass = 'Imagine\Gmagick\Imagine';
                    }
                    break;
                case 'auto':
                default:
                    if (function_exists('gd_info')) {
                        $driverClass = 'Imagine\Gd\Imagine';
                    } elseif (class_exists('Gmagick')) {
                        $driverClass = 'Imagine\Gmagick\Imagine';
                    } elseif (class_exists('Imagick')) {
                        $driverClass = 'Imagine\Gmagick\Imagine';
                    }
                    break;
            }
            if ($driverClass) {
                $this->driver = new $driverClass;
            } else {
                $this->driver = false;
            }
        }

        return $this->driver;
    }

    /**
     * Canonize Box element
     *
     * @param array|int|Box $width   Width or width and height, or Box
     * @param int           $height  Height
     *
     * @return Box
     */
    public function box($width, $height = 0)
    {
        if ($width instanceof Box) {
            $result = $width;
        } elseif (is_array($width)) {
            $result = new Box($width[0], $width[1]);
        } else {
            $result = new Box($width, $height);
        }

        return $result;
    }

    /**
     * Canonize Point element
     *
     * @param array|int|Point $x X or X and Y, or Point
     * @param int $y
     *
     * @return Point
     */
    public function point($x, $y = 0)
    {
        if ($x instanceof Point) {
            $result = $x;
        } elseif (is_array($x)) {
            $result = new Point($x[0], $x[1]);
        } else {
            $result = new Point($x, $y);
        }

        return $result;
    }

    /**
     * Canonize Color element
     *
     * @param array|string|ColorInterface $color Color value or color and alpha, or Color
     * @param int $alpha
     *
     * @return ColorInterface
     */
    public function color($color, $alpha = 0)
    {
        $result = null;
        if ($color instanceof ColorInterface) {
            $result = $color;
        } else {
            if (!is_array($color)) {
                $color = array($color);
            }
            switch (count($color)) {
                case 1:
                    $palette = new Grayscale;
                    break;
                case 3:
                    $palette = new RGB;
                    break;
                case 4:
                    $palette = new CMYK;
                    break;
                default:
                    $palette = null;
                    break;
            }
            if ($palette) {
                $result = $palette->color($color, $alpha);
            }
        }

        return $result;
    }

    /**
     * Creates a new empty image with an optional background color
     *
     * @param array|Box             $size   Width and height
     * @param string|array|ColorInterface    $color  Color value and alpha
     *
     * @return ImageInterface|bool
     */
    public function create($size, $color = null)
    {
        if (!$this->getDriver()) {
            return false;
        }

        $size = $this->box($size);
        $color = $color ? $this->color($color) : null;
        try {
            $image = $this->getDriver()->create($size, $color);
        } catch (\Exception $e) {
            $image = false;
        }

        return $image;
    }

    /**
     * Opens an existing image from $path
     *
     * @param string $path
     *
     * @return ImageInterface|bool
     */
    public function open($path)
    {
        if (!$this->getDriver()) {
            return false;
        }

        try {
            $image = $this->getDriver()->open($path);
        } catch (\Exception $e) {
            $image = false;
        }

        return $image;
    }

    /**
     * Loads an image from a binary $string
     *
     * @param string $string
     *
     * @return ImageInterface|bool
     */
    public function load($string)
    {
        if (!$this->getDriver()) {
            return false;
        }

        try {
            $image = $this->getDriver()->load($string);
        } catch (\Exception $e) {
            $image = false;
        }

        return $image;
    }

    /**
     * Loads an image from a resource $resource
     *
     * @param resource $resource
     *
     * @return ImageInterface|bool
     */
    public function read($resource)
    {
        if (!$this->getDriver()) {
            return false;
        }

        try {
            $image = $this->getDriver()->read($resource);
        } catch (\Exception $e) {
            $image = false;
        }

        return $image;
    }

    /**
     * Constructs a font with specified $file, $size and $color
     *
     * The font size is to be specified in points (e.g. 10pt means 10)
     *
     * @param string  $file
     * @param integer $size
     * @param string|array|ColorInterface $color  Color value and alpha
     *
     * @return FontInterface|bool
     */
    public function font($file, $size, $color)
    {
        if (!$this->getDriver()) {
            return false;
        }

        $color = $this->color($color);
        try {
            $font = $this->getDriver()->font($file, $size, $color);
        } catch (\Exception $e) {
            $font = false;
        }

        return $font;
    }

    /**
     * Add watermark to an image
     *
     * @param string|Image          $sourceImage
     * @param string                $to
     * @param string                $watermarkImage
     * @param string|array|Point    $position
     * @param array                 $options
     *
     * @return bool
     */
    public function watermark(
        $sourceImage,
        $to             = '',
        $watermarkImage = '',
        $position       = '',
        array $options  = array()
    ) {
        if (!$this->getDriver()) {
            return false;
        }

        if ($sourceImage instanceof ImageInterface) {
            $image = $sourceImage;
        } else {
            $image = $this->getDriver()->open($sourceImage);
        }
        if ($watermarkImage instanceof ImageInterface) {
            $watermark = $watermarkImage;
        } else {
            $watermarkImage = $watermarkImage ?: $this->getOption('watermark');
            $watermark = $this->getDriver()->open($watermarkImage);
        }
        if ($position instanceof Point) {
            $start = $position;
        } elseif (is_array($position)) {
            $start = $this->point($position[0], $position[1]);
        } else {
            $size      = $image->getSize();
            $wSize     = $watermark->getSize();
            switch ($position) {
                case 'top-left':
                    list($x, $y) = array(0, 0);
                    break;
                case 'top-right':
                    $x = $size->getWidth() - $wSize->getWidth();
                    $y = 0;
                    break;
                case 'bottom-left':
                    $x = 0;
                    $y = $size->getHeight() - $wSize->getHeight();
                    break;
                case 'bottom-right':
                default:
                    $x = $size->getWidth() - $wSize->getWidth();
                    $y = $size->getHeight() - $wSize->getHeight();
                    break;
            }
            $start = $this->point($x, $y);
        }
        try {
            $image->paste($watermark, $start);
            $result = $this->saveImage($image, $to, $sourceImage, $options);
        } catch(\Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Crops a specified box out of the source image (modifies the source image)
     * Returns cropped self
     *
     * @param string|Image      $sourceImage
     * @param array|Point       $start
     * @param array|float|int|Box   $size
     * @param string            $to
     * @param array             $options Options:
     *
     * @return bool
     */
    public function crop(
        $sourceImage,
        $start,
        $size,
        $to = '',
        array $options = array()
    ) {
        if (!$this->getDriver()) {
            return false;
        }
        if ($sourceImage instanceof ImageInterface) {
            $image = $sourceImage;
        } else {
            $image = $this->getDriver()->open($sourceImage);
        }
        $start = $this->point($start);
        $origin = $image->getSize();

        // Check if square
        if (is_integer($size)) {
            $size = array($size, $size);
        }
        // With specified width and/or height
        if (is_array($size)) {
            // Specified height only
            if (!$size[0]) {
                $size[0] = $origin->getWidth();
            // Specified width only
            } elseif (!$size[1]) {
                $size[1] = $origin->getHeight();
            }
            $box = $this->box($size);
        // With size ratio
        } elseif (is_float($size)) {
            $box = $origin->scale($size);
        } elseif ($size instanceof Box) {
            $box = $size;
        } else {
            $box = null;
        }

        try {
            $image->crop($start, $box);
            $result = $this->saveImage($image, $to, $sourceImage, $options);
        } catch(\Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Resizes current image
     *
     * @param string|Image      $sourceImage
     * @param array|float|Box   $size
     * @param string            $to
     * @param string            $filter
     * @param array             $options
     *
     * @return bool
     */
    public function resize(
        $sourceImage,
        $size,
        $to             = '',
        $filter         = '',
        array $options  = array()
    ) {
        if (!$this->getDriver()) {
            return false;
        }
        if ($sourceImage instanceof ImageInterface) {
            $image = $sourceImage;
        } else {
            $image = $this->getDriver()->open($sourceImage);
        }
        $filter = $filter ?: ImageInterface::FILTER_UNDEFINED;
        $box = $this->canonizeSize($size, $image->getSize());
        try {
            $image->resize($box, $filter);
            $result = $this->saveImage($image, $to, $sourceImage, $options);
        } catch(\Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Generates a thumbnail from a current image
     * Returns it as a new image, doesn't modify the current image
     *
     * @param string|Image      $sourceImage
     * @param array|float|Box   $size
     * @param string            $to
     * @param string            $mode
     * @param array             $options
     *
     * @return bool|ImageInterface
     */
    public function thumbnail(
        $sourceImage,
        $size,
        $to,
        $mode = '',
        array $options = array()
    ) {
        if (!$this->getDriver()) {
            return false;
        }
        if ($sourceImage instanceof ImageInterface) {
            $image = $sourceImage;
        } else {
            $image = $this->getDriver()->open($sourceImage);
        }
        $box = $this->canonizeSize($size, $image->getSize());
        $mode = $mode ?: ImageInterface::THUMBNAIL_INSET;
        try {
            $thumbnail = $image->thumbnail($box, $mode);
            $result = $this->saveImage($thumbnail, $to, $sourceImage, $options);
        } catch (\Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Rotates an image at the given angle.
     * Optional $background can be used to specify the fill color of the empty
     * area of rotated image.
     *
     * @param string|Image                  $sourceImage
     * @param int                           $angle
     * @param string                        $to
     * @param string|array|ColorInterface   $background
     * @param array                         $options
     *
     * @return bool
     */
    public function rotate(
        $sourceImage,
        $angle,
        $to             = '',
        $background     = null,
        array $options  = array()
    ) {
        if (!$this->getDriver()) {
            return false;
        }
        if ($sourceImage instanceof ImageInterface) {
            $image = $sourceImage;
        } else {
            $image = $this->getDriver()->open($sourceImage);
        }
        $background = $background ? $this->color($background) : null;
        try {
            $image->rotate($angle, $background);
            $result = $this->saveImage($image, $to, $sourceImage, $options);
        } catch(\Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Pastes an image into a parent image
     * Throws exceptions if image exceeds parent image borders or if paste
     * operation fails
     *
     * @param string|Image $sourceImage
     * @param string|Image $childImage
     * @param array|Point  $start
     * @param string       $to
     * @param array        $options
     *
     * @return bool
     */
    public function paste(
        $sourceImage,
        $childImage,
        $start,
        $to,
        array $options = array()
    ) {
        if (!$this->getDriver()) {
            return false;
        }
        if ($sourceImage instanceof ImageInterface) {
            $image = $sourceImage;
        } else {
            $image = $this->getDriver()->open($sourceImage);
        }
        if ($childImage instanceof ImageInterface) {
            $child = $childImage;
        } else {
            $child = $this->getDriver()->open($childImage);
        }
        $start = $this->point($start);
        try {
            $image->paste($child, $start);
            $result = $this->saveImage($image, $to, $sourceImage, $options);
        } catch(\Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Saves the image at a specified path, the target file extension is used
     * to determine file format, only jpg, jpeg, gif, png, wbmp and xbm are
     * supported
     *
     * @param string|Image $sourceImage
     * @param string       $to
     * @param array        $options
     *
     * @return bool
     */
    public function save($sourceImage, $to = '', array $options = array())
    {
        if (!$this->getDriver()) {
            return false;
        }
        if ($sourceImage instanceof ImageInterface) {
            $image = $sourceImage;
        } else {
            $image = $this->getDriver()->open($sourceImage);
        }
        try {
            $result = $this->saveImage($image, $to, '', $options);
        } catch(\Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Create path for image file to be stored
     *
     * @param string $file
     * @param bool $isFile
     *
     * @return mixed
     */
    public function mkdir($file, $isFile = true)
    {
        $path = $isFile ? dirname($file) : $file;
        $result = Pi::service('file')->mkdir($path);

        return $result;
    }

    /**
     * Save Image to a file
     *
     * @param ImageInterface        $image
     * @param string                $to
     * @param string|ImageInterface $source
     * @param array                 $options
     *
     * @return bool|ImageInterface
     */
    protected function saveImage(
        ImageInterface $image,
        $to,
        $source = '',
        array $options = array()
    ) {
        if (!$to && $source && !$source instanceof ImageInterface) {
            $to = $source;
        }
        if ($to) {
            $result = true;
            $mkdir = $this->getOption('auto_mkdir');
            if ((null === $mkdir || $mkdir) && !$this->mkdir($to)) {
                $result = false;
            } else {
                try {
                    $image->save($to, $options);
                } catch (\Excetpion $e) {
                    $result = false;
                }
            }
        } else {
            $result = $image;
        }

        return $result;
    }

    /**
     * Canonize image size
     *
     * @param array|int|float|Box $size float, Box, or integer for square, or array: array(width, weight); array(width, 0); array(0, height); array(width, height, keepAspectRatio)
     * @param Box $origin
     *
     * @return Box
     */
    protected function canonizeSize($size, Box $origin = null)
    {
        // Check if square
        if (is_integer($size)) {
            $size = array($size, $size);
        }
        // With specified width and/or height
        if (is_array($size)) {
            // To keep aspect ratio
            if ($size[0] && $size[1] && isset($size[2])) {
                $ratio = ($size[0] * $origin->getHeight()) / ($size[1] * $origin->getWidth());
                if ($ratio >= 1) {
                    $size[0] = 0;
                } else {
                    $size[1] = 0;
                }
            }
            // Specified height only
            if (!$size[0]) {
                $box = $origin->heighten($size[1]);
            // Specified width only
            } elseif (!$size[1]) {
                $box = $origin->widen($size[0]);
            // Specified width and height
            } else {
                $box = $this->box($size);
            }
        // With size ratio
        } elseif (is_float($size)) {
            $box = $origin->scale($size);
        } elseif ($size instanceof Box) {
            $box = $size;
        } else {
            $box = null;
        }

        return $box;
    }
}
