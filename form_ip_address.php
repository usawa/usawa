<?php

require_once("include/usawa_base.inc.php");


function form_ip_address($ip_address = NULL)
{
  global $mysqli;
  global $IP_SCOPE_LIST;

  if(isset( $_REQUEST['cluster_id'] ) ) $cluster_id = $_REQUEST['cluster_id']; else $cluster_id= NULL;
  if(isset( $_REQUEST['virtual_router_id'] ) ) $virtual_router_id = $_REQUEST['virtual_router_id']; else $virtual_router_id= NULL;

  if ( $ip_address ) {
  
    $sql = "select 
            inet6_ntoa(ip) as ip, 
            mask, 
            inet6_ntoa(broadcast) as broadcast, 
            dev, 
            scope, 
            label,
            is_gateway, 
            is_disabled,
            cluster_id,
            virtual_router_id
           from ip_address
           where ip=inet6_aton('$ip_address')";
           
    if ( ($res = $mysqli->query($sql) ) && $res->num_rows) {
      $row = $res->fetch_assoc();
      extract($row);      
      
    }
  }
  
  if($cluster_id) 
  {
    $sql = "select name from cluster where cluster_id='$cluster_id'";
    $r_cname = $mysqli->query($sql);
    list($cluster_name)= $r_cname->fetch_array();
  }

  if($virtual_router_id) 
  {
    $sql = "select name from vrrp_instance where virtual_router_id='$virtual_router_id'";
    $r_vname = $mysqli->query($sql);
    list($vrrp_name)= $r_vname->fetch_array();
  }

  if( !$ip_address  && (!$virtual_router_id || !$cluster_id) )
  {
 
    // Cluster list
    $sql = "select cluster_id, name from cluster where 1";
    $res_cluster = $mysqli->query($sql) ;
  
    // VRRP instances list
    $sql = "select virtual_router_id, name from vrrp_instance where 1";
    $res_vrrp_instance = $mysqli->query($sql);
  }
?>
  <form name="ip_form" method="POST">
  <fieldset>
    <legend>
<?php
    if($virtual_router_id)
    {
      echo "Virtual IP address for VRRP instance $vrrp_name";
    }
    else
    {
      echo "Static IP address for cluster $cluster_name";
    }
?>
    </legend>
    <div class="error_box"></div>
<?php
    if ($virtual_router_id)
    {
?>
    <input type="hidden" name="virtual_router_id" value="<?php echo $virtual_router_id ?>" />
<?php
    }
    if ($cluster_id)
    {
?>    
    <input type="hidden" name="cluster_id" value="<?php echo $cluster_id ?>" />
<?php  
    }
?>    
    <input type="hidden" name="f_type" value="ip_address" />

    <input type="hidden" name="action" value="update" />
    
    <input type="hidden" name="old_ip" value="<?php echo @$ip ?>" />    
    
    <div><label for="ip">IP</label> <input type="text" maxlength="255" name="ip" value="<?php echo @$ip?$ip:"" ?>" /></div>

    <div><label for="mask">Mask (numeric)</label> <input type="text" maxlength="2" name="mask" value="<?php echo @$mask?$mask:"" ?>" /></div>

    <div><label for="broadcast">Broadcast</label> <input type="text" maxlength="255" name="broadcast" value="<?php echo @$broadcast?$broadcast:"" ?>" /></div>

    <div><label for="dev">Device</label> <input type="text" maxlength="255" name="dev" value="<?php echo @$dev?$dev:"" ?>" /></div>

    <div><label for="label">Label</label> <input type="text" maxlength="255" name="label" value="<?php echo @$label?$label:"" ?>" /></div>

    <div>
      <label for="scope">Scope</label>
      <select name="scope">
        <option value="">---</option>
<?php
    foreach($IP_SCOPE_LIST as $value)
    {
?>
        <option value="<?php echo $value ?>" <?php echo (@$scope == $value)?'selected="selected"':'' ?>><?php echo $value ?></option>
<?php
    }
?>    
      </select>
    </div>

    <div>
      <label for="is_gateway">&nbsp;</label>
        <input type="checkbox" name="is_gateway" value="1" <?php echo @$is_gateway?'checked="checked"':"" ?>/>VIP GW
    </div>

    <div>
      <label for="is_disabled">&nbsp;</label>    
        <input type="checkbox" name="is_disabled" value="1" <?php echo @$is_disabled?'checked="checked"':"" ?>/>Disabled
    </div>
    
    <div><label for="buttons">&nbsp;</label> <input class="styled-button-10" type="submit" value="Submit" /></div>
    
    
  </fieldset>
  </form>

  <script type="text/javascript">
var validator = new FormValidator('ip_form', [{
    name: 'ip',
    display: 'IP Address',
    rules: 'required|valid_ip'
}, {
    name: 'mask',
    display: 'Netmask',
    rules: 'integer|greater_than[-1]|less_than[65]'
}, {
    name: 'broadcast',
    display: 'Broadcast IP',
    rules: 'valid_ip'
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


if(isset( $_REQUEST['ip'] ) ) $ip = $_REQUEST['ip']; else $ip=NULL;

form_ip_address($ip);