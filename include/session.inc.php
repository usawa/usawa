<?php

$cluster_dictionnary = array ( 'name', 'notification_email_from', 'smtp_server','smtp_connect_timeout', 'notification_email', 'enable_traps');
$server_dictionnary = array ('name', 'ip_address', 'router_id', 'access_backend', 'service_backend', 'service_path', 
              'ssh_user', 'ssh_passphrase', 'ssh_public_key_path', 'ssh_private_key_path', 'conf_path', 'cluster_id');
$vrrp_instance_dictionnary = array ('name', 'use_vmac', 'native_ipv6', 'interface', 'dont_track_primary', 'mcast_src_ip', 
              'lvs_sync_daemon_interface', 'garp_master_delay', 'advert_int', 'auth_type', 'auth_pass', 'nopreempt',
              'preempt_delay', 'notify_master', 'notify_backup', 'notify_fault', 'notify_stop', 'notify', 'smtp_alert', 'cluster_id', 'comment');
$ip_address_dictionnary = array ('ip', 'mask', 'broadcast', 'dev', 'scope', 'label', 'is_gateway', 'is_disabled', 'cluster_id', 'virtual_router_id' );
              
function is_connected()
{
  if(isset($_SESSION['id_user'])) return true;
  
  return false;
}

function redirect_to($args = "")
{
  if ( strpos($args,"http://") !== false )
  {
    header("Location: $args");
    exit;
  }
  else
  {
    $host  = $_SERVER['HTTP_HOST'];
    $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
  
    header("Location: http://$host$uri/$args");
    exit;
  }
  
}

function bd_connect()
{
  global $mysqli;
  
  $mysqli = new mysqli( MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB ) ;

  if($mysqli->connect_errno) {
    echo "Echec de la connexion Mysql".$mysqli->connect_error;
  }
}

function build_default_fields($dictionnary)
{
  global $mysqli;
  
  foreach($dictionnary as $field)
  {
    
     if(@$_POST[$field] == null)
     {
         $_POST[$field] = "NULL";
     }
     else
     {
         // Real Escape for Good Measure
         $_POST[$field] = "'" . $mysqli->real_escape_string($_POST[$field]) . "'";
     }
   }
}

bd_connect();


?>
