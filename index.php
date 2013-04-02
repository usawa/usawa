<?php

session_start();

require_once("include/usawa_base.inc.php");

/* 
  --------------------------------------------------------------
    cluster functions
  --------------------------------------------------------------
*/
// Display Cluster list
function table_cluster($cluster_id = NULL)
{
  global $mysqli;
  
  $unique = false ;
  
  $sql = "select 
    c.cluster_id,
    c.name,
    GROUP_CONCAT(s.name SEPARATOR ',') as servers, 
    notification_email_from, inet6_ntoa(smtp_server) as smtp_server, smtp_connect_timeout,notification_email, enable_traps 
    from cluster c 
    left join server s on s.cluster_id = c.cluster_id ";
    
  if ( $cluster_id )
  {
    $sql .= "where c.cluster_id=$cluster_id ";
    $unique = true ;
  }
  
  
  $sql .= "group by c.cluster_id
    order by c.name";

  $res = $mysqli->query($sql);
  
  if( ! $res ) {
    $mysqli->error;
    echo '<br />'.$sql;
  }

  $row = $res->fetch_assoc();
  extract($row);
?>
  <table class="bordered sorttable">
    <caption>Manage cluster <?php echo $unique?$name:"" ?></caption>
    <thead>
    <tr>
      <th>Name</th>
      <th>Servers</th>
      <th>SMTP Server</th>
      <th>SMTP Timeout</th>
      <th>Source Email</th>
      <th>Notified Emails</th>
      <th>SNMP Traps</th>
      <th>Action</th>
    </tr>
    </thead>
    <tbody>
<?php
  
  $cpt = 0;
  $res->data_seek(0);
  
  while ( $row = $res->fetch_assoc() )
  {
    extract($row);
?>
    <tr>
      <td><a href="?cluster_id=<?php echo $cluster_id ?>"><?php echo $name ?></a></td>
      <td><?php echo $servers?$servers:"-"?></td>      
      <td><?php echo $smtp_server?$smtp_server:"-" ?></td>
      <td><?php echo $smtp_connect_timeout?$smtp_connect_timeout:"-" ?></td>
      <td><?php echo $notification_email_from?$notification_email_from:"-" ?></td>
      <td><?php echo $notification_email?$notification_email:"-"?></td>
      <td><?php echo @$enable_traps?"on":"off" ?></td>
      <td>
<!-- Edit -->
        <a href="form_cluster.php?cluster_id=<?php echo $cluster_id ?>" rel=modal:open><img src="icons/link_edit.png" title="edit cluster" /></a>
        &nbsp;
<!-- Delete -->
        <a href="?action=delete&cluster_id=<?php echo $cluster_id ?>" onclick="return(confirm('Delete cluster <?php echo $name ?> ?'));"><img src="icons/link_delete.png" title="Delete cluster" /></a>
        &nbsp;
<!-- Static IP -->
        <a href="?action=edit_ip&cluster_id=<?php echo $cluster_id ?>"><img src="icons/network_ip.png" title="Static IP addresses" /></a>
      </td>
    </tr>
<?php
    $cpt ++;
  }
?>
  </tbody>
  <tfoot>
    <tr>
      <td colspan="7">&nbsp;</td>
      <td>
        <a href="form_cluster.php" rel="modal:open"><img src="icons/link_add.png" title="add cluster" /></a></td>
      </td>
    </tr>
  </tfoot>
  </table>
  
<?php
}

function update_cluster()
{
  global $mysqli;
  global $cluster_dictionnary;
  
  build_default_fields($cluster_dictionnary);
 
  extract($_POST);
    
  if ($cluster_id) 
  {

    $sql = "update cluster set 
            name=$name,
            notification_email_from=$notification_email_from ,
            smtp_server=inet6_aton($smtp_server) , 
            smtp_connect_timeout=$smtp_connect_timeout ,
            notification_email=$notification_email, 
            enable_traps=$enable_traps
            where cluster_id='$cluster_id'
            ";
    if (! $mysqli->query($sql) ) {
      echo $mysqli->error;
    }
    else
    {
      redirect_to("?cluster_id=$cluster_id");
    }
  }
  else
  {
    $sql = "insert into cluster (name,notification_email_from,smtp_server, smtp_connect_timeout,notification_email, enable_traps)
            values ( 
            $name,
            $notification_email_from,
            inet6_aton($smtp_server), 
            $smtp_connect_timeout,
            $notification_email, 
            $enable_traps
            )";
    
    if (! $mysqli->query($sql) ) echo $mysqli->error;
    
    redirect_to("?cluster_id=".$mysqli->insert_id);
    
  }
  
}

