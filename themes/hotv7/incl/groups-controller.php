<?php
class HOT_GROUPS_Controller extends WP_REST_Controller {

  // Here initialize our namespace and resource name.
  public function __construct() {
    $this->namespace     = 'hot/v2';
    $this->rest_base = 'groups';
    $this->resource_name = 'groups';
  }
// Register our routes.
  public function register_routes() {
    // first register main entrance [GET]
    // returns all our groups
    register_rest_route( $this->namespace, '/' . $this->resource_name , array(
        // Here we register the readable endpoint for collections.
        array(
          'methods'             => WP_REST_Server::READABLE,
            'callback'  => array( $this, 'get_groups' ),
            'permission_callback' => array( $this, 'get_groups_permissions_check' ),
        ),
        // Register our schema callback.
        'schema' => array( $this, 'get_groups_schema' ),
    ) );
    /** 
     *  Post new or update Group [ID]
     * returns the new GroupID
     */
    register_rest_route( $this->namespace, '/' . $this->resource_name.'/(?P<id>[\d]+)' , array(
        // Here we register the readable endpoint for collections.
        array(
          'methods'             => WP_REST_Server::CREATABLE,
            'callback'  => array( $this, 'create_group' ),
            'permission_callback' => array( $this, 'get_groups_permissions_check' ),
        ),
       
        // Register our schema callback.
        'schema' => array( $this, 'get_groups_schema' ),
    ) );
    register_rest_route( $this->namespace, '/' . $this->resource_name.'/update' , array(
        // Here we register the readable endpoint for collections.
        array(
          'methods'             => WP_REST_Server::EDITABLE,
            'callback'  => array( $this, 'update_group' ),
            'permission_callback' => array( $this, 'get_groups_permissions_check' ),
        ),
        // Register our schema callback.
        'schema' => array( $this, 'get_groups_schema' ),
    ) );
    /**
     * Manage Users in a group
     */
    register_rest_route( $this->namespace, '/'.$this->resource_name.'/users/(?P<id>[\d]+)', array(
      array(
        'methods'             => WP_REST_Server::READABLE,
          'callback'  => array( $this, 'get_HOT_user_groups_by_id' ),
          'permission_callback' => array( $this, 'get_groups_permissions_check' ),
      ),
      array(
        'methods'         => WP_REST_Server::CREATABLE,
        'callback'        => array( $this , 'add_to_group'),
        'permission_callback' => array( $this , 'get_groups_permissions_check')
      ),
      'schema' => array( $this, 'get_groups_schema')
    ) );
  }
/**
 * 
 *  Get Groups by UserID
 */
public function get_HOT_user_groups_by_id( $request ) {
		$id=  intval( $request['id'] );
		$groups_user = new Groups_User( $id );
		$user_groups = $groups_user->groups;
		
		$groups = [];
		$total = count($user_groups);
		for( $i =0 ; $i< $total ; $i++ ) {
				$userGroup = $user_groups[$i];
        $option = 'group_'.$userGroup->group->group_id;
        $courses ="";
				if( get_option($option)){
					$courses= get_option($option);
				}
        $t = (int)$userGroup->group->group_id;
        $g = new Groups_Group($t);
        $users = $g->users;
        $count = count($users);
				$groupObj = array(
					'group' => $userGroup->group,
					'courses' => $courses,
					'id' => $t,
          'total_users' => $count,
          'users' => $total
				);
				array_push($groups , 	$groupObj);
		}
	 return array( 
			'success' => true, 
			// 'groups' => $groups,
			'data' => $groups,
			'total' => $total-1,
      'message' => "user by ID"
	);
	
}
/**
 *  Add a Teacher to a group
 * 
 */
public function add_to_group($request ){
  $userId = intval(sanitize_text_field($request['id']));
  $user =  $user = get_userdata( $userId);
  $gID = intval( sanitize_text_field( $_REQUEST['groupid'] ) ) ;
  do_action( 'groups_updated_user_group', $user_id, $group_id );
  $addedToGroup = Groups_User_Group::create( array( 'user_id' => $userId, 'group_id' => $gID ) );
  $groups = Groups_Group::get_groups();
  $groups = getTheHOTGroups( $groups);
	
  if( $addedToGroup ) {

    return array( 
      'success'   => true, 
      'status'    => 'USER_ADDED_TO_GROUP',
			'groups'    =>  $groups,
    );
  } else {
    return array( 
      'success' => false, 
      'status'    => 'ERROR',
      'id'  => $userId,
      'groupId' => $gID,
      'message' => "Could not be added to group"
    );
  }

}
public function remove_from_group( $request ){
  $userId = intval(sanitize_text_field($request['id']));
}
  /**
   * 
   *  create or update a group
   */

   public function create_group( $request ){
      $name = sanitize_text_field($request['groupname']);
      $description = sanitize_text_field($request['description']);
      $creator_id = intval( $request['id']);
      $parent_id = null;
      $courses = $request['courses'];
      $datetime    = date( 'Y-m-d H:i:s', time() );
      $user = get_userdata( $creator_id );
      if( $user ){
        $input = compact( "name" , "creator_id", "datetime", "parent_id", "description"  );
        $groupID = Groups_Group::create( $input );
        if( $groupID){
          $option = 'group_'.$groupID;
          $update = update_option($option , $courses);
          if( $update){
            $groups = Groups_Group::get_groups();
            $groups = getTheHOTGroups( $groups);
            $data =  array(  
              'status' => 'GROUP_ADDED', 
              'groupID' =>  $groupID,
              'groups' => $groups
            );
            return rest_ensure_response( $data );
          }
        } else {
          $data = array( 'status' => 'GROUP_ADDED', 'ERROR' => 'Groep is niet aangemaakt, deze bestaat al' );
          return rest_ensure_response( $data );
        }
      } else {
        $data =  array( 'status' => 'GROUP_ADDED', 'ERROR'    =>  'Niet toegestaan voor deze gebruiker' );
        return rest_ensure_response( $data );


      }
   }
  /**
   * 
   *  Update a group
   */

