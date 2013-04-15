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
define ('VRRP_DEFAULT_SCRIPT_INTERVAL', 5 );
define ('VRRP_DEFAULT_SCRIPT_WEIGHT', 2 );
define ('VRRP_DEFAULT_SCRIPT_FALL', 1 );
define ('VRRP_DEFAULT_SCRIPT_RISE', 1 );

define ('KEEPALIVED_CONF_DEFAULT_PATH', '/etc/keepalived/keepalived.conf');

// Access Backend list
$ACCESS_BACKEND_LIST = array ( 'local' => 'local', 'ssh' => 'ssh' );

// Service Backend list
$SERVICE_BACKEND_LIST = array ('sysv' => 'System V', 'upstart' => 'Upstart', 'systemd' => 'Systemd', 'other' => 'other' );

// Ip scope list
$IP_SCOPE_LIST = array ('site', 'link', 'host', 'nowhere', 'global');

// Dictionnary for MySQL
$cluster_dictionnary = array ( 'name', 'notification_email_from', 'smtp_server','smtp_connect_timeout', 'notification_email', 'enable_traps');
$server_dictionnary = array ('name', 'ip_address', 'router_id', 'access_backend', 'service_backend', 'service_path', 
              'ssh_user', 'ssh_passphrase', 'ssh_public_key_path', 'ssh_private_key_path', 'conf_path', 'cluster_id');
$vrrp_instance_dictionnary = array ('name', 'use_vmac', 'native_ipv6', 'interface', 'dont_track_primary', 'mcast_src_ip', 
              'lvs_sync_daemon_interface', 'garp_master_delay', 'advert_int', 'auth_type', 'auth_pass', 'nopreempt',
              'preempt_delay', 'notify_master', 'notify_backup', 'notify_fault', 'notify_stop', 'notify', 'smtp_alert', 
              'cluster_id', 'sync_group_id', 'comment');
$ip_address_dictionnary = array ('ip', 'mask', 'broadcast', 'dev', 'scope', 'label', 'is_gateway', 'is_disabled', 'cluster_id', 'virtual_router_id' );
$vrrp_sync_group_dictionnary = array ('name', 'notify_master', 'notify_backup', 'notify_fault', 'notify', 'smtp_alert', 'cluster_id');
$vrrp_script_dictionnary = array('name','script','interval','weight','fall','rise');

setlocale(LC_ALL, LOCALE);
date_default_timezone_set(TIMEZONE);
