<?php

class HOT_webinars_Controller extends WP_REST_Controller {
 
 // Here initialize our namespace and resource name.
 public function __construct() {
     $this->namespace     = 'hot/v2';
     $this->resource_name = 'webinars';
     $this->rest_base = 'webinars';
 }

 // Register our routes.
 public function register_routes() {
     register_rest_route( $this->namespace, '/' . $this->resource_name, array(
         // Here we register the readable endpoint for collections.
         array(
            'methods'             => WP_REST_Server::READABLE,
             'callback'  => array( $this, 'get_items' ),
             'permission_callback' => array( $this, 'get_items_permissions_check' ),
         ),
         // Register our schema callback.
         'schema' => array( $this, 'get_item_schema' ),
         array(
            'methods'             => WP_REST_Server::CREATABLE,
             'callback'  => array( $this, 'set_item' ),
             'permission_callback' => array( $this, 'get_items_permissions_check' ),
         ),
         // Register our schema callback.
         'schema' => array( $this, 'get_item_schema' ),
     ) );
     register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<id>[\d]+)', array(
         // Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
         array(
            'methods'             => WP_REST_Server::READABLE,
             'callback'  => array( $this, 'get_item_by_group' ),
             'permission_callback' => array( $this, 'get_item_permissions_check' ),
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
 public function get_items( $request  ) {
    $category_id = get_cat_ID('webinar');
    $date_now = date('Y-m-d H:i:sa');
    $args = array(
       'category' => $category_id,
       'meta_query' 		=> array(
        array(
          'key'			=> 'start_datum',
          'compare'		=> '>=',
          'value'			=> $date_now,
          'type'			=> 'DATETIME'
          )
        )
     );
     $posts = get_posts( $args );

     $data = array();

     if ( empty( $posts ) ) {
        $obj = [
          'success' => false,
          'data' => $data
        ];
         return rest_ensure_response( $obj );
     }

     foreach ( $posts as $post ) {
         $response = $this->prepare_item_for_response( $post, $request );
         $data[] = $this->prepare_response_for_collection( $response );
     }

     // Return all of our  response data.
     $obj = [
       'success' => true,
       'data' => $data
     ];
     return rest_ensure_response( $obj );
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
  public function prepare_item_for_response( $post, $request ) {
    $post_data = array();
    $post_data['id'] = (int) $post->ID;
    $meta = get_post_meta( (int) $post->ID );
    $post_data['title'] = $post->post_title;
    $post_data['content'] = $post->post_content;
    $post_data['vimeo_id'] = $meta['vimeo_id'][0];
    $post_data['start_datum'] = $meta['start_datum'][0];
    $user_groups = $meta['groups'][0];
    $post_data['groups'] = $user_groups;
    return rest_ensure_response( $post_data);
 }

 
 public function get_item_by_group( $request ) {
    $id = (int) $request['id'];
    $category_id = get_cat_ID('webinar');
    $args = array(
       'category' => $category_id,
       'meta_query' 		=> array(
        array(
          'key'			=> 'groups',
          'compare'		=> '=',
          'value'			=> $id
          )
        )
     );
     $posts = get_posts( $args );

    $data = array();

     if ( empty( $posts ) ) {
         return rest_ensure_response( $data );
     }

     foreach ( $posts as $post ) {
         $response = $this->prepare_item_for_response( $post, $request );
         $data[] = $this->prepare_response_for_collection( $response );
     }

     // Return all of our comment response data.
     return rest_ensure_response( $data );
 }

public function set_item( $request ) {
    $title =  sanitize_text_field($request['title']);
    $date = date("Y-m-d H:i",strtotime($request["start_datum"]));
    
    $groups = sanitize_text_field( $request['groups'] );
    $vimeo_id = sanitize_text_field( $request['vimeo_id']);
    $content = sanitize_text_field($request['content']);
    $category_id = [get_cat_ID('webinar')];
    $user_id = get_current_user_id();
   
    $defaults = array(
        'post_author'           => $user_id,
        'post_content'          => $content,
        'post_title'            => $title,
        'post_status'           => 'publish',
        'post_category'         => $category_id
    );
    $postID = wp_insert_post( $defaults );
    update_field( 'groups' , $groups , $postID );
    update_field( 'vimeo_id' , $vimeo_id , $postID );
    update_field( 'start_datum' ,$date , $postID );
   
    $date_now = date('Y-m-d H:i:sa');
    $args = array(
       'category' => $category_id,
       'meta_query' 		=> array(
        array(
          'key'			=> 'start_datum',
          'compare'		=> '>=',
          'value'			=> $date_now,
          'type'			=> 'DATETIME'
          )
        )
     );
     $posts = get_posts( $args );

     $data = array();

     if ( empty( $posts ) ) {
         return rest_ensure_response( $data );
     }

     foreach ( $posts as $post ) {
         $response = $this->prepare_item_for_response( $post, $request );
         $data[] = $this->prepare_response_for_collection( $response );
     }

     // Return all of our comment response data.
     return rest_ensure_response( $data );

}


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
         'title'                => 'post',
         'type'                 => 'object',
         // In JSON Schema you can specify object properties in the properties attribute.
         'properties'           => array(
             'id' => array(
                 'description'  => esc_html__( 'Unique identifier for the object.', 'my-textdomain' ),
                 'type'         => 'integer',
                 'context'      => array( 'view', 'edit', 'embed' ),
                 'readonly'     => true,
             ),
             'content' => array(
                 'description'  => esc_html__( 'The content for the object.', 'my-textdomain' ),
                 'type'         => 'string',
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
function HOT_register_webinars_routes() {
 $controller = new HOT_webinars_Controller();
 $controller->register_routes();
}

add_action( 'rest_api_init', 'HOT_register_webinars_routes' );