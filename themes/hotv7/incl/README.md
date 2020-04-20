# REST API  
## groups-controller.php  

### routes:
GET / **-> get_groups**: get all groups from school  
*returns*:  
**'success'** => true,  
**'data'** => groups : { "id","group","courses","total_users","users"}
  
GET /{userID} -> **get_HOT_user_groups_by_id** get groups by userID  

POST /{userID} -> **create_group** 
additional parameters:  
**groupname**{ text},  
**description** {text },  
**creator_id** { userID }  
**courses** { comma seperated id's }  

POST /update -> **update_group**  
additional parameters:  
**name**{ text},  
**description** {text },  
**group_id** { groupID }  
**courses** { comma seperated id's }  
