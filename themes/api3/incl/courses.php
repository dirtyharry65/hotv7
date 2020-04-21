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
    register_rest_route( $this->namespace, '/' . $this->resource_name , array(
        'args'   => array(
            'id' => array(
                'description' => __( 'Unique identifier for the object.' ),
                'type'        => 'integer',
            ),
        ),
         array(
            'methods'             => WP_REST_Server::READABLE,
             'callback'  => array( $this, 'getAPICourses' ),
            //  'permission_callback' => array( $this, 'get_items_permissions_check' ),
         ),
        
     ) );
    register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<id>[\d]+)', array(
        'args'   => array(
            'id' => array(
                'description' => __( 'Unique identifier for the object.' ),
                'type'        => 'integer',
            ),
        ),
         array(
            'methods'             => WP_REST_Server::READABLE,
             'callback'  => array( $this, 'getCourse' ),
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
  function getCourse( $request){
    global $wpdb;
    $id = intval($request['id']);
    $coursesTable = $wpdb->base_prefix.'courses';
    $chaptersTable = $wpdb->base_prefix.'chapters';
    $videosTable = $wpdb->base_prefix.'videos';
    //WHERE `course_id` LIKE 1944 ORDER BY `term_id` AND `menu_order`
    $coursesQuery = "SELECT * FROM `".$coursesTable."` WHERE `course_id` LIKE ".$id;
    $chaptersQuery = "SELECT * FROM `".$chaptersTable."` WHERE `course_id` LIKE ".$id." ORDER BY `term_order`" ;
    $videosQuery = "SELECT * FROM `".$videosTable."` WHERE `course_id` LIKE ".$id." ORDER BY `term_id` AND `menu_order`" ;
    $courseResult = $wpdb->get_row( $coursesQuery );
    $courseComplete = array();
    if( $courseResult ){
      $courseResult->acf = json_decode( $courseResult->acf);
    }
    $chaptersResult = $wpdb->get_results( $chaptersQuery );
    $videosResult = $wpdb->get_results( $videosQuery );
    $chapters = array();
    $chapterCounter = 0;
    foreach ( $chaptersResult as $ch => $value ){
      $data   = array();
      $term_id = $value->term_id;
      $name= "ch_".$chapterCounter;
       if( $value ){
        $data["data"] = $value;
        $data["acf"] = json_decode( $value->acf);
        $videos = array();
        foreach( $videosResult as $video => $videoValue ) {
          if( $videoValue->term_id === $term_id){
            $vObj = array();
            $post = array();
            $meta= array();
            $post["ID"] = $videoValue->ID;
            $post["post_title"] = $videoValue->post_title;
            $post["post_name"] = $videoValue->post_name;
            $post["menu_order"] = $videoValue->menu_order;
            $meta["transcriptie"] = $videoValue->transcriptie;
            $meta["cursus_code"] = $videoValue->cursus_code;
            $meta["video_id"] = $videoValue->video_id;
            $meta["minuten"] = $videoValue->minuten;
            $meta["seconden"] = $videoValue->seconden;
            $vObj["post"]= $post;
            $vObj["meta"]= $meta;
            $vid= "'".$videoValue->ID."'";
            $videos[$vid] = $vObj;
          }
        };
        $data["video"] = $videos;
      }
      $chapters[$name] = $data;
      $chapterCounter++;
    }
    $obj =[
      'id' => $id,
      'count' => $courseResult->count,
      'description' => $courseResult->description,
      'name' => $courseResult->name,
      'slug' => $courseResult->slug,
      'term_order' => $courseResult->term_order,
      'course_id' => $courseResult->course_id,
      'acf' => $courseResult->acf,
      'chapters' => $chapters
    ];
    return $obj;
  }
/**
  * Get the Courses.
  *
  * @since 1.0.0
  *
  * @param WP_REST_Request $request Full details about the request.
  * @return Obj Response object on success, or WP_Error object on failure.
  */
  function getAPICourses( $request){
    global $wpdb;
    $coursesTable = $wpdb->base_prefix.'courses';
    $query = "SELECT * FROM `".$coursesTable."`";
    $results = $wpdb->get_results($query);
    if( $results ){
      foreach ( $results as $result => $value) {
        $value->acf = json_decode( $value->acf);
      }
    }
    $obj =[
      'success' => true,
      'data' => $results
    ];
    return $results;
  }


/**
* *
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
    $table_courses =  $wpdb->base_prefix.'courses';
    $course_id = intval( $request['id'] );
    $total = intval( $request['total'] ); 
    $meta = strval( $request['meta']) ;
    $meta = json_decode( $meta);
    $chapters= $meta->chapters;
   
  // First create a Course if it doesn't exist
   if( $total > 0  ){
     $courseQuery = "SELECT `course_id` FROM $table_courses WHERE `course_id` LIKE $course_id LIMIT 1";
     $courseCheck = $wpdb->get_results($courseQuery);
     if( count( $courseCheck ) > 0 ) { // is already in db
        $insert =  array(
          'last_update' => current_time( 'mysql' ),
          'course_id'	=> $course_id,
          'name' => $meta->name,
          'description' => $meta->description,
          'slug' => $meta->slug,
          'count' => $total,
          'acf' =>  json_encode($meta->acf),
          'active' => 1
        );
        $result = $wpdb->update(
          $table_courses, $insert , array( 
          'course_id' => $course_id
          )
        );
         $present = true;
     } else { // course not yet registered
        $currTime = current_time( 'mysql' );
        $insert = array(
            'insert_date' => $currTime,
            'course_id'	=> $course_id,
            'last_update' => $currTime,
            'name' => $meta->name,
            'description' => $meta->description,
            'slug' => $meta->slug,
            'count' => $total,
            'acf' => json_encode($meta->acf),
            'active' => 1
          );
        $result = $wpdb->insert(
           $table_courses, $insert
        );
        $present = false;
      }
     /**
      * course is registered, now iterate over the Chapters
      */
      $chaptersList = parseChapters( $course_id , $chapters );

      /**
       * Finally return result
       */
      $obj =[
        'success' => true,
        'data' => $meta->acf,
        'chapters' => $chaptersList,
        'total' => $total,
        'course_id' => $course_id,
        'result' => $result,
        'prsent' => $insert,
        'update' => $present
      ]; 
      return $obj;
   } else {
     $obj =[
        'success' => false,
        'data' => $meta,
        'total' => $total,
        'course_id' => $course_id,
      ]; 
      return $obj;
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
 

 // Sets up the proper HTTP status code for authorization.
 public function authorization_status_code() {

     $status = 401;

     if ( is_user_logged_in() ) {
         $status = 403;
     }

     return $status;
 }
}

