<?php
//PROGRESS

add_action("after_switch_theme", "hot_create_trophies");

function hot_create_trophies(){
    global $wpdb;

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . "trophies";  //get the database table prefix to create my new table

    $sql = "CREATE TABLE $table_name (
      id int(10) unsigned NOT NULL AUTO_INCREMENT,
      user_id mediumint(20)	NOT NULL,
      course_id mediumint(20)	 NOT NULL,
      progress int(11) NOT NULL,
      first_entry datetime NOT NULL,
      last_entry datetime NOT NULL,
      PRIMARY KEY  (id),
      KEY Index_2 (user_id),
      KEY Index_3 (course_id)
    ) $charset_collate;";

    dbDelta( $sql );
}
function hot_create_extra_table(){
  	// first install new tables in database for the progress and programme
   global $wpdb;
   global $prog_db_version;
  //progress first:
    $table_one = $wpdb->prefix . 'user_progress';
    
    /******** Table ONE *************/
    $charset_collate = $wpdb->get_charset_collate();
    if($wpdb->get_var('SHOW TABLES LIKE ' . $table_one) != $table_one){
      $sql_one = "CREATE TABLE $table_one (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        startdate datetime NOT NULL,
        user_id mediumint(20) NOT NULL,
        video_id mediumint(20) NOT NULL,
        course_id mediumint(20) NOT NULL,
        videoTitle tinytext NOT NULL,
        last_viewed datetime NOT NULL,
        perc_viewed int(11) DEFAULT 0,
        views tinyint(20) NOT NULL,
        meta text NOT NULL,
        PRIMARY KEY id (id)
      ) $charset_collate;";
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      dbDelta( $sql_one );
    }
    add_option( "prog_db_version", $prog_db_version ); 
}

add_action("after_switch_theme", "hot_create_extra_table");

function register_HOT_progress() {
	$nonce = $_POST['nonce'];
	// if (empty($_POST) || !wp_verify_nonce($nonce, 'syntra_rtk3ts7c' ) ){
	// wp_send_json_error(array( 'status' => 'register_HOT_progress_FAILED', 'ERROR'    =>$nonce )); // sends json_encoded success=false
	// } else {
	$id = 0;
	if ( $_POST['id']) {
		$id = intval(urldecode($_POST['id']));
		$meta = stripslashes($_POST['meta']);
		$perc_viewed = intval($_POST['perc_viewed']);
		global $wpdb;
		$base =  $wpdb->base_prefix ;
		 $table_name =  $base. 'user_progress';
		$data = array(
			'perc_viewed' => $perc_viewed,
			'meta'=> $meta,	
			'last_viewed' => current_time( 'mysql' )
		);
		$where = array(
				'id' => $id
		);
	
	$results = $wpdb->update( $table_name, $data, $where );
		if ( $results == 0 ) {
			wp_send_json_error(array(
				"ERROR" => "Not added",
				"tableName" => $table_name,
				"data" => $data,
				"where" => $where
			));
		
		} else {
			$id = array( 'id' => $id );
			wp_send_json_success($id);
		}
		
	} else {
		wp_send_json_error(array(
			"ERROR" => "Not added"
		));
	}
	

//  }
}

add_action( 'wp_ajax_register_HOT_progress', 'register_HOT_progress' ); //
add_action( 'wp_ajax_nopriv_register_HOT_progress', 'register_HOT_progress' ); //

function start_hot_user_progress(){
	$nonce = $_POST['nonce'];
	if (empty($_POST) /*|| !wp_verify_nonce($nonce, 'syntra_rtk3ts7c' ) */ ){
		wp_send_json_error( array('status' => 'START_PROGRESS_ERROR', 'ERROR'    =>  $nonce )); // sends json_encoded success=false
	} else {
		 global $wpdb;
		 $base =  $wpdb->base_prefix ;
		 //$base = strtolower( $wpdb->prefix );
		 $table_name =  $base.'user_progress';
		 	//$startdate = new Date();
		 	$user_id = intval( $_POST['user_id']);
			$video_id = intval( $_POST['video_id']);
			$results = $wpdb->get_results( 'SELECT * FROM ' . $table_name . ' WHERE video_id = ' . $video_id . ' AND user_id = ' . $user_id . ' LIMIT 1;' );
			if ( count( $results ) == 0 ) {
				$course_id = intval( $_POST['course_id']);
				$title = sanitize_text_field($_POST['title']);
				$metaData =  $_POST['meta'];
				// $views = 1;
				$wpdb->insert(
					$table_name, array(
						'video_id' => $video_id,
						'course_id' => $course_id,
						'videoTitle' => $title,
						'user_id'		=> $user_id,
						'last_viewed' => current_time( 'mysql' ),
						'views' => 1,
						'perc_viewed'=> 0,	
						'meta'	=> $metaData
					)
				);
				$results = $wpdb->get_results( 'SELECT * FROM ' . $table_name . ' WHERE id = ' . $wpdb->insert_id . ' LIMIT 1;' );
				
				$id = array( 'id' => $wpdb->insert_id, 'data' => $results[0] );
				wp_send_json_success( $id );
			} else {
				wp_send_json_success( $id = array( 'id' => $results[0]->id,  'data' => $results[0] ) );
			}
	}
}


add_action( 'wp_ajax_start_hot_user_progress', 'start_hot_user_progress' ); //
add_action( 'wp_ajax_nopriv_start_hot_user_progress', 'start_hot_user_progress' ); //

function get_hot_user_progress(){
	$nonce = $_GET['nonce'];
	if (empty($_GET) /*|| !wp_verify_nonce($nonce, 'syntra_rtk3ts7c' ) */ ){
		wp_send_json_error( array('status' => 'USER_PROGRESS_ERROR', 'ERROR'    =>  $nonce )); // sends json_encoded success=false
	} else {
		$user_id = intval( sanitize_text_field( $_GET['user_id'] ));
		$limit = '';
		$offset = '';
		if( $_GET['limit'] ){
			$limit = ' LIMIT '.intval( sanitize_text_field( $_GET['limit'] ));
		}
		if( $_GET['offset'] ){
			$offset = ' OFFSET '.intval( sanitize_text_field( $_GET['offset'] ));
		}
	
		global $wpdb;
		// $base = strtolower( $wpdb->base_prefix );
		$base =  $wpdb->base_prefix ;
		$table_name =  $base. 'user_progress';
		$orderbycol = 'last_viewed';
		$order = 'DESC';
		$results = $wpdb->get_results( 'SELECT * FROM '.$table_name.' WHERE user_id = '. $user_id.' order by '.$orderbycol.' '.$order.$limit.$offset );
		$filtered = $results[0];
		wp_send_json_success( 	$results ); 
	}
}

add_action( 'wp_ajax_get_hot_user_progress', 'get_hot_user_progress' ); //
add_action( 'wp_ajax_nopriv_get_hot_user_progress', 'get_hot_user_progress' ); //