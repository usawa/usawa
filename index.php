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
  
  $cluster_name = NULL ;
  
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
  }
  
  
  $sql .= "group by c.cluster_id
    order by c.name";

  $res = $mysqli->query($sql);
  
  if( ! $res ) 
  {
    put_error(1,"SQL Error. Please check database connectivity");
    return false;
  }

  if( $cluster_id && ! $res->num_rows ) 
  {
    put_error(1,"Selected cluster (id=$cluster_id) doesn't exist.");
    return false;
  } else {
    $row = $res->fetch_assoc();
    $cluster_name = $row['name'];
    $res->data_seek(0);
  }
?>  

  <h3 onmouseover="popup('click to display or hide')" onclick="$('#t_cluster').slideToggle()">Manage cluster <?php echo $cluster_name ?></h3>
  <div id="t_cluster">
  <table class="bordered sorttable">
    <thead>
    <tr>
      <th>Name</th>
      <th>Nodes</th>
      <th>LVS</th>
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
    
    $sql_virtual_servers = "select count(virtual_server_id) as vs_count from virtual_server where cluster_id='$cluster_id'";
    $res_virtual_servers = $mysqli->query($sql_virtual_servers);
    extract($res_virtual_servers->fetch_array());
?>
    <tr>
      <td><a href="?cluster_id=<?php echo $cluster_id ?>"><?php echo $name ?></a></td>
      <td><?php echo $servers?$servers:"-"?></td>
      <td>
	<?php echo $vs_count ?>
	<a style="float:right" href="?action=virtual_servers&cluster_id=<?php echo $cluster_id ?>"><img src="icons/building.png" title="Virtual Servers" /></a>&nbsp;        

      </td>
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
      <td colspan="8">&nbsp;</td>
      <td>
        <a href="form_cluster.php" rel="modal:open"><img src="icons/link_add.png" title="add cluster" /></a></td>
      </td>
    </tr>
  </tfoot>
  </table>
  </div>
<?php
}

function update_cluster()
{
  global $mysqli;
  global $cluster_dictionnary;
   
  $old_name = NULL;
  $cluster_id = NULL ;
  
  extract($_POST);
  
  // Name update : must not already exist
  if($old_name && $old_name != $name)
  {
    $sql = "select count(name) as count_cluster from cluster where name='$name'";
    
    $res = $mysqli->query($sql);
    
    extract($res->fetch_array());
    
    // Error : exists
    if ($count_cluster)
    {      
      put_error(1,"Can't add or update $old_name: Cluster $name already exists");
      redirect_to($_SERVER['HTTP_REFERER']);
    }
  }

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
      put_error(1,"SQL Error. Can't update cluster $name."); 
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
    
    if (! $mysqli->query($sql) )
    {
      put_error(1,"SQL Error. Can't insert cluster $name."); 
    }
  }
  
  redirect_to($_SERVER['HTTP_REFERER']);
  
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
  
  // create a cluster based on server
  $new_cluster_id = NULL;
  $old_cluster_id=@$_POST['old_cluster_id'];
  $create_cluster = NULL;
  $old_name = NULL ;
  $lb_id = NULL;
  
  extract($_POST);
  
  // Name update : must not already exist
  if($old_name && $old_name != $name)
  {
    $sql = "select count(name) as count_server from server where name='$name'";
    
    $res = $mysqli->query($sql);
    
    extract($res->fetch_array());
    
    // Error : exists
    if ($count_server)
    {      
      put_error(1,"Can't add or update: Server $name already exists");
      redirect_to($_SERVER['HTTP_REFERER']);
    }
  }
  
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
      put_error(1,"SQL Error. Can't update server $name.");
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
      put_error(1,"SQL Error. Can't insert server $name.");
    } else {
	$lb_id = $mysqli->insert_id;
    }
  }

  // check network adapters
  if ($cluster_id) {
	$return = execute_command($lb_id,"/sbin/ip -o -f inet addr show scope global primary | awk -v old='' '\$2!=old { print \$2,\$4 ; old = \$2 }'");
  }
  
  redirect_to($_SERVER['HTTP_REFERER']);
}

