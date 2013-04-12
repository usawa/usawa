<?php

/* tabulate a line. level=number of tabs */
function tabulate($string, $level)
{
        if($level) {
                for( $i = 1 ; $i <= $level ; $i++ ) $string="\t".$string;
        }
        return $string."\n";
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

  $configuration .= generate_ip_address($server_id,'static') ;

  $configuration .= generate_vrrp_script($server_id) ;
  
  $configuration .= generate_vrrp_sync_group($server_id) ;
  
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
  
  $global_defs = tabulate("global_defs {",$tabulate);
 
  $tabulate++;

  // router_id
  if ($router_id) $global_defs .= tabulate("router_id $router_id",$tabulate);

  // Notification email from
  if ($notification_email_from) $global_defs .= tabulate("notification_email_from $notification_email_from",$tabulate);
  
  // smtp server
  if ($smtp_server) $global_defs .= tabulate("smtp_server $smtp_server",$tabulate);
  
  // smtp_connect_timeout
  if ($smtp_connect_timeout) $global_defs .= tabulate("smtp_connect_timeout $smtp_connect_timeout",$tabulate);
  
  // notification_email
  if ($notification_email) 
  {
    $global_defs .= tabulate("notification_email {",$tabulate);
    
    $tabulate ++;
    
    $emails = explode(',',$notification_email);

    foreach($emails as $email) 
    {
      $global_defs .= tabulate("$email",$tabulate);
    }
    
    $tabulate--;

    $global_defs .= tabulate("}",$tabulate);

  }
  // enable_traps
  if ($enable_traps) $global_defs .= tabulate("enable_traps",$tabulate);
  
  
  $tabulate--;
  
  $global_defs .= tabulate("}",$tabulate);
  
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
    
    $vrrp_script .=tabulate("script $name {",$tabulate);
    
    $tabulate++;

    // script
    if ($script) $vrrp_script .= tabulate("script \"$script\"",$tabulate);

    // interval
    if (! is_null($interval)) $vrrp_script .= tabulate("interval $interval",$tabulate);

    // weight
    if (! is_null($weight)) $vrrp_script .= tabulate("weight $weight",$tabulate);

    // fall
    if ($fall) $vrrp_script .= tabulate("fall $fall",$tabulate);

    // rise
    if ($rise) $vrrp_script .= tabulate("rise $rise",$tabulate);

    $tabulate--;
    $vrrp_script .=tabulate("}",$tabulate);
    
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

  $track_script .= tabulate("track_script {",$tabulate);
  
  $tabulate++;
  
  while ($row = $res->fetch_assoc())
  {
    extract($row);
    
    if(! is_null($weight) ) $weight = "weight $weight";
    
    $track_script .= tabulate("$name $weight",$tabulate);

  }
  
  $tabulate--;
  $track_script .= tabulate("}",$tabulate);
  
  return $track_script;
}

// generate track_interface
function generate_track_interface($virtual_router_id = NULL)
{
  global $mysqli;
  global $tabulate;

  $track_interface = "";
  
  if (!$virtual_router_id) return false;
    
  $sql = "select 
            interface,
            weight
          from track_interface
          where virtual_router_id = '$virtual_router_id'";
          
  // SQL Error
  if (! $res = $mysqli->query($sql) ) return false;
  
  // No lines
  if ( ! $res->num_rows ) return false;

  $track_interface .= tabulate("track_interface {",$tabulate);
  
  $tabulate++;
  
  while ($row = $res->fetch_assoc())
  {
    extract($row);
    
    if(! is_null($weight) ) $weight = "weight $weight";
    
    $track_interface .= tabulate("$interface $weight",$tabulate);

  }
  
  $tabulate--;
  $track_interface .= tabulate("}",$tabulate);
  
  return $track_interface;
}

