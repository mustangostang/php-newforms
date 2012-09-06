<?php

require_once '__Field.php';

class Newforms_Textarea extends Newforms___Field {
	
	public $cols = 80;
	public $rows = 12;
    public $wysiwyg = false;
	
	public function html() {
		$text = sprintf ('<textarea id="newforms_%s" name="%s" rows="%s" cols="%s" class="%s">%s</textarea>',
      $this->__name, $this->__name, $this->rows, $this->cols, $this->class, htmlspecialchars($this->__value));
                if ($this->wysiwyg) {
                    $text .= sprintf ('<script type="text/javascript">$(function() { $("#newforms_%s").wysiwyg() });</script>', $this->__name);
                }
                return $text;
	}
	
	public function html_not_editable() {
		return nl2br(htmlspecialchars($this->__value));
	}
	
}