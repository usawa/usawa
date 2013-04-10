<?php

/* tabulate a line. level=number of tabs */
function tabulate($string, $level)
{
        if($level) {
                for( $i = 1 ; $i <= $level ; $i++ ) $string="\t".$string;
        }
        return $string;
}

/* keepalived.conf generator */

// generate keepalived.conf
function generate_configuration($server_id = NULL)
{
  global $tabulate;
  
  $tabulate = 0;
  
  if (! $server_id ) return false;
  
  $configuration = "";
  
  $configuration .= generate_global($server_id) ;
  
  $configuration .= generate_vrrp_script($server_id) ;
  
  $configuration .= generate_vrrp_instances($server_id) ;
  
  return $configuration;
}
  
// global section
function generate_global($server_id = NULL)
{
  global $mysqli;
  global $tabulate;
    
  $global_defs ="";
  
  if (!$server_id) return false;
  
  $sql = "select
            notification_email_from,
            inet6_ntoa(smtp_server) as smtp_server,
            smtp_connect_timeout,
            notification_email,
            enable_traps,
            router_id
          from cluster c, server s
          where c.cluster_id = s.cluster_id
              and s.lb_id = '$server_id'";
  
  // SQL Error
  if (! $res = $mysqli->query($sql) ) return false;
  
  // No lines
  if ( ! $res->num_rows ) return false;
  
  $row = $res->fetch_assoc();
  
  extract($row);
  
  $global_defs = tabulate("global_defs {\n",$tabulate);
 
  $tabulate++;

  // router_id
  if ($router_id) $global_defs .= tabulate("router_id $router_id\n",$tabulate);

  // Notification email from
  if ($notification_email_from) $global_defs .= tabulate("notification_email_from $notification_email_from\n",$tabulate);
  
  // smtp server
  if ($smtp_server) $global_defs .= tabulate("smtp_server $smtp_server\n",$tabulate);
  
  // smtp_connect_timeout
  if ($smtp_connect_timeout) $global_defs .= tabulate("smtp_connect_timeout $smtp_connect_timeout\n",$tabulate);
  
  // notification_email
  if ($notification_email) 
  {
    $global_defs .= tabulate("notification_email {\n",$tabulate);
    
    $tabulate ++;
    
    $emails = explode(',',$notification_email);

    foreach($emails as $email) 
    {
      $global_defs .= tabulate("$email\n",$tabulate);
    }
    
    $tabulate--;

    $global_defs .= tabulate("}\n",$tabulate);

  }
  // enable_traps
  if ($enable_traps) $global_defs .= tabulate("enable_traps\n",$tabulate);
  
  
  $tabulate--;
  
  $global_defs .= tabulate("}\n",$tabulate);
  
  return $global_defs;
  
}

// vrrp scripts functions
function generate_vrrp_script($server_id = NULL)
{
 
  global $mysqli;
  global $tabulate;

  $vrrp_script = "";
  
  if (!$server_id) return false;
    
  // Get only vrrp script described in track scripts
  $sql = "select 
            vs.name,
            vs.script,
            vs.interval,
            vs.weight,
            vs.fall,
            vs.rise
          from vrrp_script vs, track_script t, vrrp_instance v, server s
          where s.cluster_id = v.cluster_id
            and v.virtual_router_id = t.virtual_router_id
            and vs.script_id = t.script_id
            and s.lb_id='$server_id'
          group by vs.script_id";
          
  // SQL Error
  if (! $res = $mysqli->query($sql) ) return false;
  
  // No lines
  if ( ! $res->num_rows ) return false;

  while ( $row = $res->fetch_array() )
  {
    extract($row);
    
    $vrrp_script .=tabulate("script $name {\n",$tabulate);
    
    $tabulate++;

    // script
    if ($script) $vrrp_script .= tabulate("script \"$script\"\n",$tabulate);

    // interval
    if (! is_null($interval)) $vrrp_script .= tabulate("interval $interval\n",$tabulate);

    // weight
    if (! is_null($weight)) $vrrp_script .= tabulate("weight $weight\n",$tabulate);

    // fall
    if ($fall) $vrrp_script .= tabulate("fall $fall\n",$tabulate);

    // rise
    if ($rise) $vrrp_script .= tabulate("rise $rise\n",$tabulate);

    $tabulate--;
    $vrrp_script .=tabulate("}\n",$tabulate);
    
  }

  return $vrrp_script;
}

// generate track_script
function generate_track_script($virtual_router_id = NULL)
{
  global $mysqli;
  global $tabulate;

  $track_script = "";
  
  if (!$virtual_router_id) return false;
    
  $sql = "select 
            s.name,
            t.weight
          from track_script t, vrrp_script s
          where 
            t.script_id = s.script_id
            and t.virtual_router_id = '$virtual_router_id'";
          
  // SQL Error
  if (! $res = $mysqli->query($sql) ) return false;
  
  // No lines
  if ( ! $res->num_rows ) return false;

  $track_script .= tabulate("track script {\n",$tabulate);
  
  $tabulate++;
  
  while ($row = $res->fetch_assoc())
  {
    extract($row);
    
    if(! is_null($weight) ) $weight = "weight $weight";
    
    $track_script .= tabulate("$name $weight\n",$tabulate);

  }
  
  $tabulate--;
  $track_script .= tabulate("}\n",$tabulate);
  
  return $track_script;
}


