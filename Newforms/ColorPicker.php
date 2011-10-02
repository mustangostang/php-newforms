<?php

/* requires jQuery */

require_once '__Field.php';

class lib_Newforms_ColorPicker extends lib_Newforms___Field {

	public $options = array();
	public $type = 'rgb';
	public $js_onselect = '';

	  public function html() {
    $html = '';
    $rand = rand(0, 100000);
    $Options = $this->options;
    if (!empty ($Options)) {
      $html .= sprintf ('
      <div class="newforms_colorpicker newforms_colorpicker_%s %s">
      <input name="%s" type="hidden" value="%s" />
      <ul>', $rand, $this->class, $this->__name, $this->value());
      foreach ($Options as $key => $value) {
        if ($this->type == 'rgb') {
         $html .= '<li><a href="#"  style="background-color: #' . $value . '" class="newforms_colorpicker_color_'. $key . '"><span>' . $key . '</span></a></li>' . "\n";
         continue;
        }
      }
      $html .= "</ul></div>";
      // jQuery select routinge
      $html .= '<script type="text/javascript">
        $(function() {
          $(".newforms_colorpicker_' . $rand . ' .newforms_colorpicker_color_' . $this->value() . '").addClass ("selected");' .
          ( $this->js_onselect ? $this->js_onselect . '("' . $this->value() . '");'  : '') .

          '$(".newforms_colorpicker_' . $rand . ' li a").click(function() {
            $(".newforms_colorpicker_' . $rand . ' li a").removeClass("selected");
            klass = $(this).attr("class").replace (/newforms_colorpicker_color_/, "");
            $(".newforms_colorpicker_' . $rand . ' input").val(klass);' .
            ( $this->js_onselect ? $this->js_onselect . '(klass);'  : '') .
            '$(this).addClass("selected");
            return false;
          });
        });
      </script>';
      $this->value();
    }
    return $html;
  }




}