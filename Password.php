<?php

require_once '__Field.php';

class Newforms_Password extends Newforms___Field {
	
	public $maxlength = 255;
	public $minlength = 4;
	public $isConfirmationOf = null;
        public $placeholder = '';
	
	public function html() {
		return sprintf ('<input type="password" id="newforms_%s" name="%s" maxlength="%s" class="%s"%s />',
                        $this->__name, $this->__name, $this->maxlength, $this->class,
                        ($this->placeholder ? sprintf (' placeholder="%s"', $this->placeholder) : '')
                        );
	}
	
	protected function clean($value) {
		return $value;
	}
	
	public function builtinValidation() {
	  $E = parent::builtinValidation();
	  if (!$this->validate_minlength())
	   $E[] = "Минимальная длина пароля: " . $this->minlength . " " . lib_Russian_Noun::p2NomCount('знак', $this->minlength) . '.';
	  if (!$this->validate_confirmation())
	   $E[] = "Введенные пароли не совпадают.";
	  return $E;
	}
	
	public function validate_minlength() {
		if (!$this->minlength) return true;
		if (strlen($this->value()) >= $this->minlength) return true;
		return false;
	}
	
	public function validate_confirmation() {
		if (!$this->isConfirmationOf) return true;
		$confirmingField = $this->sibling($this->isConfirmationOf);
		if (!$confirmingField) return false;
		if ($this->value() == $confirmingField->value()) return true;
		return false;
	}
	
}