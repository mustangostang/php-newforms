<?php

require_once '__Field.php';

class Newforms_Optionset extends Newforms___Field {

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
  public $null = null;
  public $sorted = false;
  public $default = null;
  public $noescape = false;

  private $__typeUsed = null;

  const TYPE_KEYS = 1;
  const TYPE_VALUES = 2;

  public function html() {
    $html = '<div class="form_checkboxset_container">';
    $Options = $this->options;
    if (!empty ($Options)) {
      if ($this->sorted)
        $Options = $this->sortOptions ($Options);

      if ($this->null) {
        $html .= '<div class="form_checkboxset '. $this->class . '"><input type="radio" name="' . $this->__name . '" value=""><label for="' . $this->__name . '">' .
            $this->escapeValue($this->null) . '</label></div>' . "\n";
      }
      foreach ($Options as $key => $value) {
        if ($this->isSelected($key, $value))
          $html .= '<div class="form_checkboxset '. $this->class . '"><input type="radio" name="' . $this->__name . '" checked="checked" value="' .
              $this->escapeValue($this->optionValue($key, $value)) . '"/><label for="' . $this->__name . '">' . $this->escapeValue($value) . "</label></div>\n";
        else
          $html .= '<div class="form_checkboxset '. $this->class . '"><input type="radio" name="' . $this->__name . '" value="' .
              $this->escapeValue($this->optionValue($key, $value)) . '"/><label for="' . $this->__name . '">' . $this->escapeValue($value) . "</label></div>\n";
      }
    }
    $html .= "</div>";
    return $html;
  }

  private function escapeValue ($value) {
    if ($this->noescape) return $value;
    return htmlspecialchars($value);
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
    if ($this->determineType() == self::TYPE_KEYS && $key == $this->value())
      return true;
    if ($this->determineType() == self::TYPE_VALUES && $value == $this->value())
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

  private function sortOptions($Options) {
    asort($Options);
    return $Options;
  }

}