function delete_cluster($cluster_id = NULL)
{
  global $mysqli;
    
  if ($cluster_id) 
  {

    $sql = "delete from cluster
          where cluster_id='$cluster_id'";
    $res = $mysqli->query($sql);
    
  }
  
  redirect_to();
}

/* 
  --------------------------------------------------------------
    Server functions
  --------------------------------------------------------------
*/
function update_server()
{
  global $mysqli;
  global $server_dictionnary;
  global $error_code;
  
  // create a cluster based on server
  $new_cluster_id = NULL;
  $old_cluster_id=@$_POST['old_cluster_id'];
  $create_cluster = NULL;
  
  extract($_POST);
  
  if (!$cluster_id && $create_cluster) 
  {
    // It would be bad luck if 24bits random value exists
    $cluster_name = $name.substr(md5(rand()), 0, 6);  

    $sql = "insert into cluster (name) values ('$cluster_name')";
    if ($res = $mysqli->query($sql) )
    {
      $new_cluster_id = $mysqli->insert_id;
    }
  }

  build_default_fields($server_dictionnary);
  extract($_POST);

  if ( $new_cluster_id ) 
  {
    $cluster_id = "'".$new_cluster_id."'";
  }

  if($lb_id)
  {
    $sql = "update server set
              name=$name,
              ip_address=inet6_aton($ip_address),
              router_id=$router_id,
              cluster_id=$cluster_id,
              access_backend=$access_backend,
              service_backend=$service_backend,
              ssh_user=$ssh_user,
              ssh_passphrase=$ssh_passphrase,
              ssh_public_key_path=$ssh_public_key_path,
              ssh_private_key_path=$ssh_private_key_path,
              service_path=$service_path,
              conf_path=$conf_path
              where lb_id='$lb_id'
              ";
              
    if (! $mysqli->query($sql) ) 
    {
      echo $mysqli->error;
      $error_code = true;
    }
  }
  else
  {
    $sql = "insert into server (name,ip_address,router_id, access_backend, service_backend, service_path,
          ssh_user, ssh_passphrase, ssh_public_key_path, ssh_private_key_path, conf_path, cluster_id) 
              values (
                $name,
                inet6_aton($ip_address),
                $router_id,
                $access_backend,
                $service_backend,
                $service_path,
                $ssh_user,
                $ssh_passphrase,
                $ssh_public_key_path,
                $ssh_private_key_path,
                $conf_path,
                $cluster_id
              )";

    if (! $mysqli->query($sql) ) 
    {
      echo $mysqli->error;
      $error_code = true;
    }
    else
    {
      $lb_id = $mysqli−>insert_id;
    }
  }

  $cluster_id = str_replace("'",'',$cluster_id);

  redirect_to($_SERVER['HTTP_REFERER']);
}

function table_server($cluster_id = NULL)
{
  global $mysqli;
  
  $sql = "select
        s.lb_id,
        s.name as server_name,
        inet6_ntoa(s.ip_address) as ip_address,
        s.router_id,
        s.access_backend,
        s.service_backend,
        s.last_updated,
        c.cluster_id,
        c.name as cluster_name
        from  server s
        left join cluster c
        on c.cluster_id=s.cluster_id ";
  if ($cluster_id) 
  {
    $sql .= "where s.cluster_id='$cluster_id' ";
  }
    
  $sql .= "order by s.name";

  $res = $mysqli->query($sql);
  if( ! $res ) echo $mysqli->error;

?>
  <table class="bordered sorttable">
    <caption>Manage servers</caption>
    <thead>
    <tr>
      <th>Name</th>
      <th>IP Address</th>
      <th>Router Id</th>
      <th>Access Method</th>
      <th>Service type</th>
      <th>Cluster</th>
      <th>Last Synchro</th>
      <th>Action</th>
    </tr>
    </thead>
    <tbody>
<?php

  $cpt = 0;
  while ( $row = $res->fetch_assoc() )
  {
    extract($row);

?>
    <tr>
      <td ondblclick="alert('<?php echo $lb_id ?>')"><?php echo $server_name ?></td>
      <td><?php echo $ip_address ?></td>      
      <td><?php echo $router_id?$router_id:"-" ?></td>
      <td><?php echo $access_backend ?></td>
      <td><?php echo $service_backend ?></td>
      <td><?php echo $cluster_name?$cluster_name:"-" ?></td>
      <td><?php echo $last_updated?$last_updated:"-"?></td>
      <td>
        <a href="form_server.php?lb_id=<?php echo $lb_id ?>" rel=modal:open><img src="icons/server_edit.png" title="edit server" /></a>
        &nbsp;
        <a href="?action=delete&lb_id=<?php echo $lb_id ?>" onclick="return(confirm('Delete server <?php echo $server_name ?> ?'));"><img src="icons/server_delete.png" title="delete server" /></a>
      </td>

    </tr>
<?php
    $cpt ++;
  }
?>
  </tbody>
  <tfoot>
    <tr>
      <td colspan="7">&nbsp;</td>
      <td>
        <a href="form_server.php" rel="modal:open"><img src="icons/server_add.png" title="add server" /></a></td>
      </td>
    </tr>
  </tfoot>
  </table>
  
<?php
}

