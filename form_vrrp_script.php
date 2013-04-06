<?php

require_once("include/usawa_base.inc.php");


function form_vrrp_script($script_id = NULL)
{
  global $mysqli;

  if ($script_id)
  {
    $sql = "select 
              name, 
              script, 
              `interval`, 
              weight, 
              fall, 
              rise 
            from 
              vrrp_script 
            where
              script_id='$script_id'";

    
    if(($res = $mysqli->query($sql)) && $res->num_rows) {
      $row = $res->fetch_assoc();
      extract($row);
    } else {
?>
  <p>Unknown script</p>
<?php
      return false;
    }
  }
?>
  <form name="vrrp_script_form" method="POST">
  <fieldset>
    <legend>VRRP Script <?php echo @$name?$name:"" ?></legend>
    <div class="error_box"></div>
    
<?php
    if ( ! is_null($script_id) ) 
    {
      
?>
    <input type="hidden" name="script_id" value="<?php echo $script_id ?>" />    
    
    <input type="hidden" name="old_name" value="<?php echo $name ?>" />
<?php

    }

?>    
    <input type="hidden" name="f_type" value="vrrp_script" />

    <input type="hidden" name="action" value="update" />
    
    <div><label for="name">Name</label> <input type="text" maxlength="255" name="name" value="<?php echo @$name?$name:"" ?>" /></div>

    <div><label for="script">Script</label> <input type="text" maxlength="512" name="script" value="<?php echo @$script?$script:"" ?>" /></div>

    <div><label for="interval">Exec. Interval</label> <input type="text" maxlength="4" name="interval" value="<?php echo @$interval?$interval:VRRP_DEFAULT_SCRIPT_INTERVAL ?>" /></div>

    <div><label for="weight">Weight</label> <input type="text" maxlength="4" name="weight" value="<?php echo (!is_null(@$weight))?$weight:VRRP_DEFAULT_SCRIPT_WEIGHT ?>" /></div>

    <div><label for="fall">Number of KO</label> <input type="text" maxlength="4" name="fall" value="<?php echo @$fall?$fall:VRRP_DEFAULT_SCRIPT_FALL ?>" /></div>
    
    <div><label for="rise">Number of OK</label> <input type="text" maxlength="4" name="rise" value="<?php echo @$rise?$rise:VRRP_DEFAULT_SCRIPT_RISE ?>" /></div>

    <div><label for="buttons">&nbsp;</label> <input class="styled-button-10" type="submit" value="Submit Form" /></div>
    
  </fieldset>
  </form>
  <script type="text/javascript">
var validator = new FormValidator('vrrp_script_form', [{
    name: 'name',
    display: 'Name',
    rules: 'required|alpha_dash'
}, {
    name: 'script',
    display: 'Script',
    rules: 'required|alpha_dash'
}, {
    name: 'interval',
    display: 'Interval',
    rules: 'is_natural'
}, {
    name: 'weight',
    display: 'Weight',
    rules: 'integer|less_than[255]|greater_than[-255]'
}, {
    name: 'fall',
    display: 'Fall',
    rules: 'integer|is_natural_no_zero'
}, {
    name: 'rise',
    display: 'Rise',
    rules: 'integer|is_natural_non_zero'
}], function(errors, event) {
    var SELECTOR_ERRORS = $('.error_box'),
        SELECTOR_SUCCESS = $('.success_box');
        
    if (errors.length > 0) {
        SELECTOR_ERRORS.empty();
        
        for (var i = 0, errorLength = errors.length; i < errorLength; i++) {
            SELECTOR_ERRORS.append(errors[i].message + '<br />');
        }
        
    /*    SELECTOR_SUCCESS.css({ display: 'none' }); */
        SELECTOR_ERRORS.fadeIn(200);
    } else {
        SELECTOR_ERRORS.css({ display: 'none' });
        /* SELECTOR_SUCCESS.fadeIn(200); */
    }
    /*
    if (event && event.preventDefault) {
        event.preventDefault();
    } else if (event) {
        event.returnValue = false;
    } */
});

  </script>
  
<?php

}


if(isset( $_REQUEST['script_id'] ) ) $script_id = $_REQUEST['script_id']; else $script_id= NULL;

form_vrrp_script($script_id); 
