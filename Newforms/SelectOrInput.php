<?php

require_once '__Field.php';

class Newforms_SelectOrInput extends Newforms___Field {

    public $options = array();
    public $maxlength = "255";
    public $forceUseValues = false;
    protected $__selectValue = '';
    protected $__inputValue = '';
    public $other = null;
    public $null = null;
    public $autohide = false;
    public $show_input_if_empty = false;

    const SELECT_KEYWORD = 'select';
    const INPUT_KEYWORD = 'input';

    public function setValueIfEmpty ($value) {
        if ($this->__value) return;
        $this->setValue ($value);
    }

    public function setValue ($PostValue) {
    
    if (is_array ($PostValue)) {
      $this->__selectValue = isset ($PostValue[self::SELECT_KEYWORD]) ? $PostValue[self::SELECT_KEYWORD] : '';
      $this->__inputValue = isset ($PostValue[self::INPUT_KEYWORD]) ? $PostValue[self::INPUT_KEYWORD] : '';
      $this->__value = $this->__inputValue ? $this->__inputValue : $this->__selectValue;
      return;
    }
    // Setting default value
    if (isset ($this->options[$PostValue])) {
      $this->__value = $PostValue;
      $this->__selectValue = $PostValue;
      return;
    }
    if (in_array ($PostValue, $this->options)) {
      $PostKey = array_search($PostValue, $this->options);
      if (!$this->forceUseValues) {
        $this->__value = $PostKey;
        $this->__selectValue = $PostKey;
      } else {
        $this->__value = $PostValue;
        $this->__selectValue = $PostValue;
      }
      return;
    }
    
    $this->__value = $PostValue;
    $this->__inputValue = $PostValue;
  }

  protected function clean($value) {
    $value = parent::clean ($value);
    if ($value == '!') $value = '';
    return $value;
  }
	
  public function html() {
    if (!$this->form()->is_editable)
      return htmlspecialchars($this->__value);

    $id = 'newforms_' . $this->__name;
    $html = '';
    if (!empty ($this->options)) {
        $html .= sprintf ('<select name="%s[%s]" class="%s" id="%s">', $this->__name, self::SELECT_KEYWORD, $this->class, $id);

        if ($this->null)
            $html .= '<option value="">' . htmlspecialchars($this->null) . '</option>' . "\n";
        if (!is_array ($this->options)) {
            throw new ErrorException (sprintf ('[Newforms] Bad options for Newforms_SelectOrInput [%s]: expected: [array], got: [%s]', $this->__name, $this->options));
        }


        foreach ($this->options as $key => $value) {
            if ($this->forceUseValues) $key = $value;
                if ($key == $this->__selectValue)
                    $html .= '<option selected="selected" value="' .  htmlspecialchars($key) . '">' . htmlspecialchars($value) . '</option>' . "\n";
                else
                    $html .= '<option value="' .  htmlspecialchars($key) . '">' . htmlspecialchars($value) . '</option>' . "\n";
        }

      if ($this->other) {
        $html .= '<option ' . ( ($this->__inputValue || ($this->show_input_if_empty && !$this->__selectValue)) ? 'selected="selected"' : '') . ' value="!">' . htmlspecialchars($this->other) . '</option>' . "\n";
      }

  		$html .= '</select>';
  	}
    $html .= sprintf ('<div class="form_select_or_input_input"><input type="text" name="%s[%s]" value="%s" maxlength="%s" class="%s" /></div><br />',
                      $this->__name, self::INPUT_KEYWORD, htmlspecialchars($this->__inputValue), $this->maxlength, $this->class);

    if ($this->autohide) {
      ob_start();

      ?>
<script type="text/javascript">
  $(function () {
    if ($("#<?= $id ?>").val() != '!')
      $("#<?= $id ?>").siblings(".form_select_or_input_input").hide();
    $("#<?= $id ?>").change(function() {
      if ($(this).val() == '!') {
        $("#<?= $id ?>").siblings(".form_select_or_input_input").show();
      } else {
        $($("#<?= $id ?>").siblings(".form_select_or_input_input").children('input')[0]).attr ('value', '');
        $("#<?= $id ?>").siblings(".form_select_or_input_input").hide();
      }
    });
  });
</script>
      <?php

      $html .= ob_get_clean();
    }

    return $html;
  }
  
}