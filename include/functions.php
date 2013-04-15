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

?>
