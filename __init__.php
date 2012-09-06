<?php

require_once "__Field.php";

/**
 * Класс реализует функциональность а-ля django newforms (hence the name). Базовые вещи:
 *
 * Инициализация модели формы:
 *
 *   class FormAddBook extends Newforms {
 *     public function init() {
 *        $this->name   = new Newforms_Input (array ('required' => true));
 *        $this->author = new Newforms_SelectOrInput (array ('required' => true, 'options' => array ('Foo', 'Bar'), 'default' => 'Foo'));
 *     }
 *   }
 *
 * В контроллере:
 *
 *   if ($_POST) {
 *     $Form = new FormAddBook ($_POST);
 *     if ($Form->isValid()) {
 *        $Entry = Entry::__create ($Form->cleaned_data['name'], $Form->cleaned_data['author']);
 *        // Можно даже проще:
 *        $Entry = Entry::__create ($Form->name(), $Form->author());
 *        $this->_redirect ('/foo/');
 *     }
 *   } else {
 *     $Form = new FormAddBook;
 *   }
 *   $this->view->Form = $Form;
 *
 * В виде:
 *
 *   <form action="" method="POST">
 *     <div class="form">
 *       <label for="name">Имя, сестра, имя!</label>
 *       <?= $this->Form->name ?>
 *       <div class="form_errors"><?= $this->Form->name->errors() ?></div>
 *       <br />
 *       <!-- Или то же самое: -->
 *       <?= $this->Form->author->as_br() // Look, ma, auto names and labels! ?>
 *     </div>
 *     <input type="submit" class="form_submit" value="Добавить" />
 *   </form>
 *
 */

abstract class Newforms {

  protected $__bound = false;
  protected $__currentStep = 0;
  protected $__totalSteps = 0;
  protected $__Steps = array();
  protected $__Data = array();
  protected $__DataFromPreviousSteps = array();
  protected $__Groups = array();
  protected $__onlyFields = array();
  protected $__Exclude = array();
  /**
   * Field that form stopped at last time.
   * @var string
   */
  protected $__stopped_at = '';
  public    $__asterisk = false;
  public    $__enclose_in = 'div';
  public abstract function init();
  public $cleaned_data = array();
  public $label_suffix = ':';
  public $required_suffix = '<em>*</em>';
  public $is_editable = true;
  public $suppress_errors = false;
  /**
   * Contains fields that are disabled.
   *
   * @var unknown_type
   */
  protected $disabled = array();

  public $_css_errors = 'newforms_errors form_errors';
  public $_css_input = '';
  public $_css_break = '<br />';
  public $_css_comment = 'form_comment';
  public $_css_errors_before_break = true;
  public $_css_errors_first_only = false;

  /**
   * Constructor for Form instance.
   * @param mixed $Data Typically $_POST, $_REQUEST or some object to modify.
   */
  public function __construct ($Data = false) {
    if (empty ($Data)) $Data = false;
    $this->__bound = (!$Data === false);
    if ($this->__bound) $this->__Data = $Data;
    $this->init();
    $this->addNamesLabelsValues();
    $this->__setSteps();
  }

  public function bind ($Data) {
    $this->__bound = true;
    $this->__Data = $Data;
    $this->addNamesLabelsValues();
  }

  public function exists ($field) {
    return isset ($this->$field);
  }

  /**
   * Returns true if form is bound.
   *
   * @return bool
   */
  public function isBound() {
    return $this->__bound;
  }

  public function BoundData() {
    return $this->__Data;
  }

  /**
   * Returns all valid fields for this form.
   *
   * @return array();
   */
  protected function __fields() {
    $Fields = array_keys(get_object_vars($this));
    if (!empty ($this->__onlyFields)) {
      $Fields = $this->__onlyFields;
    }
    $NewFields = array();
    foreach ($Fields as $field) {
      if (in_array ($field, array ('__bound', '__Data', 'cleaned_data', 'label_suffix', 'required_suffix', 'is_editable', 'suppress_errors'))) continue;
      if (!is_object($this->$field)) continue;
      if (!empty ($this->disabled[$field])) continue;
      if (in_array ($field, $this->__Exclude)) continue;
      $NewFields[] = $field;
    }
    return $NewFields;
  }

