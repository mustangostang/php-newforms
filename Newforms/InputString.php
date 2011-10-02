<?php

require_once '__Field.php';

class lib_Newforms_InputString extends lib_Newforms_Input {
	
	public $maxlength = 255;
	public $charmask = null; // Example: A-Za-z.\-_
	
	public function html() {
		return sprintf ('<input type="text" name="%s" value="%s" maxlength="%s" class="%s" />', $this->__name, htmlspecialchars($this->__value), $this->maxlength, $this->class);
	}
	
  public function builtinValidation() {
    $E = parent::builtinValidation();
    if (!$this->validate_charmask())
     $E[] = "Это поле может содержать только следующие символы: " . implode (', ', split('\b', stripslashes($this->charmask)));
    return $E;
  }
  
  protected function validate_charmask() {
    if ($this->charmask === null) return true;
    if (preg_match('#^['. $this->charmask .']*$#u', $this->value())) return true;
    return false;
  }
	
}