<?php

require_once("include/usawa_base.inc.php");


function form_virtual_server($virtual_server_id = NULL, $cluster_id = NULL)
{
  global $mysqli;
  
  if ( $virtual_server_id ) {
  
    $sql = "select  inet6_ntoa(ip_address) as ip_address, 
                    port, 
                    fwmark, 
                    group, 
                    delay_loop, 
                    lvs_sched, 
                    lvs_method,
                    persistence_timeout,
                    inet6_ntoa(persistence_granularity) as persistence_granularity,
                    protocol,
                    ha_suspend,
                    virtualhost,
                    alpha,
                    omega,
                    quorum,
                    hysteresis,
                    quorum_up,
                    quorum_down,
                    inet6_ntoa(sorry_server_ip) as sorry_server_ip,
                    sorry_server_port,
                    cluster_id
                from virtual_server where virtual_server_id = '".$virtual_server_id."'";
    
    if ( ($res = $mysqli->query($sql) ) && $res->num_rows) {
      $row = $res->fetch_assoc();
      extract($row);
    }
  }
  
  // Cluster list
  $sql = "select cluster_id, name from cluster where 1";
  $res_cluster = $mysqli->query($sql) ;

?>

  <form name = "virtual_server_form" method="POST">
  <fieldset>
    <legend>Virtual Server</legend>
    <div class="error_box"></div>
    
<?php
    if ( $virtual_server_id ) 
    {
      
?>
    <input type="hidden" name="virtual_server_id" value="<?php echo $virtual_server_id ?>" />
    
<?php

    }

    if ( $cluster_id) 
    {
      
?>
    <input type="hidden" name="old_cluster_id" value="<?php echo $cluster_id ?>" />    
<?php

    }

?>    
    <input type="hidden" name="f_type" value="virtual_serverserver" />

    <input type="hidden" name="action" value="update" />

        
<!--     fwmark, group -->

    <div><label for="ip_address">IP Address</label> <input type="text" maxlength="255" name="ip_address" value="<?php echo @$ip_address?$ip_address:"" ?>" /></div>

    <div><label for="port">Port</label> <input type="text" maxlength="5" name="port" value="<?php echo @$port?$port:"" ?>" /></div>

    <div>
      <label for="protocol">Protocol</label>
      <select name="protocol">
        <option value="TCP" <?php echo (@$protocol == 'TCP')?'selected="selected"':'' ?>>TCP</option>
        <option value="UDP" <?php echo (@$protocol == 'UDP')?'selected="selected"':'' ?>>UDP</option>
      </select>
    </div>

    <div>
      <label for="lvs_method">LVS Method</label>
      <select name="lvs_method">
        <option value="NAT" <?php echo (@$lvs_method == 'NAT')?'selected="selected"':'' ?>>NAT</option>
        <option value="DR" <?php echo (@$lvs_method == 'DR')?'selected="selected"':'' ?>>DR</option>
        <option value="TUN" <?php echo (@$lvs_method == 'TUN')?'selected="selected"':'' ?>>TUN</option>
      </select>
    </div>

    <div>
      <label for="lvs_sched">LVS Scheduler</label>
      <select name="lvs_sched">
        <option value="rr" <?php echo (@$lvs_sched == 'rr')?'selected="selected"':'' ?>>Round Robin (rr)</option>
        <option value="wrr" <?php echo (@$lvs_sched == 'wrr')?'selected="selected"':'' ?>>Weighted Round Robin (wrr)</option>
        <option value="lc" <?php echo (@$lvs_sched == 'lc')?'selected="selected"':'' ?>>Least Connection (lc)</option>
        <option value="wlc" <?php echo (@$lvs_sched == 'wlc')?'selected="selected"':'' ?>>Weighted Least-Connection (wlc)</option>
        <option value="lblc" <?php echo (@$lvs_sched == 'lblc')?'selected="selected"':'' ?>>Locality-Based Least-Connection (lblc)</option>
        <option value="lblcr" <?php echo (@$lvs_sched == 'lblcr')?'selected="selected"':'' ?>>Locality-Based Least-Connection with Replication(lblcr)</option>
        <option value="dh" <?php echo (@$lvs_sched == 'dh')?'selected="selected"':'' ?>>Destination Hashing (dh)</option>
        <option value="sh" <?php echo (@$lvs_sched == 'sh')?'selected="selected"':'' ?>>Source Hashing (sh)</option>
        <option value="sed" <?php echo (@$lvs_sched == 'sed')?'selected="selected"':'' ?>>Short Expected Delay (sed)</option>
        <option value="nq" <?php echo (@$lvs_sched == 'nq')?'selected="selected"':'' ?>>Never Queue (nq)</option>
      </select>
    </div>

    <div><label for="persistence_timeout">Persistence Timeout</label> <input type="text" maxlength="255" name="persistence_timeout" value="<?php echo @$persistence_timeout?$persistence_timeout:"" ?>" /></div>

    <div><label for="persistence_granularity">Granularity</label> <input type="text" maxlength="255" name="persistence_granularity" value="<?php echo @$persistence_granularity?$persistence_granularity:"" ?>" /></div>

    <div><label for="delay_loop">Delay Loop</label> <input type="text" maxlength="255" name="delay_loop" value="<?php echo @$delay_loop?$delay_loop:"" ?>" /></div>

    <div><label for="virtualhost">Virtual Hostname</label> <input type="text" maxlength="255" name="virtualhost" value="<?php echo @$virtualhost?$virtualhost:"" ?>" /></div>

    <div><label for="sorry_server_ip">Sorry Server IP</label> <input type="text" maxlength="255" name="sorry_server_ip" value="<?php echo @$sorry_server_ip?$sorry_server_ip:"" ?>" /></div>

    <div><label for="sorry_server_port">Sorry Server Port</label> <input type="text" maxlength="255" name="sorry_server_port" value="<?php echo @$sorry_server_port?$sorry_server_port:"" ?>" /></div>

    <div><label for="quorum">Quorum</label> <input type="text" maxlength="255" name="quorum" value="<?php echo @$quorum?$quorum:"" ?>" /></div>

    <div><label for="hysteresis">Hysteresis</label> <input type="text" maxlength="255" name="hysteresis" value="<?php echo @$hysteresis?$hysteresis:"" ?>" /></div>

    <div><label for="ha_suspend">HA Suspend</label> <input type="checkbox" name="ha_suspend" value="1" <?php echo @$ha_suspend?'checked="checked"':"" ?>/></div>

    <div><label for="alpha">Alpha</label> <input type="checkbox" name="alpha" value="1" <?php echo @$alpha?'checked="checked"':"" ?>/></div>

    <div><label for="omega">Omega</label> <input type="checkbox" name="omega" value="1" <?php echo @$omega?'checked="checked"':"" ?>/></div>

    <div><label for="quorum_up">On quorum up</label> <input type="text" maxlength="255" name="quorum_up" value="<?php echo @$quorum_up?$quorum_up:"" ?>" /></div>

    <div><label for="quorum_down">On quorum down</label> <input type="text" maxlength="255" name="quorum_down" value="<?php echo @$quorum_down?$quorum_down:"" ?>" /></div>

<!-- Cluster id -->    
    <div>
      <label for="cluster_id">Cluster</label> 
      <select name="cluster_id">
        <option value="">-</option>
<?php
      while ( $row = $res_cluster->fetch_assoc() )
      {
?>
        <option value="<?php echo $row['cluster_id'] ?>" <?php if ( @$cluster_id == $row['cluster_id'] ) echo 'selected="selected"' ?>><?php echo $row['name'] ?></option>
<?php
      }
?>
      </select>
    </div>
    
    <div><label for="buttons">&nbsp;</label> <input class="styled-button-10" type="submit" value="Submit" /></div>
    
    
  </fieldset>
  </form>

  <script type="text/javascript">
var validator = new FormValidator('virtual_server_form', [{
    name: 'ip_address',
    display: 'IP address',
    rules: 'valid_ip'
    }, {
    name: 'port',
    display: 'Port',
    rules: 'integer|greater_than[-1]|less_than[65536]'
}, {
    name: 'persistence_timeout',
    display: 'Persistence Timeout',
    rules: 'integer'
}, {
    name: 'delay_loop',
    display: 'Delay Loop',
    rules: 'integer|greater_than[-1]'
}, {
    name: 'quorum',
    display: 'Quorum',
    rules: 'integer|greater_than[0]'
}, {
    name: 'hysteresis',
    display: 'Hysteresis',
    rules: 'integer|greater_than[-1]'
}, {
    name: 'delay_loop',
    display: 'Delay Loop',
    rules: 'integer|greater_than[-1]'
},{
    name: 'persistence_granularity',
    display: 'Persistence granularity',
    rules: 'valid_ip'
}, {
    name: 'sorry_server_ip',
    display: 'Sorry server',
    rules: 'valid_ip'
}, {
    name: 'sorry_server_port',
    display: 'Sorry server port',
    rules: 'integer|greater_than[-1]|less_than[65536]'
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
if(isset( $_REQUEST['virtual_server_id'] ) ) $virtual_server_id = $_REQUEST['virtual_server_id']; else $virtual_server_id= NULL;


form_virtual_server($virtual_server_id,$cluster_id);