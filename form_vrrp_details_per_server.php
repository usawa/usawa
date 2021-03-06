<?php

require_once("include/usawa_base.inc.php");

function form_vrrp_details_per_server($virtual_router_id = NULL)
{
  global $mysqli;
  
  if ( $virtual_router_id ) {
  
     $sql = "select 
                lb_id, 
                vrrp_name, 
                name as server_name, 
                state,
                priority 
              from 
                (select 
                      s.name, 
                      v.name as vrrp_name, 
                      v.virtual_router_id, 
                      s.lb_id 
                  from server s, vrrp_instance v
                  where s.cluster_id = v.cluster_id and v.virtual_router_id='$virtual_router_id') as tmp_ids

              left join vrrp_details_per_server using(virtual_router_id,lb_id)";

    if ( ($res = $mysqli->query($sql) ) && $res->num_rows) {
      $row = $res->fetch_assoc();
      extract($row);
    }
  } else {
    return(false);
  }
    
?>

  <form name = "vrrp_details_form" method="POST">
  <fieldset>
    <legend>Server details for VRRP instance <?php echo $vrrp_name ?></legend>
    <div class="error_box"></div>
    
    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="f_type" value="vrrp_details_per_server" />
    <input type="hidden" name="virtual_router_id" value="<?php echo $virtual_router_id ?>" />

<?php
  $cpt_server = 0;
  
  $validate = "";
  
  $res->data_seek(0);
  while ( $row = $res->fetch_assoc() )
  {
    $cpt_server++;
    extract($row);

      $validate .= "{
  name: 'priority[$lb_id]',
  rules: 'is_natural|less_than[256]'
},";

?>        
    <div>
      <label><?php echo $server_name; ?> Prio.</label>
      <input type="text" style="width:3em; display:inline" name="priority[<?php echo $lb_id ?>]" maxlength="3" value="<?php echo (!is_null($priority))?$priority:VRRP_DEFAULT_PRIORITY ?>" />
      <input type="radio" name="state[<?php echo $lb_id ?>]" value="MASTER" <?php echo (@$state=="MASTER")?'checked="checked"':"" ?>/>MASTER
      <input type="radio" name="state[<?php echo $lb_id ?>]" value="BACKUP" <?php echo (!@$state||$state=="BACKUP")?'checked="checked"':"" ?>/>BACKUP
    </div>

<?php
  }

?>
    <div><label for="buttons">&nbsp;</label> <input class="styled-button-10" type="submit" value="Submit" /></div>
    
    
  </fieldset>
  </form>
  <script type="text/javascript">
var validator = new FormValidator('vrrp_details_form', [
<?php
  echo $validate;
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


form_vrrp_details_per_server($virtual_router_id);