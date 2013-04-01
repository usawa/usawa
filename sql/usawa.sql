-- phpMyAdmin SQL Dump
-- version 3.5.7
-- http://www.phpmyadmin.net
--
-- Client: localhost:3306
-- Généré le: Lun 01 Avril 2013 à 20:10
-- Version du serveur: 5.6.10
-- Version de PHP: 5.4.12

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `usawa`
--

-- --------------------------------------------------------

--
-- Structure de la table `cluster`
--

CREATE TABLE IF NOT EXISTS `cluster` (
  `cluster_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique cluster id',
  `name` varchar(255) NOT NULL COMMENT 'cluster name',
  `notification_email_from` varchar(255) DEFAULT NULL,
  `smtp_server` varbinary(16) DEFAULT NULL,
  `smtp_connect_timeout` tinyint(3) unsigned DEFAULT NULL,
  `notification_email` text,
  `enable_traps` tinyint(1) DEFAULT NULL,
  `last_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`cluster_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='List of clusters' AUTO_INCREMENT=18 ;

-- --------------------------------------------------------

--
-- Structure de la table `ip_address`
--

CREATE TABLE IF NOT EXISTS `ip_address` (
  `ip` varbinary(16) NOT NULL COMMENT 'IP address v4 or v6',
  `mask` tinyint(3) unsigned DEFAULT NULL COMMENT 'Mask max 64',
  `broadcast` varbinary(16) DEFAULT NULL,
  `dev` varchar(255) DEFAULT NULL COMMENT 'device',
  `scope` enum('site','link','host','nowhere','global') DEFAULT NULL COMMENT 'scope',
  `label` varchar(255) DEFAULT NULL,
  `is_gateway` tinyint(1) DEFAULT NULL COMMENT 'true if VIP GW',
  `is_disabled` tinyint(1) DEFAULT NULL COMMENT 'true if excluded from virtual or static IP address',
  `cluster_id` smallint(5) unsigned DEFAULT NULL,
  `virtual_router_id` tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`ip`),
  KEY `cluster_id` (`cluster_id`),
  KEY `virtual_router_id` (`virtual_router_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='List of all IP';

-- --------------------------------------------------------

--
-- Structure de la table `network_details`
--

CREATE TABLE IF NOT EXISTS `network_details` (
  `cluster_id` smallint(5) unsigned NOT NULL,
  `interface` varchar(255) NOT NULL,
  `ipv4_address` varbinary(16) DEFAULT NULL,
  `ipv4_netmask` tinyint(3) unsigned DEFAULT NULL,
  `ipv6_address` varbinary(16) DEFAULT NULL,
  `ipv6_netmask` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`cluster_id`,`interface`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `privilege`
--

CREATE TABLE IF NOT EXISTS `privilege` (
  `user_id` smallint(5) unsigned NOT NULL,
  `virtual_server_id` smallint(5) unsigned NOT NULL,
  `privilege` enum('read','write') NOT NULL DEFAULT 'read',
  PRIMARY KEY (`user_id`,`virtual_server_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Specific user privileges on their virtual servers';

-- --------------------------------------------------------

--
-- Structure de la table `route`
--

CREATE TABLE IF NOT EXISTS `route` (
  `id_route` smallint(5) unsigned NOT NULL,
  `route_string` text,
  `cluster_id` smallint(5) unsigned DEFAULT NULL,
  `virtual_router_id` tinyint(3) unsigned DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_route`),
  KEY `cluster_id` (`cluster_id`),
  KEY `virtual_router_id` (`virtual_router_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Contains all routes';

-- --------------------------------------------------------

--
-- Structure de la table `server`
--

CREATE TABLE IF NOT EXISTS `server` (
  `lb_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique LB id',
  `name` varchar(255) NOT NULL COMMENT 'LB server name',
  `ip_address` varbinary(16) NOT NULL COMMENT 'Primary IP address',
  `router_id` varchar(255) DEFAULT NULL,
  `md5_hash` varchar(32) DEFAULT NULL COMMENT 'MD5 Hash of keepalived.conf',
  `last_updated` datetime DEFAULT NULL COMMENT 'Last update of server configuration',
  `access_backend` enum('local','ssh') NOT NULL DEFAULT 'local' COMMENT 'Access Method',
  `ssh_user` varchar(255) DEFAULT NULL,
  `ssh_passphrase` varchar(255) DEFAULT NULL,
  `ssh_public_key_path` text,
  `ssh_private_key_path` text,
  `service_backend` enum('sysv','upstart','systemd','other') NOT NULL DEFAULT 'sysv',
  `service_path` text COMMENT 'Path to keepalived control script',
  `conf_path` text,
  `cluster_id` smallint(5) unsigned DEFAULT NULL COMMENT 'Load Balancer is part of a cluster',
  PRIMARY KEY (`lb_id`),
  UNIQUE KEY `name` (`name`),
  KEY `cluster_id` (`cluster_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='List of all load balancers' AUTO_INCREMENT=22 ;

-- --------------------------------------------------------

--
-- Structure de la table `track_interface`
--

CREATE TABLE IF NOT EXISTS `track_interface` (
  `track_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `virtual_router_id` tinyint(3) unsigned NOT NULL COMMENT 'Virtual router id',
  `interface` varchar(255) NOT NULL,
  `weight` smallint(6) NOT NULL COMMENT 'priority to add or remove',
  PRIMARY KEY (`track_id`,`virtual_router_id`),
  KEY `virtual_router_id` (`virtual_router_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Interfaces to monitor in VRRP instance' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `track_script`
--

CREATE TABLE IF NOT EXISTS `track_script` (
  `virtual_router_id` tinyint(10) unsigned NOT NULL COMMENT 'VVRP unique ID',
  `script_id` smallint(5) unsigned NOT NULL COMMENT 'Unique script identifier',
  `weight` smallint(6) DEFAULT NULL COMMENT 'adjust priority by this weight',
  PRIMARY KEY (`virtual_router_id`,`script_id`),
  KEY `script_id` (`script_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='scripts in VVRP instance';

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id_user` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `password` varchar(32) NOT NULL DEFAULT '*',
  `default_group` tinyint(3) unsigned NOT NULL DEFAULT '255',
  `last_connection` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_ip` varbinary(16) DEFAULT NULL COMMENT 'from host',
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `login` (`login`),
  KEY `default_group` (`default_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `vrrp_details_per_server`
--

CREATE TABLE IF NOT EXISTS `vrrp_details_per_server` (
  `lb_id` smallint(5) unsigned NOT NULL COMMENT 'Load balancer Server Id',
  `virtual_router_id` tinyint(3) unsigned NOT NULL COMMENT 'VVRP Id',
  `state` enum('BACKUP','MASTER') NOT NULL DEFAULT 'BACKUP' COMMENT 'Initial VRRP instance state',
  `priority` tinyint(3) unsigned NOT NULL DEFAULT '100' COMMENT 'VRRP Priority',
  PRIMARY KEY (`lb_id`,`virtual_router_id`),
  KEY `virtual_router_id` (`virtual_router_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Specific details of VRRP instance for a server';

-- --------------------------------------------------------

--
-- Structure de la table `vrrp_instance`
--

CREATE TABLE IF NOT EXISTS `vrrp_instance` (
  `virtual_router_id` tinyint(3) unsigned NOT NULL COMMENT 'VVRP unique ID',
  `name` varchar(255) NOT NULL COMMENT 'VRRP instance name',
  `use_vmac` tinyint(1) DEFAULT NULL COMMENT 'Use VMAC unless GARP',
  `native_ipv6` tinyint(1) DEFAULT NULL COMMENT 'Force instance to use IPv6 when using mixed IPv4&IPv6 conf',
  `interface` varchar(255) NOT NULL COMMENT 'Binding interface',
  `dont_track_primary` tinyint(1) DEFAULT NULL COMMENT 'ignore VRRP interface faults',
  `mcast_src_ip` varbinary(16) DEFAULT NULL COMMENT 'src_ip to use into the VRRP packets',
  `lvs_sync_daemon_interface` varchar(255) DEFAULT NULL COMMENT 'Binding interface for lvs syncd',
  `garp_master_delay` smallint(5) unsigned DEFAULT NULL COMMENT 'delay for gratuitous ARP after MASTER',
  `advert_int` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT 'VRRP advert interval',
  `auth_type` enum('PASS','AH') NOT NULL DEFAULT 'PASS' COMMENT 'Simple Password or IPSEC AH',
  `auth_pass` varchar(8) NOT NULL DEFAULT 'VRRPass' COMMENT 'Password string',
  `nopreempt` tinyint(1) DEFAULT NULL COMMENT 'Override VRRP RFC preemption default',
  `preempt_delay` smallint(6) DEFAULT NULL COMMENT 'Seconds after startup until preemption',
  `debug` tinyint(3) unsigned DEFAULT NULL COMMENT 'Debug level',
  `notify_master` varchar(255) DEFAULT NULL COMMENT 'Script to run during MASTER transit',
  `notify_backup` varchar(255) DEFAULT NULL COMMENT 'Script to run during BACKUP transit',
  `notify_fault` varchar(255) DEFAULT NULL COMMENT 'Script to run during FAULT transit',
  `notify_stop` varchar(255) DEFAULT NULL COMMENT 'Script to run when VRRP instance is stopped',
  `notify` varchar(255) DEFAULT NULL COMMENT 'Script to run during ANY state transit',
  `smtp_alert` tinyint(1) DEFAULT NULL COMMENT 'Send email notif during state transit',
  `cluster_id` smallint(5) unsigned DEFAULT NULL,
  `sync_group_id` smallint(5) unsigned DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`virtual_router_id`),
  UNIQUE KEY `name` (`name`),
  KEY `cluster_id` (`cluster_id`),
  KEY `sync_group_id` (`sync_group_id`),
  KEY `sync_group_id_2` (`sync_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Contains all vrrp instances';

-- --------------------------------------------------------

--
-- Structure de la table `vrrp_script`
--

CREATE TABLE IF NOT EXISTS `vrrp_script` (
  `script_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique script identifier',
  `name` varchar(255) NOT NULL COMMENT 'vrrp script name',
  `script` varchar(255) NOT NULL COMMENT 'script to run periodically',
  `interval` smallint(5) unsigned DEFAULT NULL COMMENT 'run the script this every seconds',
  `weight` smallint(6) DEFAULT NULL COMMENT 'adjust priority by this weight',
  `fall` smallint(5) unsigned DEFAULT NULL COMMENT 'required number of failures for KO switch',
  `rise` smallint(5) unsigned DEFAULT NULL COMMENT 'required number of successes for OK switch',
  PRIMARY KEY (`script_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Scripts to be executed periodically' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `vrrp_sync_group`
--

CREATE TABLE IF NOT EXISTS `vrrp_sync_group` (
  `sync_group_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Internal Sync Group Identifier',
  `name` varchar(255) NOT NULL COMMENT 'Sync Group Name',
  `notify_master` varchar(255) DEFAULT NULL COMMENT 'Script to run during MASTER transit',
  `notify_backup` varchar(255) DEFAULT NULL COMMENT 'Script to run during BACKUP transit',
  `notify_fault` varchar(255) DEFAULT NULL COMMENT 'Script to run during FAULT transit',
  `notify` varchar(255) DEFAULT NULL COMMENT 'Script to run during ANY state transit',
  `smtp_alert` tinyint(1) DEFAULT NULL COMMENT 'Send email notif during state transit',
  `cluster_id` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`sync_group_id`),
  KEY `cluster_id` (`cluster_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='VRRP Sync group' AUTO_INCREMENT=4 ;

--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table `ip_address`
--
ALTER TABLE `ip_address`
  ADD CONSTRAINT `ip_address_ibfk_1` FOREIGN KEY (`cluster_id`) REFERENCES `cluster` (`cluster_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ip_address_ibfk_2` FOREIGN KEY (`virtual_router_id`) REFERENCES `vrrp_instance` (`virtual_router_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `route`
--
ALTER TABLE `route`
  ADD CONSTRAINT `route_ibfk_1` FOREIGN KEY (`cluster_id`) REFERENCES `cluster` (`cluster_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `route_ibfk_2` FOREIGN KEY (`virtual_router_id`) REFERENCES `vrrp_instance` (`virtual_router_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `server`
--
ALTER TABLE `server`
  ADD CONSTRAINT `server_ibfk_1` FOREIGN KEY (`cluster_id`) REFERENCES `cluster` (`cluster_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `track_interface`
--
ALTER TABLE `track_interface`
  ADD CONSTRAINT `track_interface_ibfk_1` FOREIGN KEY (`virtual_router_id`) REFERENCES `vrrp_instance` (`virtual_router_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `track_script`
--
ALTER TABLE `track_script`
  ADD CONSTRAINT `track_script_ibfk_1` FOREIGN KEY (`virtual_router_id`) REFERENCES `vrrp_instance` (`virtual_router_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `track_script_ibfk_2` FOREIGN KEY (`script_id`) REFERENCES `vrrp_script` (`script_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `vrrp_details_per_server`
--
ALTER TABLE `vrrp_details_per_server`
  ADD CONSTRAINT `vrrp_details_per_server_ibfk_1` FOREIGN KEY (`lb_id`) REFERENCES `server` (`lb_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `vrrp_details_per_server_ibfk_2` FOREIGN KEY (`virtual_router_id`) REFERENCES `vrrp_instance` (`virtual_router_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `vrrp_instance`
--
ALTER TABLE `vrrp_instance`
  ADD CONSTRAINT `vrrp_instance_ibfk_2` FOREIGN KEY (`sync_group_id`) REFERENCES `vrrp_sync_group` (`sync_group_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `vrrp_instance_ibfk_1` FOREIGN KEY (`cluster_id`) REFERENCES `cluster` (`cluster_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `vrrp_sync_group`
--
ALTER TABLE `vrrp_sync_group`
  ADD CONSTRAINT `vrrp_sync_group_ibfk_1` FOREIGN KEY (`cluster_id`) REFERENCES `cluster` (`cluster_id`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
