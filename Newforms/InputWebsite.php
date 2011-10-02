<?php

require_once '__Field.php';

if (!function_exists('win_checkdnsrr')) {

    function win_checkdnsrr($host, $type='MX') {
        if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
            return false;
        }
        if (empty($host)) {
            return false;
        }
        $types = array('A', 'MX', 'NS', 'SOA', 'PTR', 'CNAME', 'AAAA', 'A6', 'SRV', 'NAPTR', 'TXT', 'ANY');
        if (!in_array($type, $types)) {
            user_error("checkdnsrr() Type '$type' not supported", E_USER_WARNING);
            return false;
        }
        @exec($cmd = 'nslookup -type=' . $type . ' ' . escapeshellcmd($host), $output);
        foreach ($output as $line) {
            if (preg_match('/' . $host . '/', $line)) {
                return true;
            }
        }
    }

}

class Newforms_InputWebsite extends Newforms_Input {

    public $maxlength = 255;
    public $allow_url = false;

    public function html() {
        return sprintf('<input type="text" id="newforms_%s" name="%s" value="%s" maxlength="%s" class="%s" placeholder="http://" />',
                $this->__name, $this->__name, htmlspecialchars($this->value()), $this->maxlength, $this->class);
    }

    public function html_not_editable() {
        $url = $this->__value;
        if (!$url)
            return '';
        $url_with_http = $url;
        if (!preg_match('#^http#', $url_with_http))
            $url_with_http = "http://" . $url_with_http;
        return sprintf('<a href="%s" rel="nofollow" target="_blank">%s</a>', $url_with_http, $url);
    }

    public function builtinValidation() {
        $E = parent::builtinValidation();
        if (!$this->validate_website())
            $E[] = "Введенный вами адрес вебсайта неверен.";
        return $E;
    }

    public function validate_website() {
        $website = $this->cleaned_value();
        if (!$website)
            return true;
        $domain = rtrim(preg_replace('#^http://#', '', $website), '/');
        if (!$domain)
            return false;
        if ($this->allow_url)
            return true;
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            return win_checkdnsrr($domain, 'A');
        }
        return (!!checkdnsrr($domain, "A"));
    }

}