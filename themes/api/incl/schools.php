<?php

class HOT_SCHOOLS_CONTROLLER extends WP_REST_Controller {
 
 // Here initialize our namespace and resource name.
 public function __construct() {
     $this->namespace     = 'hot/v2';
     $this->resource_name = 'schools';
     $this->rest_base = 'schools';
 }

    /**
     * Registers the routes for the objects of the controller.
    *
    * @since 4.7.0
    *
    * @see register_rest_route()
    */
 public function register_routes() {
     // get all courses
    register_rest_route( $this->namespace, '/' . $this->resource_name , array(
         array(
            'methods'             => WP_REST_Server::READABLE,
             'callback'  => array( $this, 'getSchools' ),
             'permission_callback' => array( $this, 'get_items_permissions_check' ),
         ),
        
     ) );
     // Groups from school
      register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<id>[\d]+)', array(
        'args'   => array(
            'id' => array(
                'description' => __( 'Unique identifier for the object.' ),
                'type'        => 'integer',
            ),
        ),
         array(
            'methods'             => WP_REST_Server::READABLE,
             'callback'  => array( $this, 'getSchool' ),
             'permission_callback' => array( $this, 'get_items_permissions_check' ),
         ),
        
     ) );
     // Groups from school
      register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<id>[\d]+)/(?P<gid>[\d]+)', array(
        'args'   => array(
            'id' => array(
                'description' => __( 'Unique identifier for the object.' ),
                'type'        => 'integer',
            ),
            'gid' => array(
                'description' => __( 'Unique identifier for the object.' ),
                'type'        => 'integer',
            ),
        ),
         array(
            'methods'             => WP_REST_Server::READABLE,
             'callback'  => array( $this, 'getGroup' ),
             'permission_callback' => array( $this, 'get_items_permissions_check' ),
         ),
        
     ) );
     
 }

    /**
    * Check permissions for the posts.
    *
    * @since 1.0.0
    *
    * @param WP_REST_Request $request Current request.
    * @return BOOL True when user is permitted.
    */
 public function get_items_permissions_check( $request ) {
     if ( ! current_user_can( 'read' ) ) {
         return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view the post resource.' ), array( 'status' => $this->authorization_status_code() ) );
     }
     return true;
 }

/**
  * Get the user progress.
  *
  * @since 1.0.0
  *
  * @param WP_REST_Request $request Full details about the request.
  * @return Obj Response object on success, or WP_Error object on failure.
  */
  function getSchools( ){
     global $wpdb;
  $table_one = $wpdb->base_prefix  . 'user_progress';
  
  $viewed = $wpdb->get_var("SELECT COUNT(*) FROM $table_one");
    $subsites = get_sites();
    $list= [];
    foreach( $subsites as $subsite ) {
      $subsite_id = get_object_vars($subsite)["blog_id"];
      array_push( $list ,  get_blog_details($subsite_id) );
      // $subsite_name = get_blog_details($subsite_id)->blogname;
      // echo 'Site ID/Name: ' . $subsite_id . ' / ' . $subsite_name . '\n';
    }
    $obj =[
        'data' => $list,
        'viewed' => $viewed
    ];
    return $obj;
  }

function getSchool( $request) {
  global $wpdb;
  $table_one = $wpdb->base_prefix  . 'user_progress';
    $id = intval($request['id']);
    switch_to_blog( $id );
   // $users = get_users();
    $total = 0;
    // foreach ( $users as $user ) {
    //   $query = "SELECT `course_id` FROM `".$table_one."` WHERE `user_id` IN ( $user->ID ) ";
    //     $viewed = $wpdb->get_results($query);
    //     $total += count($viewed);
    // }
    $list = [];
    $groups = Groups_Group::get_groups();
    foreach( $groups as $group) {
      if( $group->group_id !== '1' ){
        $group_id = $group->group_id;
        $courses='';
        if( get_option('group_'.$group_id)){
          $courses= get_option('group_'.$group_id);
        }
        $t = (int)$group_id;
        $g = new Groups_Group($t);
        $users = $g->users;
        $uids = [];
        foreach ( $users as $user ) {
          array_push( $uids , $user->ID);
        }
        $count = count($users);     
        $grouped["id"] = $group_id;
        $grouped["group"] = $group;
        $grouped["courses"] = $courses;
        $grouped["total_users"] = $count;
        $grouped["users"] = implode( ',' ,$uids );
        array_push($list ,$grouped);
      }   
    }
    $obj =[
      'success' => true,
      'data' => $list,
      'total' => $total
    ]; 
    return $obj;

    restore_current_blog();
}

function getGroup( $request) {
  global $wpdb;
  $table_one = $wpdb->base_prefix  . 'user_progress';
    $id = intval($request['id']);
    $group_id = intval($request['gid']);
    switch_to_blog( $id );
    // $list = [];
    $group = new Groups_Group( $group_id );
    $users = $group->users;
    $usersList= [];
    $courses = "";
    if( get_option('group_'.$group_id)){
      $courses = get_option('group_'.$group_id);
      foreach ( $users as $user ) {
        # code...
        $query = "SELECT `id`,`course_id`,`video_id`, `last_viewed` FROM `".$table_one."` WHERE `user_id` LIKE $user->ID AND `course_id` IN ( $courses ) ORDER BY `course_id` DESC";
        $viewed = $wpdb->get_results($query);
        $total = count($viewed);
        $userSingle['ID'] = $user->ID;
        $userSingle['roles'] = $user->roles[0];
        $userSingle['viewed'] = $viewed;
        $userSingle['total'] = $total;
        array_push( $usersList , $userSingle);
      }
    }
    
    $obj =[
      'success' => true,
      'data' => $usersList,
      'courses' => $courses
    ]; 
    return $obj;

    restore_current_blog();
}
// const data = [
//   0:{
//     user : {
//       ID : 334,
//       roles : 'student',
//     },
//     viewed : [
//       // index : [ id , course_id , video_id ]
//       0 : [ 12526 , 1805 , 8116 ],
//       1 : [ 12527 , 1805 , 8117 ],
//     ]
//   },
//   1:{}
// ]


/**
* Get the user progress.
*
* @since 1.0.0
*
* @param WP_REST_Request $request Full details about the request.
* @return Obj Response object on success, or WP_Error object on failure.
*/



 

 /**
  * Check permissions for the posts.
  *
  * @param WP_REST_Request $request Current request.
  */
 public function get_item_permissions_check( $request ) {
     if ( ! current_user_can( 'read' ) ) {
         return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view the post resource.' ), array( 'status' => $this->authorization_status_code() ) );
     }
     return true;
 }
 

 // Sets up the proper HTTP status code for authorization.
 public function authorization_status_code() {

     $status = 401;

     if ( is_user_logged_in() ) {
         $status = 403;
     }

     return $status;
 }
}

// Function to register our new routes from the controller.
function HOT_register_SCHOOLS_controller() {
 $controller = new HOT_SCHOOLS_CONTROLLER();
 $controller->register_routes();
}

add_action( 'rest_api_init', 'HOT_register_SCHOOLS_controller' );