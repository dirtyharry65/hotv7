<?php

class HOT_MESSAGES_Controller extends WP_REST_Controller {
 
 // Here initialize our namespace and resource name.
 public function __construct() {
     $this->namespace     = 'hot/v2';
     $this->resource_name = 'messages';
     $this->rest_base = 'messages';
 }

 // Register our routes.
 public function register_routes() {
     register_rest_route( $this->namespace, '/' . $this->resource_name, array(
         // Here we register the readable endpoint for collections.
         array(
            'methods'             => WP_REST_Server::READABLE,
             'callback'  => array( $this, 'get_messages' ),
             'permission_callback' => array( $this, 'get_items_permissions_check' ),
         ),
         // Register our schema callback.
         'schema' => array( $this, 'get_item_schema' ),
     ) );
    register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<id>[\d]+)', array(
        // Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
        array(
          'methods'             => WP_REST_Server::READABLE,
            'callback'  => array( $this, 'get_messages' ),
            'permission_callback' => array( $this, 'get_items_permissions_check' ),
        ),
         array(
            'methods'             => WP_REST_Server::CREATABLE,
             'callback'  => array( $this, 'set_message' ),
             'permission_callback' => array( $this, 'get_items_permissions_check' ),
         ),
         array(
            'methods'             => WP_REST_Server::DELETABLE,
             'callback'  => array( $this, 'delete_message' ),
             'permission_callback' => array( $this, 'get_items_permissions_check' ),
         ),
        // Register our schema callback.
        'schema' => array( $this, 'get_item_schema' ),
    ) );
    register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<id>[\d]+)/(?P<mid>[\d]+)', array(
        // Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
       
         array(
            'methods'             => WP_REST_Server::CREATABLE,
             'callback'  => array( $this, 'set_read_message' ),
             'permission_callback' => array( $this, 'get_items_permissions_check' ),
         ),
         
        // Register our schema callback.
        'schema' => array( $this, 'get_item_schema' ),
    ) );
  
  }

 /**
  * Check permissions for the posts.
  *
  * @param WP_REST_Request $request Current request.
  */
  public function get_items_permissions_check( $request ) {
      if ( ! current_user_can( 'read' ) ) {
          return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view the post resource.' ), array( 'status' => $this->authorization_status_code() ) );
      }
      return true;
  }
   

  /**
  * Grabs the five most recent posts and outputs them as a rest response.
  *
  * @param WP_REST_Request $request Current request.
  */
  public function get_messages( $request  ) {
    $uid = (int) $request['uid'];
    $gid = $request['id'];
    $archive = $request['archive'];
    $dataObj = retreiveMessages( $gid , $uid , $archive ,"" ,"" );

      // Return all of our comment response data.
      return  $dataObj ;
  }

  // sent messages...
  public function set_message( $request ) {
    $post_data = array();
    $gID = $request['id'];
    $uid = get_current_user_id();
    $title =  $request["title"];
    $content = $request["content"];
    $archive = true;
    $args = array(
      'post_type' => 'messages',
      'post_title'    => $title,
      'post_content'  => $content ,
      'post_status'  => 'publish',
      
      /*other default parameters you want to set*/
    );
    $post_id = wp_insert_post($args);
    update_post_meta( $post_id , 'groepid', $gID );
    $dataObj = retreiveMessages( $gID , $uid , $archive, $title ,$content );
    // Return all of our comment response data.
    return  $dataObj ;
  }

  /**
   *  Mark as Read
   */
  public function set_read_message( $request ) {
    $gid = $request['id'];
    $mid = $request['mid'];
    $uid = get_current_user_id();
    global $wpdb;
		$base =  $wpdb->prefix ;
    $table_name =  $base. 'group_messages';
    $date = current_time( 'mysql' );
    $results = $wpdb->get_results( 'SELECT * FROM ' . $table_name . ' WHERE postid = ' . $mid . ' AND userid = ' . $uid . ' LIMIT 1;' );
   	if ( count( $results ) == 0 ) {
      $args = array( 
        'postid'    => $mid,
        'userid'  =>  $uid,
        'last_viewed' => $date
      );
      $wpdb->insert( $table_name, $args );
      $results = $wpdb->get_results( 'SELECT * FROM ' . $table_name . ' WHERE id = ' . $wpdb->insert_id . ' LIMIT 1;' );
			$message = array( 'id' => $wpdb->insert_id, 'data' => $results ,'update' => false );
			wp_send_json_success( $message );
    } else {
      $id =  intval($results[0]->id );
      $data = ['last_viewed' => $date];
      $where = ['id' => $id ];
      $wpdb->update( $table_name, $data , $where );
      $list = $wpdb->get_results( 'SELECT * FROM ' . $table_name . ' WHERE id = ' . $id . ' LIMIT 1' ); 
        
      $message = array( 'id' => $id, 'data' => $list , 'update' => $date );
      wp_send_json_success( $message );
    }
  }
  /**
   *  delete message
   */
  public function delete_message( $request) {
    $gid = $request['id'];
    $mid = $request['mid'];
    $uid = get_current_user_id();
    $archive = "false";
    // delete the post
    wp_delete_post( $mid , true);
    // delete all read messages by users
    global $wpdb;
    $base =  $wpdb->prefix ;
    $table_name =  $base. 'group_messages';
    $deletedMessages = $wpdb->delete( $table_name, array( 'postid' => $mid ),array( '%d' ) );
    $dataObj = retreiveMessages( $gid , $uid , $archive, $title="" , $content="" );
    //$dataObj['deleted'] = json_encode( $deletedMessages );
     wp_send_json_success($dataObj) ;

  }
  
  /**
  * Matches the post data to the schema we want.
  *
  * @param WP_Post $post The comment object whose response is being prepared.
  */
  public function prepare_item_for_response( $post, $request ) {
    $post_data = array();
    $post_data['id'] = (int) $post->ID;
    $meta = get_post_meta( (int) $post->ID );
    $post_data['meta'] = $meta;
    $post_data['post'] = $post;
    return $post_data;
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
function HOT_register_messages_routes() {
  $controller = new HOT_MESSAGES_Controller();
  $controller->register_routes();
}


function retreiveMessages( $gid , $uid , $archive, $title="" , $content="" ) {
  $args = array(
      'numberposts' => 100,
      'post_type'    => 'messages',
      'meta_key'		=> 'groepid',
      'meta_value'	=> $gid
  );
  
  $posts = get_posts( $args );
  $data = array();
  $dataObj= array();  
  $dataObj['uid'] = (int) $uid;
  $dataObj['gid'] = (int) $gid;
  $dataObj['archive'] = $archive;
  $dataObj['title'] = $title;
  $dataObj['content'] = $content;

  $dataObj['messages'] = $data;
  $dataObj['total'] = 0;
  $dataObj['read'] = 0;

  if ( empty( $posts ) ) {
      return rest_ensure_response( $dataObj );
  }
  global $wpdb;
  $base =  $wpdb->prefix ;
  $table_name =  $base. 'group_messages';

  foreach ( $posts as $post ) {
    $id =  (int) $post->ID;
    $results = $wpdb->get_results( 'SELECT * FROM ' . $table_name . ' WHERE postid = ' . $id . ' AND userid = ' . (int) $uid . ' LIMIT 1;' );
    $post_data = array();
    if( $archive == "true" ){
      $post_data['id'] = $id;
      $meta = get_post_meta( $id );
      $post_data['meta'] = $meta;
      $post_data['post'] = $post;
      $post_data['read'] = count( $results);
      array_push ( $data ,$post_data );
      $dataObj['total']++;
    } else if( $archive == "false" ){
      $date = current_time( 'mysql' );
      if( count( $results) == 0){
        $post_data['id'] = $id;
        $post_data['meta'] = get_post_meta( (int) $post->ID );
        $post_data['post'] = $post;
        $post_data['read'] = count( $results);
        array_push ( $data ,$post_data );
      } else {
        $dataObj['read']++;
      }
      $dataObj['total']++;
    }
  }

  $dataObj['messages'] = $data;
  return $dataObj;
} 

add_action( 'rest_api_init', 'HOT_register_messages_routes' );