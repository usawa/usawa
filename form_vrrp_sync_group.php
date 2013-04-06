<?php

require_once("include/usawa_base.inc.php");

function form_vrrp_sync_group($sync_group_id = NULL)
{
  global $mysqli;
  
  $vrrp_instances_count = NULL;
  
  if(isset( $_REQUEST['cluster_id'] ) ) $cluster_id = $_REQUEST['cluster_id']; else $cluster_id= NULL;

  // Cluster list
  $sql = "select cluster_id, name from cluster where 1";
  $res_cluster = $mysqli->query($sql) ;
  

  if ( $sync_group_id )
  {
  
    // Count VRRP instances in the VRRP Sync Group. Cluster value can't be modified if at least one
    $sql = "select count(virtual_router_id) from vrrp_instance where sync_group_id='$sync_group_id'";
    $res = $mysqli->query($sql);
    list($vrrp_instances_count) = $res->fetch_array();
    
    $sql = "select 
              s.name,
              s.notify_master,
              s.notify_backup,
              s.notify_fault,
              s.notify,
              s.smtp_alert,
              s.cluster_id,
              c.name as cluster_name
            from vrrp_sync_group s
            left join cluster c on s.cluster_id=c.cluster_id
            where sync_group_id='$sync_group_id'";
    
    if ( ($res = $mysqli->query($sql) ) && $res->num_rows) {
      $row = $res->fetch_assoc();
      extract($row);
    }  
  }
?>

  <form name = "vrrp_sync_group_form" method="POST">
  <fieldset>
    <legend>VRRP Synchronization Group</legend>
    <div class="error_box"></div>

    <input type="hidden" name="f_type" value="vrrp_sync_group" />
    
<?php
  if ( $sync_group_id ) 
  {
      
?>
    <input type="hidden" name="sync_group_id" value="<?php echo $sync_group_id ?>" />    

    <input type="hidden" name="old_name" value="<?php echo $name ?>" />    

    <input type="hidden" name="action" value="update" />

<?php

  }
  else
  {
?>  
    <input type="hidden" name="action" value="insert" />
   
<?php
  }
?>
    
    <div><label for="name">Name</label> <input type="text" maxlength="255" name="name" value="<?php echo @$name?$name:"" ?>" /></div>

<!-- Cluster id -->    
    <div>
      <label for="cluster_id">Cluster</label> 
<?php
    if ($vrrp_instances_count)
    {
?>   
      <input type="hidden" name="cluster_id" value="<?php echo $cluster_id ?>" />
      <?php echo $cluster_name ?> (Can't be updated : VRRP instances in this group)
<?php
    }
    else
    {
?>    
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
<?php
    }
?>    
    </div>

    <div><label for="notify_master">Notify Master</label> <input type="text" maxlength="255" name="notify_master" value="<?php echo @$notify_master?$notify_master:"" ?>" /></div>

    <div><label for="notify_backup">Notify Backup</label> <input type="text" maxlength="255" name="notify_backup" value="<?php echo @$notify_backup?$notify_backup:"" ?>" /></div>

    <div><label for="notify_fault">Notify Fault</label> <input type="text" maxlength="255" name="notify_fault" value="<?php echo @$notify_fault?$notify_fault:"" ?>" /></div>

    <div><label for="notify">Notify</label> <input type="text" maxlength="255" name="notify" value="<?php echo @$notify?$notify:"" ?>" /></div>

    <div><label for="smtp_alert">Email on change</label> <input type="checkbox" name="smtp_alert" value="1" <?php echo @$smtp_alert?'checked="checked"':'' ?> /></div>
    
    <div><label for="buttons">&nbsp;</label> <input class="styled-button-10" type="submit" value="Submit" /></div>
    
    
  </fieldset>
  </form>

  <script type="text/javascript">
var validator = new FormValidator('vrrp_sync_group_form', [{
    name: 'name',
    rules: 'Name',
    rules: 'required|alpha_dash'
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

if(isset( $_REQUEST['sync_group_id'] ) ) $sync_group_id = $_REQUEST['sync_group_id']; else $sync_group_id= NULL;


form_vrrp_sync_group($sync_group_id);