function table_server($cluster_id = NULL)
{
  global $mysqli;
  $cluster_name = NULL;
  
  // cluster name
  if ($cluster_id)
  {
    $sql = "select name from cluster where cluster_id='$cluster_id'";
    $res = $mysqli->query($sql);
    if( ! $res ) 
    {
      put_error(1,"SQL Error. Can't display servers");
      return false;
    }
    
    if ($res->num_rows) 
    {
      list($cluster_name) = $res->fetch_array();
    }
  }
  
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
  if( ! $res ) 
  {
    put_error(1,"SQL Error. Can't display servers");
    return false;
  }
  
  $title = "Manage servers ";
  if ($cluster_name)
  {
    $title .= "for cluster $cluster_name";
  }
  
?>
  <h3 onmouseover="popup('click to display or hide')" onclick="$('#t_server').slideToggle()"><?php echo $title ?></h3>
  <div id="t_server">
  <table class="bordered sorttable">
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
        <a href="form_server.php<?php echo $cluster_id?"?cluster_id=$cluster_id":'' ?>" rel="modal:open"><img src="icons/server_add.png" title="add server" /></a></td>
      </td>
    </tr>
  </tfoot>
  </table>
  </div>
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
  $old_name = NULL ;
  $old_virtual_router_id = NULL;
  
  extract($_POST);
  
  // Name update : must not already exist
  if($old_name && $old_name != $name)
  {
    $sql = "select count(name) as count_vrrp from vrrp_instance where name='$name'";
    
    $res = $mysqli->query($sql);
    
    extract($res->fetch_array());
    
    // Error : exists
    if ($count_vrrp)
    {      
      put_error(1,"Can't add or update $old_name: VRRP instance $name already exists");
      redirect_to($_SERVER['HTTP_REFERER']);
    }
  }

    // Name update : must not already exist
  if($old_virtual_router_id && $old_virtual_router_id != $virtual_router_id)
  {
    $sql = "select count(name) as count_vrrp from vrrp_instance where virtual_router_id='$virtual_router_id'";
    
    $res = $mysqli->query($sql);
    
    extract($res->fetch_array());
    
    // Error : exists
    if ($count_vrrp)
    {      
      put_error(1,"Can't add or update $name: Virtual Router Id $virtual_router_id already exists");
      redirect_to($_SERVER['HTTP_REFERER']);
    }
  }

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
      put_error(1,"SQL Error. Can't update VRRP instance $name.");
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
      put_error(1,"SQL Error. Can't insert VRRP instance $name.");
    }
  }
  
  redirect_to($_SERVER['HTTP_REFERER']);
  
}

