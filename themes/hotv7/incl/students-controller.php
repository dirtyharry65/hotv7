<?php
// create REST-API

class HOT_STUDENTS_Controller extends WP_REST_Controller {
 
 // Here initialize our namespace and resource name.
 public function __construct() {
     $this->namespace     = 'hot/v2';
     $this->resource_name = 'students';
     $this->rest_base = 'students';
 }

    // Register our routes.
    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->resource_name , array(
        // Here we register the readable endpoint for collections.
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'  => array( $this, 'get_users' ),
                'permission_callback' => array( $this, 'get_users_permissions_check' ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'  => array( $this, 'set_user' ),
                'permission_callback' => array( $this, 'get_users_permissions_check' ),
            ),
            // Register our schema callback.
            // 'schema' => array( $this, 'get_item_schema' ),
        ) );
        register_rest_route( $this->namespace, '/' . $this->resource_name.'/(?P<id>[\d]+)', array(
            // Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'  => array( $this, 'get_user' ),
                'permission_callback' => array( $this, 'get_users_permissions_check' ),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'  => array( $this, 'edit_user' ),
                'permission_callback' => array( $this, 'get_users_permissions_check' ),
            ),
            // Register our schema callback.
            //  'schema' => array( $this, 'get_item_schema' ),
        ) );
    }

  /**
  * Check permissions for the posts.
  *
  * @param WP_REST_Request $request Current request.
  */
 public function get_users_permissions_check( $request ) {
  if ( ! current_user_can( 'read' ) ) {
      return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view the post resource.' ), array( 'status' => $this->authorization_status_code() ) );
  }
  return true;
  }

  // Get all the Students And Teachers
  function get_users( $request ) {
    if( isset($request['s'])){
      $search_string = $request['s'];
      $args  =  array ( 
        'blog_id'      => $GLOBALS['blog_id'],
        'role__in'      => ['student','docent'],
        'meta_query' => array(
          'relation' => 'OR',
          array(
              'key'     => 'first_name',
              'value'   => $search_string,
              'compare' => 'LIKE'
          ),
          array(
              'key'     => 'last_name',
              'value'   => $search_string,
              'compare' => 'LIKE'
          )
        )
      );
    } else {
      $limit = intval($request['limit']);
      $offset = intval($request['offset']) * $limit;
      $args = array(
        'blog_id'      => $GLOBALS['blog_id'],
        'role__in'      => ['student','docent'],
        'orderby' 		 => 'ID',
        'order'        => 'DESC',
        'number'      => $limit,
        'offset'      => $offset
      ); 
    }
    $query = get_users( $args );
    $result = count_users();
		$users=[];
		foreach( $query as $user ){
		 	$groups_user = new Groups_User( $user->ID );
            $user_groups = $groups_user->groups;
            array_shift($user_groups);
			$subscriptions = getStudentSubscriptions( $user->ID) ;
			$usermeta = get_userdata( $user->ID );
			$userData = [
				'id'=> $user->ID,
				'user_registered'=> $user->user_registered,
				'role'=> $user->roles[0],
				'user_login'=> $user->user_login,
			    'email'=> $user->user_email,
				'firstname'=> $usermeta->first_name,
				'display_name'=> $user->display_name,
				'lastname'=> $usermeta->last_name,
				'groups'=> $user_groups,
				'subscriptions'=> $subscriptions
			];
			 array_push( $users ,$userData );
		}
        $obj =[
            'users' => $users,
            'success' => true,
            'total' => $result['total_users']
        ];
        return $obj;
    }

    // Get a Student or Teacher
    function get_user( $request ) {
        $user_id = intval( sanitize_text_field($request['id']));
        $subscriptions = getStudentSubscriptions( $user_id);
        $user =  get_user_by('id', $user_id );
        $groups_user = new Groups_User( $user->ID );
        $user_groups = $groups_user->groups;
        array_shift($user_groups);
        $subscriptions = getStudentSubscriptions( $user->ID) ;
        $usermeta = get_userdata( $user->ID );
        $userData = [
            'id'=> $user->ID,
            'user_registered'=> $user->user_registered,
            'role'=> $user->roles[0],
            'user_login'=> $user->user_login,
            'email'=> $user->user_email,
            'firstname'=> $usermeta->first_name,
            'display_name'=> $user->display_name,
            'lastname'=> $usermeta->last_name,
            'groups'=> $user_groups,
            'subscriptions'=> $subscriptions
        ];
        $obj =[
            'user' => $userData,
            'success' => true
        ];
        return $obj;
    }

    function edit_user( $request ){
      $user_id = intval( sanitize_text_field($request['id']));
      if($user_id !== 0 ){
        $type = sanitize_text_field($request['type']);
        // $subscriptions = getStudentSubscriptions( $user_id);
        if( $type ){
          switch( $type ) {
            case "1" : // remove from group
              $obj =[
                'status' => 200,
                'status' => 'Gebruiker verwijderd van groep',
              ];
              break;
            case "2" : // Inschrijving op non-actief gezet
              $obj =[
                  'status' => 200,
                  'status' => 'Inschrijving gedeactiveerd',
                ];
              break;
            default :
            $obj =[
                  'status' => 200,
                  'status' => 'Default status',
              ];
              break;
          }
          return $obj;
        }
      } else {
        $obj =[
            'status' => 403,
            'error' => 'Gebruiker onbekend',
        ];
        return $obj;
      }
    }

    function set_user($request) {
        if($request['action'] == 'registerByCode'){
            $username = generateRandomString();
            $user_id = username_exists( $username );
            if( $request['role'] == true){
                $user_email = sanitize_text_field( $request['email'] );
            } else {
                $user_email =  $username.'@house-of-training.nl';
            }
           
            if ( !$user_id and email_exists($user_email) == false ) {
                $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
                $role = sanitize_text_field( $request['role'] );
                $meta_value = sanitize_text_field($request['code']);
                $first_name = sanitize_text_field($request['firstName']);
                $last_name = sanitize_text_field($request['lastName']);
                $userRole = 'student';
                if($role == true) {
                    $userRole = 'docent';
                    $random_password = sanitize_text_field($request['password']);
                }
                $userData = array(
                    'user_login'  	=>  $username,
                    'user_email'    =>  $user_email,
                    'user_pass'  		=>  $random_password,
                    'role'					=>  $userRole,
                    'display_name' 	=> 	$meta_value,
                    'first_name'		=> 	$first_name,
                    'last_name'			=> 	$last_name,
                );
                $user_id = wp_insert_user( $userData );
                $groupID = intval($request['group']);
            
                Groups_User_Group::create( array( 'user_id' => 	$user_id, 'group_id' => $groupID ) );
                $option = 'group_'.$groupID;
                $courses = get_option($option);
                $subscriptions=[];
                if($courses){
                    $subscriptions = createUserSubscription( $user_id, $courses);
                }

                add_user_meta( $user_id, "token", $meta_value ); 
                $user = get_user_by( 'ID', $user_id );
                // $session = generate_HOT_student_token( $user);
                $session = 1;
                $obj =[
                    'status' => 'USER_CREATED',
                    'user' => $user,
                    'subscriptions' => $subscriptions,
                    'session' => $session
                ];
                return $obj;
            } else if( email_exists($user_email) == true ){
                $user = get_user_by( 'email', $user_email );
                // $session = generate_HOT_student_token( $user);
                $session = 1;
                $obj =[
                    'status' => 'USER_ALREADY_EXISTS',
                    'user' => $user,
                    'subscriptions' => $subscriptions,
                    'session' => $session
                ];
                return $obj;
            }

        } else if ( $request['action'] == 'removeFromGroup' ){
            $user_id = intval( $request['userid'] );
            $group_id = intval( $request['groupid'] );
            if( $user_id && $group_id ){
                 if( Groups_User_Group::delete( $user_id,  $groupID ) ){
                    $obj =[
                        'status' => 'Gebruiker verwijderd uit groep'
                    ];
                 };
                 $obj =[
                    'status' => 403,
                    'error' => 'Gebruiker kan niet uit de groep worden verwijderd',
                ];
                return $obj;
            }
            $obj =[
                'status' => 403,
                'error' => 'Onvoldoende data om gebruiker uit groep te verwijderen',
            ];
            return $obj;

        }
        $obj =[
            'status' => 403,
            'error' => 'Een actie verwacht',
        ];
        return $obj;
    }

     // Sets up the proper HTTP status code for authorization.
    public function authorization_status_code() {

        $status = 401;

        if ( is_user_logged_in() ) {
            $status = 403;
        }

        return $status;
    }

    

    // Helper Functions

    
}

