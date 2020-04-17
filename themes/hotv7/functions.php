<?php
/**
 * syntrawest functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage syntra-west
 * @since 1.0.0
 */


include_once( dirname( __FILE__ ) . '/incl/groups.php' );
include_once( dirname( __FILE__ ) . '/incl/progress.php' );
include_once( dirname( __FILE__ ) . '/incl/subscriptions.php' );
include_once( dirname( __FILE__ ) . '/incl/users.php' );
include_once( dirname( __FILE__ ) . '/incl/subscriptions-controller.php' );
include_once( dirname( __FILE__ ) . '/incl/progress-controller.php' );
include_once( dirname( __FILE__ ) . '/incl/groups-controller.php' );
include_once( dirname( __FILE__ ) . '/incl/students-controller.php' );
include_once( dirname( __FILE__ ) . '/incl/messages-controller.php' );
include_once( dirname( __FILE__ ) . '/incl/webinars-controller.php' );


$SUBSCRIPTIONTIME = date('Y-m-d',strtotime(date("Y-m-d", time()) . " + 365 day"));


if ( ! function_exists( 'syntrawest_setup' ) ) :

    function syntrawest_setup() {
			add_theme_support( 'title-tag' );
			if( get_role('student') ){
					remove_role( 'student' );
			}
				if( get_role('instelling') ){
					remove_role( 'instelling' );
			}
				if( get_role('docent') ){
					remove_role( 'docent' );
			}
			add_role( 'student', 		'Student', 		array( 'read' => true, 'level_0' => true ) );
			add_role( 'instelling', 'Instelling', array( 'read' => true, 'edit_posts'   => true, 'delete_users'   => true, 'level_1' => true ) );
			add_role( 'docent', 		'Docent', 		array( 'read' => true, 'edit_posts'   => true, 'delete_users'   => true, 'level_1' => true ) );
    
      
    };
	
endif;
add_action( 'after_setup_theme', 'syntrawest_setup' );


function syntrawest_styles() {
  
	// wp_enqueue_style( 'syntrawest-style',  get_template_directory_uri().'/css/2.1f156bdb.chunk.css');
	// wp_enqueue_style( 'syntrawest',  get_template_directory_uri().'/css/main.f09eac9b.chunk.css');
	// wp_enqueue_style( 'override',  get_template_directory_uri().'/css/override.css');
}


add_action( 'wp_enqueue_scripts', 'syntrawest_styles' );
if( function_exists('acf_add_options_page') ) {
	
	acf_add_options_page();
	
}

add_role( 'student', 		'Student', 		array( 'read' => true, 'level_0' => true ) );
add_role( 'instelling', 'Instelling', array( 'read' => true, 'edit_posts'   => true, 'delete_users'   => true, 'level_1' => true ) );
add_role( 'docent', 		'Docent', 		array( 'read' => true, 'edit_posts'   => true, 'delete_users'   => true, 'level_1' => true ) );


  
add_action('rest_api_init', function() {
	register_rest_field('user', 'roles', array(
	  'get_callback' => 'get_user_roles',
	  'update_callback' => null,
	  'schema' => array(
		'type' => 'array'
	  )
	));
});


function registerByCode(){
	$nonce = $_POST['nonce'];
	// if (empty($_POST) || !wp_verify_nonce($nonce, 'syntra_rtk3ts7c' ) ) {
	// 	wp_send_json_error(array( 'status' => 'Nonce not valid' )); // sends json_encoded success=false
	// } else {
		//createUser( $_POST['code'])
		$username = generateRandomString();
		$user_id = username_exists( $username );
		$user_email =  $username.'@house-of-training.nl';
		if ( !$user_id and email_exists($user_email) == false ) {
			$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
			$role = sanitize_text_field( $_POST['role'] );
			$meta_value = sanitize_text_field($_POST['code']);
			$first_name = sanitize_text_field($_POST['first_name']);
			$last_name = sanitize_text_field($_POST['last_name']);
			$userRole = 'student';
			if($role == 'on') {
				$userRole = 'docent';
			}
			$userData = array(
				'user_login'  	=>  $username,
    		'user_email'    =>  $user_email,
				'user_pass'  		=>  $random_password,
				'role'					=>  $userRole,
				'display_name' 	=> 	$meta_value,
				'first_name'		=> 	$first_name,
				'last_name'			=> 	$last_name,
			);
			$user_id = wp_insert_user( $userData );
			$groupID = intval($_POST['group']);
		
			Groups_User_Group::create( array( 'user_id' => 	$user_id, 'group_id' => $groupID ) );
			$option = 'group_'.$groupID;
			$courses = get_option($option);
			$subscriptions=[];
			if($courses){
				$subscriptions = createSubscription( $user_id, $courses);
			}

		// }

		add_user_meta( $user_id, "token", $meta_value ); 
		$user = get_user_by( 'ID', $user_id );
		$session = generate_HOT_token( $user);
		wp_send_json_success(array(
			'status' => 'USER_CREATED',
			'user' => $user,
			'subscriptions' => $subscriptions,
			'session' => $session
	   ));
	}
}
add_action( 'wp_ajax_registerByCode', 'registerByCode' );
add_action( 'wp_ajax_nopriv_registerByCode', 'registerByCode' ); //

