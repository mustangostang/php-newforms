<?php

abstract class lib_Newforms___Field {

    /**
     * Name of the field.
     * @todo Should be protected.
     * @var string
     */
    public $__name;
    /**
     * Value of the field.
     * @todo Should be protected.
     * @var mixed
     */
    public $__value = '';

    public $label = '';
    public $class = "";
    public $id = "";
    public $default = '';
    public $required = false;
    public $required_mock = false;
    public $required_nodefault = false;
    public $comment = "";

    public $__validationMethods = array();
    public $__staticDecorators  = array();
    /**
     * Array that hold JQuery functions.
     * @var array
     */
    protected $__jQuery = array();

    protected $__FormContainer = null;

    protected $__builtinValidationEnabled = true;

    public function __construct ($Params = array()) {
        foreach ($Params as $key => $value)
            $this->$key = $value;
        $this->setValue (isset ($Params['default']) ? $Params['default'] : '');
    }

    public function setForm (lib_Newforms $Form) {
        $this->__FormContainer = $Form;
        $this->class = trim (implode (' ', array ($this->form()->_css_input, $this->class)));
    }

    /**
     * Adds a method for validation.
     *
     * @param string $method Function name.
     */
    public final function addValidationMethod ($method) {
        $this->__validationMethods[] = $method;
    }

    public final function disableBuiltinValidation() {
        $this->__builtinValidationEnabled = false;
    }

    public final function addStaticDecorator ($method, $type = 'value') {
        $this->__staticDecorators[] = array ($method, $type);
    }

    public final function addJQuery ($event, $func) {
        $this->__jQuery[] = array ($event, $func);
    }

    /**
     * Return label with common suffix for all forms.
     *
     * @return string
     */
    public final function label() {
        $label = $this->label;
        if ($label === '') return '';
        $required = '';
        if (($this->required || $this->required_mock) && $this->form()->__asterisk)
            $required = $this->form()->required_suffix;
        if (preg_match('#[\.\?!:;]$#i', $label)) return $label . $required;
        return $label . $required . $this->__FormContainer->label_suffix;
    }

    public function as_br() {
        if ($this->isToBeSkipped()) return '';
        $enclose_tag = $this->form()->__enclose_in;
        $tag_start = $enclose_tag ? sprintf ('<%s id="newforms_%s_container">', $enclose_tag, $this->__name) : '';
        $tag_end = $enclose_tag ? sprintf ('</%s>', $enclose_tag) : '';
        $label = sprintf ('<label for="%s">%s</label>', $this->__name, $this->label());
        if (!$this->label()) $label = '';
        return $tag_start . sprintf ('%s%s%s', $label, $this->html_representation(), $this->errors_and_break()) . $tag_end;
    }

