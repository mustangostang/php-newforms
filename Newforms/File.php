<?php

// Please remember that you should set form enctype="multipart/form-data" in order to upload files!

require_once '__Field.php';

class lib_Newforms_File extends lib_Newforms___Field {

  public $maxlength = 255;
  public $allowed_extensions = array();
  public $max_size = 10240;
  public $tmp_root = '';
  public $allow_from_web = false;
  /* Restricts files to images (.gif, .jpg, .png) */
  public $allow_images_only = false;
  public $preview = '';
  public $preview_type = '';
  public $delete = array();

  public function html() {
      $s = '';
      if ($this->preview) $s .= $this->preview_form();
      if ($this->preview && $this->delete) {
          $s .= sprintf ('<div class="form_checkboxset"><input type="checkbox" name="%s__delete" /> <label for="%s__delete">удалить</label></div>', $this->__name, $this->__name);
      }
      $s .= $this->upload_form();
      return sprintf ('<div class="newforms_inner_container">%s</div>', $s);
    }

  private function preview_form() {
      if (!$this->preview) return '';
      if ($this->preview_type == 'image')
        return sprintf ('<div><img src="%s" alt="" /></div>', $this->preview);
      return sprintf ('<div><a href="%s">Просмотр</a></div>', $this->preview);
  }

  private function upload_form() {
    if (!$this->allow_from_web)
      return sprintf ('<input type="file" name="%s" class="%s" />', $this->__name, $this->class);
    return sprintf ('<div class="newforms_inner_comment">загрузите файл:</div><input type="file" name="%s" class="%s" />
<div class="newforms_inner_comment">или впишите интернет-адрес:</div><input type="text" name="%s___web" class="%s" value="%s" />', $this->__name, $this->class, $this->__name, $this->class, $this->__web_value());
  }

  private function __web_value() {
    if (!$this->__value || !is_object ($this->__value)) return 'http://';
    $v = $this->__value->web_address;
    if (!$v) return 'http://';
    return $v;
  }

  public function html_not_editable() {
    if (!$this->__value) return '';
    return "File";
  }

  public function setValue ($value) {
    $this->__value = '';

    // Ignoring $value from $_POST, it's irrelevant to files.
    if (isset ($_FILES[$this->__name])) {
      $this->__value = new lib_Newforms_File_Object ($this); return;
    }
    if (!empty($_POST[$this->__name . '___web'])) {
      $this->__value = new lib_Newforms_File_Object ($this); return;
    }


     
  }

  public function builtinValidation() {
    $E = array();

    if ($this->delete && !empty ($_POST[$this->__name . '__delete'])) {
        foreach ($this->delete as $f) {
          if (is_file ($f)) unlink ($f);
        }
    }

    if (!$this->validate_if_exists()) {
      if (!$this->required)
        return $E;
      if (!$this->value()) return array ("Файл не загружен");
      $E[] = $this->value()->error() ? $this->value()->error() : "Файл не загружен.";
      return $E;
    }

    $error = $this->validate_if_noerrors();
    if ($error) {
      $E[] = "Произошла ошибка во время загрузки: $error.";
      return $E;
    }

    $error = $this->validate_size();
    if ($error) $E[] = $error;

    $error = $this->validate_extension();
    if ($error) $E[] = $error;

    $error = $this->validate_image();
    if ($error) $E[] = $error;

    return $E;
  }

  public function validate_if_exists() {
    $File = $this->value();
    if (!$File) return false;
    return ($File->exists());
  }

  public function validate_if_noerrors() {
    $File = $this->value();
    if ($File->ok) return '';
    return $File->error();
  }

  public function validate_size() {
    $File = $this->value();
    if (!$File) return '';
    if ($File->size > $this->max_size * 1024) {
      $max_size = sprintf ('%s кБ', $this->max_size);
      if ($this->max_size > 1024) {
        $max_size = sprintf ('%s МБ', round($this->max_size / 102.4) / 10);
      }
      return "Размер файла превышает максимальный (" . $this->max_size . " кБ).";
    }
    return;
  }

  public function validate_extension() {
    if (empty($this->allowed_extensions)) return '';
    $File = $this->value();
    /* @var $File lib_Newforms_File_Object */
    if (!$File->checkExtension($this->allowed_extensions))
      return "Недопустимый тип файла: " . $File->extension() . ".";
    return '';
  }

  public function validate_image() {
    if (!$this->allow_images_only) return '';
    if (!file_exists($this->value()->tmp_name)) return '';
    try {
      list($width, $height, $type, $attr) = getimagesize($this->value()->tmp_name);
      if ($width > 0) return '';
    } catch (Exception $E) { }
    return "Этот файл - не картинка.";
  }

}

/**
*  A simple class that allows to read a single uploaded file by its name.
*/
class lib_Newforms_File_Reader {
    
    public $name, $mime, $size, $error, $contents;
    
    public function __construct($name) {
        $FileData = $_FILES[$name];
        $this->name = $FileData['name'];
        $this->mime = $FileData['type'];
        $this->size = $FileData['size'];
        $this->tmp_name = $FileData['tmp_name'];
        $this->error = $FileData['error'];
        $temp_file = TEMPROOT . 'foo.temp';
        move_uploaded_file($this->tmp_name, $temp_file);
        $this->contents = file_get_contents ($temp_file);
        unlink ($temp_file);
    }
    
}



class lib_Newforms_File_Object {

