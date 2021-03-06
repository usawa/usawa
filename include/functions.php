<?php

require_once("include/usawa_base.inc.php");

#GLOBAL VARIABLE
$ACTION_ON_NODE = array("start","stop","restart","reload","status"); #EXHAUSTIVE LIST

class SSH {
	// SSH Host 
	private $ssh_host ;
	// SSH Port 
	private $ssh_port = 22; 
	// SSH Server Fingerprint 
//	 private $ssh_server_fp = '228CEC41EAB74561C74FA0F702168774';
	// SSH Username 
	private $ssh_auth_user ;
	// SSH Public Key File 
	private $ssh_auth_pub ;
	// SSH Private Key File 
	private $ssh_auth_priv ;
	// SSH Private Key Passphrase (null == no passphrase) 
	private $ssh_auth_pass; 
	// SSH Connection 
	private $connection; 
    
	public function __construct($ssh_host , $ssh_auth_user, $ssh_auth_pub, $ssh_auth_priv , $ssh_auth_pass = NULL )
	{
		$this->ssh_host = $ssh_host ;
		$this->ssh_auth_user = $ssh_auth_user ;
		$this->ssh_auth_pub = $ssh_auth_pub ;
		$this->ssh_auth_priv = $ssh_auth_priv ;
		$this->ssh_auth_pass = $ssh_auth_pass ;
	}
	
	public function connect() { 
		if (!($this->connection = @ssh2_connect($this->ssh_host, $this->ssh_port ,array('hostkey'=>'ssh-rsa')))) { 
			return false;
		} 

		if (! @ssh2_auth_pubkey_file($this->connection, $this->ssh_auth_user, $this->ssh_auth_pub, $this->ssh_auth_priv, $this->ssh_auth_pass) )
		{
			return false;
		}
		
		return true;
	} 

	public function exec($cmd) { 

		if( ! $this->connection) return false ;
		
		if (!($stream = ssh2_exec($this->connection, $cmd))) { 
			return false;
		}
		stream_set_blocking($stream, true); 
		$data = ""; 
		while ($buf = fread($stream, 4096)) { 
		$data .= $buf; 
		} 
		fclose($stream); 
		
		$array = explode("\n",$data);
		array_pop($array);
		
		return $array;
	}
	
	public function copy_to($from, $to) {
		if( ! $this->connection) return false ;
		
				
		if ( ! ssh2_scp_send( $this->connection, $from, $to ) )
		{
			return false;
		}
		
		return true ;
	}
		
	public function disconnect() { 
		$this->connection = null; 
	} 
	public function __destruct() { 
		$this->disconnect(); 
	} 
} 

#FUNCTION
function getinfo_server ($serverid) {
	global $mysqli ;
        $sql = "SELECT 
			name, 
			inet6_ntoa(ip_address) as ip_address,
			service_backend,
			access_backend,
			ssh_user,
			ssh_passphrase,
			ssh_public_key_path,
			ssh_private_key_path,
			service_path,
			conf_path
		FROM server WHERE lb_id = $serverid ;" ;
	$res = $mysqli->query($sql) ;
	if(!$res)
        {
                return false; 
        }
        else
        {
		if ( ! $res->num_rows ) return false ;
		
		while($row = $res->fetch_assoc())
		{
			extract($row);
			$array = array(	"name" => $name,
					"ip_address" => $ip_address, 
					"init" => $service_backend, 
					"access" => $access_backend,
					'user' => $ssh_user,
					'pass' => $ssh_passphrase,
					'pub_key' => $ssh_public_key_path,
					'priv_key' => $ssh_private_key_path,
					'service_path' => $service_path,
					'conf_path' => ($conf_path?$conf_path:KEEPALIVED_CONF_DEFAULT_PATH) );
			return($array) ;
    		}
        }
        
        return false; ;
}

function execute_command_ssh($server_id,$action, $info) {
	global $ACTION_ON_NODE ;
	
	$return_array = null ;
	
	$ssh = new SSH($info['ip_address'], $info['user'], $info['pub_key'], $info['priv_key'], $info['pass'] ) ;
	
	if (! $ssh->connect() ) {
		put_error(1,"Can't connect to server ".$info['name'].". Please check server ssh access.");
		return false ;
	}
	
	
	if(in_array($action,$ACTION_ON_NODE)) {
		echo "BUILD COMMAND LINE FOR SERVICE HERE";
	}

	$return_array = $ssh->exec($action) ;
	
	return $return_array;
}

function execute_command_local($server_id,$action,$info) {
	global $ACTION_ON_NODE ;
	
	$return_array = null ;
	
        if(!in_array($action,$ACTION_ON_NODE))
	{
                echo "<p>OTHER COMMAND</p>" ;
        }
        else
	{
        	echo "<p>COMMAND LOCAL - exec function</p>";
	}
	
	return $return_array;
}

function execute_command($server_id,$action, $info = NULL) {
	if ( ! $info ) {
		$info = getinfo_server($server_id);
	}
  
	if ( ! $info ) return false ;
	
	if($info['access'] == 'ssh') 
	{
		return execute_command_ssh($server_id, $action, $info);
	}
	if($info['access'] == 'local')
	{
		return execute_command_local($server_id, $action, $info);
	}
}

function keepalived_action_on_node($server_id,$action) 
{
	$info = getinfo_server($server_id);
	
	return execute_command($server_id, $action, $info);
}

function keepalived_action_on_cluster($cluster_id,$action)
{
	global $mysqli ;
	$sql = "SELECT lb_id FROM server WHERE cluster_id = '$cluster_id' ;" ;
	$res = $mysqli->query($sql) ;
	while($row = $res->fetch_assoc())
	{
		return keepalived_action_on_node($row['lb_id'],$action);
	}
}

