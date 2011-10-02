<?php

require_once "__Field.php";
require_once "Select.php";

class Newforms_SelectMonth extends Newforms_Select {
  
	public $options = array();
	public $default = null;

	public function __construct($Params = array()) {
		parent::__construct ($Params);
		$this->options = array (
		  1 => 'январь', 2 => 'февраль', 3 => 'март', 4 => 'апрель', 5 => 'май', 6 => 'июнь',
		  7 => 'июль', 8 => 'август', 9 => 'сентябрь', 10 => 'октябрь', 11 => 'ноябрь', 12 => 'декабрь',
		 );
		if (!$this->default)
      $this->default = $this->options[intval (date ('m'))];
	}
	
}