   public function update_group( $request ){

      $name = sanitize_text_field($request['name']);
      $description = sanitize_text_field($request['description']);
      $group_id = intval( $request['id']);
      $parent_id = null;
      $subscriptions = explode( ',', sanitize_text_field( $request[ 'subscriptions'] ) );
      $courses =  sanitize_text_field($request['courses']);
      $input = compact( "group_id","name" , "description"  );
      $groupID = Groups_Group::update( $input );
       
      $option = 'group_'.$groupID;
      update_option($option , $courses);
      $group = new Groups_Group( $groupID );
      $isUpdated = updateSubscriptions( $subscriptions , $courses );
      $groups = Groups_Group::get_groups();
      $groups = getTheHOTGroups( $groups);
      $data =  array(  
        'status' => 'GROUP_UPDATE', 
        'groupID' =>  $groupID,
        'groups' => $groups,
        'group' => $group,
        'subscriptionsUpdated' => $isUpdated
      );
      return rest_ensure_response( $data );  
   }

  /**
  * Check permissions for the posts.
  *
  * @param WP_REST_Request $request Current request.
  */
  public function get_groups_permissions_check( $request ) {
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
  public function get_groups( $request ) {
    $groups = Groups_Group::get_groups();
    if( isset($request['id'])){
      $list = getSuperHotGroupsByID( intval($request['id']));
    } else {
      $list = getTheHOTGroups( $groups );
    }
		     
    return rest_ensure_response($list);
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
    $post_data['code'] = $post->post_title;
    $post_data['userID'] = intval($post->post_author);
    $user = get_user_meta(  $post->post_author );
    $post_data['user'] = $user;
    $post_data['meta'] = $meta;
    $groups_user = new Groups_User(intval($post->post_author) );
    $user_groups = $groups_user->groups;
    $post_data['groups'] = $user_groups;

    return rest_ensure_response( $post_data);
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
  public function get_groups_schema(  ) {
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
// end of Class
}


// Function to register our new routes from the controller.
function HOT_register_groups_routes() {
  $controller = new HOT_GROUPS_Controller();
  $controller->register_routes();
 }
 
 add_action( 'rest_api_init', 'HOT_register_groups_routes' );

 /**
  *  ROUTINES AND FUNCTIONS
  */

  function getSuperHotGroupsByID( $id ){
    $list =[];
    $courses = '';
    if( get_option('group_'.$id) ){
      $courses = get_option('group_'.$id);
    }
    $g = new Groups_Group( $id );
    if( $g ){
      $users = $g->users;
      $count = count($users);
      $grouped["id"] = $id;
      $grouped["group"] = $g;
      $grouped["courses"] = $courses;
      $grouped["total_users"] = $count;
      $grouped["users"] = [];
      $userList=[];
      if( $users){
        foreach ($users as  $userobj) {
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
            $posts = [];
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
                  );
                },
                $posts
              );
            }
          $userData = [
            'id' => $userobj->ID,
            // 'user'=> $userobj,
            'first_name'=> $usermeta->first_name,
            'last_name'=> $usermeta->last_name,
            'user_registered'=> $usermeta->user_registered,
            'roles' =>  $roles[0],
            'subscriptions' => $posts
          ];
            array_push( $userList , $userData );
          }
        }
      }
      $grouped["users"] = $userList;
      
    array_push($list ,$grouped);

    $obj =[
      'success' => true,
      'data' => $list
    ]; 
    return $obj;
    
  }
  }

  function getTheHOTGroups( $groups ) {
   
    $list = [];
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
        $count = count($users);
        $grouped["id"] = $group_id;
        $grouped["group"] = $group;
        $grouped["courses"] = $courses;
        $grouped["total_users"] = $count;
        $grouped["users"] = [];
        array_push($list ,$grouped);
      }   
    }
    $obj =[
      'success' => true,
      'data' => $list
    ]; 
    return $obj;
  }

  function updateSubscriptions( $subscriptionIds , $courses ) {

    $delCounter= 0;
    $addCounter=0;
    $coursesToAdd = explode(',' , $courses);
    // verwijder eerst alle cursussen om ze daarna weer te vullen
    foreach( $subscriptionIds as $subscriptionId ){
      $rows = get_field('cursussen', $subscriptionId);
      // array_push( $delCounter , count($rows) );
      if( !empty($rows)) {
        for( $index = count($rows); $index > 0; $index-- ){
          delete_row('cursussen', $index, $subscriptionId);
          $delCounter++;
        }
      }
      // Voeg nu cursussen toe...
      foreach ($coursesToAdd as $course) {
        $add = array(
          'cursus_id' => intval($course),
          'eind_datum'=> date('Y-m-d',strtotime(date("Y-m-d", time()) . " + 365 day"))
        );
        $added = add_row('cursussen', $add , $subscriptionId ); 
        if( $added ){
          $addCounter++;
        }
      } 
    }
    return array(
      'deleted' => $delCounter,
      'added'  => $addCounter
    );
  }