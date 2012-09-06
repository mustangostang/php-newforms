<?php

require_once '__Field.php';

class Newforms_Checkbox extends Newforms___Field {
  
  public function html() {
  	$html = '<div class="form_checkboxset_container">';
  			if ($this->isSelected($this->value()))
  			 $html .= sprintf ('<div class="form_checkboxset %s"><input type="checkbox" name="%s" checked="checked" /></div>', 
  			                   $this->class, $this->__name);
  			else 
         $html .= sprintf ('<div class="form_checkboxset %s"><input  type="checkbox" name="%s" /></div>', 
                           $this->class, $this->__name);
  	$html .= "</div>";
    return $html;
  }
  
  public function html_not_editable() {
  	return $this->html();
    return $this->value();
  }
  
  public function clean($value) {
  	return $value;
  }
  
  public function setValue ($value) {
    $this->__value = !empty ($value);
  }
  
  private function isSelected ($value) {
  	if ($this->value())
  	 return true;
    return false;
  }
  
  private function optionValue ($value) {
  	return $value;
  }
  
}