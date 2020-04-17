<?php

// USERS


function getUserSubscriptions(){
	$nonce = $_GET['nonce'];
	if (empty($_GET) || !$nonce== 'oqS5ouTP0mA.Zot8w9uEULd8c5AzOe2RK8F.QB6PBPVi50pjh0' ) {
		wp_send_json_error( array( 'status' => 'GET_USER_SUBSCRIPTIONS', 'ERROR'    =>  'Nonce verkeerd' )); // sends json_encoded success=false
	}  else { 
	
		$user_id = intval( sanitize_text_field($_GET['id']));
		$subscriptions = getInternalSubscriptions( $user_id);
		wp_send_json_success( array(
			'status' => 'GET_USER_SUBSCRIPTIONS',
			'subscriptions' => $subscriptions ));
	}
}
function getInternalSubscriptions( $user_id) {
	$subscriptions = [];
		$inschrijvingen = get_posts( array(
			'suppress_filters' => FALSE,
			'post_type'		=>	'inschrijving',
			'numberposts' => -1,
				'meta_query'=> array(
					array(
						'key' => 'cursist',
						'compare' => 'LIKE',
						'value' =>  $user_id,
					)
				),
		));

		foreach ($inschrijvingen as $inschrijving => $value) {
			$meta =	get_post_meta($value->ID );
			$subObj = array(
				'subscription' => $value,
				'subscriptionmeta' => $meta
			);
		
			array_push( $subscriptions , $subObj );
		}		
		return $subscriptions;
}

add_action( 'wp_ajax_getUserSubscriptions', 'getUserSubscriptions' );
add_action( 'wp_ajax_nopriv_getUserSubscriptions', 'getUserSubscriptions' ); 


function deleteHotUser(){
	$nonce = $_POST['nonce'];
	if (empty($_POST) || !$nonce== 'oqS5ouTP0mA.Zot8w9uEULd8c5AzOe2RK8F.QB6PBPVi50pjh0' ) {
		wp_send_json_error(array( 'status' => $nonce )); // sends json_encoded success=false
	} else {
		wp_send_json_success(array(
			'status' => 'USER_DELETED',
			'user' => $userID
	   ));	
	}
}

add_action( 'wp_ajax_deleteHotUser', 'deleteHotUser' );
add_action( 'wp_ajax_nopriv_deleteHotUser', 'deleteHotUser' ); //

// function createUser( $userCode) {
	
// 	$user_id = username_exists( $userCode );
// 	$user_email =  $userCode.'@house-of-training.nl';
// 	if ( !$user_id and email_exists($user_email) == false ) {
// 		$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
// 		$user_id = wp_create_user( $user_name, $random_password, $user_email );
// 	} else {
// 		$random_password = __('User already exists.  Password inherited.');
// 	}
// }

function getHotStudents(){
	//$headers = checkHeaders();
	$nonce = $_GET['nonce'];
	if (empty($_GET) || !$nonce== 'oqS5ouTP0mA.Zot8w9uEULd8c5AzOe2RK8F.QB6PBPVi50pjh0' ) {
		wp_send_json_error( array("ERROR STATUS" => "Nonce rejected")); // sends json_encoded success=false
	} else {
		$args = array(
			'blog_id'      => $GLOBALS['blog_id'],
			'role_in'      => ['student','docent'],
			'orderby' 		 => 'ID',
			'order'        => 'DESC',
		
		 ); 
		$query = get_users( $args );
		$users=[];
		foreach( $query as $user ){
		 	$groups_user = new Groups_User( $user->ID );
			$user_groups = $groups_user->groups;
			$subscriptions = getInternalSubscriptions( $user->ID) ;
			$usermeta = get_userdata( $user->ID );
			$userData = [
				'user'=> $user,
				'firstname'=> $usermeta->first_name,
				'lastname'=> $usermeta->last_name,
				'groups'=> $user_groups,
				'subscriptions'=> $subscriptions
			];
			 array_push( $users ,$userData );
		}

		wp_send_json_success( array(
			'status' => 'GET_STUDENTS',
			'users' => $users,
			'headers' => $headers
	   ));
	}
}
add_action( 'wp_ajax_getHotStudents', 'getHotStudents' );
add_action( 'wp_ajax_nopriv_getHotStudents', 'getHotStudents' ); //

function get_user_roles($object, $field_name, $request) {
	return get_userdata($object['id'])->roles;
}