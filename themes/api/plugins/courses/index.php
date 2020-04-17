<?php
/**
	Plugin Name: Cursus Cache
	Plugin URI: http://house-of-training.nl
	Description: Plugin voor het cachen van cursussen van House-of-training naar api.
	Version: 2
	Author: Harry de lange
	Author URI: http://digitalmagician.nl
	License: GPL2 
 **/
// global $prog_db_version;
// $prog_db_version = "2,0";

// function course_install() {
// 	// first install new tables in database for the progress and programme
//    global $wpdb;
//    global $prog_db_version;
   
// //progress first:
// 	$table_one = $wpdb->prefix . 'courses_cache';
	
	
// 	/******** Table ONE *************/
// 	$charset_collate = $wpdb->get_charset_collate();
// 	if($wpdb->get_var('SHOW TABLES LIKE ' . $table_one) != $table_one){
// 		$sql_one = "CREATE TABLE $table_one (
// 			id mediumint(9) NOT NULL AUTO_INCREMENT,
// 			insertDate datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
// 			course_id mediumint(20) NOT NULL,
// 			last_update datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
// 			meta text NOT NULL,
// 			UNIQUE KEY id (id)
// 		) $charset_collate;";
// 		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
// 		dbDelta( $sql_one );
// 	}
// 	add_option( "prog_db_version", $prog_db_version );  
// }
// register_activation_hook( __FILE__, 'course_install' );