  /**
   * Adds names, labels and values for all fields in form.
   *
   */
  protected function addNamesLabelsValues() {
    foreach ($this->__fields() as $field) {
      $this->$field->__name = $field;
      if (!$this->$field->label && $this->$field->label !== '') $this->$field->label = strtoupper($field[0]) . substr ($field, 1);
      $this->setFieldValue($field);
      $this->$field->setForm($this);
    }
  }

  /**
   * Internal function for setting field data.
   *
   * @param string $field
   */
  protected function setFieldValue ($field) {
    if ($this->$field instanceof Newforms_File)
      $this->$field->setValue('');
    if (!$this->__bound) return;
    if (is_array($this->__Data)) {
      if (isset($this->__Data[$field])) {
        $this->$field->setValue ($this->__Data[$field]);
        return;
      }
      return;
    }
    if (is_object ($this->__Data)) {
      if (isset($this->__Data->$field)) {
        $this->$field->setValue ($this->__Data->$field);
        return;
      }
      return;
    }
  }

  /**
   * Returns true if function is valid. Populated cleaned_data.
   *
   * @return bool
   */
  public function isValid() {
    if ($this->__totalSteps > 0) {
      if ($this->__currentStep < ($this->__totalSteps - 1)) return false;
      $PrevData = $this->__getFormData();
      if (!isset ($PrevData['__current_step']) || $PrevData['__current_step'] < $this->__currentStep) return false;
    }
    return $this->isCurrentValid();
  }

  /**
   * Returns true if current view (form or step) is valid.
   * @return bool
   */
  protected function isCurrentValid() {
    if (!$this->isBound()) return false;
    if ($this->suppress_errors) return false;
    foreach ($this->__fields() as $field) {
      if ($this->$field->errors()) return false;
      $this->cleaned_data[$field] = $this->$field->cleaned_value();
    }
    return true;
  }

  /**
   * Returns all errors of the form as a hash.
   *
   * @return unknown
   */
  public function errors() {
    $Errors = array();
    foreach ($this->__fields() as $field) {
      $E = $this->$field->errorsList();
      if ($E) $Errors[$field] = $E;
    }
    return $Errors;
  }

  // Adding to group functions.

  /**
   * Adds a named group as an alias for an array of $fieldNames. If $fieldNames is empty, acts as addGroupForUngrouped.
   *
   * @param string $groupName
   * @param array $fieldNames
   */
  public function addToGroup ($groupName, array $fieldNames = array()) {
    if (empty ($fieldNames)) {
      $this->addGroupForUngrouped($groupName);
      return;
    }
    $existingGroupFields = isset($this->__Groups[$groupName]) ? $this->__Groups[$groupName] : array();
    $this->__Groups[$groupName] = array_unique(array_merge ($existingGroupFields, $fieldNames));
  }

  public function clearGroup ($groupName) {
    unset ($this->__Groups[$groupName]);
  }

  /**
   * Alias for addToGroup().
   * @param string $groupName
   * @param array $fieldNames
   */
  public function addGroup ($groupName, array $fieldNames = array()) {
    $this->addToGroup($groupName, $fieldNames);
    return;
  }
  
  public function add_group ($groupName, array $fieldNames = array()) {
      $this->addToGroup($groupName, $fieldNames);
      return;      
  }

  /**
   * Adds all ungrouped fields to a group.
   *
   * @param string $groupName
   */
  protected function addGroupForUngrouped ($groupName) {
    $Fields = array_combine($this->__fields(), $this->__fields());
    foreach ($this->__Groups as $group) {
      foreach ($group as $groupedField)
        unset ($Fields[$groupedField]);
    }
    $Fields = array_values ($Fields);
    $this->addGroup($groupName, $Fields);
  }

  /**
   * Use only this group in form output and validation.
   *
   * @param string $groupName
   */
  public function useOnlyGroup ($groupName) {
    if (is_array ($groupName)) {
      $Fields = array();
      foreach ($groupName as $groupNameOne) {
        if (empty ($this->__Groups[$groupNameOne]))
          throw new Exception("There is no group $groupNameOne in " . get_class($this) . "!");
        $Fields = array_merge($Fields, $this->__Groups[$groupNameOne]);
      }
      $this->useOnlyFields($Fields);
      return;
    }
    if (empty ($this->__Groups[$groupName]))
      throw new Exception("There is no group $groupName in $this!");
    $this->useOnlyFields ($this->__Groups[$groupName]);
  }

