<?php

require_once '__Field.php';

if (!function_exists('win_checkdnsrr')) {
  function win_checkdnsrr($host, $type='MX') {
      if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') { return; }
      if (empty($host)) { return; }
      $types=array('A', 'MX', 'NS', 'SOA', 'PTR', 'CNAME', 'AAAA', 'A6', 'SRV', 'NAPTR', 'TXT', 'ANY');
      if (!in_array($type,$types)) {
          user_error("checkdnsrr() Type '$type' not supported", E_USER_WARNING);
          return;
      }
      @exec('nslookup -type='.$type.' '.escapeshellcmd($host), $output);
      foreach($output as $line){
          if (preg_match('/'.$host.'/',$line)) { return true; }
      }
  }
}

// Define
if (!function_exists('checkdnsrr')) {
    function checkdnsrr($host, $type='MX') {
        return win_checkdnsrr($host, $type);
    }
}

class Newforms_InputEmail extends Newforms_Input {
	
	public $maxlength = 255;
        public $isConfirmationOf = null;
        public $placeholder = '';
	
	public function html() {
		return sprintf ('<input type="text" id="newforms_%s" name="%s" value="%s" maxlength="%s" class="%s"%s />',
                        $this->__name, $this->__name, htmlspecialchars($this->value()), $this->maxlength, $this->class,
                        ($this->placeholder ? sprintf (' placeholder="%s"', $this->placeholder) : '')
                        );
	}
	
	public function html_not_editable() {
		if (!$this->__value) return '';
		return "<a href=\"mailto:" . htmlspecialchars($this->__value) . "\">" . htmlspecialchars($this->__value) . "</a>";
	}
	
  public function builtinValidation() {
    $E = parent::builtinValidation();
    if (!$this->validate_email())
      $E[] = "Введенный вами адрес электронной почты неверен.";
    if (!$this->validate_confirmation())
      $E[] = "Введенные адреса электронной почты не совпадают.";
    return $E;
  }

  public function validate_confirmation() {
    if (!$this->isConfirmationOf) return true;
    $confirmingField = $this->sibling($this->isConfirmationOf);
    if (!$confirmingField) return false;
    if ($this->value() == $confirmingField->value()) return true;
    return false;
  }

  public function validate_email() {
    $email = $this->cleaned_value();
    if (!$email) return true;
    $isValid = true;
    $atIndex = strrpos($email, "@");
    if (is_bool($atIndex) && !$atIndex) {
      $isValid = false;
    } else {
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64) {
         // local part length exceeded
         $isValid = false;
      }
      else if ($domainLen < 1 || $domainLen > 255) {
         // domain part length exceeded
         $isValid = false;
      }
      else if ($local[0] == '.' || $local[$localLen-1] == '.') {
         // local part starts or ends with '.'
         $isValid = false;
      }
      else if (preg_match('/\.\./', $local)) {
         // local part has two consecutive dots
         $isValid = false;
      }
      else if (!preg_match('/^[A-Za-z0-9\-\.]+$/', $domain)) {
         // character not valid in domain part
         $isValid = false;
      }
      else if (preg_match('/\.\./', $domain)) {
         // domain part has two consecutive dots
         $isValid = false;
      } else if (!preg_match('/^(\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                 str_replace("\\","",$local))) {
         // character not valid in local part unless 
         // local part is quoted
         if (!preg_match('/^"(\\"|[^"])+"$/',
             str_replace("\\","",$local))) {
            $isValid = false;
         }
      }
      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
         // domain not found in DNS
         $isValid = false;
      }
   }
   return $isValid;
  	
  }
  
}