function cleanHotData(&$str)  {
	$str = preg_replace("/\t/", "\\t", $str);
	$str = preg_replace("/\r?\n/", "\\n", $str);
	if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
}



// Returns a Subscriptions CSV on Request via Ajax Call
function progress_csv_pull() {

	// $nonce = $_GET['nonce'];
	// if ( !$nonce || !wp_verify_nonce($nonce, 'syntra_rtk3ts7c' ) ) {
	// 	wp_send_json_error(array( 'status' => $nonce )); // sends json_encoded success=false
	// } else {
		global $wpdb;
		$userID = intval( $_GET['id']);
		if( $userID !== 0){
			$hot_generate_value_arr = array();
			$base =  $wpdb->base_prefix ;
			$table_name =  $base. 'user_progress';
			$orderbycol = 'last_viewed';
			$order = 'DESC';
			$results = $wpdb->get_results( 'SELECT * FROM '.$table_name.' WHERE user_id = '. $userID.' order by '.$orderbycol.' '.$order );
			$hot_count_posts = count( $results );
			if( $hot_count_posts < 1){
				wp_send_json_error(array( 'status' => 'geen data gevonden onder gebruikersID '.$userID )); // sends json_encoded success=false
			}

			$i=0;
			foreach( $results as $result ):

				$hot_generate_value_arr['Video ID'][$i] =  $result->video_id;
				$hot_generate_value_arr['Titel'][$i] =  $result->videoTitle;
				$hot_generate_value_arr['Cursus ID'][$i] =  $result->course_id;
				$hot_generate_value_arr['Perc bekeken'][$i] =  $result->perc_viewed;
				$hot_generate_value_arr['Video gestart op'][$i] =  $result->startdate;
				$hot_generate_value_arr['Laatst bekeken'][$i] =  $result->last_viewed;
				$i++;
			endforeach;
			$hot_generate_value_arr_new = array();
			foreach($hot_generate_value_arr as $value) {
				$i = 0;
				while ($i <= ($hot_count_posts-1)) {
						$hot_generate_value_arr_new[$i][] = $value[$i];
						$i++;
				}
			}
			$filename = get_bloginfo('name').'-voortgang-'.$userID.'-'.date('d_m_Y_Hi').'.xls';
		//output the headers for the XLS file
			header('Content-Encoding: UTF-8');
			header("Content-Type: Application/vnd.ms-excel; charset=utf-8");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header('Content-Description: File Transfer');
			header("Content-Disposition: Attachment; Filename=\"$filename\"");
			header("Expires: 0");
			header("Pragma: public");

			$flag = false; // Remove field names from the top?
			foreach ( $hot_generate_value_arr_new as $data ) {
				if(!$flag) {
					echo implode("\t", array_keys($hot_generate_value_arr)) . "\r\n";
					$flag = true;
				}
				array_walk($data, 'cleanHotData');
				$data_string = implode("\t", array_values($data));
				// Final output
				echo $data_string . "\r\n";
			}
			exit; 
			
		}


	// }
}

// add the action:

add_action('wp_ajax_progress_csv_pull','progress_csv_pull');
add_action('wp_ajax_nopriv_progress_csv_pull','progress_csv_pull');


