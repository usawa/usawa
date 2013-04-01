<?php

require_once("include/usawa_base.inc.php");


function form_server($lb_id = NULL, $cluster_id = NULL)
{
  global $mysqli;
  global $SERVICE_BACKEND_LIST;
  
  if ( $lb_id ) {
  
    $sql = "select name, 
                    inet6_ntoa(ip_address) as ip_address, 
                    router_id, 
                    access_backend, 
                    service_backend, 
                    ssh_user, 
                    ssh_passphrase, 
                    ssh_public_key_path, 
                    ssh_private_key_path, 
                    service_path, 
                    cluster_id 
                    from server where lb_id = '".$lb_id."'";
    
    if ( ($res = $mysqli->query($sql) ) && $res->num_rows) {
      $row = $res->fetch_assoc();
      extract($row);
    }
  }
  
  // Cluster list
  $sql = "select cluster_id, name from cluster where 1";
  $res_cluster = $mysqli->query($sql) ;

?>

  <form name = "lb_form" method="POST">
  <fieldset>
    <legend>Server</legend>
    <div class="error_box"></div>
    
<?php
    if ( $lb_id ) 
    {
      
?>
    <input type="hidden" name="lb_id" value="<?php echo $lb_id ?>" />    
<?php

    }

    if ( $cluster_id) 
    {
      
?>
    <input type="hidden" name="old_cluster_id" value="<?php echo $cluster_id ?>" />    
<?php

    }

?>    
    <input type="hidden" name="f_type" value="server" />

    <input type="hidden" name="action" value="update" />
    
    <div><label for="name">Name</label> <input type="text" maxlength="255" name="name" value="<?php echo @$name?$name:"" ?>" /></div>

    <div><label for="ip_address">IP Address</label> <input type="text" maxlength="255" name="ip_address" value="<?php echo @$ip_address?$ip_address:"" ?>" /></div>

    <div><label for="router_id">Router Identifier</label> <input type="text" maxlength="255" name="router_id" value="<?php echo @$router_id?$router_id:"" ?>" /></div>

    <div>
      <label for="method">Connect Method</label>
        <input type="radio" onclick="$('#ssh').hide(); $.modal.resize();" name="access_backend" value="local" <?php echo (!@$access_backend||$access_backend=="local")?'checked="checked"':"" ?>/>local
        <input type="radio" onclick="$('#ssh').show(); $.modal.resize();" name="access_backend" value="ssh" <?php echo (@$access_backend=="ssh")?'checked="checked"':"" ?>/>ssh
    </div>
    
    <div id="ssh" <?php echo (@$access_backend!="ssh")?'style="display:none"':"" ?>>
      <div><label for="ssh_user">SSH User</label> <input type="text" maxlength="255" name="ssh_user" value="<?php echo @$ssh_user?$ssh_user:"" ?>" /></div>
      <div><label for="ssh_passphrase">SSH Passphrase</label> <input type="text" maxlength="255" name="ssh_passphrase" value="<?php echo @$ssh_passphrase?$ssh_passphrase:"" ?>" /></div>
      <div><label for="ssh_public_key_path">SSH Public key path</label> <input type="text" maxlength="255" name="ssh_public_key_path" value="<?php echo @$ssh_public_key_path?$ssh_public_key_path:"" ?>" /></div>
      <div><label for="ssh_private_key_path">SSH Private key path</label> <input type="text" maxlength="255" name="ssh_private_key_path" value="<?php echo @$ssh_private_key_path?$ssh_private_key_path:"" ?>" /></div>
    </div>
    
    <div>
      <label for="service_backend">Service type</label>
      <select name="service_backend">
<?php
    foreach($SERVICE_BACKEND_LIST as $value => $label)
    {
?>
        <option value="<?php echo $value ?>" <?php echo (@$service_backend == $value)?'selected="selected"':'' ?>><?php echo $label ?></option>
<?php
    }
?>    
      </select>
    </div>
    <div><label for="conf_path">keepalived.conf path</label> <input type="text" maxlength="255" name="conf_path" value="<?php echo @$conf_path?$conf_path:"" ?>" /></div>
    
    <div>
      <label for="cluster_id">Cluster</label> 
      <select name="cluster_id">
        <option value="">---</option>
<?php
    while ( $row = $res_cluster->fetch_assoc() )
    {
?>
        <option value="<?php echo $row['cluster_id'] ?>" <?php if ( $cluster_id == $row['cluster_id'] ) echo 'selected="selected"' ?>><?php echo $row['name'] ?></option>
<?php
    }
?>
      </select>
      or create new one 
      <input type="checkbox" name="create_cluster" value="1" />
    </div>
    
    <div><label for="buttons">&nbsp;</label> <input class="styled-button-10" type="submit" value="Submit" /></div>
    
    
  </fieldset>
  </form>

  <script type="text/javascript">
var validator = new FormValidator('lb_form', [{
    name: 'name',
    rules: 'Name',
    rules: 'required|alpha_dash'
    }, {
    name: 'ip_address',
    display: 'IP Address',
    rules: 'required|valid_ip'
}, {
    name: 'router_id',
    display: 'Router Identifier',
    rules: 'alpha_dash'
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


if(isset( $_REQUEST['cluster_id'] ) ) $cluster_id = $_REQUEST['cluster_id']; else $cluster_id= NULL;
if(isset( $_REQUEST['lb_id'] ) ) $lb_id = $_REQUEST['lb_id']; else $lb_id= NULL;


form_server($lb_id,$cluster_id);