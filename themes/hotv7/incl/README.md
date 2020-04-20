# REST API  
## groups-controller.php  

### routes:
GET / **-> get_groups**: get all groups from school  
*returns*:  
**'success'** => true,  
**'data'** => groups (array) : { "id","group","courses","total_users","users"}
  
GET /{userID} -> **get_HOT_user_groups_by_id** get groups by userID  
*returns*:  
**'success'** => true,  
**'data'** => groups (array) \* *only first will be read in frontend, rest will be ignored* : { "id","group","courses","total_users","users"} 

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
**'status'** => 'GROUP_ADDED',  
**'group_id'** =>group_id,  
**'groups'** => groups  
