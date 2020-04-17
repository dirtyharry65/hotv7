<?php 
// SUBSCRIPTIONS

use  \Firebase\JWT\JWT ;

function subscribeHotUser()
{
	$nonce = $_POST['nonce'];
	if (empty($_POST) || !$nonce == 'oqS5ouTP0mA.Zot8w9uEULd8c5AzOe2RK8F.QB6PBPVi50pjh0' ) {
		wp_send_json_error(array( 'status' => 'SUBSCRIBE_STUDENT', 'ERROR'    =>  $nonce )); // sends json_encoded success=false
	} else {
		$user_id =  sanitize_text_field( $_POST['userID'] );
		$course_id =  sanitize_text_field($_POST['courseID']) ;
		// $courses = explode( ',' , $course_id );
	 	// $new_post = array(
		// 	'post_title'    =>	$token,
		// 	'post_status'   =>	'publish',
		// 	'post_type'		=>	'inschrijving',
		// 	'post_author'	=>	$user_id
		// );
	
		// $post_id = wp_insert_post( $new_post );
		// update_field( 'cursist', $user_id , $post_id); // vul de curssit veld in
		// add_post_meta( $post_id, 'factuurnummer', $token, true );
		// add_post_meta( $post_id, 'price', 100, true );
		// foreach ($courses as $course) {
		// 	$row = array(
		// 		'cursus_id' => $course,
		// 		'eind_datum'=> date('Y-m-d',strtotime(date("Y-m-d", time()) . " + 365 day"))
		// 	);
		// 	add_row('cursussen', $row , $post_id );
				
		// }
		
		// $subscription = array(
		// 	'subscription' => get_post($post_id),
		// 	'subscriptionMeta' => get_post_meta( $post_id )
		// );
		$subscription = createSubscription( $user_id , $course_id );
		if( $subscription) {
			wp_send_json_success( array('status' => 'SUBSCRIPTION_ADDED', 'subscription' =>  $subscription , 'courseID' , $course_id	)) ;
		} else {
			wp_send_json_error(array( 'status' => 'SUBSCRIPTION_ADDED_FAILED', 'ERROR'    =>  "Not subscribed" )); // 
		};
	}
}

function createSubscription( $user_id, $course_id){
	$token = generateSubscriptionToken();
		$courses = explode( ',' , $course_id );
		$new_post = array(
			'post_title'    =>	$token,
			'post_status'   =>	'publish',
			'post_type'		=>	'inschrijving',
			'post_author'	=>	$user_id
		);

		$post_id = wp_insert_post( $new_post );
		update_field( 'cursist', $user_id , $post_id); // vul de curssit veld in
		add_post_meta( $post_id, 'factuurnummer', $token, true );
		add_post_meta( $post_id, 'price', 100, true );
		foreach ($courses as $course) {
			$row = array(
				'cursus_id' => $course,
				'eind_datum'=> date('Y-m-d',strtotime(date("Y-m-d", time()) . " + 365 day"))
			);
			add_row('cursussen', $row , $post_id );
				
		}
		
		$subscription = array(
			'subscription' => get_post($post_id),
			'subscriptionMeta' => get_post_meta( $post_id )
		);
		return $subscription;
}

add_action( 'wp_ajax_subscribeHotUser', 'subscribeHotUser' );
add_action( 'wp_ajax_nopriv_subscribeHotUser', 'subscribeHotUser' ); //