  /**
   * User only these fields for form output and validation.
   *
   * @param array $Fields
   */
  public function useOnlyFields (array $Fields) {
    $this->__onlyFields = $Fields;
  }

  /**
   * Use all fields available.
   */
  public function useAllFields() {
    $this->__onlyFields = array();
  }

  // Excluding fields

  /**
   * Add following fields to be excluded.
   * @param string[] $fieldNames
   */
  public function exclude ($fieldNames) {
    if (!is_array($fieldNames)) $fieldNames = array ($fieldNames);
    $this->__Exclude = array_unique(array_merge ($this->__Exclude, $fieldNames));
  }

  /**
   * Exclude the following group.
   * @param string $group
   */
  public function excludeGroup ($group) {
    $this->exclude($this->__Groups[$group]);
  }

  /**
   * Clear exclude list.
   */
  public function excludeClear () {
    $this->__Exclude = array();
  }


  // Mapping functions

  public function mapGroup (&$Object, $groupName) {
    if (is_array ($groupName)) {
      foreach ($groupName as $groupNameOne)
        $this->mapFields ($Object, $this->__Groups[$groupNameOne]);
      return;
    }
    $this->mapFields ($Object, $this->__Groups[$groupName]);
  }

  public function mapAll (&$Object) {
    $this->mapFields($Object, $this->__fields());
  }

  public function mapFields (&$Object, array $Fields) {
    if ($Fields == array_values ($Fields)) {
      $Fields = array_combine($Fields, $Fields);
    }
    foreach ($Fields as $formId => $objectKey) {
      if (in_array ($formId, $this->__Exclude)) continue;
      $value = $this->$formId->cleaned_value();
      if (is_object($Object)) {
        $Object->$objectKey = $value;
      } else
        $Object[$objectKey] = $value;
    }
  }

  public function group_as_br($groupName) {
    if (empty ($this->__Groups[$groupName]))
      return '';
    $out = '';
    foreach ($this->__Groups[$groupName] as $Field) {
      if (in_array ($Field, $this->__Exclude)) continue;
      if (!isset ($this->$Field))
        throw new Exception (sprintf("Field `%s` (specified in group `%s`) is not found in %s", $Field, $groupName, get_class($this)));
      $out .= $this->$Field->as_br();
    }
    return $out;
  }

  public function group_as_dl($groupName, $as_array = false) {
    if (empty ($this->__Groups[$groupName]))
      return '';
    $out = array();
    foreach ($this->__Groups[$groupName] as $Field) {
      if (in_array ($Field, $this->__Exclude)) continue;
      if ($this->$Field->as_dl())
        $out[] = $this->$Field->as_dl();
    }
    if ($as_array)
      return $out;
    return implode ("", $out);
  }

  public function group_has_values ($groupName) {
    if (empty ($this->__Groups[$groupName]))
      return false;
    foreach ($this->__Groups[$groupName] as $Field) {
      if ($this->$Field->as_dl())
        return true;
    }
    return false;
  }

  /**
   * Return contents of form as a value.
   * @return array
   */
  public function values_as_array() {
    $Data = array();
    foreach ($this->__fields() as $field) {
      $v = $this->$field->cleaned_value();
      if ($v instanceof Newforms_File_Object) continue;
      $Data[$field] = $v;
    }
    return $Data;
  }

  /**
   * Quick set data from array ($field => $value)
   * @param array $Data
   */
  public function set_values_from_array(array $Data) {
    foreach ($Data as $field => $value) {
      if (!isset ($this->$field)) continue;
      $this->$field->setValue ($value);
    }
  }

  public function as_array() {
    return $this->values_as_array();
  }

  public function as_array_with_labels($preserve_duplicates = false, $flatten_arrays = false) {
    $Data = array();
    foreach ($this->__fields() as $field) {
      $value = $this->$field->cleaned_value();
      if (is_object ($value)) continue;
      if ($flatten_arrays && is_array ($value))
        $value = implode (', ', $value);
      if (!$preserve_duplicates)
        $Data[$this->$field->label] = $value;
      if ($preserve_duplicates)
        $Data[] = array ($this->$field->label => $value);
    }
    return $Data;
  }