function table_vrrp_instance($cluster_id = NULL, $virtual_router_id = NULL)
{
  global $mysqli;
  
  $cluster_name = NULL;
  
  // cluster name
  if ($cluster_id)
  {
    $sql = "select name from cluster where cluster_id='$cluster_id'";
    $res = $mysqli->query($sql);
    if( ! $res ) 
    {
      put_error(1,"SQL Error. Can't display vrrp instances");
      return false;
    }
    
    if ($res->num_rows) 
    {
      list($cluster_name) = $res->fetch_array();
      
    } else {
      put_error(1,"Cluster (id=$cluster_id) doesn't exist");
      return false;
    }
    
  }
 
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

  $title = "Manage VRRP Instances ";
  if ($cluster_name)
  {
    $title .= "for cluster $cluster_name";
  }
  

?>
  <h3 onmouseover="popup('click to display or hide')" onclick="$('#t_vrrp_instance').slideToggle()"><?php echo $title ?></h3>
  <div id="t_vrrp_instance">
  <table class="bordered sorttable">
    <thead>
    <tr>
      <th>VRRP Id.</th>
      <th>Name</th>
      <th>Cluster</th>
      <th>Iface</th>
      <th>Advert Int.</th>
      <th>Sync Group</th>
      <th>Virt. IPs.</th>
      <th>Virt. Routes</th>
      <th>Track Interfaces</th>
      <th>Track Scripts</th>
      <th>Action</th>
    </tr>
    </thead>
    <tbody>
<?php

  $cpt = 0;
  while ( $row = $res->fetch_assoc() )
  {
    extract($row);
    
    // IP
    $sql = "select count(ip) from ip_address where virtual_router_id='$virtual_router_id'";

    $res_ip_count = $mysqli->query($sql);
    list($ip_count) = $res_ip_count->fetch_array();

    // Routes
    $sql = "select count(id_route) from route where virtual_router_id='$virtual_router_id'";

    $res_route_count = $mysqli->query($sql);
    list($route_count) = $res_route_count->fetch_array();
    
    // Track Interfaces
    $interfaces = NULL;
    $sql = "SELECT 
              group_concat(distinct interface order by interface separator ', ') 
            FROM 
              `track_interface` 
            WHERE
              virtual_router_id='$virtual_router_id'
            GROUP BY virtual_router_id";
    $res_interfaces = $mysqli->query($sql);
    if($res_interfaces && $res_interfaces->num_rows)
    {
      list($interfaces) = $res_interfaces->fetch_array();
    }
    
    // Track scripts
    $scripts = NULL;
    $sql = "SELECT 
              group_concat(distinct name order by name separator ', ') 
            FROM 
              track_script t, vrrp_script v
            WHERE
              t.script_id = v.script_id
              and t.virtual_router_id='$virtual_router_id'
            GROUP BY virtual_router_id";
    $res_scripts = $mysqli->query($sql);
    if($res_scripts && $res_scripts->num_rows)
    {
      list($scripts) = $res_scripts->fetch_array();
    }
    
?>
    <tr onmouseover="$('#server<?php echo $cpt ?>').toggle();" onmouseout="$('#server<?php echo $cpt ?>').toggle()">
      <td><?php echo $virtual_router_id ?></td>
      <td><?php echo $name ?></td>      
      <td><?php echo $cluster_name?$cluster_name:"-" ?></td>
      <td><?php echo $interface ?></td>
      <td><?php echo $advert_int?$advert_int:"-" ?></td>
      <td><?php echo $sync_group_name?$sync_group_name:"-" ?></td>
      <td>
        <?php echo $ip_count ?>
        <a style="float:right" href="?action=edit_ip&virtual_router_id=<?php echo $virtual_router_id ?>"><img src="icons/network_ip.png" title="Virtual IP addresses" /></a>&nbsp;        
      </td>
      <td><?php echo $route_count ?></td>
      <td>
        <?php echo $interfaces?$interfaces:"-" ?>
        <a style="float:right" href="form_track_interface.php?virtual_router_id=<?php echo $virtual_router_id ?>" rel="modal:open"><img src="icons/network_adapter.png" title="Track Interfaces" /></a>
      </td>
      <td>
        <?php echo $scripts?$scripts:"-" ?>
      <a  style="float:right" href="form_track_script.php?virtual_router_id=<?php echo $virtual_router_id ?>" rel="modal:open"><img src="icons/script.png" title="Track a new script" /></a>        

      <td>
        <a href="form_vrrp_instance.php?virtual_router_id=<?php echo $virtual_router_id ?>" rel="modal:open"><img src="icons/brick_edit.png" title="Sdit VRRP instance" /></a>
        &nbsp;
        <a href="?action=delete&virtual_router_id=<?php echo $virtual_router_id ?>"  onclick="return(confirm('Delete VRRP instance <?php echo $name ?> ?'));"><img src="icons/brick_delete.png" title="Delete VRRP instance" /></a>
        &nbsp;
        <a href="form_vrrp_details_per_server.php?virtual_router_id=<?php echo $virtual_router_id ?>" rel="modal:open"><img src="icons/computer_link.png" title="Edit nodes priorities" /></a>
<!--         &nbsp; -->
<!--         <a href="form_track_interface.php?virtual_router_id=<?php echo $virtual_router_id ?>" rel="modal:open"><img src="icons/network_adapter.png" title="Track Interfaces" /></a>         -->
        </td>
    </tr>
<?php
    if ( $cluster_id)
    {
?>
<?php
      $sql = "select 
                name as server_name, 
                state,priority 
              from 
                (select 
                  s.name, 
                  v.virtual_router_id, 
                  s.lb_id 
                 from server s, vrrp_instance v
                 where s.cluster_id = v.cluster_id and v.virtual_router_id='$virtual_router_id') as tmp_ids
              left join vrrp_details_per_server using(virtual_router_id,lb_id)";

      $res_servers = $mysqli->query($sql);
      if ($res_servers && $mysqli->affected_rows) 
      {
?>
    <tr style="display:none" id="server<?php echo $cpt ?>" >
      <td colspan="11">
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
      <td colspan="10">&nbsp;</td>
      <td>
        <a href="form_vrrp_instance.php<?php echo $cluster_id?"?cluster_id=$cluster_id":'' ?>" rel="modal:open"><img src="icons/brick_add.png" title="add VRRP Instance" /></a>
      </td>
    </tr>
  </tfoot>
  </table>
  </div>
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
  $old_name = NULL;
  $sync_group_id = NULL;
  
  extract($_POST);
  
  // Name update : must not already exist
  if($old_name && $old_name != $name)
  {
    $sql = "select count(name) as count_sync from vrrp_sync_group where name='$name'";
    
    $res = $mysqli->query($sql);
    
    extract($res->fetch_array());
    
    // Error : exists
    if ($count_sync)
    {      
      put_error(1,"Can't add or update $old_name: VRRP Synchronization Group $name already exists");
      redirect_to($_SERVER['HTTP_REFERER']);
    }
  }

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
      put_error(1,"SQL Error. Can't update VRRP Synchronization group $name.");
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
      put_error(1,"SQL Error. Can't insert VRRP Synchronization group $name.");
    }
  }
  
  redirect_to($_SERVER['HTTP_REFERER']);
  
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

  <h3 onmouseover="popup('click to display or hide')" onclick="$('#t_vrrp_sync').slideToggle()"><?php echo $caption ?></h3>
  <div id="t_vrrp_sync">
  
  <table class="bordered sorttable">
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
  </div>
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
    VRRP script functions
  --------------------------------------------------------------
*/
function table_vrrp_script()
{
  global $mysqli;
  
  $sql = "select script_id, name, script, `interval`, weight, fall, rise from vrrp_script order by name";

  $res = $mysqli->query($sql);
  if( ! $res ) {
    put_error(1,"Can't execute SQL query. Please check database connectivity");
    return false;
  } 

?>
  <h3 onmouseover="popup('click to display or hide')" onclick="$('#t_vrrp_script').slideToggle()">VRRP Scripts</h3>
  <div id="t_vrrp_script">
  <table class="bordered sorttable">
    <thead>
    <tr>
      <th>Name</th>
      <th>Script</th>
      <th>Interval</th>
      <th>Weight</th>
      <th>Fall</th>
      <th>Rise</th>
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
      <td><?php echo $name ?></td>
      <td><?php echo $script ?></td>      
      <td><?php echo (!is_null($interval))?$interval:"-" ?></td>
      <td><?php echo (!is_null($weight))?$weight:"-" ?></td>
      <td><?php echo (!is_null($fall))?$fall:"-" ?></td>
      <td><?php echo (!is_null($rise))?$rise:"-" ?></td>
      <td>
        <a href="form_vrrp_script.php?script_id=<?php echo $script_id ?>" rel=modal:open><img src="icons/script_edit.png" title="edit VRRP script" /></a>
        &nbsp;
        <a href="?action=delete&script_id=<?php echo $script_id ?>" onclick="return(confirm('Delete script <?php echo $name ?> ?'));"><img src="icons/script_delete.png" title="delete server" /></a>
      </td>

    </tr>
<?php
    $cpt ++;
  }
?>
  </tbody>
  <tfoot>
    <tr>
      <td colspan="6">&nbsp;</td>
      <td>
        <a href="form_vrrp_script.php" rel="modal:open"><img src="icons/script_add.png" title="add VRRP script" /></a></td>
      </td>
    </tr>
  </tfoot>
  </table>
  </div>
<?php
}

function update_vrrp_script()
{
  global $mysqli;
  global $vrrp_script_dictionnary;
  global $error_code;
  
  
  $old_name = NULL;
  $script_id = NULL;
  
  extract($_POST);
  
  // Name update : must not already exist
  if($old_name && $old_name != $name)
  {
    $sql = "select count(name) as count_script from vrrp_script where name='$name'";

    $res = $mysqli->query($sql);
    
    extract($res->fetch_array());
    
    // Error : exists
    if ($count_script)
    {      
      put_error(1,"Can't add or update: VRRP script $name already exists");
      redirect_to($_SERVER['HTTP_REFERER']);
    }
  }
  
  build_default_fields($vrrp_script_dictionnary);
  
  extract($_POST);
  
  if($script_id) {
    $sql = "update vrrp_script set
                name=$name,
                script=$script,
                `interval`=$interval,
                weight=$weight,
                fall=$fall,
                rise=$rise
              where script_id='$script_id'
              ";
    if (! ($mysqli->query($sql) && $mysqli->affected_rows ) )
    {
      put_error(1,"VRRP script $name not updated");
      redirect_to($_SERVER['HTTP_REFERER']);
    }
    
  } else {
    $sql = "insert into vrrp_script (
              name,
              script,
              `interval`,
              weight,
              fall,
              rise )
            values (
              $name,
              $script,
              $interval,
              $weight,
              $fall,
              $rise)
            ";
        
    if (! ($mysqli->query($sql) && $mysqli->affected_rows ) ) {
      put_error(1,"VRRP script $name not inserted");
      redirect_to($_SERVER['HTTP_REFERER']);
    }
  }
  
  redirect_to($_SERVER['HTTP_REFERER']);
}

