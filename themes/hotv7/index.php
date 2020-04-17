<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package WordPress
 * @subpackage hotv5
 * @since 1.0.0
 */

//$SECRET_KEY= wp_create_nonce('syntra_rtk3ts7c');
$SECRET_KEY= 'oqS5ouTP0mA.Zot8w9uEULd8c5AzOe2RK8F.QB6PBPVi50pjh0';
$url =  get_site_url();
$domain = array_slice(explode('/', $url), -1)[0];
// if( $domain == undefined ){
//   $domain='/';
// }

?><!doctype html>
<html <?php language_attributes(); ?> >
  <head>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no" />
    <?php 
    // wp_head(); 
    ?>
    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Open+Sans:400,600,700|Roboto+Slab:100,400,700&display=swap"/>

    <title>Cursusssen</title>
  </head>
  <body >
       
    <?php 
    wp_footer(); 
    ?>
  </body>
</html>
