<?php

require_once '__Field.php';

class lib_Newforms_InputInteger extends lib_Newforms_Input {
	
	public $max = null;
	public $min = null;
	
	protected function clean ($value) {
		return intval($value); 
	}
	
  public function builtinValidation() {
    $E = parent::builtinValidation();
    if (!$this->validate_checkMax())
     $E[] = "Максимальное значение для этого поля: " . $this->max . ".";
    if (!$this->validate_checkMin())
     $E[] = "Минимальное значение для этого поля: " . $this->min . ".";
    return $E;
  }
	
	protected function validate_checkMax() {
    if (!$this->max === null) return true;
    if ($this->value() >= $this->max) return true;
    return false;
	}

  protected function validate_checkMin() {
    if (!$this->min === null) return true;
    if ($this->value() <= $this->min) return true;
    return false;
  }
	
}