function delete_server($lb_id = NULL)
{
  global $mysqli;
    
  if ($lb_id) 
  {

    $sql = "delete from server
          where lb_id='$lb_id'";
    $res = $mysqli->query($sql);
    
  }
  
  redirect_to($_SERVER['HTTP_REFERER']);
}


/* 
  --------------------------------------------------------------
    VRRP instances functions
  --------------------------------------------------------------
*/
function update_vrrp_instance()
{
  global $mysqli;
  global $vrrp_instance_dictionnary;
  global $error_code;
  
  $state = false ; 
  $new_cluster_id=@$_POST['cluster_id'];
  $old_cluster_id=@$_POST['old_cluster_id'];
  $old_sync_group_id=@$_POST['old_sync_group_id'];
  
  /* echo "<pre>";
  print_r($_POST);
  echo "</pre>"; */
  
  build_default_fields($vrrp_instance_dictionnary);
 
  extract($_POST);
 
  if ($auth_pass == "NULL")
  {
    $auth_pass = "'VRRPass'";
  }
  
  if ( $action == "update" ) 
  {

    $sql = "update vrrp_instance set 
            name=$name,
            use_vmac=$use_vmac,
            native_ipv6=$native_ipv6,
            interface=$interface,
            dont_track_primary=$dont_track_primary,
            mcast_src_ip=inet6_aton($mcast_src_ip),
            lvs_sync_daemon_interface=$lvs_sync_daemon_interface,
            garp_master_delay=$garp_master_delay,
            advert_int=$advert_int,
            auth_type=$auth_type,
            auth_pass=$auth_pass,
            nopreempt=$nopreempt,
            preempt_delay=$preempt_delay,
            notify_master=$notify_master,
            notify_backup=$notify_backup,
            notify_fault=$notify_fault,
            notify=$notify,
            smtp_alert=$smtp_alert,
            cluster_id=$cluster_id,
            sync_group_id=$sync_group_id,
            comment=$comment
           where virtual_router_id='$virtual_router_id'
            ";
            
  if (! $mysqli->query($sql) ) {
      echo "<br />".$sql."<br />";
      echo $mysqli->error;
      $error_code = true;
    }
  }
  else
  {
    $sql = "insert into vrrp_instance (
              virtual_router_id,
              name,
              use_vmac,
              native_ipv6,
              interface,
              dont_track_primary,
              mcast_src_ip, 
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
              notify,
              smtp_alert,
              cluster_id,
              sync_group_id,
              comment)
            values ( 
              $virtual_router_id,
              $name,
              $use_vmac,
              $native_ipv6,
              $interface,
              $dont_track_primary,
              inet6_aton($mcast_src_ip),
              $lvs_sync_daemon_interface,
              $garp_master_delay,
              $advert_int,
              $auth_type,
              $auth_pass,
              $nopreempt,
              $preempt_delay,
              $notify_master,
              $notify_backup,
              $notify_fault,
              $notify,
              $smtp_alert,
              $cluster_id,
              $sync_group_id,
              $comment
            )";
    
    if (! $mysqli->query($sql) )
    {
      echo "<br />".$sql."<br />";
      echo $mysqli->error;
      $error_code = true;
    }
    else
    {
      $virtual_router_id = $mysqli->insert_id;
    }
        
  }

  if (! $error_code ) 
  {
    // redirect_to("?virtual_router_id=".$virtual_router_id);
    redirect_to($_SERVER['HTTP_REFERER']);
  }
  
}