function getStudentSubscriptions( $user_id) {
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
            $active = get_field('actief' , $value->ID );
            $subObj = array(
                'id' => $value->ID,
                'code' => $value->post_title,
                'subscriptionmeta' => $meta,
                'active' => $active
            );
        
            array_push( $subscriptions , $subObj );
        }		
        return $subscriptions;
}

// Generate Token

/**
 * Get the user and password in the request body and generate a JWT
 *
 * @param object $request a WP REST request object
 * @since 1.0
 * @return mixed Either a WP_Error or current user data.
 */
function generate_HOT_student_token( $user ) {
    $secret_key = Simple_Jwt_Authentication_Api::get_key();
    $username   = $user->username;
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
    $user = get_user_by('login', $username);

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
                'id' => $user,
            ),
        ),
    );

    // Let the user modify the token data before the sign.
    $token = JWT::encode( apply_filters( 'jwt_auth_token_before_sign', $token, $user ), $secret_key );

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
        'user_email'        => $user->data->user_email,
        'user_nicename'     => $user->data->user_nicename,
        'user_display_name' => $user->data->display_name,
        'token_expires'     => $expire,
    );

    // Let the user modify the data before send it back.
    return apply_filters( 'jwt_auth_token_before_dispatch', $data, $user );
}
    
function createUserSubscription( $user_id, $course_id){
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
    update_field( 'actief', 0, $post_id );
    // foreach ($courses as $course) {
    //     $row = array(
    //         'cursus_id' => $course,
    //         'eind_datum'=> date('Y-m-d',strtotime(date("Y-m-d", time()) . " + 365 day"))
    //     );
    //     add_row('cursussen', $row , $post_id );
            
    // }
    
    $subscription = array(
        'subscription' => get_post($post_id),
        'subscriptionMeta' => get_post_meta( $post_id )
    );
    return $subscription;
}

// Function to register our new routes from the controller.
function HOT_register_students_routes() {
 $controller = new HOT_STUDENTS_Controller();
 $controller->register_routes();
}

add_action( 'rest_api_init', 'HOT_register_students_routes' );