  public $name, $mime, $size, $tmp_name, $error = '', $ok = false;
  public $is_file = false;
  public $Parent;

  public $web_address = '';
  public $physical_file = '';

  public $AllowedImages = array ('.jpg', '.gif', '.png');
  public $AllowedMaterials = array ('.pdf', '.doc', '.docx', '.xls', '.xlsx', '.odt', '.odf', '.txt', '.zip', '.rtf', '.ppt', '.pptx', '.pps');
  public $AllowedCertificates = array ('.pdf', '.jpg', '.gif', '.png');

  /**
   * Constructs a movable file object from the name of a file input.
   *
   * @param string $name File input name
   */
  public function __construct($Parent) {
    $name = $Parent->__name;
    if (isset ($_FILES[$name]) && !empty ($_FILES[$name]['name'])) {
      $FileData = $_FILES[$name];
      $this->Parent = $Parent;
      $this->name = $FileData['name'];
      $this->mime = $FileData['type'];
      $this->size = $FileData['size'];
      $this->tmp_name = $FileData['tmp_name'];
      $this->error = $FileData['error'];
      $this->is_file = true;
      if ($this->error == UPLOAD_ERR_NO_FILE) { $this->is_file = false; }
      if ($this->error == UPLOAD_ERR_OK) $this->ok = true;
    } else {
      $this->is_file = false;
      if (empty ($_POST[$name . '___web'])) return;
      $remote_file = $_POST[$name . '___web'];
      if (!preg_match ('#^http://.+#i', $remote_file)) return;
      try {
          $this->web_address = $remote_file;
          $c = file_get_contents (($remote_file));
          if (!$c) $this->error = 'Не удалось загрузить файл';
          $temp_file = tempnam(sys_get_temp_dir(), 'Upload');
          file_put_contents ($temp_file, $c);
          $this->tmp_name = $temp_file;
          $url = parse_url ($remote_file);
          if (!isset ($url['path']))
            return;
          $pathinfo = pathinfo($url['path']);
          $this->name = $pathinfo['filename'];
          $this->size = filesize ($temp_file);
          if (!$this->size) $this->error = 'Файл нулевого размера.';
          if (isset ($pathinfo['extension']))
            $this->mime = $pathinfo['extension'];
          $this->is_file = true;
          $this->ok = true;
      } catch (ErrorException $E) {
          $this->error = 'Файл недоступен по указанному адресу.';
      }
    }
  }

  /**
   * @return bool
   */
  public function exists () {
    return $this->is_file;
  }

  /**
   * Moves a file to a new location.
   *
   * @param string $to
   * @return bool
   */
  public function move($to = '') {
    if (!$this->ok) return false;
    if (!$to) $to = rtrim ($this->Parent->tmp_root, '/') . ( $this->Parent->tmp_root ? '/' : '' ) . rand (10000, 99999) . ".tmp";
    try {
        if (!is_dir (dirname($to))) mkdir(dirname($to), 0777, true);
    } catch (ErrorException $E) {
        throw new Exception ("Can't create directory " . dirname($to));
    }
    if (move_uploaded_file($this->tmp_name, $to)) {
      chmod ($to, 0755);
      $this->physical_file = $to;
      return true;
    }
    $I = pathinfo($this->tmp_name);
    $r1 = rtrim (realpath($I['dirname']), '/\\');
    $r2 = rtrim (realpath(sys_get_temp_dir()), '/\\');
    //echo $r1, $r2, $r1 == $r2;
    if ($r1 == $r2) {
      if (file_exists ($to)) unlink ($to);
      if (!file_exists ($this->tmp_name)) throw new ErrorException ("Can't find uploaded file: " . $this->tmp_name);
      rename ($this->tmp_name, $to);
      chmod ($to, 0755);
      $this->physical_file = $to;
      return true;
    }
    throw new ErrorException ("Possible file upload attack!");
    return false;
  }

  public function get_name() {
      return $this->name;
  }

  /**
   * Checks if a file has one of the extensions allowed.
   *
   * @param array $Allowed
   * @return bool
   */
  public function checkExtension (array $Allowed) {
    return in_array ($this->extension(), $Allowed);
  }

  /**
   * Returns original file extension.
   *
   * @return string
   */
  public function extension() {
    preg_match('#(\.[A-z]+?)$#', strtolower($this->name), $Matches);
    $extension = isset ($Matches[1]) ? $Matches[1] : '&lt;неизвестное расширение&gt;';
    return $extension;
  }

  public function read() {
    if (!$this->physical_file) $this->move();
    return file_get_contents ($this->physical_file);
  }

  public function delete_file() {
    if (!$this->physical_file) unlink ($this->physical_form);
  }

  public function error() {
    if (is_string ($this->error)) return $this->error;
    if ($this->error == 1)
      return "The uploaded file exceeds the upload_max_filesize directive in php.ini: " . ini_get('upload_max_filesize');
    if ($this->error == 2)
      return "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form. ";
    if ($this->error == 3)
      return "The uploaded file was only partially uploaded. ";
    if ($this->error == 6)
      return "Missing a temporary folder. ";
    if ($this->error == 7)
      return "Failed to write file to disk.";
    if ($this->error == 8)
      return "File upload stopped by extension.";
    /* if ($this->error == 4)
      return "Ошибка при загрузке файла: не найден адрес."; */
  }

}