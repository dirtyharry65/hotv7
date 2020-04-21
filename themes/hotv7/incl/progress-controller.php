<?php

class HOT_PROGRESS_Controller extends WP_REST_Controller {
 
 // Here initialize our namespace and resource name.
 public function __construct() {
     $this->namespace     = 'hot/v2';
     $this->resource_name = 'progress';
     $this->rest_base = 'progress';
 }

    /**
     * Registers the routes for the objects of the controller.
    *
    * @since 4.7.0
    *
    * @see register_rest_route()
    */
 public function register_routes() {
     // get the progress of the user by userID
    register_rest_route( $this->namespace, '/' . $this->resource_name . '/user/(?P<id>[\d]+)', array(
        'args'   => array(
            'id' => array(
                'description' => __( 'Unique identifier for the object.' ),
                'type'        => 'integer',
            ),
        ),
         array(
            'methods'             => WP_REST_Server::READABLE,
             'callback'  => array( $this, 'get_user_progress_by_id' ),
             'permission_callback' => array( $this, 'get_items_permissions_check' ),
         ),
         // Register our schema callback.
         'schema' => array( $this, 'get_item_schema' ),
     ) );
    register_rest_route( $this->namespace, '/' . $this->resource_name . '/course/(?P<id>[\d]+)', array(
        'args'   => array(
            'id' => array(
                'description' => __( 'Unique identifier for the object.' ),
                'type'        => 'integer',
            ),
        ),
         array(
            'methods'             => WP_REST_Server::READABLE,
             'callback'  => array( $this, 'get_user_progress_by_course' ),
             'permission_callback' => array( $this, 'get_items_permissions_check' ),
         ),
         // Register our schema callback.
         'schema' => array( $this, 'get_item_schema' ),
     ) );
     // posts a new or existing ProgressID
     register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<id>[\d]+)', array(
         // Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
         array(
            'methods'             => WP_REST_Server::CREATABLE,
             'callback'  => array( $this, 'create_progress' ),
             'permission_callback' => array( $this, 'get_item_permissions_check' ),
         ),
         // Register our schema callback.
          'schema' => array( $this, 'get_item_schema' ),
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