// Returns a Subscriptions CSV on Request via Ajax Call
function hot_groups_csv_pull() {
	// $nonce = $_GET['nonce'];
	// if ( !$nonce || !wp_verify_nonce($nonce, 'syntra_rtk3ts7c' ) ) {
	// 	wp_send_json_error(array( 'status' => $nonce )); // sends json_encoded success=false
	// } else {
		$args = array(
			'posts_per_page'   => 50,
			'post_type'        => 'inschrijving',
		);
		$getPosts = get_posts( $args );
		$hot_count_posts = count($getPosts);
		$hot_generate_value_arr = array();
		$i = 0;
		foreach ($getPosts as $post): setup_postdata($post);
			$user = get_field('cursist', $post->ID);
			$actief = get_field('actief', $post->ID);
			$userObj = get_user_by('ID' ,  $user["ID"]);
			$groups_user = new Groups_User($user["ID"] );
			$user_groups = $groups_user->groups;
			$groups= array();
			$count = 0;
			foreach( $user_groups as $user_group){
				if( $count != 0 ){
					array_push( $groups , $user_group->name);
				}
				$count++;
			}
			// $post_data['groups'] = $user_groups;
			foreach($post as $key => $value) {
				$hot_generate_value_arr['RegistratieCode'][$i] =  $post->post_title;
				$hot_generate_value_arr['Voornaam'][$i] =  $user['user_firstname'];
				$hot_generate_value_arr['Achternaam'][$i] =  $user['user_lastname'];
				$hot_generate_value_arr['Interne Code'][$i] =  $user['display_name'];
				$hot_generate_value_arr['Actief'][$i] =  $actief;
				$hot_generate_value_arr['Rol'][$i] =  $userObj->roles[0];
				$hot_generate_value_arr['Groep'][$i] =  implode(" ",$groups);
				$hot_generate_value_arr['Geregistreerd'][$i] = $post->post_date;
				$hot_generate_value_arr['Laatst bewerkt'][$i] = $post->post_modified;
			}
			$i++;
		endforeach;
		//Exit
		$hot_generate_value_arr_new = array();
		foreach($hot_generate_value_arr as $value) {
			$i = 0;
			while ($i <= ($hot_count_posts-1)) {
					$hot_generate_value_arr_new[$i][] = $value[$i];
					$i++;
			}
		}
		
		$filename = get_bloginfo('name').'-'.date('d_m_Y_Hi').'.xls';
		//output the headers for the XLS file
		header('Content-Encoding: UTF-8');
		header("Content-Type: Application/vnd.ms-excel; charset=utf-8");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header('Content-Description: File Transfer');
		header("Content-Disposition: Attachment; Filename=\"$filename\"");
		header("Expires: 0");
		header("Pragma: public");

		$flag = false; // Remove field names from the top?
		foreach ( $hot_generate_value_arr_new as $data ) {
			if(!$flag) {
				echo implode("\t", array_keys($hot_generate_value_arr)) . "\r\n";
				$flag = true;
			}
			array_walk($data, 'cleanHotData');
			$data_string = implode("\t", array_values($data));
			// Final output
			echo $data_string . "\r\n";
		}
		exit; 
	// }
}
add_action('wp_ajax_csv_pull','hot_groups_csv_pull');
add_action('wp_ajax_nopriv_csv_pull','hot_groups_csv_pull');


/**
 * IN BETA !!
 */
