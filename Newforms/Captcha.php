<?php

require_once '__Field.php';

class lib_Newforms_Captcha extends lib_Newforms___Field {

    public $maxlength = 255;
    public $url = '/captcha/';

    public function html() {

  if (!$this->cleaned_value() || !$this->saved_captcha()) {
    $Captcha = new lib_Newforms_Captcha_Inner($this->__name);
    $Captcha->setBackground('ffffff');
    $Captcha->setFont('6186b2', 22);
    $Captcha->setJitterAngle(30);
    $Captcha->setJitterSize(10);
    $Captcha->setJitterTransparency(20);
    $Captcha->output();
  }

        return sprintf('
            <img src="%s?%s" alt="Captcha" />
            <input type="text" id="newforms_%s" name="%s" value="%s" maxlength="%s" class="%s" />',
                $this->url, $this->__name,
                $this->__name, $this->__name, htmlspecialchars($this->value()), $this->maxlength, $this->class);
    }

    public function builtinValidation() {
        $E = array();
        if (!$this->validate_captcha())
            $E[] = "Введенный вами код проверки неверен.";
        return $E;
    }

    protected function saved_captcha() {
        $saved_captcha_key = 'captcha__' . $this->__name;
        if (empty($_SESSION[$saved_captcha_key])) return '';
        return trim($_SESSION[$saved_captcha_key]);
    }

    protected function clear_captcha() {
        $saved_captcha_key = 'captcha__' . $this->__name;
        unset ($_SESSION[$saved_captcha_key]);
    }

    public function validate_captcha() {
        $captcha = $this->cleaned_value();
        if (!$captcha)
            return false;
        // print_r ($_SESSION);
        if (!$this->saved_captcha())
            return false;
        if (trim($captcha) == $this->saved_captcha()) {
            $this->clear_captcha();
            return true;
        }
        return false;
    }

}



require_once 'Zend/Session/Namespace.php';

/* Example:


  $Captcha = new Captcha();
  $Captcha->setBackground('ffffff');
  $Captcha->setFont('6186b2', 22);
  $Captcha->setJitterAngle(30);
  $Captcha->setJitterSize(10);
  $Captcha->setJitterTransparency(20);
  $Captcha->output();

 */

class lib_Newforms_Captcha_Inner {

    private $width = 100, $height = 70, $length = 4, $session_name, $request_name,
    $backgroundPath = '', $tiling = 'original',
    $fontColor = '000000', $fontSize = 12, $fontFace = '',
    $backgroundColor = 'ffffff',
    $jitterRotation = 0, $jitterHue = 0, $jitterTransparency = 0, $jitterSize = 0,
    $Vocabulary = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'),
    $Image, $Colors = array(), $AllocatedColors = array(), $Letters = array(), $XCoords = array();

    public function __construct($name) {
        $this->session_name = 'captcha__' . $name;
        $this->request_name = $name;
        $this->fontFace = SHAREDROOT . 'misc/arial.ttf';
        $this->Colors[] = $this->backgroundColor;
        $this->Colors[] = $this->fontColor;
    }

    public function writeToSession() {
        $_SESSION[$this->session_name] = implode('', $this->Letters);
    }

    public function passedValidation() {
        if (empty($_REQUEST[$this->request_name]))
            return false;
        if (empty($_SESSION[$this->session_name]))
            return false;
        if (trim($_REQUEST[$this->request_name]) == trim($_SESSION[$this->session_name])) {
            $_SESSION[$this->session_name] = '';
            return true;
        }
        return false;
    }

    public function setWidth($width) {
        $this->width = intval($width);
    }

    public function setHeight($height) {
        $this->height = intval($height);
    }

    public function getWidth() {
        return $this->width;
    }

    public function getHeight() {
        return $this->width;
    }

    public function setBackground($color, $pathToImage = '', $tiling = 'original') {
        $this->backgroundColor = $color;
        $this->Colors[] = $color;
    }

    public function setFont($color, $size) {
        $this->Colors[] = $color;
        $this->fontSize = $size;
        $this->fontColor = $color;
    }

    public function setFontFace($pathToFont) {
        $this->fontFace = $pathToFont;
    }

    public function setJitterAngle($angleAlpha) {
        $this->jitterRotation = $angleAlpha;
    }

    public function setJitterHue($colorAlpha) {

    }

    public function setJitterTransparency($alphaChannelAlpha) {
        $this->jitterTransparency = $alphaChannelAlpha;
    }

    public function setJitterSize($sizeAlpha) {
        $this->jitterSize = $sizeAlpha;
    }