function generate_vrrp_instance($virtual_router_id = NULL)
{
  global $mysqli;
  global $tabulate;

  $vrrp_instance = "";
  
  if (!$virtual_router_id) return false;
    
  $sql = "select 
            name,
            use_vmac,
            native_ipv6,
            interface,
            dont_track_primary,
            inet6_ntoa(mcast_src_ip) as mcast_src_ip,
            lvs_sync_daemon_interface,
            garp_master_delay,
            advert_int,
            auth_type,
            auth_pass,
            nopreempt,
            preempt_delay,
            notify_master,
            notify_backup,
            notify_fault,
            notify_stop,
            notify,
            smtp_alert, 
            cluster_id, 
            sync_group_id,
            comment
          from vrrp_instance
          where virtual_router_id = '$virtual_router_id'";
          
  // SQL Error
  if (! $res = $mysqli->query($sql) ) return false;
  
  // No lines
  if ( ! $res->num_rows ) return false;

  $row = $res->fetch_array();

  extract($row);
    
  $vrrp_instance .=tabulate("vrrp_instance $name {\n",$tabulate);
  
  $tabulate++;

  if ($virtual_router_id) $vrrp_instance .=tabulate("virtual_router_id $virtual_router_id\n",$tabulate);

  if ($use_vmac) $vrrp_instance .=tabulate("use vmac\n",$tabulate);

  if ($native_ipv6) $vrrp_instance .=tabulate("native_ipv6\n",$tabulate);
  
  if ($interface) $vrrp_instance .=tabulate("interface $interface\n",$tabulate);

  if ($dont_track_primary) $vrrp_instance .=tabulate("dont_track_primary\n",$tabulate);

  if ($mcast_src_ip) $vrrp_instance .=tabulate("mcast_src_ip $mcast_src_ip\n",$tabulate);
  
  if ($lvs_sync_daemon_interface) $vrrp_instance .=tabulate("lvs_sync_daemon_interface $lvs_sync_daemon_interface\n",$tabulate);

  if ($garp_master_delay) $vrrp_instance .=tabulate("garp_master_delay $garp_master_delay\n",$tabulate);

  if ($advert_int) $vrrp_instance .=tabulate("advert_int $advert_int\n",$tabulate);

  if ($nopreempt) $vrrp_instance .=tabulate("nopreempt\n",$tabulate);

  if ($preempt_delay) $vrrp_instance .=tabulate("preempt_delay $preempt_delay\n",$tabulate);
  
  if ($auth_type) {
    $vrrp_instance .= tabulate("authentication\n",$tabulate);
    $tabulate++;
    $vrrp_instance .= tabulate("auth_type $auth_type\n",$tabulate);
    if ($auth_pass) $vrrp_instance .= tabulate("auth_pass $auth_pass\n",$tabulate);
    $tabulate--;
    $vrrp_instance .= tabulate("}\n",$tabulate);
  }

  if ($notify_master) $vrrp_instance .=tabulate("notify_master \"$notify_master\"\n",$tabulate);

  if ($notify_backup) $vrrp_instance .=tabulate("notify_backup \"$notify_backup\"\n",$tabulate);

  if ($notify_fault) $vrrp_instance .=tabulate("notify_fault \"$notify_fault\"\n",$tabulate);

  if ($notify_stop) $vrrp_instance .=tabulate("notify_stop \"$notify_stop\"\n",$tabulate);

  if ($notify) $vrrp_instance .=tabulate("notify \"$notify\"\n",$tabulate);

  if ($smtp_alert) $vrrp_instance .=tabulate("smtp_alert\n",$tabulate);

  // track script
  $vrrp_instance .= generate_track_script($virtual_router_id);

  $tabulate--;
  $vrrp_instance .=tabulate("}\n",$tabulate);
  
  
  return $vrrp_instance;
}

// VRRP instances
function generate_vrrp_instances ($server_id = NULL)
{
  global $mysqli;
  global $tabulate;

  $vrrp_instances = "";
  
  if (!$server_id) return false;
  
  $sql = "select 
            virtual_router_id 
          from vrrp_instance v, server s 
          where 
            s.cluster_id = v.cluster_id
          and lb_id = '$server_id'";

  // SQL Error
  if (! $res = $mysqli->query($sql) ) return false;
  
  
  // No lines
  if ( ! $res->num_rows ) return false;
  
  while ($row = $res->fetch_assoc() ) 
  {
    extract($row);
    
    $vrrp_instances .= generate_vrrp_instance($virtual_router_id);
  }
  
  return $vrrp_instances;
}

?> 

