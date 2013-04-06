<?php

require_once("include/usawa_base.inc.php");


function form_track_script($virtual_router_id = NULL)
{
  global $mysqli;

  
  // Check VRRP instance
  if ( $virtual_router_id ) {

    // Get vrrp_instance name
    $sql = "select name from vrrp_instance where virtual_router_id='$virtual_router_id'";
    $res = $mysqli->query($sql);
    if($res && $res->num_rows)
    {
      list($vrrp_name) = $res->fetch_array();
    } else {
?>
  <p>No such VRRP instance</p>
<?php
      return false;
    }
  } else {

?>
  <p>VRRP instance must be specified</p>
<?php
    return false;
  }

  // Get track scripts in vrrp
  $sql = "select
            t.script_id,
            name as script_name,
            t.weight
          from track_script t, vrrp_script v
          where t.script_id = v.script_id
          and t.virtual_router_id='$virtual_router_id'";

  if ($res_track = $mysqli->query($sql) )
  {
    $count_track_scripts = $res_track->num_rows;
  } else {
?>
  <p>SQL error. Please check database connectivity.</p>
  
<?php
    return false;
  }
  
  // get vrrp scripts not in vrrp
  $sql = "select 
            script_id, 
            name as script_name 
          from 
            vrrp_script 
          where script_id not in 
            (select script_id from track_script where virtual_router_id='$virtual_router_id')";
              
  // Found
  if($res_vrrp = $mysqli->query($sql) )
  {
    $count_vrrp_scripts = $res_vrrp->num_rows;
  } else {
?>
  <p>SQL error. Please check database connectivity.</p>
<?php
    return false;
  }
  
  // No VRRP scripts
  if($count_track_scripts == 0 && $count_vrrp_scripts == 0)
  {
?>
  <p>No VRRP scripts. Nothing to do</p>
<?php
    return false;
  }
?>

  <form name="track_script_form" method="POST">
  <fieldset>
    <legend>Add track script for VRRP instance <?php echo $vrrp_name ?></legend>
    <div class="error_box"></div>
    
<?php
      
?>
    <input type="hidden" name="virtual_router_id" value="<?php echo $virtual_router_id ?>" />    

    <input type="hidden" name="f_type" value="track_script" />

    <input type="hidden" name="action" value="update" />
    
<!--     Pass One : VRRP scripts in instance -->
<?php
  $script_count=0;
  while ($row = $res_track->fetch_assoc() )
  {
    extract($row);
?>
    <input type="hidden" name="script[<?php echo $script_count ?>]" value="<?php echo $script_id ?>" />
    
    <div>
      <label>Vrrp script</label>
      <input type="checkbox" name="track[<?php echo $script_id ?>]" value="1" checked="checked" />
      <?php echo $script_name ?>
      &nbsp;&nbsp;&nbsp;&nbsp;
      Weight
      <input type="text" style="width:4em; display:inline" name="weight[<?php echo $script_id ?>]" maxlength="4" value="<?php echo $weight?$weight:"" ?>" />

    </div>

<?php
  $script_count++;
  }

  // Pass Two : Add a new VRRP script  
  if($count_vrrp_scripts)
  {
?>
    <div>
      <label for="script_id">new VRRP Script</label>
      <select name="new_script_id">
        <option value="">-</option>
<?php

    while( $row = $res_vrrp->fetch_assoc() )
    {
      extract($row);
?>
        <option value="<?php echo $script_id ?>"><?php echo $script_name ?></option>
<?php
    }
        
?>
      </select>
    &nbsp;&nbsp;&nbsp;&nbsp;
    Weight<input style="width:4em; display:inline" type="text" maxlength="4" name="new_weight" />
<?php

  } // if($count_vrrp_scripts)
  
?>
    </div>
  
    <div><label for="buttons">&nbsp;</label> <input class="styled-button-10" type="submit" value="Submit Form" /></div>
    
  </fieldset>
  </form>
  <script type="text/javascript">
var validator = new FormValidator('track_script_form', [
{
  name: 'weight',
  rules: 'integer|greater_than[-255]|less_than[255]'
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

if(isset( $_REQUEST['virtual_router_id'] ) ) $virtual_router_id = $_REQUEST['virtual_router_id']; else $virtual_router_id= NULL;

form_track_script($virtual_router_id); 
