<?php 

//GROUPS


function getHotUserGroupsById( ) {
	$nonce = $_GET['nonce'];
	if (empty($_GET) || !$nonce = 'oqS5ouTP0mA.Zot8w9uEULd8c5AzOe2RK8F.QB6PBPVi50pjh0' ) {
		wp_send_json_error( array( 'status' => 'GET_USER', 'ERROR'    =>  'Nonce verkeerd' )); // sends json_encoded success=false
	} else {
		$id=  intval( sanitize_text_field($_GET['id'] ));
		$groups_user = new Groups_User( $id );
		$user_groups = $groups_user->groups;
		
		$groups = [];
		$total = count($user_groups);
		for( $i =0 ; $i< $total ; $i++ ) {
				$userGroup = $user_groups[$i];
				$option = 'group_'.$userGroup->group->group_id;
				if( get_option($option)){
					$courses= get_option($option);
				}
				$groupObj = array(
					'group' => $userGroup,
					'courses' => $courses,
					'id' => $userGroup->group->group_id 
				);
				array_push($groups , 	$groupObj);
		}
	
		wp_send_json_success(array( 
			'status' => 'USER_GROUPS_BY_ID', 
			'groups' => $groups,
			'total' => $total,
			
	));
	}
}

add_action( 'wp_ajax_getHotUserGroupsById', 'getHotUserGroupsById' );
add_action( 'wp_ajax_nopriv_getHotUserGroupsById', 'getHotUserGroupsById' ); //

function getGroupMembers(){
	//$nonce = $_GET['nonce'];
	// if (empty($_GET) || !wp_verify_nonce($nonce, 'hot-token') ) {
		// wp_send_json_error(); // sends json_encoded success=false
	// } else {
		
		$group_id = $_GET['id'];
		$group = new Groups_Group( $group_id );
		$users = $group->users;
	
	
		wp_send_json_success( array(
			'status' => 'GET_GROUPMEMBERS',
			'groups' => $users ));
	
};
add_action( 'wp_ajax_getGroupMembers', 'getGroupMembers' );
add_action( 'wp_ajax_nopriv_getGroupMembers', 'getGroupMembers' ); 

function getHotGroups(){
	$nonce = $_GET['nonce'];
	if (empty($_GET) || !$nonce = 'oqS5ouTP0mA.Zot8w9uEULd8c5AzOe2RK8F.QB6PBPVi50pjh0' ) {
		wp_send_json_error( array(
			'status' => $nonce
		)); // sends json_encoded success=false
	} else {
		$groups = Groups_Group::get_groups();
		$list = getAllHOTGroups( $groups );
		if( $groups ){
			wp_send_json_success( array(
				'status' => 'GET_GROUPS',
				'groups' => $list
			 ));
		} else {
			wp_send_json_success( array(
				'status' => 'GET_GROUPS',
				'groups' => $list
			 ));
		}
	
	}
}
add_action( 'wp_ajax_getHotGroups', 'getHotGroups' );
add_action( 'wp_ajax_nopriv_getHotGroups', 'getHotGroups' ); //

function getSubscriptionByGroup( $ID , $courses ){ 
	$args = array(
		'meta_query' => array(
				array(
						'key' => 'cursist',
						'value' => $ID,
						'compare' => '=',
				)
		)
 );
  return $query = new WP_Query($args);
}

function getAllHOTGroups( $groups) {
	$list=[];
		foreach( $groups as $group) {
			$group_id = $group->group_id;
			$courses='';
			if( get_option('group_'.$group_id)){
				$courses= get_option('group_'.$group_id);
			}
			$g = new Groups_Group( $group_id );
			$users = $g->users;
			$grouped["id"] = $group_id;
			$grouped["group"] = $group;
			$grouped["courses"] = $courses;
			$userList=[];
			foreach ($users as $user => $userobj) {
				$roles = $userobj->roles;
				if($roles[0] !== "administrator" || $roles[0] !== 'instelling' ){
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
				 if($subscriptions->have_posts()) :
					$posts = $subscriptions->posts;
					$posts = array_map(
						function( $post ) {
							return array( 
								'id' => $post->ID,
								'subscription' => $post->post_title,
							);
						},
						$posts
					);
				 endif;
					$userData = [
						'id' => $userobj->ID,
						'user'=> $userobj,
						'first_name'=> $usermeta->first_name,
						'last_name'=> $usermeta->last_name,
						'user_registered'=> $usermeta->user_registered,
						'roles' =>  $roles[0],
						'subscriptions' => $posts
					];
					array_push( $userList , $userData );
				}
			}
			$grouped["users"] = $userList;
			array_push($list ,$grouped);
		}
		return $list;	
}

function addNewGroup(){
	$nonce = $_GET['nonce'];
	if (empty($_GET) || !$nonce = 'oqS5ouTP0mA.Zot8w9uEULd8c5AzOe2RK8F.QB6PBPVi50pjh0' ) {
		wp_send_json_error(); // sends json_encoded success=false
	} else {
		$name = sanitize_text_field($_POST['groupname']);
		$description = sanitize_text_field($_POST['description']);
		$creator_id = intval( $_POST['userid']);
		$parent_id = null;
		$courses = $_POST['courses'];
		$datetime    = date( 'Y-m-d H:i:s', time() );
		$user = get_userdata( $creator_id );
		if( $user ){
			$input = compact( "name" , "creator_id", "datetime", "parent_id", "description"  );
			$groupID = Groups_Group::create( $input );
			if( $groupID){
				$option = 'group_'.$groupID;
				$update = update_option($option , $courses);
				if( $update){
					wp_send_json_success( array(  'status' => 'GROUP_ADDED', 'groupID' =>  $groupID	));
				}
			} else {
				wp_send_json_error( array( 'status' => 'GROUP_ADDED', 'ERROR' => 'Groep is niet aangemaakt, deze bestaat al' ));
			}
		
		} else {
			wp_send_json_error( array( 'status' => 'GROUP_ADDED', 'ERROR'    =>  'Niet toegestaan voor deze gebruiker' ));
		}
	}
}

