<?php

function put_error($error_code,$msg)
{
  if($error_code) {
    $_SESSION['ERROR_CODE'] = $error_code;
    $_SESSION['ERROR_MSG'] = $msg;
  }
}

function check_error()
{
  if(@$_SESSION['ERROR_CODE']) return true;
 
  return false;
}

function get_error()
{
  if(check_error()) return $_SESSION['ERROR_MSG'];
}

function reset_error()
{
  $_SESSION['ERROR_CODE'] = false;
  $_SESSION['ERROR_MSG'] = false;
  unset($_SESSION['ERROR_CODE']);
  unset($_SESSION['ERROR_MSG']);
}

function display_error()
{
  if( check_error() )
  {
    $msg=get_error();
?>
<script>
  $('#error_msg').text('<?php echo addslashes($msg); ?>');
  $('.display_error').show();
</script>

<?php

  }
}

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
