<?php

require_once '__Field.php';

class Newforms_CheckboxSet extends Newforms___Field {

  public $options = array();
  /**
   * Forces key select for option value.
   *
   * @var bool
   */
  public $forceUseKeys = false;
  /**
   * Forces value select for option value.
   *
   * @var unknown_type
   */
  public $forceUseValues = false;
  public $lcase_first = false;

  public $glue_with = false;
  
  public $max = -1;

  private $__typeUsed = null;

  const TYPE_KEYS = 1;
  const TYPE_VALUES = 2;

  public function html() {
    $html = sprintf('<div class="form_checkboxset_container" id="newforms_%s">', $this->__name);
    if (!empty ($this->options)) {
      foreach ($this->options as $key => $value) {
        if ($this->isSelected($key, $value))
          $html .= sprintf ('<div class="form_checkboxset %s"><input type="checkbox" name="%s[%s]" checked="checked" /><label for="%s[%s]">%s</label></div>',
              $this->class, $this->__name, $this->optionValue($key, $value), $this->__name, $this->optionValue($key, $value), htmlspecialchars($value));
        else
          $html .= sprintf ('<div class="form_checkboxset %s"><input  type="checkbox" name="%s[%s]" /><label for="%s[%s]">%s</label></div>',
              $this->class, $this->__name, $this->optionValue($key, $value), $this->__name, $this->optionValue($key, $value), htmlspecialchars($value));
      }
    }
    $html .= "</div>";
    
    if ($this->max > 0) {
    ob_start(); ?>
    
    <script type="text/javascript" charset="utf-8">
        $(function() {
           var container = $("#newforms_<?= $this->__name ?>");
           $("input", container).change(function() {
               var count = $("input:checked", container).length;
               if (count >= <?= $this->max ?>) {
                   $('input[type=checkbox]', container).not(':checked').each (function() {
                       $(this).attr ('disabled', 'disabled');
                       $(this).nextAll ("label").addClass ("disabled");
                   });
               } else {
                   $('input[type=checkbox]', container).attr ("disabled", "");
                   $("label", container).removeClass ("disabled");
               }
           });
           $($("input", container).get(0)).trigger ('change');
        });
    </script>
    
    
    <?php $html .= ob_get_clean();
    }
    
    return $html;
  }

  public function html_not_editable() {
    $Data = array();
    foreach ($this->options as $key => $value) {
      if ($this->isSelected($key, $value)) {
        if ($this->lcase_first && !empty ($Data)) {
          if (function_exists('mb_strtoupper')) {
            $fc = mb_strtolower(mb_substr($value, 0, 1, 'utf-8'), 'utf-8');
            $value = $fc.mb_substr($value, 1, mb_strlen($value, 'utf-8'), 'utf-8');
          }
        }
        $Data[] = $value;
      }
    }
    return htmlspecialchars(implode (', ', $Data));
  }

  public function clean($value) {
    $v = $this->value_as_array($value);
    if (!$this->glue_with) return $v;
    return implode ($this->glue_with, $v);
  }

  private function value_as_array ($value) {
    if (!is_array ($value)) {
      return array();
    }
    return array_values ($value);
  }
  
  public function setValueIfEmpty($value) {
      $this->setValue ($value);
  }  

  public function setValue ($value) {
    if (empty ($value)) {
      $this->__value = array();
      return;
    }
    if (!is_array ($value) && $this->glue_with) {
        $this->__value = explode ($this->glue_with, $value);
        return;
    }
    if ($value !=  array_values ($value)) {
      $this->__value = array_keys ($value);
      return;
    }
    // print_r ($value);
    $this->__value = array_values ($value);
  }


  private function optionValue ($key, $value) {
    if ($this->determineType() == self::TYPE_KEYS)
      return $key;
    if ($this->determineType() == self::TYPE_VALUES)
      return $value;
    throw new Exception();
    return '';
  }

  private function isSelected ($key, $value) {
    if ($this->determineType() == self::TYPE_KEYS && in_array ($key, $this->value()))
      return true;
    if ($this->determineType() == self::TYPE_VALUES && in_array ($value, $this->value()))
      return true;
    return false;
  }

  private function determineType() {
    if ($this->__typeUsed) return $this->__typeUsed;
    if ($this->forceUseKeys) {
      $this->__typeUsed = self::TYPE_KEYS;
      return $this->__typeUsed;
    }
    if ($this->forceUseValues) {
      $this->__typeUsed = self::TYPE_VALUES;
      return $this->__typeUsed;
    }
    if ($this->options == array_values ($this->options)) {
      $this->__typeUsed = self::TYPE_VALUES;
      return $this->__typeUsed;
    }
    $this->__typeUsed = self::TYPE_KEYS;
    return $this->__typeUsed;
  }

}