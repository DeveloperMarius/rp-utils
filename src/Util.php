<?php

namespace utils;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Deprecated;
use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\NoReturn;
use JetBrains\PhpStorm\Pure;
use utils\image\exceptions\ImageExifReadDataFailedException;
use utils\image\ImageManipulation;

class Util{

    /**
     * @param string $tmpName
     * @return string|false
     */
    public static function getFileMimeType(string $tmpName): string|false{
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        return finfo_file($finfo, $tmpName);
    }

    /**
     * @param string $color
     * @param string $default_on_error
     * @return int[]
     */
    #[ArrayShape([
        'r' => "int",
        'g' => "int",
        'b' => "int"
    ])]
    public static function hex2rgb(string $color, string $default_on_error = '#FFFFFF'): array{
        // if color is not formatted correctly
        // use the default color
        if (preg_match('/^#?([a-f]|[0-9]){3}(([a-f]|[0-9]){3})?$/i', $color) == 0)
            $color = $default_on_error;

        // trim off the "#" prefix from $background_color
        $color = ltrim($color, '#');

        // if color is given using the shorthand (i.e. "FFF" instead of "FFFFFF")
        if (strlen($color) == 3) {

            $tmp = '';

            // take each value
            // and duplicate it
            for ($i = 0; $i < 3; $i++) $tmp .= str_repeat($color[$i], 2);

            // the color in it's full, 6 characters length notation
            $color = $tmp;

        }

        // decimal representation of the color
        $int = hexdec($color);

        // extract and return the RGB values
        return array(
            'r' =>  0xFF & ($int >> 0x10),
            'g' =>  0xFF & ($int >> 0x8),
            'b' =>  0xFF & $int
        );
    }

    /**
     * @param array $file - File object
     * @param string $absoluteTargetPath
     * @return bool
     */
    public static function uploadImage(array $file, string $absoluteTargetPath): bool{
        try{
            $image = new ImageManipulation($file['tmp_name']);
            $image->setKeepExifData(false);
            $image->setOutputPath($absoluteTargetPath);
            $image->exportImage();

            /*$imagick = new Imagick();
            $imagick->readImage($file['tmp_name']);
            $profiles = $imagick->getImageProfiles('icc', true);
            $imagick->stripImage();
            if(!empty($profiles))
                $imagick->profileImage('icc', $profiles['icc']);
            switch($imagick->getImageOrientation()){
                case Imagick::ORIENTATION_BOTTOMRIGHT:
                    $imagick->rotateimage("#000", 180); // rotate 180 degrees
                    break;
                case Imagick::ORIENTATION_RIGHTTOP:
                    $imagick->rotateimage("#000", 90); // rotate 90 degrees CW
                    break;
                case Imagick::ORIENTATION_LEFTBOTTOM:
                    $imagick->rotateimage("#000", -90); // rotate 90 degrees CCW
                    break;
            }
            // Now that it's auto-rotated, make sure the EXIF data is correct in case the EXIF gets saved with the image!
            $imagick->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
            //$imagick->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);
            $imagick->writeImage($absoluteTargetPath);
            $imagick->destroy();*/
            return true;
        }catch(\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * @param string $directory
     * @return array
     */
    public static function scanDirectory(string $directory): array{
        $files = array();
        if(is_dir($directory)){
            foreach(scandir($directory) as $file){
                if(in_array($file, array('.', '..')))
                    continue;
                $files = array_merge($files, self::scanDirectory($directory . '/' . $file));
            }
        }else{
            $files[] = $directory;
        }
        return $files;
    }

    /**
     * @param string $title
     * @param int|null $id
     * @return string
     */
    public static function createUrl(string $title, ?int $id = null): string{
        $title = strtolower($title);
        if($id !== null){
            $title = str_replace(['ä', 'ü', 'ö'], ['ae', 'ue', 'oe'], trim($title));
            $title = preg_replace('/[^a-zA-Z0-9\s]/', '', $title);
            $title = preg_replace('/\s+/', '-', $title);
        }
        return urlencode($title . ($id !== null ? '-' . $id : ''));
    }

    /**
     * @param string $title
     * @param int|null $id
     * @return string
     */
    public static function createTag(string $title, ?int $id = null): string{
        $title = strtolower($title);
        $title = str_replace(['ä', 'ü', 'ö'], ['ae', 'ue', 'oe'], trim($title));
        $title = preg_replace('/[^a-zA-Z0-9\-\s]/', '', $title);
        $title = preg_replace('/\s+/', '-', $title);
        $title = preg_replace('/\-+/', '-', $title);
        return urlencode($title . ($id !== null ? '-' . $id : ''));
    }

    /**
     * @param string $url
     * @param bool $id
     * @return array
     */
    #[ArrayShape([
        'title' => "string",
        'id' => "int|null"
    ])]
    public static function decodeUrl(string $url, bool $id = true): array{
        $title = strtolower(urldecode($url));
        if($id){
            $title_split = explode('-', $title);
            $title = substr($title, 0, strlen($title) - (strlen(end($title_split)) + 1));
        }
        return array(
            'title' => $title,
            'id' => $id ? intval(end($title_split)) : null
        );
    }

    /**
     * @return string
     */
    #[Pure]
    private static function randomHiddenTag(): string{
        $tags = array(
            '<span style="display: none !important;">bot</span>',
            '<span class="cms-email-hidden">bot</span>',
            '<span hidden style="display: none !important;">bot</span>'
        );
        return $tags[rand(0, 2)];
    }

    /**
     * @param string $email
     * @return string
     */
    public static function encryptEmailPlain(string $email): string{
        $encrypted = '';
        $j = 0;
        for($i = 0; $i < strlen($email); $i++){
            if($j === 3){
                $j = 0;
                $encrypted .= self::randomHiddenTag();
            }
            $encrypted .= $email[$i];
            $j++;
        }
        return $encrypted;
    }

    /**
     * @param string $email
     * @return string
     */
    public static function encryptEmail(string $email): string{
        $encrypted = '';
        for($i = 0; $i < strlen($email); $i++){
            $char = ord($email[$i]);
            if($char >= 8364)
                $char = 128;
            $encrypted .= chr($char+1);
        }
        return $encrypted;
    }

    /**
     * @param string $email
     * @return string
     */
    public static function decryptEmail(string $email): string{
        $decrypted = '';
        for($i = 0; $i < strlen($email); $i++){
            $char = ord($email[$i]);
            if($char >= 8364)
                $char = 128;
            $decrypted .= chr($char-1);
        }
        return $decrypted;
    }

    public static function jsonEncodeForClientSide(array $array){
        $json = "";
        if(!empty($array)){
            $json = json_encode($array);
            $json = addslashes($json);
        }

        if(empty($json))
            $json = '{}';

        $json = "'" . $json . "'";

        return($json);
    }

    public static function compress_css($buffer){
        /* remove comments */
        $buffer = preg_replace("!/\*[^*]*\*+([^/][^*]*\*+)*/!", "", $buffer) ;
        /* remove tabs, spaces, newlines, etc. */
        $arr = array("\r\n", "\r", "\n", "\t", "  ", "    ", "    ");
        $rep = array("", "", "", "", " ", " ", " ");
        $buffer = str_replace($arr, $rep, $buffer);
        /* remove whitespaces around {}:, */
        $buffer = preg_replace("/\s*([\{\}:,])\s*/", "$1", $buffer);
        /* remove last ; */
        $buffer = str_replace(';}', "}", $buffer);

        return $buffer;
    }

    /**
     * @param string $input
     * @param string $divider
     * @return string
     */
    public static function replaceToCamelCase(string $input, string $divider = '_'): string{
        $input = str_replace($divider . 'a', 'A', $input);
        $input = str_replace($divider . 'b', 'B', $input);
        $input = str_replace($divider . 'c', 'C', $input);
        $input = str_replace($divider . 'd', 'D', $input);
        $input = str_replace($divider . 'e', 'E', $input);
        $input = str_replace($divider . 'f', 'F', $input);
        $input = str_replace($divider . 'g', 'G', $input);
        $input = str_replace($divider . 'h', 'H', $input);
        $input = str_replace($divider . 'i', 'I', $input);
        $input = str_replace($divider . 'j', 'J', $input);
        $input = str_replace($divider . 'k', 'K', $input);
        $input = str_replace($divider . 'l', 'L', $input);
        $input = str_replace($divider . 'm', 'M', $input);
        $input = str_replace($divider . 'n', 'N', $input);
        $input = str_replace($divider . 'o', 'O', $input);
        $input = str_replace($divider . 'p', 'P', $input);
        $input = str_replace($divider . 'q', 'Q', $input);
        $input = str_replace($divider . 'r', 'R', $input);
        $input = str_replace($divider . 's', 'S', $input);
        $input = str_replace($divider . 't', 'T', $input);
        $input = str_replace($divider . 'u', 'U', $input);
        $input = str_replace($divider . 'v', 'V', $input);
        $input = str_replace($divider . 'w', 'W', $input);
        $input = str_replace($divider . 'x', 'X', $input);
        $input = str_replace($divider . 'y', 'Y', $input);
        $input = str_replace($divider . 'z', 'Z', $input);
        $input = str_replace($divider . '0', '0', $input);
        $input = str_replace($divider . '1', '1', $input);
        $input = str_replace($divider . '2', '2', $input);
        $input = str_replace($divider . '3', '3', $input);
        $input = str_replace($divider . '4', '4', $input);
        $input = str_replace($divider . '5', '5', $input);
        $input = str_replace($divider . '6', '6', $input);
        $input = str_replace($divider . '7', '7', $input);
        $input = str_replace($divider . '8', '8', $input);
        $input = str_replace($divider . '9', '9', $input);
        return $input;
    }

    /**
     * @param string $input
     * @param string $divider
     * @return string
     */
    public static function replaceFromCamelCase(string $input, string $divider = '_'): string{
        $input = str_replace('A', $divider . 'a', $input);
        $input = str_replace('B', $divider . 'b', $input);
        $input = str_replace('C', $divider . 'c', $input);
        $input = str_replace('D', $divider . 'd', $input);
        $input = str_replace('E', $divider . 'e', $input);
        $input = str_replace('F', $divider . 'f', $input);
        $input = str_replace('G', $divider . 'g', $input);
        $input = str_replace('H', $divider . 'h', $input);
        $input = str_replace('I', $divider . 'i', $input);
        $input = str_replace('J', $divider . 'j', $input);
        $input = str_replace('K', $divider . 'k', $input);
        $input = str_replace('L', $divider . 'l', $input);
        $input = str_replace('M', $divider . 'm', $input);
        $input = str_replace('N', $divider . 'n', $input);
        $input = str_replace('O', $divider . 'o', $input);
        $input = str_replace('P', $divider . 'p', $input);
        $input = str_replace('Q', $divider . 'q', $input);
        $input = str_replace('R', $divider . 'r', $input);
        $input = str_replace('S', $divider . 's', $input);
        $input = str_replace('T', $divider . 't', $input);
        $input = str_replace('U', $divider . 'u', $input);
        $input = str_replace('V', $divider . 'v', $input);
        $input = str_replace('W', $divider . 'w', $input);
        $input = str_replace('X', $divider . 'x', $input);
        $input = str_replace('Y', $divider . 'y', $input);
        $input = str_replace('Z', $divider . 'z', $input);
        $input = str_replace('0', $divider . '0', $input);
        $input = str_replace('1', $divider . '1', $input);
        $input = str_replace('2', $divider . '2', $input);
        $input = str_replace('3', $divider . '3', $input);
        $input = str_replace('4', $divider . '4', $input);
        $input = str_replace('5', $divider . '5', $input);
        $input = str_replace('6', $divider . '6', $input);
        $input = str_replace('7', $divider . '7', $input);
        $input = str_replace('8', $divider . '8', $input);
        $input = str_replace('9', $divider . '9', $input);
        return $input;
    }

    /**
     * @param String $url
     * @param string $siteurl
     */
    #[NoReturn] public static function redirect(string $url, string $siteurl = ''){
        header('Location: ' . $siteurl . $url);
        exit;
    }

    /**
     * @return String
     *
     * Sources:
     * @link https://docs.php.earth/faq/misc/ip/
     */
    public static function getUserIP(): string{
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $user_ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $user_ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $user_ips = array_map('trim', $user_ips);
            $user_ip = $user_ips[0];
        } else {
            $user_ip = $_SERVER['REMOTE_ADDR'];
        }
        return $user_ip;
    }

    /**
     * @return String
     */
    #[Pure]
    #[Deprecated(
        reason: 'Use getUserAgent()',
        replacement: '%class%::getUserAgent()'
    )]
    public static function getBrowser(): string{
        return self::getUserAgent();
    }

    /**
     * @return String
     */
    public static function getUserAgent(): string{
        return substr($_SERVER['HTTP_USER_AGENT'], 0, 300);
    }

    /**
     * @return string
     */
    public static function getURLBase(): string{
        $url_base = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $url_base .= '?' . $_SERVER['QUERY_STRING'];
        }
        if (!empty($url_base)) {
            $url_base = str_replace('.php', '', $url_base);
            if($url_base == '/index'){
                $url_base = '/';
            }
            return $url_base;
        }else {
            return '/';
        }
    }

    /**
     * @param int $length
     * @param bool $numbers
     * @param bool $specialChars
     * @param array $specialCharsList
     * @return string
     */
    public static function randomString(int $length = 10, bool $numbers = true, bool $specialChars = false, array $specialCharsList = array('!', '?', '_', '.', '/', '{', '}', '@', '#', '$', '%')): string{
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        if($numbers)
            $alphabet .= '0123456789';
        if($specialChars){
            $alphabet .= implode('', $specialCharsList);
        }
        $pass = array();
        $alphaLength = strlen($alphabet) - 1;
        for ($i = 0; $i < $length; $i++) {
            $n = mt_rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass);
    }

    /**
     * @return string
     */
    public static function generateTwoFactorCode(): string{
        $alphabet = "0123456789";
        $code = '';
        $alphaLength = strlen($alphabet) - 1;
        for ($i = 0; $i < 4; $i++) {
            $code .= $alphabet[mt_rand(0, $alphaLength)];
        }
        return $code;
    }

    /**
     * @param string $char
     * @return string
     */
    #[Pure] public static function getCharPosition(string $char): string{
        switch (strtolower($char)){
            case 'a': return '01';
            case 'b': return '02';
            case 'c': return '03';
            case 'd': return '04';
            case 'e': return '05';
            case 'f': return '06';
            case 'g': return '07';
            case 'h': return '08';
            case 'i': return '09';
            case 'j': return '10';
            case 'k': return '11';
            case 'l': return '12';
            case 'm': return '13';
            case 'n': return '14';
            case 'o': return '15';
            case 'p': return '16';
            case 'q': return '17';
            case 'r': return '18';
            case 's': return '19';
            case 't': return '20';
            case 'u': return '21';
            case 'v': return '22';
            case 'w': return '23';
            case 'x': return '24';
            case 'y': return '25';
            case 'z': return '26';
            default: return '00';
        }
    }

    /**
     * @param String $search
     * @param String $string
     * @param int $offset
     * @return false|int
     */
    #[Pure] public static function strposOffset(string $search, string $string, int $offset): false|int{
        $arr = explode($search, $string);
        switch ($offset) {
            case $offset == 0:
            case $offset > max(array_keys($arr)):
                return false;
            default:
                return strlen(implode($search, array_slice($arr, 0, $offset)));
        }
    }

    #[Pure] public static function isAssocArray(array $array): bool{
        if (array() === $array) return false;
        return array_keys($array) !== range(0, count($array) - 1);
    }

    #[Pure] #[Deprecated(
        reason: 'since php8, use str_starts_with() instead',
        replacement: 'str_starts_with(%parameter0%, %parameter1%)'
    )]
    public static function startsWith( $haystack, $needle ): bool{
        $length = strlen( $needle );
        return substr( $haystack, 0, $length ) === $needle;
    }

    #[Pure] #[Deprecated(
        reason: 'since php8, use str_ends_with() instead',
        replacement: 'str_ends_with(%parameter0%, %parameter1%)'
    )]
    public static function endsWith( $haystack, $needle ): bool{
        $length = strlen( $needle );
        if( !$length ) {
            return true;
        }
        return substr( $haystack, -$length ) === $needle;
    }

    /**
     * @param bool $milliseconds
     * @return int
     */
    #[Pure]
    #[Deprecated(
        reason: 'deprecated',
        replacement: 'Time::getCurrentTimestamp(%parametersList%)'
    )]
    public static function getTimestamp(bool $milliseconds = true): int{
        return $milliseconds ? intval(round(microtime(true) * 1000)) : intval(round(microtime(true)));
    }

    /**
     * @param string $date
     * @return string
     */
    public static function translateDate(string $date): string{
        return preg_replace([
            '/Mar/',
            '/Oct/',
            '/Dec/',
            '/January/',
            '/February/',
            '/March/',
            '/April/',
            '/May/',
            '/June/',
            '/July/',
            '/October/',
            '/December/'
        ], [
            'Mär',
            'Okt',
            'Dez',
            'Januar',
            'Februar',
            'März',
            'April',
            'Mai',
            'Juni',
            'Juli',
            'Oktober',
            'Dezember'
        ], $date);
    }

    /**
     * @param string $date
     * @param bool $short
     * @return string
     */
    #[Deprecated(
        reason: 'Please use translateDate()',
        replacement: '%class%::translateDate(%parameter0%)'
    )]
    public static function translateDateMonth(string $date, bool $short = false): string{//'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'
        return self::translateDate($date);
    }

    /**
     * @return array
     */
    public static function getFiles(): array{
        $ret = array();
        if (!empty($_FILES)) {
            $files = array();
            foreach ($_FILES as $input => $infoArr){
                $filesByInput = array();
                foreach ($infoArr as $key => $valueArr) {
                    if (is_array($valueArr)) {
                        foreach($valueArr as $i => $value) {
                            $filesByInput[$i][$key] = $value;
                        }
                    } else {
                        array_push($filesByInput, $infoArr);
                        break;
                    }
                }
                $files = array_merge($files, $filesByInput);
            }
            foreach($files as $file) {
                if (!$file['error'])
                    array_push($ret, $file);
            }
        }
        return $ret;
    }

    /**
     * @return string
     */
    public static function getDocumentRoot(): string{
        return __DIR__ . '\..\..\..';
    }

    /**
     * @param string $interval - z.B. "10i5h1y"
     * @param bool $milliseconds
     * @return int
     *
     * Millisekunden: v<br>
     * Seconds: s<br>
     * Minutes: i<br>
     * Hours: h<br>
     * Days: d<br>
     * Weeks: w<br>
     * Months: m<br>
     * Years: y
     */
    public static function getSecondsForInterval(string $interval, bool $milliseconds = true): int{
        $seconds = 0;
        preg_match_all('/(\d+)([vsihdwmy])/', $interval, $matches);
        for($i = 0; $i < sizeof($matches[0]); $i++){
            $value = intval($matches[1][$i]);
            $type = $matches[2][$i];
            $seconds += match($type){
                'v' => $value*0.1,
                's' => $value*1,
                'i' => $value*60,
                'h' => $value*3600,
                'd' => $value*86400,
                'w' => $value*604800,
                'm' => $value*2419200,
                'y' => $value*29030400,
                default => 0
            };
        }
        if($milliseconds)
            $seconds *= 1000;
        return $seconds;
    }

    /**
     * 11.25€ -> 11.50€: ceiling(11.25, 0.50);
     *
     * @param float $number
     * @param float $significance
     * @return float
     */
    public static function ceiling(float $number, float $significance = 1): float{
        return ceil($number / $significance) * $significance;
    }

    /**
     * @param int $number
     * @param int $size - max 10
     * @return string
     */
    public static function pad(int $number, int $size = 2): string{
        return substr('000000000' . $number, -$size, $size);
    }

    /**
     * @param string $name
     * @param string $value
     * @param int $expires
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @param string $sameSite
     * @return bool
     */
    //TODO test samesite attribute! | alternative: https://stackoverflow.com/questions/39750906/php-setcookie-samesite-strict
    public static function setCookie(string $name, string $value, int $expires, string $path = '/', string $domain = '', bool $secure = true, bool $httponly = true, #[ExpectedValues(['None', 'Strict', 'Lax'])] string $sameSite = 'Strict'): bool{
        return setcookie($name, $value, [
            'expires' => $expires,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $sameSite
        ]);
    }

    /**
     * @param string $name
     * @param string $path
     * @param string $domain
     * @return bool
     */
    public static function unsetCookie(string $name, string $path = '/', string $domain = ''): bool{
        return setcookie($name, null, time() - 3600, $path, $domain, true, true);
    }

    /**
     * @param float|int|string $num0
     * @param float|int|string $num1
     * @return int
     * Solves problem when multiplying 17.9 with 100000:
     * 17.9*100000 = 1789999.9999999998
     * bcmul(17.9, 100000) = 1790000
     */
    public static function multiplyFloats(float|int|string $num0, float|int|string $num1): int{
        return intval(bcmul(strval($num0), strval($num1)));
    }

    /**
     * @param float|int|string $num0
     * @param float|int|string $num1
     * @return int
     */
    public static function subtractFloats(float|int|string $num0, float|int|string $num1): int{
        return intval(bcsub(strval($num0), strval($num1)));
    }

    public static function generateUuid(): string{
        $factory = new \Ramsey\Uuid\UuidFactory();

        $generator = new \Ramsey\Uuid\Generator\CombGenerator(
            $factory->getRandomGenerator(),
            $factory->getNumberConverter()
        );

        $codec = new \Ramsey\Uuid\Codec\TimestampFirstCombCodec($factory->getUuidBuilder());

        $factory->setRandomGenerator($generator);
        $factory->setCodec($codec);

        $factory->uuid4();

        \Ramsey\Uuid\Uuid::setFactory($factory);
        return \Ramsey\Uuid\Uuid::uuid4()->toString();
    }

}