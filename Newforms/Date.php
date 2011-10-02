<?php

require_once '__Field.php';

class lib_Newforms_Date extends lib_Newforms___Field {

    public function html() {
        $date = strtotime ($this->__value);
        $day = date ('d', $date);
        $month = date ('m', $date);
        $year = date ('Y', $date);
        
        $hour = date ('H', $date);
        $minute = date ('i', $date);
        
        ob_start();
        ?>
            <input type="hidden" name="<?= $this->__name ?>" value="<?= $this->__value ?>" />
            <select name="<?= $this->__name ?>_day" id="newforms_<?= $this->__name ?>_day">
                <?php for ($i = 1; $i <= 31; $i ++) { ?>
                <option <?= $i == $day ? 'selected' : '' ?> value="<?= $i ?>"><?= $i ?></option>
                <?php } ?>
            </select>
        
            <select name="<?= $this->__name ?>_month" id="newforms_<?= $this->__name ?>_month">
                <?php for ($i = 1; $i <= 12; $i ++) { ?>
                <option <?= $i == $month ? 'selected' : '' ?> value="<?= $i ?>"><?= lib_Auxilary_Date::monthByNumberGen($i) ?></option>
                <?php } ?>
            </select>
        
            <select name="<?= $this->__name ?>_year" id="newforms_<?= $this->__name ?>_year">
                <?php for ($i = date ('Y') - 10; $i <= date ('Y') + 5; $i ++) { ?>
                <option <?= $i == $year ? 'selected' : '' ?> value="<?= $i ?>"><?= $i ?></option>
                <?php } ?>
            </select>
            <span style="padding-right: 10px">,</span>
            
            <select name="<?= $this->__name ?>_hour" id="newforms_<?= $this->__name ?>_hour">
                <?php for ($i = 0; $i <= 23; $i ++) { ?>
                <option <?= $i == $hour ? 'selected' : '' ?> value="<?= $i ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                <?php } ?>
            </select>
            <span>:</span>
            <select name="<?= $this->__name ?>_minutes" id="newforms_<?= $this->__name ?>_minutes">
                <?php for ($i = 0; $i <= 59; $i ++) { ?>
                <option <?= $i == $minute ? 'selected' : '' ?> value="<?= $i ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                <?php } ?>
            </select>
                  
        
        <?php return ob_get_clean();
    }
    
    public function setValue ($value) {
      $this->__value = $value;
      if (!empty ($_POST) && $this->__name) {
          $day    = $_POST[$this->__name . '_day'];
          $month  = $_POST[$this->__name . '_month'];
          $year   = $_POST[$this->__name . '_year'];
          $hour   = $_POST[$this->__name . '_hour'];
          $minute = $_POST[$this->__name . '_minutes'];
          $day = str_pad($day, 2, '0', STR_PAD_LEFT);
          $month = str_pad($month, 2, '0', STR_PAD_LEFT);
          $hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
          $minute = str_pad($minute, 2, '0', STR_PAD_LEFT);
          $this->__value = sprintf ('%s-%s-%s %s:%s:00', $year, $month, $day, $hour, $minute);
      }

    }    

    protected function clean($value) {
        if (is_string($value)) {
            $value = trim($value, ' -');
            if (!strlen ($value)) return $value;
        }
        return $value;
    }

}