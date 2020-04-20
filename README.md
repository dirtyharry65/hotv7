# House of Training API
*Wp-content inhoud van de API's*  
De thema's HOT v7 is de meeste recente, HOT v6 is inbegrepen omdat oudere lopende scholen nog gebruik maken van dit theme
De Plugins zijn behalve de groups en user-progress niet relevant  
## Themes  
### api3  
API3 is huidige thema voor de Root ( https://api3.house-of-training.nl)  
**/incl** bevat de belangrijkste REST calls.  
### hot v7  
HOT v7 is huidige Thema voor alle Scholen.  
( https://api3.house-of-training.nl/*{school}* )   
**/incl** bevat de belangrijkste REST Calls, allen genaamd met de achtervoeging: {naam}-controller.php.  
de andere bestanden zijn legacy en zullen tzt. verdwijnen. 
het zou kunnen dat er nog een aantal sub-functies aangeroepen in deze bestanden.
## Plugins
**user-progress**: wordt gebruikt om de voortgang te registreren, deze plugin is zelfgemaakt.  
 **simple-jwt-authentication**: voor het genereren van JWT tokens  
 **groups**: groepsbeheer, deze wordt intensief gebruikt.
