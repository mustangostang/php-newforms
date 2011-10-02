<?php

require_once '__Field.php';

class lib_Newforms_Select extends lib_Newforms___Field {

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
	/**
	 * Do no type guessing for static view and just return raw value.
	 *
	 * @var bool
	 */
	public $return_static_raw = false;

	private $__typeUsed = null;

	const TYPE_KEYS = 1;
	const TYPE_VALUES = 2;

  public function html() {
    $html = '';
    $Options = $this->options;
    if (empty ($Options) || !is_array($Options)) return '';
      if ($this->sorted)
        $Options = $this->sortOptions ($Options, $this->sorted);


      $html .= sprintf ('<select id="%s" name="%s"%s>', ($this->id ? $this->id : 'newforms_' . $this->__name), $this->__name,
                        $this->class ? sprintf (' class="%s"', $this->class) : ''
                      );
      if ($this->null) {
        $html .= '<option value="">' . htmlspecialchars($this->null) . '</option>' . "\n";
      }
      foreach ($Options as $key => $value) {
        if ($this->isSelected($key, $value))
         $html .= '<option selected="selected" value="' .  $this->escapeValue($this->optionValue($key, $value)) . '">' . $this->escapeValue($value) . '</option>' . "\n";
        else
         $html .= '<option value="' .  $this->escapeValue($this->optionValue($key, $value)) . '">' . $this->escapeValue($value) . '</option>' . "\n";
      }
      $html .= '</select><br />';

    return $html;
  }

  public function html_not_editable() {
    if ($this->return_static_raw)
      return htmlspecialchars($this->value());
    if ($this->determineType() == self::TYPE_VALUES)
      return htmlspecialchars($this->value());
    return isset ($this->options[$this->value()]) ? htmlspecialchars($this->options[$this->value()]) : '';
  }

  private function escapeValue ($value) {
  	if ($this->noescape) return $value;
  	return htmlspecialchars($value);
  }

  public function setValue($value) {
  	if (!$value) return; // If there's no value, use default.
  	parent::setValue($value);
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

  private function sortOptions($Options, $type = '') {
  	asort($Options);
  	return $Options;
  }

}