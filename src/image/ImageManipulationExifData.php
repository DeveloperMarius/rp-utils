<?php

namespace utils\image;

use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Pure;
use lsolesen\pel\PelDataWindow;
use lsolesen\pel\PelEntry;
use lsolesen\pel\PelExif;
use lsolesen\pel\PelIfd;
use lsolesen\pel\PelIfdException;
use lsolesen\pel\PelInvalidArgumentException;
use lsolesen\pel\PelJpeg;
use lsolesen\pel\PelJpegContent;
use lsolesen\pel\PelTag;
use lsolesen\pel\PelTiff;
use utils\image\exceptions\ImageExifPelNotInstalled;
use utils\image\exceptions\ImageExifReadDataFailedException;

class ImageManipulationExifData{

    const ORIENTATION_UNDEFINED = 0;
    const ORIENTATION_TOPLEFT = 1;
    const ORIENTATION_TOPRIGHT = 2;
    const ORIENTATION_BOTTOMRIGHT = 3;
    const ORIENTATION_BOTTOMLEFT = 4;
    const ORIENTATION_LEFTTOP = 5;
    const ORIENTATION_RIGHTTOP = 6;
    const ORIENTATION_RIGHTBOTTOM = 7;
    const ORIENTATION_LEFTBOTTOM = 8;

    /**
     * @return bool
     */
    public static function isPelInstalled(): bool{
        return class_exists('lsolesen\pel\PelJpeg');
    }

    private array $exif_data;
    private ?PelJpeg $pel_jpeg = null;

    /**
     * ImageManipulationExifData constructor.
     * @param string $source_path
     * @param int $source_type
     * @throws ImageExifReadDataFailedException
     */
    public function __construct(string $source_path, int $source_type){
        $exif = exif_read_data($source_path);
        if($exif === false){
            throw new ImageExifReadDataFailedException('Failed to read exif data from file with source: "' . $source_path . '"');
        }
        $this->exif_data = $exif;
        if(self::isPelInstalled() && ($source_type === IMAGETYPE_JPEG)){//TODO add support for png (transparent images)
            $this->pel_jpeg = new PelJpeg($source_path);
        }
    }

    /**
     * @return array
     */
    public function getExifData(): array{
        return $this->exif_data;
    }

    /**
     * @return int
     */
    #[Pure]
    #[ExpectedValues([self::ORIENTATION_UNDEFINED, self::ORIENTATION_TOPLEFT, self::ORIENTATION_TOPRIGHT, self::ORIENTATION_BOTTOMRIGHT, self::ORIENTATION_BOTTOMLEFT, self::ORIENTATION_LEFTTOP, self::ORIENTATION_RIGHTTOP, self::ORIENTATION_RIGHTBOTTOM, self::ORIENTATION_LEFTBOTTOM])]
    public function getOrientation(): int{
        if(isset($this->getExifData()['Orientation'])){
            return $this->getExifData()['Orientation'];
        }
        return self::ORIENTATION_UNDEFINED;
    }

    /**
     * From comment {@link https://www.php.net/manual/en/function.exif-thumbnail.php#84777}
     *
     * @return bool
     * @throws PelInvalidArgumentException
     * @throws PelIfdException
     */
    public function generateThumbnail(): bool{
        $ifd0 = $this->getPelIfd();
        $ifd1 = $ifd0->getNextIfd();
        if(!$ifd1){// Only create thumbnail if one doesn't exist (i.e. there is no IFD1)
            $ifd1 = new PelIfd(1);
            $ifd0->setNextIfd($ifd1); # point ifd0 to the new ifd1 (or else ifd1 will not be read)

            $original = ImageCreateFromString($this->getPelJpeg()->getBytes()); # create image resource of original
            $orig_w = imagesx($original);
            $orig_h = imagesy($original);
            $wmax = 160;
            $hmax = 120;

            if($orig_w > $wmax || $orig_h > $hmax){
                $thumb_w = $wmax;
                $thumb_h = $hmax;
                if($thumb_w / $orig_w * $orig_h > $thumb_h)
                    $thumb_w = round($thumb_h * $orig_w / $orig_h); # maintain aspect ratio
                else
                    $thumb_h = round($thumb_w * $orig_h / $orig_w);
            } else { # only set the thumb's size if the original is larger than 'wmax'x'hmax'
                $thumb_w = $orig_w;
                $thumb_h = $orig_h;
            }

            # create image resource with thumbnail sizing
            $thumb = imagecreatetruecolor($thumb_w, $thumb_h);
            ## Resize original and copy to the blank thumb resource
            imagecopyresampled($thumb, $original, 0, 0, 0, 0, $thumb_w, $thumb_h, $orig_w, $orig_h);

            /*
            # start writing output to buffer
            ob_start();
            # outputs thumb resource contents to buffer
            ImageJpeg($thumb, null, 100);
            # create PelDataWindow from buffer thumb contents (and end output to buffer)
            $window = new PelDataWindow(ob_get_clean());
            */
            $window = new PelDataWindow($thumb);

            $ifd1->setThumbnail($window); # set window data as thumbnail in ifd1
            return true;
        }
        return false;
    }

    private function checkPelInstalled(){
        if(!self::isPelInstalled())
            throw new ImageExifPelNotInstalled('The Pel extension (https://github.com/pel/pel) is not installed');
    }

    /**
     * @return PelJpeg
     */
    public function getPelJpeg(): PelJpeg{
        $this->checkPelInstalled();
        return $this->pel_jpeg;
    }

    public function getPelExif(): ?PelExif{
        return $this->getPelJpeg()->getExif();
    }

    public function getPelICC(): PelExif|PelJpegContent|null{
        return $this->getPelJpeg()->getICC();
    }

    public function getPelTiff(): ?PelTiff{
        return $this->getPelExif()?->getTiff();
    }

    public function getPelIfd(): ?PelIfd{
        return $this->getPelTiff()?->getIfd();
    }

    public function getPelEntry(int $entry): ?PelEntry{
        return $this->getPelIfd()?->getEntry($entry);
    }

    public function getPelEntryValue(int $entry): ?PelEntry{
        return $this->getPelIfd()?->getEntry($entry)?->getValue();
    }

    public function getCopyright(): mixed{
        return $this->getPelEntry(PelTag::COPYRIGHT)->getValue();
    }

    public function clearExif(){
        $this->getPelJpeg()->clearExif();
    }

}