    public function as_dl() {
        if ($this->isToBeSkipped()) return '';
        return sprintf ('<dt id="newforms_%s_container">%s</dt>
      <dd>%s</dd>%s', $this->__name, $this->label(), $this->html_representation(), $this->errors_and_break());
    }

    /**
     * Adds errors and break code.
     * @return string
     */
    public function errors_and_break() {
        $Errors = $this->errors();
        $Output = array ( empty ($Errors) ? '' :  sprintf ('<div class="%s">%s</div>', $this->form()->_css_errors, $Errors) );
        if ($this->form()->_css_errors_before_break)
            $Output[] = $this->form()->_css_break;
        else
            array_unshift($Output, $this->form()->_css_break);
        return implode ('', $Output);
    }

    abstract public function html();

    public function html_not_editable() {
        if (is_array ($this->__value)) return '';
        return htmlspecialchars($this->__value);
    }

    protected final function static_text() {
        $text = $this->html_not_editable();
        foreach ($this->__staticDecorators as $Decorator) {
            list ($decoratorFunc, $type) = $Decorator;
            if ($type == 'value') {
                $text = call_user_func($decoratorFunc, $text);
                continue;
            }
            $text = call_user_func($decoratorFunc, $this);
        }
        return $text;
    }

    protected final function isToBeSkipped () {
        if (!$this->form()->is_editable && !$this->static_text())
            return true;
        return false;
    }

    public final function html_representation() {
        if (!$this->form()->is_editable)
            return $this->static_text();
        $html = $this->html();
        if ($this->comment) {
            $html .= sprintf('<p class="%s">%s</p>', $this->form()->_css_comment, $this->comment);
        }
        $html .= $this->jquery_code();
        return $html;
    }

    protected final function jquery_code() {
        if (empty ($this->__jQuery)) return '';
        $out = '<script type="text/javascript">' . "\n" . '$(function() {' . "\n";
        foreach ($this->__jQuery as $vals) {
            list ($event, $func) = $vals;
            if (!$event) {
                $out .= $func . "\n";
                continue;
            }
            $out .= sprintf ('  $("#newforms_%s").bind ("%s", function() { %s } );', $this->__name, $event, $func);
        }
        $out .= "\n});\n</script>";
        return $out;
    }

    /**
     * Returns cleaned value of current field.
     *
     * @return mixed
     */
    public final function cleaned_value() {
        return $this->clean($this->__value);
    }

    /**
     * @TODO: actually, it's undefined. Let's proceed to either raw or cleaned value.
     * @return mixed
     */
    public final function value() {
        return $this->__value;
    }

    /**
     * Returns the raw value of current field.
     * @return mixed
     */
    public final function raw_value() {
        return $this->__value;
    }

    /**
     * Function performing cleaning tasks.
     *
     * @param string $value
     * @return string
     */
    protected function clean($value) {
        if (is_string($value))
            return trim($value, ' -');
        return $value;
    }

    /**
     * Sets a value.
     *
     * @param mixed $value
     */
    public function setValue($value) {
        $this->__value = $value;
    }

    /**
     * Sets a value if current is empty.
     *
     * @param mixed $value
     */
    public function setValueIfEmpty($value) {
        if ($this->__value) return;
        $this->setValue ($value);
    }

    public function set_default ($value) {
        $this->setValueIfEmpty ($value);
    }

    /**
     * Returns string representation for all errors for current field.
     *
     * @return string
     */
    public final function errors() {
        if (!$this->form()->isBound()) return '';
        if (!$this->form()->is_editable) return '';
        if ($this->form()->suppress_errors) return '';
        $Err = $this->errorsList();
        if (empty ($Err)) return '';
        if ($this->form()->_css_errors_first_only)
            return $Err[0];
        return '<ul><li>' . implode ('</li><li>', $Err) . '</li></ul>';
    }

    /**
     * Returns array of errors for current field.
     *
     * @return array
     */
    public final function errorsList () {
        $Err = array();

        if ($this->__builtinValidationEnabled) {
            $ErrorsFromBuiltinValidation = $this->builtinValidation();
            if (!empty($ErrorsFromBuiltinValidation)) {
                foreach ($ErrorsFromBuiltinValidation as $e)
                    $Err[] = $e;
            }
        }

        foreach ($this->__validationMethods as $method) {
            if (strpos ($method, '::')) {
                list($class) = explode('::', $method);
                if (!in_array($class, get_declared_classes())) {
                    if (startswith($class, 'Form')) {
                        $form = substr ($class, 4);
                        if (is_file (APPROOT. "forms/" . $form . '.php')) {
                            require APPROOT. "forms/" . $form;
                            break;
                        }
                    }
                }
            }
            $e = call_user_func($method, $this);
            if ($e) $Err[] = $e;
        }
        return $Err;
    }

    /**
     * Function that provides built-in validations for different form fields.
     *
     * @return array of string
     */
    protected function builtinValidation() {
        $Err = array();
        if ($this->required && !$this->cleaned_value()) {
            $Err[] = 'Поле обязательно должно быть заполнено.';
        } else if ($this->required_nodefault && $this->cleaned_value() == $this->default) {
            $Err[] = 'Поле обязательно должно быть заполнено.';
        }
        return $Err;
    }

    public final function sibling ($name) {
        if (!$this->__FormContainer) return null;
        if (!isset ($this->__FormContainer->$name)) return null;
        return $this->__FormContainer->$name;
    }

    /**
     * Returns form for this Field.
     *
     * @return lib_Newforms
     */
    public final function form() {
        return $this->__FormContainer;
    }

    public function __toString() {
        return $this->html_representation();
    }

}