function table_vrrp_instance($cluster_id = NULL, $virtual_router_id = NULL)
{
  global $mysqli;
  
  
  $sql = "select
              v.virtual_router_id,
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
              v.comment,
              v.sync_group_id,
              s.name as sync_group_name,
              c.name as cluster_name
        from  vrrp_instance v
        left join cluster c on c.cluster_id=v.cluster_id
        left join vrrp_sync_group s on v.sync_group_id = s.sync_group_id
        ";
  if ($cluster_id) 
  {
    $sql .= "where v.cluster_id='$cluster_id' ";
  }
  if ($virtual_router_id)
  {
    $sql .= "where v.virtual_router_id='$virtual_router_id' ";
  }
    
  $sql .= "order by v.virtual_router_id";
  
  $res = $mysqli->query($sql);
  if( ! $res ) {
    echo "<br />$sql</br />";
    echo $mysqli->error;
  }
?>
  
  <table class="bordered sorttable">
    <caption>Manage VRRP Instances</caption>
    <thead>
    <tr>
      <th>VRRP Id.</th>
      <th>Name</th>
      <th>Iface</th>
      <th>Advert Int.</th>
      <th>Auth.</th>
      <th>Cluster</th>
      <th>Sync Group</th>
      <th>Virt. IPs.</th>
      <th>Virt. Routes</th>
      <th>Action</th>
    </tr>
    </thead>
    <tbody>
<?php

  $cpt = 0;
  while ( $row = $res->fetch_assoc() )
  {
    extract($row);
    
    $sql = "select count(ip) from ip_address where virtual_router_id='$virtual_router_id'";

    $res_ip_count = $mysqli->query($sql);
    list($ip_count) = $res_ip_count->fetch_array();

    $sql = "select count(id_route) from route where virtual_router_id='$virtual_router_id'";

    $res_route_count = $mysqli->query($sql);
    list($route_count) = $res_route_count->fetch_array();
    
?>
    <tr onmouseover="$('#server<?php echo $cpt ?>').toggle();" onmouseout="$('#server<?php echo $cpt ?>').toggle()">
      <td><?php echo $virtual_router_id ?></td>
      <td><?php echo $name ?></td>      
      <td><?php echo $interface ?></td>
      <td><?php echo $advert_int?$advert_int:"-" ?></td>
      <td><?php echo $auth_type ?></td>
      <td><?php echo $cluster_name?$cluster_name:"-" ?></td>
      <td><?php echo $sync_group_name?$sync_group_name:"-" ?></td>
      <td><?php echo $ip_count ?></td>
      <td><?php echo $route_count ?></td>
      <td>
        <a href="form_vrrp_instance.php?virtual_router_id=<?php echo $virtual_router_id ?>" rel="modal:open"><img src="icons/brick_edit.png" title="Sdit VRRP instance" /></a>
        &nbsp;
        <a href="?action=delete&virtual_router_id=<?php echo $virtual_router_id ?>"  onclick="return(confirm('Delete VRRP instance <?php echo $name ?> ?'));"><img src="icons/brick_delete.png" title="Delete VRRP instance" /></a>
        &nbsp;
        <a href="form_vrrp_details_per_server.php?virtual_router_id=<?php echo $virtual_router_id ?>" rel="modal:open"><img src="icons/computer_link.png" title="Edit nodes priorities" /></a>
        &nbsp;
        <a href="?action=edit_ip&virtual_router_id=<?php echo $virtual_router_id ?>"><img src="icons/network_ip.png" title="Virtual IP addresses" /></a>        
        </td>
    </tr>
<?php
    if ( $cluster_id)
    {
?>
<?php
      $sql = "select s.lb_id, s.name as server_name, state, priority from vrrp_instance v 
          left join server s on v.cluster_id = s.cluster_id 
          left join vrrp_details_per_server d on s.lb_id = d.lb_id 
          where v.virtual_router_id='$virtual_router_id'
          group by s.lb_id";

      $res_servers = $mysqli->query($sql);
      if ($res_servers && $mysqli->affected_rows) 
      {
?>
    <tr style="display:none" id="server<?php echo $cpt ?>" >
      <td colspan="10">
    <table class="subtable">
<?php
        while ( $row = $res_servers->fetch_assoc() )
        {
          extract($row);
          
        
?>        
      <tr>   
      <td style="font-weight:bold">Server</td><td><?php echo $server_name; ?></td>
      <td style="font-weight:bold">State</td><td><?php echo $state?$state:"---"; ?></td>
      <td style="font-weight:bold">Priority</td><td><?php echo !is_null($priority)?$priority:"---"; ?></td>
      <td style="width:70%">&nbsp;</td>
      </tr>
<?php
        }
?>
    </table>
      </td>
    </tr>
<?php
      }
    }
?>    
    
<?php
    $cpt ++;
  }
?>
  </tbody>
  <tfoot>
    <tr>
      <td colspan="9">&nbsp;</td>
      <td>
        <a href="form_vrrp_instance.php" rel="modal:open"><img src="icons/brick_add.png" title="add VRRP Instance" /></a>
      </td>
    </tr>
  </tfoot>
  </table>
  
<?php
}

