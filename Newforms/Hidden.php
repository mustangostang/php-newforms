<?php

require_once '__Field.php';

class lib_Newforms_Hidden extends lib_Newforms___Field {

    public function html() {
        return sprintf('<input type="hidden" id="newforms_%s" name="%s" value="%s"/>', $this->__name,
                $this->__name, htmlspecialchars($this->__value));
    }

    protected function clean($value) {
        if (is_string($value)) {
            $value = trim($value, ' -');
            if (!strlen($value))
                return $value;
        }
        return $value;
    }

    public function as_br() {
        return $this->html();
    }

    public function as_dl() {
        return $this->html();
    }

}