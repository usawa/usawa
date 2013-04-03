<?php 

// Include global configuration (constants, base parameters, etc.) 
require_once("include/config.inc.php") ;

function page_header($title = NULL)
{
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
        <title><?php echo $title ?></title>
        <meta name="author" content="seb" />
        <meta name="Description" content="MakeAlive keepalived configurator" />
        <meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" />

        <meta http-equiv="expires" content="0" />
        <meta http-equiv="pragma" content="no-cache" />
        <meta http-equiv="cache-control" content="no-cache, must-revalidate" />

        <link rel="stylesheet" type="text/css" href="styles/usawa.css" />
        <script type="text/javascript" src="jscript/jquery.js"></script>
        <script type="text/javascript" src="jscript/validate.js"></script>
        <script type="text/javascript" src="jscript/sorttable.js"></script>
        <script type="text/javascript" src="jscript/popup.js"></script>
        
        <script src="jscript/jquery.modal.min.js" type="text/javascript" charset="utf-8"></script>
        <link rel="stylesheet" href="styles/jquery.modal.css" type="text/css" media="screen" />
</head>

<body>
  <div id="wrapper">
    <div id="header">

<ul id="menu">    
<li><a href="#">Home</a>
    <div class="menu-container-2"> <!--Home Start -->

      <div class="column-2">
        <h2>CSS 3 Menu</h2>
      </div>
      <div class="column-2">
        <p>You can add links ,text , images  etc...</p>
      </div>
    </div>
    <!-- End Home -->
  </li>
  <li><a href="#">Manage</a>
    <div class="menu-container-1"><!-- Start tutorial menu section ( 2nd menu ) -->

      <div class="column-1">
        <ul>
          <li><a href="?action=clusters"><img src="icons/link.png" />&nbsp;Clusters</a></li>
          <li><a href="?action=servers"><img src="icons/server.png" />&nbsp;Servers</a></li>
          <li><a href="?action=vrrp_instances"><img src="icons/brick.png" />&nbsp;VRRP Instances</a></li>
        </ul>
      </div>
    </div>
  <!-- END tutorial menu section ( 2nd menu ) -->
  </li>
  <li><a href="#">Settings</a>
    <div class="menu-container-2"><!-- Latest Tuts start -->

      <div class="column-2">
        <h3>YouHack.me Tutorial</h3>
      </div>
      <div class="column-2">
          <a href="#">A title 1 here </a>
          <a href="#">A title 2 here </a>
          <a href="#">A title 3 here </a>
 </div>
    </div>
    <!-- Latest Tuts END -->
  </li>
  <li><a href="#">About</a>
    <div class="menu-container-2"><!-- START - How it works-->

      <div class="column-1">
        <h3>Users</h3>
        <ul>
          <li><a href="#">Photoshop</a></li>
          <li><a href="#">Xcode</a></li>
          <li><a href="#">After Effect</a></li>
        </ul>
      </div>
      <div class="column-1">
        <h3>Contributors</h3>
        <ul>
          <li><a href="#">Photoshop</a></li>
          <li><a href="#">Xcode</a></li>
          <li><a href="#">After Effect</a></li>
        </ul>
      </div>
    </div>
    <!-- END - How it works -->
  </li>
  
  
  </li>
  <li class="menu-right"><a href="#">Useful Links</a>
    <div class="menu-container-1 align-right"><!-- External social links - START  -->

      <div class="column-1">
        <ul class="l1">
          <li><a href="#">Forum</a></li>
          <li><a href="#">Blog</a></li>
          <li><a href="#">Facebook</a></li>
          <li><a href="#">Twitter</a></li>
          <li><a href="#">Youtube</a></li>
          <li><a href="#">Contact</a></li>
        </ul>
      </div>
    </div>
    <!-- External social links - END   -->
  </li>
</ul>
    </div>
    <div id="content">
<?php

}

function page_footer()
{
?>
    <div id="footer"> 
      Usawa 0.0.1a - S. ROHAUT - S. BELARBI<br />
    </div> 
  </div>

</body>
</html>

<?php   
}
 
?>