function delete_vrrp_instance($virtual_router_id = NULL)
{
  global $mysqli;
    
  if ($virtual_router_id) 
  {

    $sql = "delete from vrrp_instance
          where virtual_router_id='$virtual_router_id'";
    $res = $mysqli->query($sql);
    
  }
  
  redirect_to($_SERVER['HTTP_REFERER']);
}

/* 
  --------------------------------------------------------------
    VRRP Sync group functions
  --------------------------------------------------------------
*/
function update_vrrp_sync_group()
{
  global $mysqli;
  global $vrrp_sync_group_dictionnary;
  global $error_code;
  
  $state = false ; 
  $unquoted_sync_group_id=@$_POST['sync_group_id'];
  
  /* echo "<pre>";
  print_r($_POST);
  echo "</pre>"; */
  
  build_default_fields($vrrp_sync_group_dictionnary);
 
  extract($_POST);

  if ( $action == "update" ) 
  {

    $sql = "update vrrp_sync_group set 
            name=$name,
            notify_master=$notify_master,
            notify_backup=$notify_backup,
            notify_fault=$notify_fault,
            notify=$notify,
            smtp_alert=$smtp_alert,
            cluster_id=$cluster_id
           where sync_group_id='$sync_group_id'
            ";
    if (! $mysqli->query($sql) )
    {
      echo "<br />".$sql."<br />";
      echo $mysqli->error;
      $error_code = true;
    }

  }          
  else
  {
    $sql = "insert into vrrp_sync_group (
              name,
              notify_master,
              notify_backup,
              notify_fault,
              notify,
              smtp_alert,
              cluster_id
            )
            values ( 
              $name,
              $notify_master,
              $notify_backup,
              $notify_fault,
              $notify,
              $smtp_alert,
              $cluster_id
            )";
    
    if (! $mysqli->query($sql) )
    {
      echo "<br />".$sql."<br />";
      echo $mysqli->error;
      $error_code = true;
    }
    else
    {
      $sync_group_id = $mysqli->insert_id;
    }
        
  }

  if (! $error_code ) 
  {
    // redirect_to("?virtual_router_id=".$virtual_router_id);
    redirect_to($_SERVER['HTTP_REFERER']);
  }
  
}

