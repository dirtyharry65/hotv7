# REST API  
Alle {naam}-controller.php zijn REST, alle andere bestanden zijn legacy en worden verwijdered in volgende versie.
## groups-controller.php  

### routes:
GET / **-> get_groups**: get all groups from school  
*returns*:  
**'success'** => true,  
**'data'** => groups (array) : { "id","group","courses","total_users","users"}
  
GET /users/{userID} -> **get_HOT_user_groups_by_id** get groups by userID  
*returns*:  
**'success'** => true,  
**'data'** => groups (array) \* *only 1 group result, rest will be ignored* : { "id","group","courses","total_users","users"} 

POST /users/{userID} -> **add_to_group** add a user to a new group  
required parameters:  
**groupname**{ text},  
**description** {text },  
**creator_id** { userID }  
**courses** { comma seperated id's }  
*returns*:  
**'success'** => true,  
**'status'** => 'USER_ADDED_TO_GROUP',  
**'data'** => groups (array) 

POST /{userID} -> **create_group**  
required parameters:  
**groupname**{ text},  
**description** {text },  
**creator_id** { userID }  
**courses** { comma seperated id's }  

*returns*:  
**'status'** => 'GROUP_ADDED',  
**'group_id'** =>group_id,  
**'groups'** => groups 

POST /update -> **update_group**  
required parameters:  
**name**{ text},  
**description** {text },  
**id** { group_id }  
**courses** { comma seperated id's }  

*returns*:  
**'status'** => 'GROUP_UPDATED',  
**'group_id'** =>group_id,  
**'groups'** => groups  
## NOTE: ##

**remove from group and delete group is not implemented: this is done in WP-admin**

