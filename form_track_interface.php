<?php

require_once("include/usawa_base.inc.php");


function form_track_interface($virtual_router_id = NULL)
{
  global $mysqli;

  $network_interfaces=array();
  
  if ( $virtual_router_id ) {

    // get network interfaces    
    $sql = "select n.interface from network_details n, vrrp_instance v
            where n.cluster_id = v.cluster_id and v.virtual_router_id='$virtual_router_id'";
    $res = $mysqli->query($sql);
    
    // Found
    if($res && $res->num_rows)
    {
      while ($row = $res->fetch_assoc() )
      {
        $network_interfaces[]=$row['interface'];
      }
    } else {
?>
  <p>Error : no such VRRP instance or no interface</p>
<?php
      return false;
    }
  } else {
?>
  <p>No VRRP instance specified</p>
<?php
    return false;
  }
  
  // Get vrrp_instance name
  $sql = "select name from vrrp_instance where virtual_router_id='$virtual_router_id'";
  $res = $mysqli->query($sql);
  list($vrrp_name) = $res->fetch_array();
  
/*
    $sql = "select 
              interface,
              weight 
            from 
              (select 
                v.cluster_id, 
                v.virtual_router_id, 
                n.interface 
              from 
                vrrp_instance v, 
                network_details n 
              where 
                v.cluster_id=n.cluster_id 
                and v.virtual_router_id='$virtual_router_id'
              ) as tbl
            left join track_interface 
            using (virtual_router_id, interface)";

    
    if(($res = $mysqli->query($sql)) && $res->num_rows) {
      $row = $res->fetch_assoc();
      extract($row);
    }
*/
?>

  <form name="track_interface_form" method="POST">
  <fieldset>
    <legend>Track Interfaces for VRRP instance <?php echo $vrrp_name ?></legend>
    <div class="error_box"></div>
    
<?php
      
?>
    <input type="hidden" name="virtual_router_id" value="<?php echo $virtual_router_id ?>" />    

    <input type="hidden" name="f_type" value="track_interface" />

    <input type="hidden" name="action" value="update" />
<?php
  foreach($network_interfaces as $interface)
  {
    $track = false ;
    $weight = NULL ;
    
    $sql = "select weight 
            from track_interface
            where virtual_router_id='$virtual_router_id' and interface='$interface'";
    $res = $mysqli->query($sql);
    if($res && $res->num_rows)
    {
      list($weight) = $res->fetch_array();
      $track = true ;
    }
        
?>    
    <div>
      <label for="interface_<?php echo $interface ?>">Interface <?php echo $interface ?></label>
      <input type="checkbox" name="track[<?php echo $interface ?>]" value="1" <?php echo $track?'checked="checked"':"" ?>/>
      Weight
      <input type="text" style="width:4em; display:inline" name="weight[<?php echo $interface ?>]" maxlength="4" value="<?php echo $weight?$weight:"" ?>" />

      </div>
<?php
  }
?>  

    <div><label for="buttons">&nbsp;</label> <input class="styled-button-10" type="submit" value="Submit Form" /></div>
    
    
  </fieldset>
  </form>
  <script type="text/javascript">
var validator = new FormValidator('track_interface_form', [
<?php
  foreach($network_interfaces as $interface)
  {
?>
{
  name: 'weight[<?php echo $interface ?>]',
  rules: 'integer|greater_than[-255]|less_than[255]'
},
<?php
  }
?>
{}], function(errors, event) {
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

form_track_interface($virtual_router_id); 