function copy_keepalived_conf_to_server($server_id, $path, $info = null) {

	if ( ! $info ) {
		$info = getinfo_server($server_id);
	}
	
	$distant_path = $info['conf_path'] ;

	$distant_path_bkp = $distant_path.strftime("%F_%T");
	
	if ( $info['access'] == 'ssh' )
	{
		$ssh = new SSH($info['ip_address'], $info['user'], $info['pub_key'], $info['priv_key'], $info['pass'] ) ;
	
		if (! $ssh->connect() ) {
			put_error(1,"Can't connect to server ".$info['name'].". Please check server ssh access.");
			return false ;
		}

		// backup old conf
		$ssh->exec("test -f $distant_path && cp $distant_path $distant_path_bkp");
		
		// Copy
		if (! $ssh->copy_to( $path, $info['conf_path']) ) 
		{
			put_error(1,"Can't copy file to server ".$info['name'].". Please check server ssh access.");
			return false ;
		}
		
	}
	
	if ( $info['access'] == 'local' )
	{
		if (! copy($path, $info['conf_path'] ) )
		{
			put_error(1,"Can't copy file to server ".$info['name'].".");
			return false;
		}
	}
	
	return true ; 
	
}

// Check if a server is accessible via ssh
function check_server_access($server_id = null)
{
	global $mysqli ;
        if (! $server_id ) return false ;
        
        // prepare query
        $sql = "select inet6_ntoa(ip_address) as ip_address, access_backend from server where lb_id='$server_id'";

        // execute query
        $res = $mysqli->query($sql);

        if( ! $res ) return false ;
	
	if ( ! $res->num_rows ) return false ;
	
        $row = $res->fetch_array();

        extract($row);

	if($access_backend == 'ssh')
	{
		// Test socket on port 22 (because no timeout for ssh2_connect)
		$fp = @fsockopen("tcp://$ip_address", 22 , $errno, $errstr, 5);
		if ($fp) {
			fclose($fp);
			return true;
		} return false ;
			
	}
	if ($access_backend == 'local' ) return true ;

        return false ;
}

// Get iface/network information for a server and update database
function update_network_information($server_id = null)
{
	global $mysqli ;
	if (! $server_id ) return false ;
	
	// no access
	if (! check_server_access($server_id ) ) return false ;
	
	// Get cluster_id
	$sql = "select cluster_id from server where lb_id ='$server_id'";
	$res = $mysqli->query($sql);
	list($cluster_id) = $res->fetch_array();
	
	// Not in cluster, nothing to do
	if( ! $cluster_id) return false ;
	
	// IPv4
	if ( ! $net_array = execute_command($server_id,"/sbin/ip -o -f inet addr show scope global primary | awk -v old='' '\$2!=old && \$2!=\"lo\" { print \$2,\$4 ; old=\$2 }'") ) return false ;

	$iface_list = null ;
	foreach ($net_array as $net_info)
	{
		list ($iface, $cidr ) = explode(' ',$net_info);
		list ($ip, $netmask ) = explode ('/', $cidr);

		
		$ipLong = ip2long($ip);

		$maskBinStr =str_repeat("1", $netmask ) . str_repeat("0", 32-$netmask );
		$ipMaskLong = bindec( $maskBinStr );

		$inverseMaskBinStr = str_repeat("0", $netmask ) . str_repeat("1",  32-$netmask ); //inverse mask
		$inverseIpMaskLong = bindec( $inverseMaskBinStr );

		$network = $ipLong & $ipMaskLong ;  
                $start = $network+1 ;
		$end = ($network | $inverseIpMaskLong) -1 ;

		$ip_network = long2ip($network) ;
		$ip_start = long2ip($start) ;
		$ip_end = long2ip($end) ;
		
		$iface_list[] = "'$iface'" ;
		
		$sql = "insert into network_details (cluster_id, interface, ipv4_address, ipv4_netmask)
			values ('$cluster_id','$iface',inet6_aton('$ip'),'$netmask')
			on duplicate key update
			ipv4_address = inet6_aton('$ip_network'),
			ipv4_netmask = '$netmask'";
		$mysqli->query($sql);
	}
	
	// delete ifaces not in list
	if (count($iface_list) )
	{
		$not_in = implode(',',$iface_list);
		$sql = "delete from network_details where cluster_id='$cluster_id' and interface not in ($not_in)";
		$mysqli->query($sql);
	} else {
		// no network in server : delete all
		$sql = "delete from network_details where cluster_id='$cluster_id'";
		$mysqli->exec($sql);
	}
	
	
}

function getIpRang(  $cidr) {

   list($ip, $mask) = explode('/', $cidr);
 
   $maskBinStr =str_repeat("1", $mask ) . str_repeat("0", 32-$mask );      //net mask binary string
   $inverseMaskBinStr = str_repeat("0", $mask ) . str_repeat("1",  32-$mask ); //inverse mask
   
   $ipLong = ip2long( $ip );
   $ipMaskLong = bindec( $maskBinStr );
   $inverseIpMaskLong = bindec( $inverseMaskBinStr );
   $netWork = $ipLong & $ipMaskLong;  

   $start = $netWork+1;//去掉网络号 ,ignore network ID(eg: 192.168.1.0)
  
   $end = ($netWork | $inverseIpMaskLong) -1 ; //去掉广播地址 ignore brocast IP(eg: 192.168.1.255)
   return array( $start, $end );
}

?>
