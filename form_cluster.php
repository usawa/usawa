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
    
    <input type="hidden" name="old_name" value="<?php echo $name ?>" />
    
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


if(isset( $_REQUEST['cluster_id'] ) ) $cluster_id = $_REQUEST['cluster_id']; else $cluster_id= NULL;

form_cluster($cluster_id);