function hot_group_csv_pull() {
  if ( !isset($_GET['nonce']) && !isset( $_GET['id']) ) {
    wp_send_json_error(array( 'status' => "Incorrect parameters" )); // sends json_encoded success=false
  } else {
    $nonce = $_GET['nonce'];
    $groupid = intval( $_GET['id'] );
    $currentGroup = new Groups_group( $groupid );
    $users = $currentGroup->users;
    $hot_count_users = count( $users );
    $userList=[];
    $i = 0;
    $hot_generate_value_arr = array();
    foreach( $users as $userobj ){
      $roles = $userobj->roles;
      if($roles[0] !== "docent"  ){
        $usermeta = get_userdata( $userobj->ID );
        $args = array(
          'post_type' => 'inschrijving',
          'meta_query' => array(
              array(
                  'key' => 'cursist',
                  'value' => $userobj->ID,
                  'compare' => '='
              )
          )
        );
        $subscriptions= new WP_Query($args);
        if($subscriptions->have_posts()) {
          $posts = $subscriptions->posts;
          $posts = array_map(
            function( $post ) {
              $active = get_field('actief' , $post->ID );
              return array( 
                'id' => $post->ID,
                'subscription' => $post->post_title,
                'active' => $active,
                'modified' => $post->post_modified,
                'registered' => $post->post_date
              );
            },
            $posts
          );
        }
        	$hot_generate_value_arr['RegistratieCode'][$i] = $posts[0]['subscription'];
          $hot_generate_value_arr['Voornaam'][$i] =  $usermeta->first_name;
          $hot_generate_value_arr['Achternaam'][$i] = $usermeta->last_name;
          $hot_generate_value_arr['Interne Code'][$i] =  $userobj->display_name;
          $hot_generate_value_arr['Actief'][$i] =  $posts[0]['active'];
          $hot_generate_value_arr['Rol'][$i] =  $roles[0];
          $hot_generate_value_arr['Groep'][$i] =  $currentGroup->group->name;
          $hot_generate_value_arr['Geregistreerd'][$i] = $posts[0]['registered'];
          $hot_generate_value_arr['Laatst bewerkt'][$i] = $posts[0]['modified'];
        
        
        $userData = [
          'id' => $userobj->ID,
          // 'user'=> $userobj,
          'first_name'=> $usermeta->first_name,
          'last_name'=> $usermeta->last_name,
          'user_registered'=> $usermeta->user_registered,
          'roles' =>  $roles[0],
          'subscriptions' => $posts[0]['subscription']
        ];


        array_push( $userList , $userData );
       $i++;
      }
    }
    $hot_generate_value_arr_new = array();
		foreach($hot_generate_value_arr as $value) {
			$i = 0;
			while ($i <= ($hot_count_users-1)) {
					$hot_generate_value_arr_new[$i][] = $value[$i];
					$i++;
			}
		}

    $filename = get_bloginfo('name').'-'. $currentGroup->group->name.'-'.date('d_m_Y_Hi').'.xls';
		//output the headers for the XLS file
		header('Content-Encoding: UTF-8');
		header("Content-Type: Application/vnd.ms-excel; charset=utf-8");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header('Content-Description: File Transfer');
		header("Content-Disposition: Attachment; Filename=\"$filename\"");
		header("Expires: 0");
		header("Pragma: public");

		$flag = false; // Remove field names from the top?
		foreach ( $hot_generate_value_arr_new as $data ) {
			if(!$flag) {
				echo implode("\t", array_keys($hot_generate_value_arr)) . "\r\n";
				$flag = true;
			}
			array_walk($data, 'cleanHotData');
			$data_string = implode("\t", array_values($data));
			// Final output
			echo $data_string . "\r\n";
		}
		exit; 
    //  $response = array( 'data' => $hot_generate_value_arr_new );
    // wp_send_json_success( $response , 200 );
  };
}


add_action('wp_ajax_group_csv_pull','hot_group_csv_pull');
add_action('wp_ajax_nopriv_group_csv_pull','hot_group_csv_pull');

/**
 * END OF IN BETA
 */

// FUNCTIONS



function generateRandomString($length = 10) {
	$characters = 'abcdefghijklmnopqrstuvwxyz1234567890';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}
