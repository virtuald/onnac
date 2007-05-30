<?php
/*
* $Id$
*
* Copyright (c) 2006-2007, Dustin Spicuzza
* All rights reserved.
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*
*     * Redistributions of source code must retain the above copyright
*       notice, this list of conditions and the following disclaimer.
*     * Redistributions in binary form must reproduce the above copyright
*       notice, this list of conditions and the following disclaimer in the
*       documentation and/or other materials provided with the distribution.
*     * Neither the name of Dustin Spicuzza nor the
*       names of any contributors may be used to endorse or promote products
*       derived from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY DUSTIN SPICUZZA AND CONTRIBUTORS ``AS IS'' AND ANY
* EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
* DISCLAIMED. IN NO EVENT SHALL DUSTIN SPICUZZA AND CONTRIBUTORS BE LIABLE FOR ANY
* DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

	Page sitting at the root directory, does initial setup and final cleanup

*/


// start by measuring the execution time
global $execution_time;
list($usec, $sec) = explode(" ", microtime());
$execution_time = ((float)$usec + (float)$sec);

// this is *really* inefficient, so you should disable magic quotes somewhere else. 
// However, in case you forget, this is here. Also, if you're running php from CGI 
// instead of an apache module and you cant change it... :(
// 
// Code is from the PHP manual
if (get_magic_quotes_gpc()) {
   function stripslashes_deep($value)
   {
       $value = is_array($value) ?
                   array_map('stripslashes_deep', $value) :
                   stripslashes($value);

       return $value;
   }

   $_POST = array_map('stripslashes_deep', $_POST);
   $_GET = array_map('stripslashes_deep', $_GET);
   $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
   $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
}

// include this first
require_once ("./include/render.inc.php");		// rendering engine

// use output buffering to allow us to output headers whenever needed, and to set Content-Length, among other things
// (e.g... we can be really lazy :) )
ob_start("output_callback");

unset($cfg);
require_once("./include/default.inc.php");

if ((@include './include/config.inc.php') != 1){
	echo "<html><title>Onnac Not Installed</title><body>Onnac has not been properly installed! Please create an .htaccess file and a config.inc.php file using the <a href=\"install/install.php\">installer</a>!</body></html>";
	die;
}

require_once ("./include/db.inc.php");			// wrapper functions for db

// detect whether mod_rewrite was enabled correctly!
if (!isset($_GET['url'])){

	show_internal_error("#00, mod_rewrite is not enabled correctly! Onnac does not work correctly unless mod_rewrite or its equivalent is enabled.");
	
	ob_end_flush();
	die;
}


// Lets make a connection to the database here
$dbCon = db_connect($cfg['db_host'],$cfg['db_user'],$cfg['db_pass'],$cfg['db_name']);
if (!$dbCon){
	
	// no connection, show internal error!
	show_internal_error("#01, The server could not connect to the database. Please try again later, thanks!");

}else{

	$input_url = $_GET['url'];

	// add support for SSL here
	if ($cfg['use_ssl'] && isset($_SERVER['HTTPS']))
		$cfg['rootURL'] = str_replace('http://','https://',$cfg['rootURL']);

	// configure a base directory (windows compatible)
	$cfg['basedir'] = str_replace('\\','/',dirname(__FILE__));
		
	// nasty hack -- but only way to resolve nesting in directories... works in windows too!
	if (stristr($input_url,$cfg['basedir']) || substr($input_url,0,2) == "//")
		$input_url = "";
	
	// render the page
	render_page($input_url);
	
	// close db connection
	db_close($dbCon);
}

// finally, flush the output buffer -- is this really needed? Honestly?
ob_end_flush();

?>