function table_vrrp_sync_group($cluster_id = NULL)
{
  global $mysqli;

  $vrrp_sync_group_dictionnary = array ('name', 'notify_master', 'notify_backup', 'notify_fault', 'notify', 'smtp_alert');
  
  $unique = false;
  $cluster_name = NULL ;
  
  if (! is_null($cluster_id) ) 
  {

    $sql = "select 
            name as cluster_name
          from cluster
          where cluster_id='$cluster_id'";
    $res = $mysqli->query($sql);
    list($cluster_name) = $res->fetch_array();
  }

  $sql="SELECT 
          s.sync_group_id, 
          GROUP_CONCAT( v.name SEPARATOR  ',' ) AS vrrp_instances,
          s.name, 
          s.notify_master, 
          s.notify_backup, 
          s.notify_fault, 
          s.notify, 
          s.smtp_alert
        FROM vrrp_sync_group s
        LEFT JOIN vrrp_instance v ON v.sync_group_id=s.sync_group_id ";
  if (! is_null($cluster_id) ) 
  {
    $sql .= "where s.cluster_id='$cluster_id' ";
    $unique = true ; 
  }

  $sql .= "GROUP BY s.sync_group_id";

  $res = $mysqli->query($sql);
  if( ! $res ) {
    echo $mysqli->error;
    exit;
  }

  if($cluster_name) 
  {
    $caption = "Manage VRRP Synchronization Groups for $cluster_name";
  }
  else
  {
    $caption = "Manage VRRP Synchronization Groups";
  }
?>
  
  <table class="bordered sorttable">
  
    <caption><?php echo $caption ?></caption>
    <thead>
    <tr>
      <th>Name</th>
      <th>Cluster</th>
      <th>VRRP instances</th>
      <th>Notify master</th>
      <th>Notify backup</th>
      <th>Notify fault</th>
      <th>Notify</th>
      <th>SMTP alert</th>
      <th>Action</th>
    </tr>
    </thead>
    <tbody>
<?php

  $cpt = 0;
  $res->data_seek(0);

  while ( $row = $res->fetch_assoc() )
  {
    extract($row);
    
?>
    <tr>
      <td><?php echo $name ?></td>      
      <td><?php echo $cluster_name?$cluster_name:"-" ?></td>
      <td><?php echo @$vrrp_instances?$vrrp_instances:"-" ?></td>      
      <td><?php echo $notify_master?$notify_master:"-" ?></td>
      <td><?php echo $notify_backup?$notify_backuo:"-" ?></td>
      <td><?php echo $notify_fault?$notify_fault:"-" ?></td>
      <td><?php echo $notify?$notify:"-" ?></td>
      <td><?php echo $smtp_alert?"yes":"no" ?></td>
      <td>
        <a href="form_vrrp_sync_group.php?sync_group_id=<?php echo $sync_group_id ?>" rel="modal:open"><img src="icons/plugin_edit.png" title="Edit VRRP Sync Group" /></a>
        &nbsp;
        <a href="?action=delete&sync_group_id=<?php echo $sync_group_id ?>"  onclick="return(confirm('Delete VRRP Sync group <?php echo $name ?> ?'));"><img src="icons/plugin_delete.png" title="Delete VRRP instance" /></a>
      </td>
    </tr>
    </tr>
<?php
    $cpt ++;
  }
?>
  </tbody>
  <tfoot>
    <tr>
      <td colspan="8">&nbsp;</td>
      <td>
        <a href="form_vrrp_sync_group.php<?php echo $cluster_id?"?cluster_id=$cluster_id":"" ?>" rel="modal:open"><img src="icons/plugin_add.png" title="Add VRRP Sync Group" /></a>
      </td>
    </tr>
  </tfoot>
  </table>
  
<?php
}

function delete_vrrp_sync_group($sync_group_id = NULL)
{
  global $mysqli;
    
  if ($sync_group_id) 
  {

    $sql = "delete from vrrp_sync_group
          where sync_group_id='$sync_group_id'";
    $res = $mysqli->query($sql);
    
  }
  
  redirect_to($_SERVER['HTTP_REFERER']);
}

/* 
  --------------------------------------------------------------
    IP / Routes functions
  --------------------------------------------------------------
*/
function update_vrrp_details_per_server()
{
  global $mysqli;
  
  extract($_POST);
  
  // state/priority arrays : update vrrp details per server
  if($state)
  {
    foreach($state as $lb_id => $initial_state)
    {
      $initial_priority=$priority[$lb_id];
      
      // Initial priority 0-255
      $initial_priority = min($initial_priority,255) ;
      $initial_priority = max($initial_priority,0);
      
      $sql = "insert into vrrp_details_per_server 
                (lb_id,virtual_router_id,state,priority)
                values( '$lb_id', '$virtual_router_id', '$initial_state', '$initial_priority' )
                on duplicate key update state='$initial_state', priority='$initial_priority'";
  
      $mysqli->query($sql);      
      
    }
  }
  redirect_to($_SERVER['HTTP_REFERER']);
}

