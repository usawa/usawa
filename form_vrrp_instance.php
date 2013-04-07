<?php

require_once("include/usawa_base.inc.php");

function form_vrrp_instance($virtual_router_id = NULL)
{
  global $mysqli;
  
  if(isset( $_REQUEST['cluster_id'] ) ) $cluster_id = $_REQUEST['cluster_id']; else $cluster_id= NULL;

  if ( $virtual_router_id ) {
  
    $sql = "select 
              v.name,
              v.use_vmac,
              v.native_ipv6,
              v.interface,
              v.dont_track_primary,
              inet6_ntoa(v.mcast_src_ip) as mcast_src_ip, 
              v.lvs_sync_daemon_interface,
              v.garp_master_delay,
              v.advert_int,
              v.auth_type,
              v.auth_pass,
              v.nopreempt,
              v.preempt_delay,
              v.notify_master,
              v.notify_backup,
              v.notify_fault,
              v.notify,
              v.smtp_alert,
              v.cluster_id,
              v.sync_group_id,
              v.comment,
              c.name as cluster_name
            from vrrp_instance v
            left join cluster c on v.cluster_id = c.cluster_id
            where virtual_router_id='".$virtual_router_id."'";
    
    if ( ($res = $mysqli->query($sql) ) && $res->num_rows) {
      $row = $res->fetch_assoc();
      extract($row);
    }
  } else {
//     $cluster_id = NULL ;
    $sync_group_id = NULL ;
    $auth_type = VRRP_DEFAULT_AUTH_TYPE ;
  }
    
  // Cluster list
  $sql = "select cluster_id, name from cluster where 1";
  $res_cluster = $mysqli->query($sql) ;

  // Sync group
  if($cluster_id)
  {
    $sql = "select sync_group_id, name as sync_group_name from vrrp_sync_group where cluster_id='$cluster_id'";
    $res_group = $mysqli->query($sql) ;
  }
?>

  <form name = "vrrp_instance_form" method="POST">
  <fieldset>
    <legend>VRRP Instance</legend>
    <div class="error_box"></div>
    
<?php   
    if ( $cluster_id )
    {
?>
    <input type="hidden" name="old_cluster_id" value="<?php echo $cluster_id ?>" />
  
<?php
    }

    if ( $sync_group_id )
    {
?>
    <input type="hidden" name="old_sync_group_id" value="<?php echo $sync_group_id ?>" />
  
<?php
    }
    
    if ( $virtual_router_id ) 
    {
?>
    <input type="hidden" name="action" value="update" />

    <input type="hidden" name="old_name" value="<?php echo $name ?>" />

    <input type="hidden" name="old_virtual_router_id" value="<?php echo $virtual_router_id ?>" />
<?php

    }
    else
    {
?>
    <input type="hidden" name="action" value="insert" />
<?php
    }
?>    
    <input type="hidden" name="f_type" value="vrrp_instance" />

    <div><label for="virtual_router_id">VRRP Id.</label> <input type="text" maxlength="3" name="virtual_router_id" value="<?php echo @$virtual_router_id?$virtual_router_id:"" ?>" /></div>
    
    <div><label for="name">Name</label> <input type="text" maxlength="255" name="name" value="<?php echo @$name?$name:"" ?>" /></div>

<!-- Cluster id -->    
    <div>
      <label for="cluster_id">Cluster</label> 
<?php
    if ( $sync_group_id )
    {
?>
      <input type="hidden" name="cluster_id" value="<?php echo $cluster_id ?>" />
      <?php echo $cluster_name ?> (can't be updated, instance already in Sync group)
<?php
    }
    else
    {
?>
      <select name="cluster_id">
        <option value="">-</option>
<?php
      while ( $row = $res_cluster->fetch_assoc() )
      {
?>
        <option value="<?php echo $row['cluster_id'] ?>" <?php if ( $cluster_id == $row['cluster_id'] ) echo 'selected="selected"' ?>><?php echo $row['name'] ?></option>
<?php
      }
    }
?>
      </select>
    </div>

<!-- vrrp instance -->    
    <div>
      <label for="sync_group_id">Sync Group</label> 
      <select name="sync_group_id">
        <option value="">-</option>
<?php
    if($cluster_id)
    {
      while ( $row = $res_group->fetch_assoc() )
      {
?>
        <option value="<?php echo $row['sync_group_id'] ?>" <?php if ( $sync_group_id == $row['sync_group_id'] ) echo 'selected="selected"' ?>><?php echo $row['sync_group_name'] ?></option>
<?php
      }
    }
?>
      </select>
    </div>
    
    <div><label for="interface">Interface</label> <input type="text" maxlength="255" name="interface" value="<?php echo @$interface?$interface:"" ?>" /></div>

    <div><label for="advert_int">Advert interval</label> <input type="text" maxlength="3" name="advert_int" value="<?php echo @$advert_int?$advert_int:VRRP_DEFAULT_ADVERT_INT ?>" /></div>

    <div>
      <label for="auth_type">Auth type</label>
        <input type="radio" onclick="$('#auth').show(); $.modal.resize();" name="auth_type" value="PASS" <?php echo (!@$auth_type||$auth_type=="PASS")?'checked="checked"':"" ?>/>PASS
        <input type="radio" onclick="$('#auth').hide(); $.modal.resize();" name="auth_type" value="AH" <?php echo (@$auth_type=="AH")?'checked="checked"':"" ?>/>AH
    </div>

    <div id="auth" <?php echo (@$auth_type!="PASS")?'style="display:none"':"" ?>><label for="auth_pass">Pass Phrase</label> <input type="text" maxlength="8" name="auth_pass" value="<?php echo @$auth_path?$auth_pass:"VRRPass" ?>" /></div>

    <div id="details" style="display:none">

    <div><label for="lvs_sync_daemon_interface">LVS Sync Interface</label> <input type="text" maxlength="255" name="lvs_sync_daemon_interface" value="<?php echo @$lvs_sync_daemon_interface?$lvs_sync_daemon_interface:"" ?>" /></div>

    <div><label for="mcast_src_ip">Multicast Source IP</label> <input type="text" maxlength="255" name="mcast_src_ip" value="<?php echo @$mcast_src_ip?$mcast_src_ip:"" ?>" /></div>

    <div><label for="use_vmac">Use VMAC</label> <input type="checkbox" name="use_vmac" value="1" <?php echo @$use_vmac?'checked="checked"':'' ?> /></div>

    <div><label for="native_ipv6">Force IPv6 usage</label> <input type="checkbox" name="native_ipv6" value="1" <?php echo @$native_ipv6?'checked="checked"':'' ?> /></div>

    <div><label for="dont_track_primary">Ignore Iface faults</label> <input type="checkbox" name="dont_track_primary" value="1" <?php echo @$dont_track_primary?'checked="checked"':'' ?> /></div>

    <div><label for="nopreempt">Don't preempt Master</label> <input type="checkbox" name="nopreempt" value="1" <?php echo @$nopreempt?'checked="checked"':'' ?> /></div>

    <div><label for="preempt_delay">Preempt delay</label> <input type="text" maxlength="4" name="preempt_delay" value="<?php echo @$preempt_delay?$preempt_delay:"" ?>" /></div>

    <div><label for="garp_master_delay">GARP Delay</label> <input type="text" size="3" name="garp_master_delay" value="<?php echo @$garp_master_delay?$garp_master_delay:"" ?>" /></div>

    <div><label for="notify_master">Notify Master</label> <input type="text" maxlength="255" name="notify_master" value="<?php echo @$notify_master?$notify_master:"" ?>" /></div>

    <div><label for="notify_backup">Notify Backup</label> <input type="text" maxlength="255" name="notify_backup" value="<?php echo @$notify_backup?$notify_backup:"" ?>" /></div>

    <div><label for="notify_fault">Notify Fault</label> <input type="text" maxlength="255" name="notify_fault" value="<?php echo @$notify_fault?$notify_fault:"" ?>" /></div>

    <div><label for="notify_stop">Notify Stop</label> <input type="text" maxlength="255" name="notify_stop" value="<?php echo @$notify_stop?$notify_stop:"" ?>" /></div>

    <div><label for="notify">Notify</label> <input type="text" maxlength="255" name="notify" value="<?php echo @$notify?$notify:"" ?>" /></div>

    <div><label for="smtp_alert">Email on change</label> <input type="checkbox" name="smtp_alert" value="1" <?php echo @$smtp_alert?'checked="checked"':'' ?> /></div>

    </div>
<script>
  function hide_show()
  {
    if ($('#details').css('display') == 'none') { 
      $('#details').show(); 
      $('#hideshow').text('Less'); 
    } else { 
      $('#details').hide();
      $('#hideshow').text('More'); 
    } 
    $.modal.resize();
  }
</script>

    <div><a id="hideshow" href="#" onclick="hide_show()">more</a></div>
    
    <div><label for="buttons">&nbsp;</label> <input class="styled-button-10" type="submit" value="Submit" /></div>
    
    
  </fieldset>
  </form>

  <script type="text/javascript">
var validator = new FormValidator('vrrp_instance_form', [{
    name: 'name',
    rules: 'Name',
    rules: 'required|alpha_dash'
    }, {
    name: 'virtual_router_id',
    display: 'Virtual Router Id',
    rules: 'required|integer|greater_than[-1]|less_than[256]'
}, {
    name: 'interface',
    display: 'Interface',
    rules: 'required|alpha_dash'
}, {
    name: 'lvs_sync_daemon_interface',
    display: 'LVS Sync Interface',
    rules: 'alpha_dash'
}, {
    name: 'advert_int',
    display: 'Advert interval',
    rules: 'integer|greater_than[0]|less_than[256]'
}, {
    name: 'auth_pass',
    display: 'Pass phrase',
    rules: 'min_length[0]|max_length[8]'
}, {
    name: 'mcast_src_ip',
    display: 'VRRP Multicast source IP',
    rules: 'valid_ip'
}, {
    name: 'preempt_delay',
    display: 'Master preemption delay',
    rules: 'integer|greater_than[-1]'
}, {
    name: 'garp_master_delay',
    display: 'Advert interval',
    rules: 'integer|greater_than[0]|less_than[256]'
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


form_vrrp_instance($virtual_router_id);