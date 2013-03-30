<?php

require_once("include/usawa_base.inc.php");


function form_cluster($cluster_id = NULL)
{
  global $mysqli;

  if ( $cluster_id ) {
  
    $sql = "select name,notification_email_from, inet6_ntoa(smtp_server) as smtp_server, smtp_connect_timeout,notification_email, enable_traps from cluster where cluster_id = '".$cluster_id."'";

    
    if(($res = $mysqli->query($sql)) && $res->num_rows) {
      $row = $res->fetch_assoc();
      extract($row);
    }
  }
?>

  <form name="cluster_form" method="POST">
  <fieldset>
    <legend>Cluster</legend>
    <div class="error_box"></div>
    
<?php
    if ( $cluster_id ) 
    {
      
?>
    <input type="hidden" name="cluster_id" value="<?php echo $cluster_id ?>" />    
<?php

    }

?>    
    <input type="hidden" name="f_type" value="cluster" />

    <input type="hidden" name="action" value="update" />
    
    <div><label for="name">Name</label> <input type="text" maxlength="255" name="name" value="<?php echo @$name?$name:"" ?>" /></div>

    <div><label for="smtp_server">SMTP Server</label> <input type="text" maxlength="255" name="smtp_server" value="<?php echo @$smtp_server?$smtp_server:"" ?>" /></div>

    <div><label for="smtp_connect_timeout">SMTP Timeout</label> <input type="text" maxlength="255" name="smtp_connect_timeout" value="<?php echo @$smtp_connect_timeout?$smtp_connect_timeout:"" ?>" /></div>

    <div><label for="notification_email_from">Source email</label> <input type="text" maxlength="255" name="notification_email_from" value="<?php echo @$notification_email_from?$notification_email_from:"" ?>" /></div>
    
    <div><label for="notification_email">Notified emails</label> <input type="text" maxlength="512" name="notification_email" value="<?php echo @$notification_email?$notification_email:"" ?>" /></div>

    <div><label for="enable_traps">Enable SNMP traps</label> <input type="checkbox" name="enable_traps" value="1" <?php echo @$enable_traps?'checked="checked"':"" ?> /></div>

    <div><label for="buttons">&nbsp;</label> <input class="styled-button-10" type="submit" value="Submit Form" /></div>
    
    
  </fieldset>
  </form>
  <script type="text/javascript">
var validator = new FormValidator('cluster_form', [{
    name: 'smtp_connect_timeout',
    rules: 'integer'
}, {
    name: 'smtp_server',
    display: 'SMTP Server',
    rules: 'valid_ip'
}, {
    name: 'name',
    display: 'Name',
    rules: 'required|alpha_dash'
}, {
    name: 'notification_email',
    display: 'Notified emails',
    rules: 'valid_emails'
}, {
    name: 'notification_email_from',
    display: 'Source email',
    rules: 'valid_email'
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

function form_server($lb_id = NULL, $cluster_id = NULL)
{
  global $mysqli;

  if ( $lb_id ) {
  
    $sql = "select name, inet6_ntoa(ip_address) as ip_address, router_id, method, cluster_id from server where lb_id = '".$lb_id."'";
    
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

?>    
    <input type="hidden" name="f_type" value="server" />

    <input type="hidden" name="action" value="update" />
    
    <div><label for="name">Name</label> <input type="text" maxlength="255" name="name" value="<?php echo @$name?$name:"" ?>" /></div>

    <div><label for="ip_address">IP Address</label> <input type="text" maxlength="255" name="ip_address" value="<?php echo @$ip_address?$ip_address:"" ?>" /></div>

    <div><label for="router_id">Router Identifier</label> <input type="text" maxlength="255" name="router_id" value="<?php echo @$router_id?$router_id:"" ?>" /></div>

    <div>
      <label for="method">Connect Method</label>
        <input type="radio" name="method" value="local" <?php echo (!@$method||$method=="local")?'checked="checked"':"" ?>/>local
        <input type="radio" name="method" value="ssh" <?php echo (@$method=="ssh")?'checked="checked"':"" ?>/>ssh
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
if(isset( $_REQUEST['virtual_router_id'] ) ) $virtual_router_id = $_REQUEST['virtual_router_id']; else $virtual_router_id= NULL;


form_cluster($cluster_id);