/* 
  --------------------------------------------------------------
    IP / Routes functions
  --------------------------------------------------------------
*/
function table_ip_adresses($type, $id)
{
  global $mysqli;

  $virtual_router_id = NULL;
  $cluster_id = NULL;
  
  if($type == 'static') {
    $cluster_id = $id;
    $sql = "select inet6_ntoa(ip) as ip, mask, inet6_ntoa(broadcast) as broadcast, dev, scope, label, is_gateway, is_disabled from ip_address where cluster_id='$cluster_id' order by ip";
  }
  
  if($type == 'virtual') {
    $virtual_router_id = $id;
    $sql = "select inet6_ntoa(ip) as ip, mask, inet6_ntoa(broadcast) as broadcast, dev, scope, label, is_gateway, is_disabled from ip_address where virtual_router_id='$virtual_router_id' order by ip";
  }

  $res = $mysqli->query($sql);
  
?>  
  <table class="bordered sorttable">
    <caption>Manage IP addresses</caption>
    <thead>
    <tr>
      <th>IP</th>
      <th>mask</th>
      <th>broadcast</th>
      <th>Device</th>
      <th>Scope</th>
      <th>label</th>
      <th>Disabled</th>
      <th>Gateway</th>
      <th>Action</th>
    </tr>
    </thead>
    <tbody>
<?php
  $ip_count = 0 ;
  while ($res && $row = $res->fetch_assoc())
  {
    $ip_count++;
    
    extract($row);
    
?>
    <tr>
      <td><?php echo $ip ?></td>
      <td><?php echo $mask?$mask:"-" ?></td>
      <td><?php echo $broadcast?$broadcast:"-" ?></td>
      <td><?php echo $dev?$dev:"-" ?></td>
      <td><?php echo $scope?$scope:"-" ?></td>
      <td><?php echo $label?$label:"-" ?></td>
      <td><?php echo $is_disabled?"yes":"no" ?></td>
      <td><?php echo $is_gateway?"yes":"no" ?></td>
      <td>
        <a href="form_ip_address.php?ip=<?php echo $ip ?>" rel="modal:open"><img src="icons/network_ip_edit.png" title="Edit IP Address" /></a>
        &nbsp;
        <a href="?action=delete&ip=<?php echo $ip ?>" onclick="return(confirm('Delete IP <?php echo $ip ?> ?'));"><img src="icons/network_ip_delete.png" title="Delete IP Address" /></a>
      </td>
    </tr>
<?php
  }
?>
    </tbody>
    <tfoot>
    <tr>
      <td colspan="8">
        IP count :<?php echo $ip_count ?>
      </td>
      <td>
<?php
    if ($type == 'virtual' )
    {
?>
      <a href="form_ip_address.php?virtual_router_id=<?php echo $virtual_router_id ?>" rel="modal:open"><img src="icons/network_ip_add.png" title="Add IP Address" /></a>
<?php
    }
    if( $type == 'static' )
    {
?>
      <a href="form_ip_address.php?cluster_id=<?php echo $cluster_id ?>" rel="modal:open"><img src="icons/network_ip_add.png" title="Add IP Address" /></a>
<?php
    }
?>  
      </td>
    </tr>
    </tfoot>
  </table>
<?php
}

function update_ip_address()
{
  global $mysqli;
  global $ip_address_dictionnary;
  global $error_code;
  
  extract($_POST);

  $new_ip = $ip;
 
  build_default_fields($ip_address_dictionnary);
  extract($_POST);

  // delete OLD IP
  if($old_ip)
  {
    $sql = "delete from ip_address where ip=inet6_aton('$old_ip')";
    $mysqli->query($sql);
  }
  
  $sql = "insert into ip_address
          (ip, mask, broadcast, dev, scope, label, is_gateway, is_disabled, cluster_id, virtual_router_id)
          values (
            inet6_aton($ip),
            $mask,
            inet6_aton($broadcast),
            $dev,
            $scope,
            $label,
            $is_gateway,
            $is_disabled,
            $cluster_id,
            $virtual_router_id
            )
          on duplicate key update
            mask=$mask,
            broadcast=inet6_aton($broadcast),
            dev=$dev,
            scope=$scope,
            label=$label,
            is_gateway=$is_gateway,
            is_disabled=$is_disabled,
            cluster_id=$cluster_id,
            virtual_router_id=$virtual_router_id             
            ";


  if (! $mysqli->query($sql) ) 
  {
    echo $mysqli->error;
    $error_code = true;
    exit;
  }

  redirect_to($_SERVER['HTTP_REFERER']);
}

