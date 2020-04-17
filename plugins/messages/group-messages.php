<?php

/**
	Plugin Name: Groeps Berichten en Opdrachten
	Plugin URI: http://house-of-media.nl
	Description: Plugin voor het geven van opdrachten of berichten aan Studenten in een groep
	Version: 1
	Author: Harry de lange
	Author URI: http://digitalmagician.nl
	License: GPL2 
 **/

//require_once( dirname(__FILE__) . '/includes/list-table-progress.php' );
global $prog_db_version;
$prog_db_version = "2,0";

/**	
 * 
 * @global type $wpdb
 * @global string $prog_db_version
 * @return database table creates db table called userprogress
 */
function on_activate( $network_wide ) {
    global $wpdb;
   
    if ( is_multisite()  ) {
        // Get all blogs in the network and activate plugin on each one
        $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
        foreach ( $blog_ids as $blog_id ) {
            switch_to_blog( $blog_id );
            create_messages_table();
            restore_current_blog();
        }
    } else {
        create_messages_table();
    }
}
register_activation_hook( __FILE__, 'on_activate' );

function create_messages_table( ) {
    global $wpdb;
    // global $table_version;
    $table_name = $wpdb->prefix . 'group_messages';
    if( $wpdb->get_var( "show tables like '{$table_name}'" ) != $table_name ) {
        $sql = "CREATE TABLE " . $table_name . " (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          userid mediumint(20) NOT NULL,
          postid mediumint(20) NOT NULL,
          last_viewed datetime NOT NULL,
          PRIMARY KEY id (id)
        );";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        // update_option("table_version", $table_version);
    } 
}
add_action( 'init', 'create_message_post_type' );

/**
 * @return post-type generates a custom post type called message
 * 
 */
function create_message_post_type() {
  $labels = array(
		'name'               => _x( 'Opdrachten', 'post type general name', 'hom' ),
		'singular_name'      => _x( 'Opdracht', 'post type singular name', 'hom' ),
		'menu_name'          => _x( 'Opdrachten', 'admin menu', 'hom' ),
		'name_admin_bar'     => _x( 'Opdrachten', 'add new on admin bar', 'hom' ),
		'add_new'            => _x( 'Nieuwe Opdracht',  'hom' ),
		'add_new_item'       => __( 'Nieuwe Opdracht toevoegen', 'hom' ),
		'new_item'           => __( 'Nieuwe Opdracht', 'hom' ),
		'edit_item'          => __( 'Bewerk Opdracht', 'hom' ),
		'view_item'          => __( 'Bekijk Opdracht', 'hom' ),
		'all_items'         => __( 'Alle Opdrachten', 'hom' ),
		'search_items'       => __( 'Zoek Bericht', 'hom' ),
		'parent_item_colon'  => __( 'Bovenliggende items:', 'hom' ),
		'not_found'          => __( 'Geen Bericht gevonden.', 'hom' ),
		'not_found_in_trash' => __( 'Geen Bericht gevonden in de trash.', 'hom' )
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'messages' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => 70,
  'menu_icon'			      => 'dashicons-welcome-write-blog',
		'taxonomies'          => array(  'post_tag' ),
		'show_in_rest'       => true,
		'rest_base'          => 'messages',
		'rest_controller_class' => 'HOT_MESSAGES_Controller',
		'supports' => array('revisions')
		);
	register_post_type( 'messages', $args );
}
// Creating table whenever a new blog is created
function on_create_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
    if ( is_plugin_active_for_network( 'messages/group-messages.php' ) ) {
        switch_to_blog( $blog_id );
        create_messages_table();
        restore_current_blog();
    }
}
add_action( 'wpmu_new_blog', 'on_create_blog', 10, 6 );