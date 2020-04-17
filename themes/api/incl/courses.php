<?php

class HOT_COURSES_CONTROLLER extends WP_REST_Controller {
 
 // Here initialize our namespace and resource name.
 public function __construct() {
     $this->namespace     = 'hot/v2';
     $this->resource_name = 'courses';
     $this->rest_base = 'courses';
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
    register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<id>[\d]+)', array(
        'args'   => array(
            'id' => array(
                'description' => __( 'Unique identifier for the object.' ),
                'type'        => 'integer',
            ),
        ),
         array(
            'methods'             => WP_REST_Server::READABLE,
             'callback'  => array( $this, 'getCourses' ),
            //  'permission_callback' => array( $this, 'get_items_permissions_check' ),
         ),
        
     ) );
     // posts a new or existing ProgressID
     register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<id>[\d]+)', array(
         // Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
         array(
            'methods'             => WP_REST_Server::CREATABLE,
             'callback'  => array( $this, 'create_course' ),
             'permission_callback' => array( $this, 'get_item_permissions_check' ),
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
  function getCourses( $request){
    global $wpdb;
    $id = intval($request['id']);
    $tble = $wpdb->base_prefix.'courses_cache';
    $query = "SELECT * FROM `".$tble."` WHERE `course_id` LIKE ".$id;
    $results = $wpdb->get_results($query);
    $obj =[
      'query' => $query,
        'data' => $results
    ];
    return $obj;
  }


/**
* Get the user progress.
*
* @since 1.0.0
*
* @param WP_REST_Request $request Full details about the request.
* @return Obj Response object on success, or WP_Error object on failure.
*/


/**
* Update or Create progress.
*
* @since 1.0.0
*
* @param WP_REST_Request $request Full details about the request.
* @return Obj Response object on success, or WP_Error object on failure.
*/
function create_course( $request ) {
    global $wpdb;
    $table_up = $wpdb->base_prefix  . 'courses_cache';
    $table_two = $wpdb->base_prefix  . 'courses_count';
    $course_id = intval( $request['id'] );
    $total = intval( $request['total'] ); 
    if( $total ){
       $checked = "SELECT * FROM $table_two WHERE `course_id` LIKE $course_id LIMIT 1";
       if($checked > 0 ){
        $resultTotal = $wpdb->update(
          $table_two, array(
              'total' => intval( $request['total'] 
          ),array( 
              'course_id' => $course_id
          )
        ));
        } else {
            $resultTotal = $wpdb->insert(
            $table_two, array(
              'course_id' => $course_id,
              'total' =>  $total
            )
          );
        }
    }
    $meta = strval( $request['meta']) ;
    $check = "SELECT * FROM $table_up WHERE `course_id` LIKE $course_id LIMIT 1";
    $list = $wpdb->get_results($check);
    if( count( $list) == 0 ) { // if it doesn't appear in the dB
      $wpdb->insert(
        $table_up, array(
          'course_id' => $course_id,
          'insertDate' =>  current_time( 'mysql' ),
          'last_update' => current_time( 'mysql' ),
          'meta'	=> $meta,
          'totals' => $resultTotal
        )
      );
      $list = $wpdb->get_results( 'SELECT * FROM ' . $table_up . ' WHERE id = ' . $wpdb->insert_id . ' LIMIT 1;' ); 
      $obj =[
        'success' => true,
        'data' => json_decode($meta)
      ]; 
    } else {
      $result = $wpdb->update(
        $table_up, array(
            'last_update' => current_time( 'mysql' ),
            'meta'	=> $meta
        ),array( 
            'course_id' => $course_id
        )
      );
      $obj =[
        'update' => $result,
        'data' => json_decode($meta)
      ]; 
    }
   
    
    return $obj;
    
}



 

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
function HOT_register_courses_routes() {
 $controller = new HOT_COURSES_CONTROLLER();
 $controller->register_routes();
}

add_action( 'rest_api_init', 'HOT_register_courses_routes' );