function delete_vrrp_script($script_id = NULL)
{
  global $mysqli;
    
  if ($script_id) 
  {

    $sql = "delete from vrrp_script
          where script_id='$script_id'";
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
    Track functions
  --------------------------------------------------------------
*/
function update_track_interface()
{
  global $mysqli;
  
  extract($_POST);
  
  if(@$iface) 
  {
    foreach($iface as $interface)
    {
      $t_check=@$track[$interface];
      $t_weight=@$weight[$interface];
      
      if (! $t_weight ) $t_weight = 'NULL';
      
      echo "$interface : $t_check, $t_weight<br />";
      
      if($t_check) 
      {
        $sql = "insert into track_interface (
                  virtual_router_id,
                  interface,
                  weight)
                values (
                  '$virtual_router_id',
                  '$interface',
                  $t_weight)
                on duplicate key update
                  weight=$t_weight";
                  echo $sql;
        $mysqli->query($sql);
      } else {
        $sql = "delete from track_interface where virtual_router_id='$virtual_router_id' and interface='$interface'";
        $mysqli->query($sql);
      }
    }
  }
  redirect_to($_SERVER['HTTP_REFERER']);
}

function update_track_script()
{
  global $mysqli;
  extract($_POST);
  
  // first pass : delete or update track scripts
  if(@$script)
  {
    foreach($script as $script_id)
    {
      $t_track = @$track[$script_id];
      $t_weight = @$weight[$script_id];
      
      // Delete
      if(! $t_track ) 
      {
        $sql = "delete from track_script where virtual_router_id='$virtual_router_id' and script_id='$script_id'";
        $mysqli->query($sql);
      } else {
        // Update
        $sql = "update track_script set weight='$t_weight' where virtual_router_id='$virtual_router_id' and script_id='$script_id'";
        $mysqli->query($sql);
      }
    }
  }

  // Pass two : new track script
  if (@$new_script_id)
  {
    $t_weight=@$new_weight;
    if(!$t_weight) $t_weight= 'NULL';
    else     $t_weight="'".$t_weight."'";

    $sql = "insert into track_script values ('$virtual_router_id','$new_script_id',$t_weight)";
    $mysqli->query($sql);
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
  <h3>Manage IP addresses</h3>
  <div id="t_ip">

  <table class="bordered sorttable">
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
  </div>
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
    put_error(1,"SQL error. Can't insert or update IP addres $ip.");
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

/* 
  --------------------------------------------------------------
    Virtual Server functions
  --------------------------------------------------------------
*/
function update_virtual_server()
{
  global $mysqli;
  global $virtual_server_dictionnary;
  
  extract($_POST);
  
  // Check lvs_type
  switch($lvs_type) {
	case "ip":
		$_POST['fwmark'] = null ;
		$_POST['group'] = null ;
		
		if($ip_address == null || $port == null || $protocol == null)
		{
			put_error(1,"Can't insert or update LVS. Missing fields : IP, Port or Protocol.");
			redirect_to($_SERVER['HTTP_REFERER']);
		}
		
		$lvs_name="$protocol:$ip_address:$port";
		
		break;
	case "group":
		$_POST['fwmark'] = null ;
		$_POST['ip'] = null ;
		$_POST['port'] = null ;
		$_POST['protocol'] = null ;
		if($group == null )
		{
			put_error(1,"Can't insert or update LVS. Missing field : Group.");
			redirect_to($_SERVER['HTTP_REFERER']);
		}
		$lvs_name = "group $group";
		
		break;
		
	case "fwmark":
		$_POST['group'] = null ;
		$_POST['ip'] = null ;
		$_POST['port'] = null ;
		$_POST['protocol'] = null ;
		if($fwmark == null )
		{
			put_error(1,"Can't insert or update LVS. Missing field : Firewall mark.");
			redirect_to($_SERVER['HTTP_REFERER']);
		}
		
		$lvs_name="fwmark $fwmark";
		
		break;
  }
  
  build_default_fields($virtual_server_dictionnary);
  
  extract($_POST);
  
  // check if service already exists
  
  if(@$virtual_server_id) {
	$sql = "update virtual_server 
		set
		 ip_address=inet6_aton($ip_address), 
		 port=$port,
		 fwmark=$fwmark,
		 `group`=$group,
		 delay_loop=$delay_loop,
		 lvs_sched=$lvs_sched,
		 lvs_method=$lvs_method,
                 persistence_timeout=$persistence_timeout, 
                 persistence_granularity=inet6_aton($persistence_granularity),
                 protocol=$protocol,
                 ha_suspend=$ha_suspend,
                 virtualhost=$virtualhost,
                 alpha=$alpha, 
                 omega=$omega,
                 quorum=$quorum,
                 hysteresis=$hysteresis,
                 quorum_up=$quorum_up,
                 quorum_down=$quorum_down,
                 sorry_server_ip=inet6_aton($sorry_server_ip), 
                 sorry_server_port=$sorry_server_port, 
                 cluster_id=$cluster_id
		where virtual_server_id='$virtual_router_id'
	";
	if (! $mysqli->query($sql) ) {
		put_error(1,"LVS $lvs_name not updated");
		redirect_to($_SERVER['HTTP_REFERER']);
	}

  } else {
	$sql = "insert into virtual_server 
		(ip_address, port ,fwmark, `group`, delay_loop, lvs_sched, lvs_method,
                    persistence_timeout, persistence_granularity, protocol, ha_suspend, virtualhost,
                    alpha, omega, quorum, hysteresis, quorum_up, quorum_down, sorry_server_ip, 
                    sorry_server_port, cluster_id)
                values (
			inet6_aton($ip_address), 
			$port,
			$fwmark,
			$group,
			$delay_loop,
			$lvs_sched,
			$lvs_method,
			$persistence_timeout, 
			inet6_aton($persistence_granularity),
			$protocol,
			$ha_suspend,
			$virtualhost,
			$alpha, 
			$omega,
			$quorum,
			$hysteresis,
			$quorum_up,
			$quorum_down,
			inet6_aton($sorry_server_ip), 
			$sorry_server_port, 
			$cluster_id
                )
	";
	
	if (! ($mysqli->query($sql) && $mysqli->affected_rows ) ) {
	
		put_error(1,"LVS $lvs_name not inserted");
		redirect_to($_SERVER['HTTP_REFERER']);
	}
	
  }

  // Static Ip address 
  if( $ip_storage == 'static' )  {
	$sql = "insert into ip_address (ip, mask, cluster_id)
		values ( inet6_aton($ip_address), '32', $cluster_id )
		on duplicate key update
			cluster_id=$cluster_id, 
			virtual_router_id=NULL";
	if (! $mysqli->query($sql) ) {
		put_error(1,"IP $ip_address not inserted or updated.");
	}
  }

  // VRRP IP address
  if( $ip_storage == 'vrrp' && $virtual_router_id )  {
	$sql = "insert into ip_address (ip, mask, virtual_router_id)
		values ( inet6_aton($ip_address), '32', '$virtual_router_id' )
		on duplicate key update
			cluster_id=NULL, 
			virtual_router_id='$virtual_router_id'";
	if (! $mysqli->query($sql) ) {
		put_error(1,"IP $ip_address not inserted or updated.");
	}
  }
  
  redirect_to($_SERVER['HTTP_REFERER']);
}

function table_virtual_server($cluster_id = NULL)
{
  global $mysqli;
  $cluster_name = NULL;
  
  // cluster name
  if ($cluster_id)
  {
    $sql = "select name from cluster where cluster_id='$cluster_id'";
    $res = $mysqli->query($sql);
    if( ! $res ) 
    {
      put_error(1,"SQL Error. Can't display servers");
      return false;
    }
    
    if ($res->num_rows) 
    {
      list($cluster_name) = $res->fetch_array();
    }
  }
  
  $sql = "select
		vs.virtual_server_id,
		inet6_ntoa(vs.ip_address) as ip_address, 
                vs.port, 
                vs.fwmark, 
                vs.group, 
                vs.lvs_sched, 
                vs.lvs_method,
                vs.persistence_timeout,
                vs.protocol,
                c.cluster_id,
                c.name as cluster_name
	from virtual_server vs
	left join cluster c
        on c.cluster_id=vs.cluster_id ";

  if ($cluster_id) 
  {
    $sql .= "where vs.cluster_id='$cluster_id' ";
  }
    
  $sql .= "order by vs.ip_address,vs.port,vs.protocol";

  $res = $mysqli->query($sql);
  if( ! $res ) 
  {
	echo $mysqli->error;
	exit(1);
	
    put_error(1,"SQL Error. Can't display Linux Virtual Servers");
    return false;
  }
  
  $title = "Manage Virtual Servers ";
  if ($cluster_name)
  {
    $title .= "for cluster $cluster_name";
  }
  
?>
  <h3 onmouseover="popup('click to display or hide')" onclick="$('#t_virtual_server').slideToggle()"><?php echo $title ?></h3>
  <div id="t_virtual_server">
  <table class="bordered sorttable">
    <thead>
    <tr>
      <th>IP Address</th>
      <th>Port</th>
      <th>Protocol</th>
      <th>Method</th>
      <th>Scheduler</th>
      <th>Persistence</th>
      <th>Real Servers</th>
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
      <td><?php echo $ip_address ?></td>
      <td><?php echo $port ?></td>      
      <td><?php echo $protocol ?></td>
      <td><?php echo $lvs_method ?></td>
      <td><?php echo $lvs_sched ?></td>
      <td><?php echo $persistence_timeout?$persistence_timeout:"0" ?></td>
      <td><?php echo "-"?></td>
      <td>
        <a href="form_virtual_server.php?virtual_server_id=<?php echo $virtual_server_id ?>" rel=modal:open><img src="icons/building_edit.png" title="edit virtual server" /></a>
        &nbsp;
        <a href="?action=delete&virtual_server_id=<?php echo $virtual_server_id ?>" onclick="return(confirm('Delete Linux Virtual Server <?php echo "$protocol:$ip_address:$port" ?> ?'));"><img src="icons/building_delete.png" title="delete virtual server" /></a>
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
        <a href="form_virtual_server.php<?php echo $cluster_id?"?cluster_id=$cluster_id":'' ?>" rel="modal:open"><img src="icons/building_add.png" title="add Linux Virtual Server" /></a></td>
      </td>
    </tr>
  </tfoot>
  </table>
  </div>
<?php
}

function delete_virtual_server($virtual_server_id = NULL)
{
  global $mysqli;
    
  if ($virtual_server_id) 
  {

    $sql = "delete from virtual_server
          where virtual_server_id='$virtual_server_id'";
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
if(isset( $_REQUEST['virtual_server_id'] ) ) $virtual_server_id = $_REQUEST['virtual_server_id']; else $virtual_server_id= NULL;
if(isset( $_REQUEST['sync_group_id'] ) ) $sync_group_id = $_REQUEST['sync_group_id']; else $sync_group_id = NULL;
if(isset( $_REQUEST['script_id'] ) ) $script_id = $_REQUEST['script_id']; else $script_id= NULL;


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

      case "virtual_server":
        update_virtual_server();
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
        
      case "vrrp_script":
        update_vrrp_script();
        break;
        
      case "track_interface":
        update_track_interface();
        break;
      
      case "track_script":
        update_track_script();
        break;
        
      case "ip_address":
        update_ip_address();
        break;
    }
    break;
    
  case "delete":
    if($cluster_id) delete_cluster($cluster_id);
    if($lb_id) delete_server($lb_id);
    if($virtual_server_id) delete_virtual_server($virtual_server_id);
    if($virtual_router_id) delete_vrrp_instance($virtual_router_id);
    if($ip) delete_ip_address($ip);
    if($sync_group_id) delete_vrrp_sync_group($sync_group_id);
    if($script_id) delete_vrrp_script($script_id);
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
  case "virtual_servers":
    table_virtual_server($cluster_id);
    break;
  case "servers":
    table_server();
    break;
  case "vrrp_instances":
    table_vrrp_instance();
    table_vrrp_sync_group();
    break;
  case "vrrp_scripts":
    table_vrrp_script();
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

require_once("include/functions.php");
require("include/conf_generator.php");

/*
$keepalived_conf= generate_configuration(22);


$tmpfname = tempnam("/tmp", "Keepalived_");

if (! write_file($tmpfname,$keepalived_conf) )
{
	echo "erreur";
}

if  ( !copy_keepalived_conf_to_server(22,$tmpfname) ) echo "ERREUR";

unlink($tmpfname);

*/

// update_network_information(22);


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