    public function setVocabularyPreset($vocabulary = 'digits') {

    }

    public function setVocabularyCustom($vocabulary) {

    }

    public function setLength($length) {

    }

    public function output() {
        $this->prepareCanvas();
        $this->tileBackground();
        for ($i = 1; $i <= $this->length; $i++) {
            $this->Letters[$i] = $this->randomLetter();
        }

        $this->findXCoords();
        for ($i = 1; $i <= $this->length; $i++) {
            $this->writeLetter($i);
        }

        $this->writeToSession();
        $this->outputToBuffer();
    }

    private function addColor($color, $opacity = 100) {
        $colorRed = hexdec(substr($color, 0, 2));
        $colorGreen = hexdec(substr($color, 2, 2));
        $colorBlue = hexdec(substr($color, 4, 2));

        if ($opacity != 100) {
            $colorRed = round($colorRed / 100 * $opacity);
            $colorGreen = round($colorGreen / 100 * $opacity);
            $colorBlue = round($colorBlue / 100 * $opacity);

            $_red = dechex($colorRed);
            if (strlen($_red) < 2)
                $_red = '0' . $_red;
            $_green = dechex($colorGreen);
            if (strlen($_green) < 2)
                $_green = '0' . $_green;
            $_blue = dechex($colorBlue);
            if (strlen($_blue) < 2)
                $_blue = '0' . $_blue;
            $color = $_red . $_green . $_blue;
        }

        $this->AllocatedColors[$color] = imagecolorallocate($this->Image, $colorRed, $colorGreen, $colorBlue);

        return $color;
    }

    private function prepareCanvas() {
        $this->Image = imagecreatetruecolor($this->width, $this->height);
        $this->Colors = array_unique($this->Colors);
        foreach ($this->Colors as $color)
            $this->addColor($color);
        imagefill($this->Image, 1, 1, $this->AllocatedColors[$this->backgroundColor]);
    }

    private function tileBackground() {

    }

    private function writeLetter($i) {
        $letter = $this->Letters[$i];

        $angle = $this->applyJitterAngle();
        $size = $this->applyJitterSize();
        $color = $this->applyJitterTransparency();


        // print_r ($this->XCoords);

        $x = $this->XCoords[$i];
        $y = $this->findVerticalMiddle($letter, $size, $angle);



        imagettftext($this->Image, $size, $angle, $x, $y, $this->AllocatedColors[$color], $this->fontFace, $letter);
    }

    private function applyJitterAngle() {
        if (!$this->jitterRotation)
            return 0;
        return rand(0, $this->jitterRotation) - floor($this->jitterRotation / 2);
    }

    private function applyJitterSize() {
        if (!$this->jitterSize)
            return $this->fontSize;
        return rand(0, $this->jitterSize) - floor($this->jitterSize / 2) + $this->fontSize;
    }

    private function applyJitterTransparency() {
        if (!$this->jitterTransparency)
            return $this->fontColor;
        $tempTransparency = rand(0, $this->jitterTransparency);
        //echo $tempTransparency;
        return $this->addColor($this->fontColor, 100 - $tempTransparency);
    }

    private function findXCoords() {
        $Widths = array();
        $totalLength = 0;
        for ($i = 1; $i <= $this->length; $i++) {
            $Box = imagettfbbox($this->fontSize, 0, $this->fontFace, $this->Letters[$i]);
            //print_r ($Box);
            $_length = $Box[2] - $Box[6];
            //echo $_length;
            $Widths[$i] = $_length;
            $totalLength += $_length;
        }

        $n = ceil(($this->width - $totalLength) / ($this->length - 1 + 4));
        //echo $n;

        $prevWidth = $n * 2;
        for ($i = 1; $i <= $this->length; $i++) {
            $this->XCoords[$i] = $prevWidth;
            $prevWidth += $Widths[$i] + $n;
        }
    }

    private function findVerticalMiddle($letter, $size, $angle) {
        $Box = imagettfbbox($size, $angle, $this->fontFace, $letter);
        $height = $Box[7] - $Box[3];
        $startingY = round(($this->height - $height) / 2);
        return $startingY;
    }

    private function randomLetter() {
        $RandomVocabulary = array_rand($this->Vocabulary, 1);
        return $RandomVocabulary;
    }

    private function outputToBrowser() {
        header("Content-Type: image/png");
        imagepng($this->Image);
    }

    private function outputToBuffer() {
        ob_start();
        imagepng ($this->Image);
        $image = ob_get_clean();
        $_SESSION[$this->session_name . '_data'] = $image;
    }

}