function delete_ip_address($ip_address = NULL)
{
  global $mysqli;
    
  if ($ip_address) 
  {

    $sql = "delete from ip_address
          where ip=inet6_aton('$ip_address')";
    $res = $mysqli->query($sql);
    
  }
  
  redirect_to($_SERVER['HTTP_REFERER']);
}


/* actions */
// Check parameters
if(isset( $_REQUEST['action'] ) ) $action = $_REQUEST['action']; else $action = NULL ;
if(isset( $_REQUEST['f_type'] ) ) $f_type = $_REQUEST['f_type']; else $f_type = NULL ;
if(isset( $_REQUEST['cluster_id'] ) ) $cluster_id = $_REQUEST['cluster_id']; else $cluster_id= NULL;
if(isset( $_REQUEST['lb_id'] ) ) $lb_id = $_REQUEST['lb_id']; else $lb_id= NULL;
if(isset( $_REQUEST['ip'] ) ) $ip = $_REQUEST['ip']; else $ip = NULL;
if(isset( $_REQUEST['virtual_router_id'] ) ) $virtual_router_id = $_REQUEST['virtual_router_id']; else $virtual_router_id= NULL;
if(isset( $_REQUEST['sync_group_id'] ) ) $sync_group_id = $_REQUEST['sync_group_id']; else $sync_group_id = NULL;


switch($action) {
  case "update":
  case "insert":
    switch($f_type)
    {
      case "cluster":
        update_cluster();
        break;
      
      case "server":
        update_server();
        break;
    
      case "vrrp_instance":
        update_vrrp_instance();
        break;
        
      case "vrrp_sync_group":
        update_vrrp_sync_group();
        break;
       
      case "vrrp_details_per_server":
        update_vrrp_details_per_server();
        break;
        
      case "ip_address":
        update_ip_address();
        break;
    }
    break;
    
  case "delete":
    if($cluster_id) delete_cluster($cluster_id);
    if($lb_id) delete_server($lb_id);
    if($virtual_router_id) delete_vrrp_instance($virtual_router_id);
    if($ip) delete_ip_address($ip);
    if($sync_group_id) delete_vrrp_sync_group($sync_group_id);
    break;
  
}


page_header('Usawa');

switch($action) {
  case "edit":
    if($lb_id) form_server($lb_id);
    if($cluster_id) form_cluster($cluster_id);
    if($virtual_router_id) form_vrrp_instance($virtual_router_id);
    break;
  case "edit_ip";
    if($virtual_router_id) 
    {
      table_vrrp_instance(NULL, $virtual_router_id);
      table_ip_adresses('virtual', $virtual_router_id);
    }
    if($cluster_id)
    {
      table_cluster($cluster_id);
      table_ip_adresses('static',$cluster_id);
    }
    break;
  case "add":
    if($f_type == "cluster") form_cluster();
    if($f_type == "server") form_server($lb_id, $cluster_id);
    break;
  case "servers":
    table_server();
    break;
  case "vrrp_instances":
    table_vrrp_instance();
    table_vrrp_sync_group();
    break;
  default:
    if($cluster_id)
    {
//      echo "<pre>"; print_r($_SERVER); echo "</pre>";
      table_cluster($cluster_id);
      table_server($cluster_id);
      table_vrrp_instance($cluster_id);
      table_vrrp_sync_group($cluster_id);

    } else {
      table_cluster();
    }
    break;
}


// table_ip_adresses('virtual',1);

// ipv4

/*
$exec="ip -o -f inet addr show scope global primary|head -n 1 | awk '{print \$2,\$4}'";

function sizeofvar($var) {
    $start_memory = memory_get_usage();
    $tmp = unserialize(serialize($var));
    return memory_get_usage() - $start_memory;
}


$last_output=exec($exec, $output_array, $return);
foreach($output_array as $output_line)
{
  list($adapter,$ip) = preg_split("/[ ]+/", $output_line);
  list($ip, $netmask) = preg_split("/\//", $ip);

  $binary_ip=inet_pton($ip);
  $text_ip=inet_ntop($binary_ip);
  
  echo "Adapter : $adapter, IP: $ip, Netmask : $netmask, $text_ip <br />";
}
*/

?>

<?php
page_footer();

?>


