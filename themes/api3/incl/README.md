
# REST API #

Alle php bestanden in deze map zijn REST

## courses.php ##

### hot/v2/courses ###

*Deze beheert de cursussen. Hiervoor zijn 3 speciale tabellen aangemaakt: {prefix}_courses, {prefix}_chapters en {prefix}_videos.*

### routes ###

**GET** / **-> getAPICourses**: get all courses  

*returns*:  
**'success'** => true,  
**'data'** => { JSON } // alle cursus objecten zonder hoofdstukken en video's  

**GET** /{id} **-> getCourse**: get course by id  

required parameters:  
**'id'** {id}

*returns*:  
**'id'** => id  
**'count'** => { 0 }  
**'description'** => {text}  
**'name'** => {text}  
**'slug'** => {text}  
**'term_order'** => { 0 }  
**'course_id'** => {text}  
**'acf'** => { json }  
**'chapters'** => { json}  

**POST** /{id} -> **create_course**:  

required parameters:  
**'id'** -> id  
**'total'** -> { 0}  
**'meta'** -> { json }

*returns*:  

## schools.php ##

### hot/v2/schools ###

*Deze beheert de scholen. In de beheer omgeving kunnen alle scholen en hun klassen (groups) worden opgevraagd*

#### routes ####

**GET** / -> getSchools *// gets all schools*  

*returns*:
**'data'** => array (
>**'blogname'** => { text }  
**'blog_id'** => { text }  
**'viewed'** => { 0 }  

)  // end of array

**'viewed'** => { 0 } // total of all schools

**GET** /{id} *// getSchool* // retrieves all school groups

*returns*:  
**'success'** => true  
**'data'** => array(  
>**'id'** = { text }  
**'group'** = { object}  
**'courses'** = {text} // commas seperated list of courseID's  
**'total_users'** = { 0 }  
**'users'**  = { text } // commas seperated list of userID's

)  
**'total'** => { 0 }

**GET** /{schoolId}/{groupID} -> *getGroup* // gets the group of a certain school

*returns*:  
 **'success'** => true  
**'data'** => array ( 
>**'ID'** => { text }  
**'roles'** => { text }  
**'viewed'** => { 0 }  
**'total'** => { 0 }  

)  // end of array  
**'courses'** =>  { text } // commas seperated list of courseID's
