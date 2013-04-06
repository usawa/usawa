<?php

require_once("include/usawa_base.inc.php");

#GLOBAL VARIABLE
$ACTION_ON_NODE = array("start","stop","restart","reload","status"); #EXHAUSTIVE LIST

#FUNCTION
function getinfo_server ($serverid) {
	global $mysqli ;
        $sql = "SELECT inet6_ntoa(ip_address) as ip_address,service_backend,access_backend,service_path FROM server WHERE lb_id = $serverid ;" ;
	$res = $mysqli->query($sql) ;
        if(!$res)
        {
                echo $mysqli->error ;
        }
        else
        {
		while($row = $res->fetch_assoc())
		{
			extract($row);
			$array = array(	"ip_address" => $ip_address, 
					"init" => $service_backend, 
					"access" => $access_backend, 
					"service_path" => $service_path );
			return($array) ;
    		}
        }
}

function execute_command_ssh($server_id,$info,$action) {
	global $ACTION_ON_NODE ;
        if(!in_array($action,$ACTION_ON_NODE)) {
                echo "<p>OTHER COMMAND</p>" ;
        }
        else{
		echo "<p>COMMAND SSH - ssh2_connect function</p>";
	}
}

function execute_command_local($server_id,$info,$action) {
	global $ACTION_ON_NODE ;
        if(!in_array($action,$ACTION_ON_NODE))
	{
                echo "<p>OTHER COMMAND</p>" ;
        }
        else
	{
        	echo "<p>COMMAND LOCAL - exec function</p>";
	}
}

function execute_command($server_id,$info,$action){
	if($info['access'] == 'ssh') 
	{
		execute_command_ssh($server_id,$info,$action);
	}
	if($info['access'] == 'local')
	{
		execute_command_local($server_id,$info,$action);
	}
}

function keepalived_action_on_node($server_id,$action) 
{
	$info = getinfo_server($server_id);
	print_r($info);
	execute_command($server_id,$info,$action);
}

function keepalived_action_on_cluster($cluster_id,$action)
{
	global $mysqli ;
	$sql = "SELECT lb_id FROM server WHERE cluster_id = '$cluster_id' ;" ;
	$res = $mysqli->query($sql) ;
	while($row = $res->fetch_assoc())
	{
		keepalived_action_on_node($row['lb_id'],$action);
	}
}

#MAIN
$array = getinfo_server('19');
keepalived_action_on_node('19','start');
keepalived_action_on_cluster('8','stop');
keepalived_action_on_cluster('8','ifconfig'); #FOR EXAMPLE
?>
