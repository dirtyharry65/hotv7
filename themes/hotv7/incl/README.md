# REST API  
Alle {naam}-controller.php zijn REST, alle andere bestanden zijn legacy en worden verwijdered in volgende versie.
## groups-controller.php  
*Deze beheert de groepen ( klassen ). Alle studenten zitten in een klas, naar aanleiding van deze klas kunnen zij bepaalde cursussen bekijken.
Studenten kunnen maar in 1 klas zitten. Docenten kunnen in meerdere klassen zitten.* 
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
## PROGRESS-CONTROLLER.PHP ##  
*Deze beheert de voortgang van de studenten en docenten. In de meta staat in json de handelingen die de student heeft gehad met de player.*

GET /user/{userID} -> **get_user_progress_by_id**: gets the users  progress  
required parameters:   
**id** { user_id }  
**limit** 100  
**offset** 0  
**(direction)** { ASC / DESC }  
**(sortby)** { 'last_viewed' }  

*returns*:  
**'success'** => true,  
**'id'** =>id,  
**'limit'** => 100  
**'offset'** => 0  
**'total'** => total  
**'data'** => Object [  
  >**'video_id'** =>video_id,  
**'course_id'** =>course_id,  
**'videoTitle'** =>videoTitle,  
**'user_id'** => id  
**'last_viewed'** => date  
**'startdate'** => date  
**'views'** => 1  
**'perc_viewed'** => 0  
**'meta'** => meta  
  
] **// end of object**  
**'trophies'** => trophies  

 
 GET /course/{userID} -> **get_user_progress_by_course**: gets the users progress per course  

*returns*:  
**'success'** => true,  
**'id'** =>id,  
**'courseid'** =>course_id,  
**'limit'** => 100  
**'offset'** => 0  
**'total'** => total  
**'data'** => data  
**'trophies'** => trophies  

POST /{id} -> **create_progress** : register the progress of user  

required parameters:  
**id** { id }  
**videoTitle**{ text},  
**video_id** { text},  
**course_id** { text},  
**perc_viewed** { text},  
**meta** { json},  
**update** { boolean },  


*returns*:  
**'success'** => true,  
**'data'** => Object, [  
**('update')** => boolean // only if update is set  
>**'video_id'** =>video_id,  
**'course_id'** =>course_id,  
**'videoTitle'** =>videoTitle,  
**'user_id'** => id  
**'last_viewed'** => date  
**'startdate'** => date  
**'views'** => 1  
**'perc_viewed'** => 0  
**'meta'** => meta  

] **// end of data object**
