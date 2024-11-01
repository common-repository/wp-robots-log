<?php
/*

**************************************************************************

Plugin Name:  WP Robots Log
Plugin URI:   http://www.arefly.com/wp-robots-log/
Description:  Log the Search Engine Robots Visits of your Webite. 取得搜索引擎的蜘蛛訪問網站的記錄
Version:      1.0.1
Author:       Arefly
Author URI:   http://www.arefly.com/
Text Domain:  wp-robots-log
Domain Path:  /lang/

**************************************************************************

	Copyright 2014  Arefly  (email : eflyjason@gmail.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

**************************************************************************/

define("WP_ROBOTS_LOG_PLUGIN_URL", plugin_dir_url( __FILE__ ));
define("WP_ROBOTS_LOG_FULL_DIR", plugin_dir_path( __FILE__ ));
define("WP_ROBOTS_LOG_TEXT_DOMAIN", "wp-robots-log");
define("WP_ROBOTS_LOG_TIME_STAMP", time()+(8*3600));			// Define Time Stamp
define("WP_ROBOTS_LOG_MAX_RECORDS", 1000);						// Define Max Records

/* Plugin Localize */
function wp_robots_log_load_plugin_textdomain() {
	load_plugin_textdomain(WP_ROBOTS_LOG_TEXT_DOMAIN, false, dirname(plugin_basename( __FILE__ )).'/lang/');
}
add_action('plugins_loaded', 'wp_robots_log_load_plugin_textdomain');

/* Add Links to Plugins Management Page */
function wp_robots_log_action_links($links){
	$links[] = '<a href="'.home_url().'/robots_log.txt" target="_blank">'.__("Check Robots Log", WP_ROBOTS_LOG_TEXT_DOMAIN).'</a>';
	return $links;
}
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'wp_robots_log_action_links');

$log_file = ABSPATH."robots_log.txt";
$log_file_lock = $log_file.".lock";

/* Get Old Records */
function wp_robots_log_get_old(){
	global $log_file;
	if(!file_exists($log_file)){
		touch($log_file);
		chmod($log_file,0755);
		clearstatcache();
		return NULL;
	}else{
		$old_log = file_get_contents($log_file);
		$fo = explode("\r\n", substr($old_log, 0, -2));
		$old = "";
		switch(isset($fo[WP_ROBOTS_LOG_MAX_RECORDS])){
			case TRUE:
				for($i = 0; $i < WP_ROBOTS_LOG_MAX_RECORDS; $i++){
					$old .= $fo[$i]. "\r\n";
				}
				break;
			case FALSE:
				$old = $old_log;
			break;
		}
		unset($fo);
		unset($old_log);
		return $old;
	}
}

/* Log Robots */
function wp_robots_log(){
	global $log_file, $log_file_lock;
	if(isset($_SERVER['HTTP_USER_AGENT']) && isset($_SERVER['REQUEST_URI'])){
		$is_bot = FALSE;
		if(preg_match("/bot/i", $_SERVER['HTTP_USER_AGENT']) || preg_match("/spider/i", $_SERVER['HTTP_USER_AGENT'])){
			$is_bot = TRUE;
		}
		if($is_bot == TRUE){
			$real_ip = preg_replace("/^::ffff:/i", "", $_SERVER['REMOTE_ADDR']);			// IPV6 Compatible
			$str = date("Y-m-d H:i:s", WP_ROBOTS_LOG_TIME_STAMP). "\t". $real_ip. "\t". $_SERVER['REQUEST_URI']. "\t". $_SERVER['HTTP_USER_AGENT']. "\r\n";		// Log Format, you could defined it by yourself
			$fo = wp_robots_log_get_old();				// Got old Records
			if(!file_exists($log_file_lock)){			// Check log file write lock if exists
				touch($log_file_lock);					// Create file lock prevent write log twice each time
				file_put_contents($log_file, $str. $fo);		// Puts new & old records together
				unlink($log_file_lock);					// Remove the log file write lock
				clearstatcache();
			}
			unset($real_ip);
			unset($str);
			unset($fo);
		}
		unset($is_bot);
	}
}
add_action("init", 'wp_robots_log');
