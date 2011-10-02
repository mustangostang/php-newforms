<?php

require_once '__Field.php';

class lib_Newforms_Input extends lib_Newforms___Field {

    public $maxlength = 255;
    public $first_letter_uppercase = false;
    public $placeholder = '';

    public function html() {
        return sprintf ('<input type="text" id="newforms_%s" name="%s" value="%s" maxlength="%s" class="%s"%s />', $this->__name,
                $this->__name, htmlspecialchars($this->__value), $this->maxlength, $this->class,
                ($this->placeholder ? sprintf (' placeholder="%s"', $this->placeholder) : '')
                );
    }

    protected function clean($value) {
        if (is_string($value)) {
            $value = trim($value, ' -');
            if (!strlen ($value)) return $value;
            if ($this->first_letter_uppercase) {
                $newvalue = lib_Encoding_UTF::toWindows1251($value);
                if (lib_Encoding_UTF::fromWindows1251($newvalue) == $value) {
                    $value = lib_Encoding_UTF::to_upper($newvalue[0]) . substr ($newvalue, 1);
                    $value = lib_Encoding_UTF::fromWindows1251($value);
                }
            }
        }
        return $value;
    }

}