<?php

require_once "__Field.php";
require_once "Select.php";

class Newforms_SelectYear extends Newforms_Select {
  
	public $options = array();
	public $lower = -10;
	public $upper = 3;
	public $default = null;

	public function __construct($Params = array()) {
		parent::__construct ($Params);
		for ($i = date('Y') + $this->lower; $i <= date ('Y') + $this->upper; $i++)
		  $this->options[$i] = $i;
		if (!$this->default)
		  $this->default = intval(date ('Y'));
	}
	
}