function generateSubscriptionToken($length = 10) {
	$characters = 'ABCDEFGHIJKLMNOPQRSTUVWYXZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

// Generate Token

/**
	 * Get the user and password in the request body and generate a JWT
	 *
	 * @param object $request a WP REST request object
	 * @since 1.0
	 * @return mixed Either a WP_Error or current user data.
	 */
	function generate_HOT_token( $user ) {
		$secret_key = Simple_Jwt_Authentication_Api::get_key();
		$username   = $user->username;
		// $password   = $request->ge t_param( 'password' );

		/** First thing, check the secret key if not exist return a error*/
		if ( ! $secret_key ) {
			return new WP_Error(
				'jwt_auth_bad_config',
				__( 'JWT is not configurated properly, please contact the admin. The key is missing.', 'simple-jwt-authentication' ),
				array(
					'status' => 403,
				)
			);
		}
		/** Try to authenticate the user with the passed credentials*/
		// $user = wp_authenticate( $username, $password );
		$user = get_user_by('login', $username);

		/** If the authentication fails return a error*/
		if ( is_wp_error( $user ) ) {
			$error_code = $user->get_error_code();
			return new WP_Error(
				'[jwt_auth] ' . $error_code,
				$user->get_error_message( $error_code ),
				array(
					'status' => 403,
				)
			);
		}

		// Valid credentials, the user exists create the according Token.
		$issued_at  = time();
		$not_before = apply_filters( 'jwt_auth_not_before', $issued_at );
		$expire     = apply_filters( 'jwt_auth_expire', $issued_at + ( DAY_IN_SECONDS * 7 ), $issued_at );
		$uuid       = wp_generate_uuid4();

		$token = array(
			'uuid' => $uuid,
			'iss'  => get_bloginfo( 'url' ),
			'iat'  => $issued_at,
			'nbf'  => $not_before,
			'exp'  => $expire,
			'data' => array(
				'user' => array(
					'id' => $user->data->ID,
				),
			),
		);

		// Let the user modify the token data before the sign.
		$token = JWT::encode( apply_filters( 'jwt_auth_token_before_sign', $token, $user ), $secret_key );

		// Setup some user meta data we can use for our UI.
		$jwt_data   = get_user_meta( $user->data->ID, 'jwt_data', true ) ?: array();
		$user_ip    = Simple_Jwt_Authentication_Api::get_ip();
		$jwt_data[] = array(
			'uuid'      => $uuid,
			'issued_at' => $issued_at,
			'expires'   => $expire,
			'ip'        => $user_ip,
			'ua'        => $_SERVER['HTTP_USER_AGENT'],
			'last_used' => time(),
		);
		update_user_meta( $user->data->ID, 'jwt_data', apply_filters( 'simple_jwt_auth_save_user_data', $jwt_data ) );

		// The token is signed, now create the object with no sensible user data to the client.
		$data = array(
			'token'             => $token,
			'user_id'           => $user->data->ID,
			'user_email'        => $user->data->user_email,
			'user_nicename'     => $user->data->user_nicename,
			'user_display_name' => $user->data->display_name,
			'token_expires'     => $expire,
		);

		// Let the user modify the data before send it back.
		return apply_filters( 'jwt_auth_token_before_dispatch', $data, $user );
	}

/**
 * Disable the emoji's
 */
function disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' ); 
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' ); 
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'tiny_mce_plugins', 'disable_emojis_tinymce' );
	add_filter( 'wp_resource_hints', 'disable_emojis_remove_dns_prefetch', 10, 2 );
 }
 add_action( 'init', 'disable_emojis' );
 
 /**
	* Filter function used to remove the tinymce emoji plugin.
	* 
	* @param array $plugins 
	* @return array Difference betwen the two arrays
	*/
 function disable_emojis_tinymce( $plugins ) {
	if ( is_array( $plugins ) ) {
	return array_diff( $plugins, array( 'wpemoji' ) );
	} else {
	return array();
	}
 }
 
 /**
	* Remove emoji CDN hostname from DNS prefetching hints.
	*
	* @param array $urls URLs to print for resource hints.
	* @param string $relation_type The relation type the URLs are printed for.
	* @return array Difference betwen the two arrays.
	*/
 function disable_emojis_remove_dns_prefetch( $urls, $relation_type ) {
	if ( 'dns-prefetch' == $relation_type ) {
	/** This filter is documented in wp-includes/formatting.php */
	$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );
 
 $urls = array_diff( $urls, array( $emoji_svg_url ) );
	}
 
 return $urls;
 }


 remove_action ('wp_head', 'rsd_link');

 function crunchify_remove_version() {
	return '';
}
add_filter('the_generator', 'crunchify_remove_version');


remove_action( 'wp_head', 'wlwmanifest_link');
remove_action( 'wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'rest_output_link_wp_head', 10);
remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
remove_action('template_redirect', 'rest_output_link_header', 11, 0);
function my_deregister_scripts(){
  wp_deregister_script( 'wp-embed' );
}
add_action( 'wp_footer', 'my_deregister_scripts' );
function remove_block_css(){
	wp_dequeue_style( 'wp-block-library' );
	}
	add_action( 'wp_enqueue_scripts', 'remove_block_css', 100 );

  