add_action( 'wp_ajax_addNewGroup', 'addNewGroup' );
add_action( 'wp_ajax_nopriv_addNewGroup', 'addNewGroup' ); //

function deleteGroupByID(){
	$nonce = $_POST['nonce'];
	if (empty($_POST) || !$nonce = 'oqS5ouTP0mA.Zot8w9uEULd8c5AzOe2RK8F.QB6PBPVi50pjh0' ) {
		wp_send_json_error( array( 'status' => 'GROUP_DELETED', 'ERROR'    =>  'NOnce verkeerd' )); // sends json_encoded success=false
	} else {
		$id = intval( sanitize_text_field( $_POST['id'] ) );
		if( get_option('group_'.$id)){
			findMembersAndDeleteSubscriptions( $id );
		} else {
			$group = Groups_Group::delete( $id );
			if( $group ){
				wp_send_json_success( array('status' => 'GROUP_DELETED', 'group' =>  $group	));
			} else {
				wp_send_json_error( array( 'status' => 'GROUP_DELETED', 'ERROR'    =>  'Groep is niet verwijderd' ));
			}
		}
	}

}
add_action( 'wp_ajax_deleteGroupByID', 'deleteGroupByID' );
add_action( 'wp_ajax_nopriv_deleteGroupByID', 'deleteGroupByID' ); //

function 	findMembersAndDeleteSubscriptions( $groupID ){

}
function addHotCoursesToGroup(){
	$nonce = $_POST['nonce'];
	if (empty($_POST) || !$nonce = 'oqS5ouTP0mA.Zot8w9uEULd8c5AzOe2RK8F.QB6PBPVi50pjh0' ) {
		wp_send_json_error( array( 'status' => 'ADD_COURSES_TO_GROUP', 'ERROR'    =>  'Nonce verkeerd: '.$_POST['courses'])); // sends json_encoded success=false
	} else {
		$groupID = intval($_POST['groupid']);
		$courses = $_POST['courses'];
		$option = 'group_'.$groupID;
		$update = update_option($option , $courses);
		if( $update){
			wp_send_json_success( array('status' => 'ADD_COURSES_TO_GROUP', 'added' =>  true	));
		} else {
			wp_send_json_error( array( 'status' => 'ADD_COURSES_TO_GROUP', 'ERROR'    =>  'Cursus kon niet worden toegevoegd' ));
		}
	}
}

add_action( 'wp_ajax_addHotCoursesToGroup', 'addHotCoursesToGroup' );
add_action( 'wp_ajax_nopriv_addHotCoursesToGroup', 'addHotCoursesToGroup' ); //


function addUserToGroup(){
	$nonce = $_POST['nonce'];
	if (empty($_POST) || !$nonce = 'oqS5ouTP0mA.Zot8w9uEULd8c5AzOe2RK8F.QB6PBPVi50pjh0' ) {
		wp_send_json_error( array("ERROR STATUS" => "Nonce rejected")); // sends json_encoded success=false
	} else {
	
		$uID = intval( sanitize_text_field( $_REQUEST['userid'] ) );
		$gID = intval( sanitize_text_field( $_REQUEST['groupid'] ) ) ;
		$addedToGroup = Groups_User_Group::create( array( 'user_id' => $uID, 'group_id' => $gID ) );
		$groups = Groups_Group::get_groups();
		$list = getAllHOTGroups( $groups);
	
		wp_send_json_success( array(
			'status' => 'USER_ADDED_TO_GROUP',
			'groups'    =>  $list,
		));

	}
}
add_action( 'wp_ajax_addUserToGroup', 'addUserToGroup' );
add_action( 'wp_ajax_nopriv_addUserToGroup', 'addUserToGroup' ); //


function deleteUserFromGroup(){
	$nonce = $_POST['nonce'];
	if (empty($_POST) || !$nonce = 'oqS5ouTP0mA.Zot8w9uEULd8c5AzOe2RK8F.QB6PBPVi50pjh0' ) {
		wp_send_json_error( array("ERROR STATUS" => "Nonce rejected")); // sends json_encoded success=false
	} else {
		$user_id = intval( sanitize_text_field( $_REQUEST['userid'] ) );
		$group_id = intval( sanitize_text_field( $_REQUEST['groupid'] ) );
		$is_deleted = Groups_User_Group::delete($user_id, $group_id);
	 if( $is_deleted ){
		$groups = Groups_Group::get_groups();
		$list = getAllHOTGroups( $groups);
	
		wp_send_json_success( array(
				'status' => 'USER_DELETED_FROM_GROUP'	,
				'groups' => $list	
			));
		} else {
			wp_send_json_error(array('status' => 'Gebruiker kan niet worden verwijderd uit de groep' ));
		}
	}
}
add_action( 'wp_ajax_deleteUserFromGroup', 'deleteUserFromGroup' );
add_action( 'wp_ajax_nopriv_deleteUserFromGroup', 'deleteUserFromGroup' ); //

