<?php
/*
Plugin Name: Allow CORS XML-RPC
Plugin URI: https://github.com/j801/allow-cors-xml-rpc
Description: This plugin Allow CORS for XML-RPC API in WordPress 3.5+.
Version: 0.1
Author: YUKI OKAMOTO (HN:Justice)
Author URI: http://press.monaca.mobi/
Contributors: yuki-okamoto
Stable tag: trunk
Tags: cors,xml-rpc,http header,Access-Control-Allow-Origin,Access-Control-Allow-Headers
Requires at least: 3.6.0
Tested up to: 3.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if (defined('XMLRPC_REQUEST')) {
  add_action('init', 'allow_cors_xml_rpc');
}

function allow_cors_xml_rpc()
{
  header('Access-Control-Allow-Origin: *'); 
  header('Access-Control-Allow-Headers: Content-Type'); 
  return true;
}
?>