// generate ip_address
function generate_ip_address($id = NULL, $type= NULL)
{
  global $mysqli;
  global $tabulate;

  $ip_address = "";
  
  if (!$id) return false;

  // Get cluster_id from server
  if ($type == 'static') {
    $server_id = $id ;
    $sql = "select cluster_id from server where lb_id='$server_id'";
    
    if (! $res = $mysqli->query($sql) ) return false;
    if ( ! $res->num_rows ) return false;
    
    list($cluster_id) = $res->fetch_array();
   
  } else $virtual_router_id = $id ;
  
  $sql = "select 
            inet6_ntoa(ip) as ip,
            mask,
            inet6_ntoa(broadcast) as broadcast,
            dev,
            scope,
            label,
            is_gateway
          from ip_address ";
          
  if ($type == 'static') {
    $sql .= "where cluster_id='$cluster_id' ";
  } else {
    $sql .= "where virtual_router_id = '$virtual_router_id' ";
  }
  
  $sql .= "and is_disabled is null ";

  if ($type == "excluded") {
    $sql .= "and is_excluded is not null";
  } else {
    $sql .= "and is_excluded is null";
  }
    
  // SQL Error
  if (! $res = $mysqli->query($sql) ) return false;
  
  // No lines
  if ( ! $res->num_rows ) return false;

  switch($type) {
    case 'excluded':
      $ip_address .= tabulate("ip_address_excluded {",$tabulate);
      break;
      
    case 'static':
      $ip_address .= tabulate("static_ip_address {",$tabulate);
      break;
    default:
      $ip_address .= tabulate("ip_address {",$tabulate);  
  }
  
  $tabulate++;
  
  while ($row = $res->fetch_assoc())
  {
    extract($row);
   
    if($mask) $ip.= "/$mask";
    if($broadcast) $ip .=" brd $broadcast";
    if($dev) $ip .= " dev $dev";
    if($scope) $ip .= " scope $scope";
    if($label) $ip .= " label $label";
   
    $ip_address .= tabulate("$ip",$tabulate);

  }
  
  $tabulate--;
  $ip_address .= tabulate("}",$tabulate);
  
  return $ip_address;
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
    
  $vrrp_instance .=tabulate("vrrp_instance $name {",$tabulate);
  
  $tabulate++;

  if ($virtual_router_id) $vrrp_instance .=tabulate("virtual_router_id $virtual_router_id",$tabulate);

  if ($use_vmac) $vrrp_instance .=tabulate("use vmac",$tabulate);

  if ($native_ipv6) $vrrp_instance .=tabulate("native_ipv6",$tabulate);
  
  if ($interface) $vrrp_instance .=tabulate("interface $interface",$tabulate);

  if ($dont_track_primary) $vrrp_instance .=tabulate("dont_track_primary",$tabulate);

  if ($mcast_src_ip) $vrrp_instance .=tabulate("mcast_src_ip $mcast_src_ip",$tabulate);
  
  if ($lvs_sync_daemon_interface) $vrrp_instance .=tabulate("lvs_sync_daemon_interface $lvs_sync_daemon_interface",$tabulate);

  if ($garp_master_delay) $vrrp_instance .=tabulate("garp_master_delay $garp_master_delay",$tabulate);

  if ($advert_int) $vrrp_instance .=tabulate("advert_int $advert_int",$tabulate);

  if ($nopreempt) $vrrp_instance .=tabulate("nopreempt",$tabulate);

  if ($preempt_delay) $vrrp_instance .=tabulate("preempt_delay $preempt_delay",$tabulate);
  
  if ($auth_type) {
    $vrrp_instance .= tabulate("authentication",$tabulate);
    $tabulate++;
    $vrrp_instance .= tabulate("auth_type $auth_type",$tabulate);
    if ($auth_pass) $vrrp_instance .= tabulate("auth_pass $auth_pass",$tabulate);
    $tabulate--;
    $vrrp_instance .= tabulate("}",$tabulate);
  }

  if ($notify_master) $vrrp_instance .=tabulate("notify_master \"$notify_master\"",$tabulate);

  if ($notify_backup) $vrrp_instance .=tabulate("notify_backup \"$notify_backup\"",$tabulate);

  if ($notify_fault) $vrrp_instance .=tabulate("notify_fault \"$notify_fault\"",$tabulate);

  if ($notify_stop) $vrrp_instance .=tabulate("notify_stop \"$notify_stop\"",$tabulate);

  if ($notify) $vrrp_instance .=tabulate("notify \"$notify\"",$tabulate);

  if ($smtp_alert) $vrrp_instance .=tabulate("smtp_alert",$tabulate);

  // IP address
  $vrrp_instance .= generate_ip_address($virtual_router_id);
  $vrrp_instance .= generate_ip_address($virtual_router_id, 'excluded');

  // track interface
  $vrrp_instance .= generate_track_interface($virtual_router_id);
  
  // track script
  $vrrp_instance .= generate_track_script($virtual_router_id);

  $tabulate--;
  $vrrp_instance .=tabulate("}",$tabulate);
  
  
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

// VRRP Sync group
function generate_vrrp_sync_group ($server_id = NULL)
{
  global $mysqli;
  global $tabulate;

  $vrrp_sync_group = "";
  
  if (!$server_id) return false;
  
  // Select vrrp sync group 
  $sql = "select 
            g.sync_group_id, 
            g.name as group_name, 
            g.notify_master,
            g.notify_backup,
            g.notify_fault,
            g.notify,
            g.smtp_alert
          from 
            vrrp_sync_group g, server s
          where 
            g.cluster_id = s.cluster_id
            and s.lb_id = '$server_id'";

  // SQL Error
  if (! $res = $mysqli->query($sql) ) return false;
  
  // No lines
  if ( ! $res->num_rows ) return false;
  
  while ($row = $res->fetch_assoc() ) 
  {
    extract($row);
    
    // Get VRRP instances in this group
    $sql = "select name as vrrp_name from vrrp_instance where sync_group_id='$sync_group_id'";
    
    if ( ! $res_v = $mysqli->query($sql) ) return false ;
    
    // no vrrp instances in this group => go to next
    if (! $res_v->num_rows ) continue;
    
    $vrrp_sync_group .= tabulate("vrrp_sync_group $group_name {", $tabulate );
    $tabulate++;

    $vrrp_sync_group .= tabulate("group {", $tabulate );
    $tabulate++;

    while ( $row = $res_v->fetch_assoc() ) 
    {
      extract($row);
      $vrrp_sync_group .= tabulate("$vrrp_name", $tabulate );
      
    }
    
    $tabulate--;
    $vrrp_sync_group .= tabulate( "}" , $tabulate );

    if ($notify_master) $vrrp_instance .=tabulate("notify_master \"$notify_master\"",$tabulate);

    if ($notify_backup) $vrrp_instance .=tabulate("notify_backup \"$notify_backup\"",$tabulate);

    if ($notify_fault) $vrrp_instance .=tabulate("notify_fault \"$notify_fault\"",$tabulate);

    if ($notify) $vrrp_instance .=tabulate("notify \"$notify\"",$tabulate);

    if ($smtp_alert) $vrrp_instance .=tabulate("smtp_alert",$tabulate);

    $tabulate--;
    $vrrp_sync_group .= tabulate( "}" , $tabulate );
    
  }
  
  return $vrrp_sync_group;
}


?> 