  public function for_email($question = "<b>%s</b>", $answer = "%s", $separator = ': ', $linebreak = "<br /><br />") {
    $Data = $this->as_array_with_labels(true, true);
    $Lines = array();
    foreach ($Data as $D) {
      foreach ($D as $q => $a) {
        $q = sprintf ($question, $q);
        $a = sprintf ($answer, $a);
        $Lines[] = $q . $separator . $a;
      }
    }
    return implode ($linebreak, $Lines);
  }

  public function save_to ($dir) {
    $text = $this->for_email();
    $md5 = md5 ($text);
    $file = rtrim ($dir, '/') . '/' . $md5 . '.txt';
    file_put_contents($file, $text);
  }

  public function as_br($stop_at = '') {
    $out = $this->__formData();
    $show_current = !$this->__stopped_at;
    foreach ($this->__fields() as $Field) {
      if (!$show_current) {
        if ($Field == $this->__stopped_at)
          $show_current = true;
        continue;
      }
      $out .= $this->$Field->as_br();
      if ($stop_at && $Field == $stop_at) {
        break;
      }
    }
    $this->__stopped_at = $stop_at;
    return $out;
  }

  public function  __toString() {
      return $this->as_br();
  }

  public function as_dl($stop_at = '') {
    $out = '';
    $show_current = !$this->__stopped_at;
    foreach ($this->__fields() as $Field) {
      if (!$show_current) {
        if ($Field == $this->__stopped_at)
          $show_current = true;
        continue;
      }
      $out .= $this->$Field->as_dl();
      if ($stop_at && $Field == $stop_at) {
        break;
      }
    }
    $this->__stopped_at = $stop_at;
    return $out;
  }

  public function __call($method, $args) {
    if (isset ($this->$method))
      return $this->$method->cleaned_value();
    return null;
    throw new Exception ("Non-existent form field: $method");
  }

  public function asteriskForRequired ($bool) {
    $this->__asterisk = $bool;
  }
  
  public function set_enclose_in ($tag) {
    $this->__enclose_in = $tag;
  }

  // Enabling/disabling steps.

  public function disableField ($field) {
    $this->disabled[$field] = true;
  }

  public function enableField ($field) {
    unset ($this->disabled[$field]);
  }

  // Steps

  protected function __formData() {
    if (empty ($this->__DataFromPreviousSteps)) return '';
    unset ($this->__DataFromPreviousSteps['__step_data']);
    return '<input type="hidden" name="__step_data" value="' . base64_encode(gzcompress(serialize($this->__DataFromPreviousSteps))) . '" />';
  }

  protected function __getFormData() {
    if (!isset ($this->__Data['__step_data'])) return array();
    $Data = unserialize(gzuncompress(base64_decode($this->__Data['__step_data'])));
    foreach ($Data as $k => $v) {
      if (isset ($this->__Data[$k])) unset ($Data[$k]);
    }
    return $Data;
  }

  public function addStep ($index, $name, array $Groups) {
    $this->__Steps[] = array ('index' => $index, 'name' => $name, 'groups' => $Groups);
    $this->__totalSteps++;
  }

  public function currentStepIndex() {
    return $this->__Steps[$this->__currentStep]['index'];
  }

  public function currentStepName() {
    return $this->__Steps[$this->__currentStep]['name'];
  }

  protected function currentStepGroups() {
    return $this->__Steps[$this->__currentStep]['groups'];
  }

  protected function __setSteps() {
    if (!$this->__totalSteps) return;
    $PrevData = $this->__getFormData();
    $this->set_values_from_array($PrevData);
    foreach ($this->__Steps as $k => $Step) {
      $this->__currentStep = $k;
      $this->useAllFields();
      $this->useOnlyGroup($this->currentStepGroups());
      if (!$this->isCurrentValid()) break;
      $this->useAllFields();
    }
    if (!isset ($PrevData['__current_step']) || $PrevData['__current_step'] < $this->__currentStep) {
      $this->suppress_errors = true;
    }
    $this->__DataFromPreviousSteps = array_merge ($PrevData, $this->__Data);
    $this->__DataFromPreviousSteps['__current_step'] = $this->__currentStep;
  // if ($this->isCurrentValid())
  //  $this->__currentStep++;
  }

  public static function is_windows() {
    return (!empty ($_SERVER['WINDIR']));
  }

}