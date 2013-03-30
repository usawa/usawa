<?php

// Regular expressions
define ('REGEX_EMAIL','/^([.0-9a-z_-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i');
define ('REGEX_IP','/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/');
define ('REGEX_IP_RANGE','/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}-[0-9]{1,5}$/');
 
// Timezone and locales. Don't change UTF8
define ('TIMEZONE', 'Europe/Paris');
define ('LOCALE', 'fr_FR.UTF-8');
// define ('LOCALE', 'en_US.UTF-8');

// MySQL Database ids
define ('MYSQL_DB', 'usawa');
define ('MYSQL_HOST', 'localhost');
define ('MYSQL_USER', 'usawa');
define ('MYSQL_PASS', 'Us@w@');

// VRRP DEFAULTS
define ('VRRP_DEFAULT_STATE','BACKUP');
define ('VRRP_DEFAULT_PRIORITY', 100);
define ('VRRP_DEFAULT_ADVERT_INT', 1);
define ('VRRP_DEFAULT_AUTH_TYPE', 'PASS');


// Access Backend list
$ACCESS_BACKEND_LIST = array ( 'local' => 'local', 'ssh' => 'ssh' );

// Service Backend list
$SERVICE_BACKEND_LIST = array ('sysv' => 'System V', 'upstart' => 'Upstart', 'systemd' => 'Systemd', 'other' => 'other' );

// Ip scope list
$IP_SCOPE_LIST = array ('site', 'link', 'host', 'nowhere', 'global');