/**
 *  parse the chapters
 */

 function parseChapters( $course_id , $chapters ){
  global $wpdb;
  $table_chapters =  $wpdb->base_prefix.'chapters';
  // $table_videos =  $wpdb->base_prefix.'videos';
  $newChapters = [];
  foreach ( $chapters as $chapter => $value ) { // iterate over every chapter
    $data = $value->data;
    $term_id = $data->term_id;
    $result= [];
    $check = "SELECT `term_id` FROM $table_chapters WHERE `term_id` LIKE $term_id LIMIT 1";
    $insert = array(
      'course_id'	=> $course_id,
      'term_id' => $term_id,
      'term_order' => $data->term_order,
      'name' => $data->name,
      'slug' => $data->slug,
      'description' => $data->description,
      'count' => $data->count,
      'acf' => json_encode($value->acf),
      'active' => 1
    );
    $chapterCheck = $wpdb->get_results($check);
    if( count($chapterCheck) > 0 ) {
      $result = $wpdb->update(
          $table_chapters, $insert , array( 
          'term_id' => $term_id
          )
      );
    } else {
      $result = $wpdb->insert(
          $table_chapters, $insert
      );
    };
    // Add the videos to _videos table
    $videos = parseVideos( $value->video , $term_id, $course_id );
    $results = array(
      'result' => $result,
      'videos' => $videos 
    );

    array_push( $newChapters , $results );
  }
  return $newChapters;

}

function parseVideos( $videos , $term_id , $course_id ) {
global $wpdb;
  $table_videos =  $wpdb->base_prefix.'videos';
  $result = array();
  $counter = 0;
  foreach( $videos as $video => $value){
    $post = $value->post;
    $meta = $value->meta;
    $insert = array(
      'video_id' => $meta->video_id,
      'term_id' => $term_id,
      'course_id' => $course_id,
      'ID' => $post->ID,
      'post_title' => $post->post_title,
      'post_name' => $post->post_title,
      'menu_order' => $counter,
      'cursus_code' => $meta->cursus_code,
      // 'punten' => $meta->punten,
      // 'is_user_logged_in' => $meta->is_user_logged_in,
      'minuten' => $meta->minuten,
      'seconden' => $meta->seconden,
      'transcriptie' => $meta->transcriptie,
      // 'is_update' => $meta->update,
    );
    $counter++;
    $check = "SELECT `ID` FROM $table_videos WHERE `ID` LIKE $post->ID LIMIT 1";
    $videoCheck = $wpdb->get_results($check);
    if( count( $videoCheck) > 0 ){ // update the video
      $inserted = $wpdb->update( $table_videos, $insert , array( 
          'ID' => $post->ID
          )
      );
    } else { // new Video
      $inserted = $wpdb->insert(
          $table_videos, $insert
      );
    }
    array_push( $result , $inserted );
  }
  return $result;
}
 

// Function to register our new routes from the controller.
function HOT_register_courses_routes() {
 $controller = new HOT_COURSES_CONTROLLER();
 $controller->register_routes();
}

add_action( 'rest_api_init', 'HOT_register_courses_routes' );