function checkRegistration(){
	
	$nonce = $_POST['nonce'];
	if (empty($_POST) || !$nonce == 'oqS5ouTP0mA.Zot8w9uEULd8c5AzOe2RK8F.QB6PBPVi50pjh0' ) {
		wp_send_json_error(array( 'status' => 'CHECK_REGISTRATION_ERROR', 'ERROR'    =>  $nonce )); // sends json_encoded success=false
	} else {
		$code = sanitize_text_field( $_POST['code'] );
		
		$inschrijving = get_page_by_title( $code, OBJECT, 'inschrijving' );
		if( $inschrijving ) {
      $active = intval(get_field( 'actief', $inschrijving->ID ));
      if( $active !== 2 ){
        $meta =	get_post_meta($inschrijving->ID );
        $userid = $inschrijving->post_author;
        $user = get_user_by( "ID" , intVal($userid) );
        $groups_user = new Groups_User(  intVal($userid));
		    $user_groups = $groups_user->groups;
        $groupCourses='';
       
        if( intval($user_groups[1]->group->group_id) != 1 ){

          $option = 'group_'.$user_groups[1]->group->group_id;
           if( get_option($option)){
            $groupCourses = get_option($option);
          }
          $user_groups = $user_groups[1]->group;
        } 
        $usermeta = get_user_meta( intVal($userid) );
        $userData = get_userdata(intVal($userid));
        $session = generate_HOT2_token( $userid );
        update_field('actief' , 1 , $inschrijving->ID );
        $secret_key = Simple_Jwt_Authentication_Api::get_key();
        $inschrijvingObj = array(
          'ID' => $inschrijving->ID,
          'post_title' => $inschrijving->post_title,
          'post_author'=> $inschrijving->post_author
        );
        $uObj = array(
          'roles' => $user->roles[0],
          'id'	=> intval($userid),
          'first_name' => $userData->first_name,
          'last_name' => $userData->last_name,
          'token' => $userData->token,
          'display_name' => $userData->display_name,
          'group' => $user_groups
        );
        $subObj = array(
          'subscription' => $inschrijvingObj,
          'courses' => $groupCourses,
          'total' => intval($meta['cursussen'][0]),
          'subscriptionmeta' => $meta,
          'actief' => get_field( 'actief', $inschrijving->ID ),
          'session' => $session,
          'userobject' => $uObj
        );
			  wp_send_json_success( array( 'status' => 'USER_IS_REGISTERED', 'registration' =>  $subObj	)) ;
      } else {
			  wp_send_json_error(array( 'status' => 'Registratie code is niet meer actief', 'ERROR'    =>  $active )); // sends json_encoded 
      }
		} else {
			wp_send_json_error(array( 'status' => 'Registratie code onbekend of niet meer actief', 'ERROR'    =>  $code )); // sends json_encoded 
		}
		
	}

}
add_action( 'wp_ajax_checkRegistration', 'checkRegistration' );
add_action( 'wp_ajax_nopriv_checkRegistration', 'checkRegistration' ); //


// Generate Token

/**
	 * Get the user and password in the request body and generate a JWT
	 *
	 * @param object $request a WP REST request object
	 * @since 1.0
	 * @return mixed Either a WP_Error or current user data.
	 */
	function generate_HOT2_token( $id ) {
		$secret_key = Simple_Jwt_Authentication_Api::get_key();
		//$username   = $user->username;
		// $password   = $request->get_param( 'password' );

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
		$user = get_user_by('ID', $id);

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
					'id' => $id,
				),
			),
		);

		// Let the user modify the token data before the sign.
		//  $token = apply_filters( 'jwt_auth_token_before_sign', $token, $user );
		  $token = JWT::encode(apply_filters( 'jwt_auth_token_before_sign', $token, $user ), $secret_key );

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
			// 'user_email'        => $user->data->user_email,
			// 'user_nicename'     => $user->data->user_nicename,
			// 'user_display_name' => $user->data->display_name,
			'token_expires'     => $expire,
		);

		// Let the user modify the data before send it back.
		return $data;
	}



function addCourseToSubscription(){

	$userID =  intval(sanitize_text_field($_POST['userID'] ));
	$subscriptionID =  intval(sanitize_text_field( $_POST['subscriptionID'] ));
	$courseID =  intval(sanitize_text_field( $_POST['courseID;']));
	$subscription = get_post( $subscriptionID );
	if( $subscription ){

	} else {
		wp_send_json_error(array( 'status' => 'COURSE_ADDED_SUBSCRIPTION_FAILED', 'ERROR'    =>  "No such subscription" )); // 
	}
}


function buy_hot_course() {
	$nonce = $_POST['nonce'];
	if (empty($_POST) || !$nonce == 'oqS5ouTP0mA.Zot8w9uEULd8c5AzOe2RK8F.QB6PBPVi50pjh0' ) {
		wp_send_json_error( array( 'status' => 'BUY_COURSE', 'ERROR'    =>  'Nonce verkeerd' )); // sends json_encoded success=false
	}  else { 
			$userid = intval( sanitize_text_field( $_POST['userid'] ) );
			$courseids = intval( sanitize_term_field( $_POST['courseid']));
			$price = sanitize_text_field($_POST['price']);
			$discount_code = sanitize_text_field($_POST['discount']);
			$pointer = get_current_pointer();
			$new_post = array( 
				'post_title'    =>	generateRandomString(),
				'post_status'   =>	'publish',
				'post_type'			=>	'inschrijving',
				'post_author'		=>	$userid
			);
	
		$post_id = wp_insert_post( $new_post );
		$post_title =  $post_id."-inschrijving";
		wp_update_post( array(
			'ID'            => $post_id,
			'post_title'    => $post_title,
			'post_name'     => $post_id
			)
		);
		update_field("eind_datum", date('Y-m-d',strtotime(date("Y-m-d", mktime()) . " + 365 day")) , $post_id);	
		update_field("price", 100 , $post_id);
		update_field("factuurnummer", "fact-".$post_id , $post_id);
	}
};

add_action( 'wp_ajax_buy_hot_course', 'buy_hot_course' );
add_action( 'wp_ajax_nopriv_buy_hot_course', 'buy_hot_course' ); 