 function get_user_progress_by_id( $request ) {
    global $wpdb;
    $id =  intval($request['id']);
    $limit = intval($request['limit']);
    $offset = intval($request['offset']) * $limit;
    $table_one = $wpdb->prefix  . 'user_progress';
    $direction = 'DESC';
    // $query = "SELECT * FROM `".$table_one."` WHERE `user_id` LIKE $id ORDER BY `";
    if( isset($request['direction'])) {
        $direction = strtoupper(sanitize_text_field($request['direction']));
    }
    if( isset($request['sortby']) ) {
        $sortby =  sanitize_text_field($request['sortby']).`, `;
        $query = "SELECT * FROM `".$table_one."` WHERE `user_id` LIKE $id ORDER BY `".$sortby."` ".$direction;
    } else {
        $query = "SELECT * FROM `".$table_one."` WHERE `user_id` LIKE $id ORDER BY `last_viewed` ".$direction;
    }
    // $table_one = $wpdb->base_prefix  . 'user_progress';
  
    $results = $wpdb->get_results($query);
    $rowcount = $wpdb->num_rows;
    // if(isset($request['xls']){

    // }
    if( isset($request['limit']) ){
        $query2 = $query . " LIMIT $limit OFFSET $offset";
    } else {
        $query2 = $query;
    }
    $list = $wpdb->get_results($query2);
    $table_two = $wpdb->prefix  . 'trophies';
    $query3 = "SELECT * FROM `".$table_two."` WHERE `user_id` LIKE $id ";
    $trophies = $wpdb->get_results($query3);
    $obj =[
        'success' => true,
        'id' => $id,
        'limit' => $limit,
        'offset' => $offset,
        'total' => $rowcount,
        'data' => $list,
        'trophies' => $trophies
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

function get_user_progress_by_course( $request ) {
  global $wpdb;
  $id =  intval($request['id']);
  $limit = 50;
  $offset = 0;
  $table_one = $wpdb->prefix  . 'user_progress';
  $direction = 'DESC';
  $sortby =  'last_viewed';
  if( isset($request['courseid']) ) {
    $cID = intval($request['courseid']);
    $query = "SELECT * FROM `".$table_one."` WHERE `user_id` LIKE $id AND `course_id` LIKE $cID ";
    
    if( isset($request['direction'])) {
        $direction = strtoupper(sanitize_text_field($request['direction']));
    }
    if( isset($request['sortby']) ) {
        $sortby =  sanitize_text_field($request['sortby']) ;
    }    
    $query .= "ORDER BY `".$sortby."` $direction ";
    if( isset($request['limit']) && isset($request['offset']) ){
        $offset= intval($request['offset']) * $limit;
        $query .= "LIMIT $limit OFFSET $offset";
    }
    $list = $wpdb->get_results($query);
    $rowcount = $wpdb->num_rows;
     $table_two = $wpdb->prefix  . 'trophies';
     
      $courseID =  intval($request['courseid']);
      $query3 = "SELECT * FROM `".$table_two."` WHERE `user_id` LIKE $id AND `course_id` LIKE  $courseID ";
      $trophies = $wpdb->get_results($query3);
    $obj =[
        'success' => true,
        'id' => $id,
        'courseid' => $cID,
        'limit' => $limit,
        'offset' => $offset,
        'total' => $rowcount,
        'data' => $list,
        'trophies' => $trophies
    ];
  } else {
    $obj =[
        'error' => true,
        'message' => 'CourseID not set, could not resolve data',
    ];
  }
  return $obj;
}
/**
* Update or Create progress.
*
* @since 1.0.0
*
* @param WP_REST_Request $request Full details about the request.
* @return Obj Response object on success, or WP_Error object on failure.
*/
function create_progress( $request ) {
    global $wpdb;
    $table_up = $wpdb->prefix  . 'user_progress';
    $user_id = intval( $request['user_id'] );
    if ( isset( $request['update'] ) ) {
        $meta = stripslashes($request['meta']);
        $viewed = intval($_REQUEST['perc_viewed']);
        $result = $wpdb->update(
            $table_up, array(
                'last_viewed' => current_time( 'mysql' ),
                'perc_viewed'=> $viewed,	
                'meta'	=> $meta
            ),array( 
                'ID' => intval($request['update'])
            )
        );
        if( $result === false){
            $obj =[
                'error' => true,
                'message' => 'could not update',
            ];
        }else{
            $list = $wpdb->get_results( 'SELECT * FROM ' . $table_up . ' WHERE id = ' . intval($request['update']) . ' LIMIT 1' ); 
            $obj =[
                'success' => true,
                'update' => true,
                'data' => $list
            ]; 
        }
        return $obj;
    } else {
        $video_id = intval( $request['video_id'] );
        $check = "SELECT * FROM $table_up WHERE `user_id` LIKE $user_id AND `video_id` LIKE $video_id  LIMIT 1";
        $list = $wpdb->get_results($check);
        // activateAccount( $user_id);
        $course_id = intval( $request['course_id'] );
        $obj =[];
        if( count( $list) == 0 ) { // if it doesn't appear in the dB
            $title = sanitize_text_field($request['title']);
            $meta = stripslashes($request['meta']);
            $wpdb->insert(
                $table_up, array(
                    'video_id' => $video_id,
                    'course_id' => $course_id,
                    'videoTitle' => $title,
                    'user_id'		=> $user_id,
                    'last_viewed' => current_time( 'mysql' ),
                    'startdate' => current_time( 'mysql' ),
                    'views' => 1,
                    'perc_viewed'=> 0,	
                    'meta'	=> $meta
                )
            );
            $list = $wpdb->get_results( 'SELECT * FROM ' . $table_up . ' WHERE id = ' . $wpdb->insert_id . ' LIMIT 1;' ); 
        } 
        $obj =[
            'success' => true,
            'data' => $list[0]
        ]; 
        return $obj;
    }
}


public function activateAccount( $user_id ){
 
  $args = array(
    'author' => $user_id,
    'post_type' => 'inschrijving',
  );
  $author_posts = get_posts( $args );
  if( count( $author_posts ) > 0 ) {
    $pID = $author_posts[0]->ID;
    $actief = get_field('actief', $pID );
    if( $actief !== 2 ){
     update_field( 'actief', 1, $pID );
    }
  }
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
  /**
  * Matches the post data to the schema we want.
  *
  * @param WP_Post $post The comment object whose response is being prepared.
  */
  



 /**
  * Prepare a response for inserting into a collection of responses.
  *
  * This is copied from WP_REST_Controller class in the WP REST API v2 plugin.
  *
  * @param WP_REST_Response $response Response object.
  * @return array Response data, ready for insertion into collection data.
  */
 public function prepare_response_for_collection( $response ) {
     if ( ! ( $response instanceof WP_REST_Response ) ) {
         return $response;
     }

     $data = (array) $response->get_data();
     $server = rest_get_server();

     if ( method_exists( $server, 'get_compact_response_links' ) ) {
         $links = call_user_func( array( $server, 'get_compact_response_links' ), $response );
     } else {
         $links = call_user_func( array( $server, 'get_response_links' ), $response );
     }

     if ( ! empty( $links ) ) {
         $data['_links'] = $links;
     }

     return $data;
 }

 /**
  * Get our sample schema for a post.
  *
  * @param WP_REST_Request $request Current request.
  */
 public function get_item_schema(  ) {
     $schema = array(
         // This tells the spec of JSON Schema we are using which is draft 4.
         '$schema'              => 'http://json-schema.org/draft-04/schema#',
         // The title property marks the identity of the resource.
         'title'                => 'progress',
         'type'                 => 'array',
         // In JSON Schema you can specify object properties in the properties attribute.
         'properties'           => array(
             'id' => array(
                 'description'  => esc_html__( 'Unique identifier for the object.', 'my-textdomain' ),
                 'type'         => 'integer',
                 'context'      => array( 'view', 'edit', 'embed' ),
                 'readonly'     => true,
             ),
             'course_id' => array(
                 'description'  =>  'De cursus identifier' ,
                 'type'         => 'string',
             ),
             'last_viewed' => array(
                 'description'  =>  'Laatste keer bekeken' ,
                 'type'         => 'time',
             ),
             'meta' => array(
                 'description'  =>  'Interactie met speler' ,
                 'type'         => 'object',
             ),
             'perc_viewed' => array(
                 'description'  =>  'Percentage bekeken' ,
                 'type'         => 'string',
             ),
             'startdate' => array(
                 'description'  =>  'StartDatum' ,
                 'type'         => 'SQL Time',
             ),
             'user_id' => array(
                 'description'  =>  'Gebruikers ID' ,
                 'type'         => 'string',
             ),
             'videoTitle' => array(
                 'description'  =>  'Titel van de video' ,
                 'type'         => 'string',
             ),
             'video_id' => array(
                 'description'  =>  'ID van de video' ,
                 'type'         => 'string',
             ),
             'views' => array(
                 'description'  =>  'Aantal keren bekeken' ,
                 'type'         => 'integer',
             ),
         ),
     );

     return $schema;
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
function HOT_register_progress_routes() {
 $controller = new HOT_PROGRESS_Controller();
 $controller->register_routes();
}

add_action( 'rest_api_init', 'HOT_register_progress_routes' );