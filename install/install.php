<?php
/*
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
* DISCLAIMED. IN NO EVENT SHALL THE REGENTS AND CONTRIBUTORS BE LIABLE FOR ANY
* DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

	Really simple installer script

*/

require_once('../include/util.inc.php');

$import_filename = "default/install.osf";

// ok, lets get all the post variables submitted by the form
$db_type = get_post_var('db_type');
$db_table_type = get_post_var('db_table_type');
$db_host = get_post_var('db_host');
$db_user = get_post_var('db_user');
$db_pass = get_post_var('db_pass');
$db_name = get_post_var('db_name');
$db_database = get_post_var('db_database');
$db_prefix = get_post_var('db_prefix');

$banner_directory = get_post_var('banner_directory');
$root_url = get_post_var('root_url');

$username = get_post_var('username');
$password = get_post_var('password');
$cpassword = get_post_var('cpassword');

$enable_transactions = get_post_var('enable_transactions');

// Used later
$now = gmdate('D, d M Y H:i:s') . ' GMT';

// General header for no caching
header('Expires: ' . $now); // rfc2616 - Section 14.21
header('Last-Modified: ' . $now);
header('Cache-Control: no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0'); // HTTP/1.1
header('Pragma: no-cache');

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>Onnac Installer script</title>
	<link rel="stylesheet" type="text/css" href="../interface/admin.css" />
</head>
<body><?php

$is_sql_error = 0;
$success = 0;

if ($db_type != ""){
	$validated = 1;
	
	if ($db_type != "mysql" && $db_type != "postgre"){
		echo "<p><strong>Error</strong>: Invalid DB type!</p>";
		$validated = 0;
	}
	
	if ($enable_transactions != "true" && $enable_transactions != "false"){
		echo "<p><strong>Error</strong>: Invalid response to 'enable transactions'";
		$validated = 0;
	}
	
	if ($db_type == "mysql" && $db_table_type != "INNODB" && $db_table_type != "BDB" && $db_table_type != "MYISAM"){
		echo "<p><strong>Error</strong>: Invalid table type specified!";
		$validated = 0;
	}
	
	if ($db_host == ""){
		echo "<p><strong>Error</strong>: Must specify a DB host!</p>";
		$validated = 0;
	}
	
	if ($db_user == ""){
		echo "<p><strong>Error</strong>: Must specify a DB username!</p>";
		$validated = 0;
	}
	
	if ($db_database == ""){
		echo "<p><strong>Error</strong>: Must specify a database!</p>";
		$validated = 0;
	}

	if ($db_prefix == ""){
		echo "<p><strong>Error</strong>: Must specify a table prefix!</p>";
		$validated = 0;
	}
	
	if ($banner_directory == ""){
		echo "<p><strong>Error</strong>: Banner Directory must be specified!</p>";
		$validated = 0;
	}else if (!file_exists("..$banner_directory")){
		echo "<p><strong>Error</strong>: Banner directory does not exist!";
		$validated = 0;
	}
	
	if ($root_url == ""){
		echo "<p><strong>Error</strong>: Empty root URL!";
		$validated = 0;
	}else if ($root_url{strlen($root_url)-1} == '/'){
		echo "<p><strong>Error</strong>: RootURL cannot have a trailing /!</p>";
		$validated = 0;
	}
	
	if (strlen($username) < 5){
		echo "<p><strong>Error</strong>: Username should be greater than 5 characters long</p>";
		$validated = 0;
	}
	
	if ($password == "" || ($cpassword != $password)){
		echo "<p><strong>Error</strong>: Passwords must match!</p>";
		$validated = 0;
	}else if (strlen($password) < 6){
		echo "<p><strong>Error</strong>: Password should be greater than 6 characters!</p>";
		$validated = 0;
	}else if ($password == $username){
		echo "<p><strong>Error</strong>: Password should NOT be the same as the username!</p>";
		$validated = 0;
	}

	if ($validated){
		// do the install here
		
		require_once('../include/default.inc.php');
		
		global $cfg;
		$cfg['db_type'] = $db_type;
		
		if ($enable_transactions == "true")
			$cfg['enable_transactions'] = true;
		else
			$cfg['enable_transactions'] = false;
		
		require_once('../include/db.inc.php');
		require_once('../include/admin/import.inc.php');
	
		$db_prefix = db_escape_string($db_prefix);
	
		$is_sql_error = 1;

		// test db connection
		$dbCon = db_connect($db_host, $db_user, $db_pass, $db_database);
		
		if (!$dbCon){
			echo "<p><strong>Error</strong>: Could not connect to database, error: <em>" . htmlentities(db_error()) . "</em></p>";
			$validated = 0;
		}
	}
	
	/*
		TODO: Need to optimize the tables, mysql complains that SERIAL PRIMARY KEY makes two indexes, which
		apparently is a *bad* thing. Go figure. 
	
		Need better idea of how postgre support should go as well. 
	*/
	if ($validated)
		echo "<p>Begin DB Creation...";

$indexes = array();

$q_content = 
"CREATE TABLE " . $db_prefix . "content (
	url_hash 		varchar(32) NOT NULL default '' PRIMARY KEY,
	url 			varchar(255) NOT NULL,
	page_execute 	integer NOT NULL default '0',
	hidden			integer NOT NULL default '0',
	page_title 		varchar(255) NOT NULL,
	banner_id 		integer NOT NULL default '-1',
	template_id 	integer NOT NULL default '-1',
	menu_id 		integer NOT NULL default '-1',
	visited_count 	integer NOT NULL default '0',
	page_content 	text NOT NULL,
	last_update 	timestamp NOT NULL default NOW(),
	other_update	timestamp NOT NULL,
	last_visit 		timestamp NOT NULL,
	last_update_by 	varchar(255) NOT NULL
)";

$q_banners = 
"CREATE TABLE " . $db_prefix . "banners (
	banner_id SERIAL NOT NULL PRIMARY KEY,
	name varchar(255) NOT NULL
)";

$q_banner_groups = 
"CREATE TABLE " . $db_prefix . "banner_groups (
	banner_id integer NOT NULL default '0',
	item_id integer NOT NULL default '0'
)";

$indexes[] = "CREATE INDEX banner_id ON " . $db_prefix . "banner_groups (banner_id)";
$indexes[] = "CREATE INDEX m_item_id ON " . $db_prefix . "banner_groups (item_id)";

$q_banner_items = 
"CREATE TABLE " . $db_prefix . "banner_items (
	item_id SERIAL NOT NULL PRIMARY KEY,
	src varchar(255) NOT NULL,
	alt varchar(255) NOT NULL
)";

$q_menus =
"CREATE TABLE " . $db_prefix . "menus (
	menu_id 	SERIAL NOT NULL PRIMARY KEY,
	name 		varchar(255) NOT NULL
)";

$q_menu_groups = 
"CREATE TABLE " . $db_prefix . "menu_groups (
	menu_id     integer NOT NULL default '0',
	rank         integer NOT NULL default '0',
	item_id     integer NOT NULL default '0'
)";

$indexes[] = "CREATE INDEX menu_id ON " . $db_prefix . "menu_groups (menu_id)";
$indexes[] = "CREATE INDEX m_item_id ON " . $db_prefix . "menu_groups (item_id)";

$q_menu_items =
"CREATE TABLE " . $db_prefix . "menu_items (
	item_id 	SERIAL NOT NULL PRIMARY KEY,
	text 		varchar(255) NOT NULL,
	href 		varchar(255) NOT NULL
)";

$q_templates = 
"CREATE TABLE " . $db_prefix . "templates (
	template_id 	SERIAL NOT NULL PRIMARY KEY,
	template_name 	varchar(32) NOT NULL UNIQUE default '',
	template 		text NOT NULL,
	last_update 	timestamp NOT NULL default CURRENT_TIMESTAMP,
	last_update_by 	varchar(255) NOT NULL
)";

$q_users =
"CREATE TABLE " . $db_prefix . "users (
	user_id 		SERIAL NOT NULL PRIMARY KEY,
	username 		varchar(32) NOT NULL default '' UNIQUE,
	description		varchar(255) NOT NULL default '',
	hash 			varchar(32) NOT NULL default ''
)";

$q_user_groups =	
"CREATE TABLE " . $db_prefix . "user_groups (
	user_id 		integer NOT NULL default '0',
	group_id 	integer NOT NULL default '0'
)";

$indexes[] = "CREATE INDEX user_id ON " . $db_prefix . "user_groups  (user_id)";
$indexes[] = "CREATE INDEX group_id ON " . $db_prefix . "user_groups  (group_id)";

$q_user_group_names = 
"CREATE TABLE " . $db_prefix . "user_group_names (
	group_id 	SERIAL NOT NULL PRIMARY KEY,
	group_name 	varchar(32) NOT NULL UNIQUE
)";

$q_errors = 
"CREATE TABLE " . $db_prefix . "errors (
	error_id 	SERIAL NOT NULL PRIMARY KEY,
	referer 	varchar(255) NOT NULL,
	ip 			varchar(15) NOT NULL default '',
	url 		text NOT NULL,
	time 		timestamp NOT NULL default CURRENT_TIMESTAMP
)";
		
	// begin transacted installation -- note, MySQL does NOT support transacted table creation
	if ($validated) db_begin_transaction();
	$validated = install_db_create($validated,$q_content,$db_prefix,'content');
	$validated = install_db_create($validated,$q_banners,$db_prefix,'banners');
	$validated = install_db_create($validated,$q_banner_groups,$db_prefix,'banner_groups');	
	$validated = install_db_create($validated,$q_banner_items,$db_prefix,'banner_items');
	$validated = install_db_create($validated,$q_menus,$db_prefix,'menus');
	$validated = install_db_create($validated,$q_menu_groups,$db_prefix,'menu_groups');
	$validated = install_db_create($validated,$q_menu_items,$db_prefix,'menu_items');
	$validated = install_db_create($validated,$q_templates,$db_prefix,'templates');
	$validated = install_db_create($validated,$q_users,$db_prefix,'users');
	$validated = install_db_create($validated,$q_user_groups,$db_prefix,'user_groups');
	$validated = install_db_create($validated,$q_user_group_names,$db_prefix,'user_group_names');
	$validated = install_db_create($validated,$q_errors,$db_prefix,'errors');
	if ($validated) db_commit_transaction();
	// end transaction -- mostly because of MySQL
	
	// begin next transaction
	if ($validated) db_begin_transaction();
	
	// create indexes
	if ($validated) echo "success!</p><p>Creating indexes...";
	foreach($indexes as $query){
		if ($validated){
			if (!db_is_valid_result(db_query($query,true))){
				echo "</p>";
				db_rollback_transaction();
				$validated = 0;
			}
		}
	}
	
	if ($validated) db_commit_transaction();
		
	// create root user
	if ($validated){
		echo "success!</p><p>Creating admin user...";
		
		$result = db_query("INSERT INTO " . $db_prefix . "users (username,hash,description) VALUES ('" . db_escape_string($username) . "','" . md5(db_escape_string($username) . ':' . db_escape_string($password)) . "','Administrator')",true);
		
		if (!db_is_valid_result($result) || db_affected_rows($result) < 1){
			$validated = 0;
			db_rollback_transaction();
			echo "</p>";
		}else{
			// get index of user id
			// 		in some conditions, this MAY fail on postgre.. hopefully it fails now, instead
			//		of later on when they use onnac... so we can fix it :)
			$result = db_get_last_id($db_prefix . "users",'user_id');
			if (db_has_rows($result)){
				$row = db_fetch_row($result);
				$default_user_id = $row[0];
			}else{
				$validated = 0;
				db_rollback_transaction();
				echo "</p>";
			}
		}
	}
		
	// create root group
	if ($validated){
		echo "success!<br/>Creating admin group...";
		
		$result = db_query("INSERT INTO " . $db_prefix . "user_group_names (group_name) VALUES ('root')",true);
		if (!db_is_valid_result($result) || db_affected_rows($result) < 1){
			$validated = 0;
			db_rollback_transaction();
			echo "</p>";
		}else{
			// get index of group
			// 		in some conditions, this MAY fail on postgre.. hopefully it fails now, instead
			//		of later on when they use onnac... so we can fix it :)
			$result = db_get_last_id($db_prefix . "user_group_names",'group_id');
			if (db_has_rows($result)){
				$row = db_fetch_row($result);
				$default_group_id = $row[0];
			}else{
				$validated = 0;
				db_rollback_transaction();
				echo "</p>";
			}
		}
	}
	
	// join the root user to the root group
	if ($validated){
		echo "success!<br/>Joining admin group...";
		
		$result = db_query("INSERT INTO " . $db_prefix . "user_groups (user_id,group_id) VALUES ($default_user_id,$default_group_id)",true);
		
		if (!db_is_valid_result($result) || db_affected_rows($result) < 1){
			$validated = 0;
			db_rollback_transaction();
			echo "</p>";
		}
	}
	

	
	// call the import function to easily import the data
	if ($validated){
	
		echo 'success!</p><p>Importing default data into database...</p><div style="border: 1px dashed #000000;background-color: #eeeeee;width: 30em;padding: 1em;">';
		
		$imported = get_import_data($import_filename);
		
		//echo "<pre>";
		//print_r($imported);
		//echo "</pre>";
		
		if ($imported !== false && $imported['dumptype'] == 'content'){
			if (import_content($imported,true,true) == false)
				$validated = 0;		// import_content will rollback transaction
		}else{
			echo "Error: the import file used is invalid.";
			$validated = 0;
			db_rollback_transaction();
			$is_sql_error = 0;
		}
		
		echo "</div>";
	}
	
	// end transaction
	if ($validated)
		db_commit_transaction();
	
	// create configuration file
	if ($validated){
		
		echo "<p>Creating config file...";
		$config_str = "<?php\n/*\n\tPlace configuration data here\n*/\n\n";
		$config_str .= '$cfg[\'db_type\']' . "\t\t\t\t= \"" . $db_type . "\";\t\t\t// Database type\n";
		$config_str .= '$cfg[\'db_host\']' . "\t\t\t\t= \"" . $db_host . "\";\t\t\t// DB hostname\n";
		$config_str .= '$cfg[\'db_user\']' . "\t\t\t\t= \"" . $db_user . "\";\t\t\t// DB user\n";
		$config_str .= '$cfg[\'db_pass\']' . "\t\t\t\t= \"" . $db_pass . "\";\t\t\t// DB password\n";
		$config_str .= '$cfg[\'db_name\']' . "\t\t\t\t= \"" . $db_database . "\";\t\t\t// Database name\n";
		$config_str .= '$cfg[\'enable_transactions\']' . ' = ' . $cfg['enable_transactions'] . ";\t\t\t// transactions\n";
		
		$config_str .= "\n\n// tables where items are located\n";
		$config_str .= '$cfg[\'t_banners\']' . "\t\t\t= \"" . $db_prefix . "banners\";\t\t// banner image group names\n";
		$config_str .= '$cfg[\'t_banner_groups\']' . "\t\t\t= \"" . $db_prefix . "banner_groups\";\t// banner image groups\n";
		$config_str .= '$cfg[\'t_banner_items\']' . "\t\t\t= \"" . $db_prefix . "banner_items\";\t// banner image group names\n";
		$config_str .= "\n";
		$config_str .= '$cfg[\'t_menus\']' . "\t\t\t\t= \"" . $db_prefix . "menus\";\t\t// menu group names\n";
		$config_str .= '$cfg[\'t_menu_groups\']' . "\t\t\t= \"" . $db_prefix . "menu_groups\";\t// menu groups\n";
		$config_str .= '$cfg[\'t_menu_items\']' . "\t\t\t= \"" . $db_prefix . "menu_items\";\t\t// menu items\n";
		$config_str .= "\n";
		$config_str .= '$cfg[\'t_content\']' . "\t\t\t= \"" . $db_prefix . "content\";\t\t// content\n";
		$config_str .= '$cfg[\'t_templates\']' . "\t\t\t= \"" . $db_prefix . "templates\";\t\t// templates\n";
		$config_str .= "\n";
		$config_str .= '$cfg[\'t_errors\']' . "\t\t\t= \"" . $db_prefix . "errors\";\t\t// anytime a 404 happens :)\n";
		$config_str .= "\n";
		$config_str .= '$cfg[\'t_users\']' . "\t\t\t\t= \"" . $db_prefix . "users\";\t\t// users\n";
		$config_str .= '$cfg[\'t_user_groups\']' . "\t\t\t= \"" . $db_prefix . "user_groups\";\t\t// user groups\n";
		$config_str .= '$cfg[\'t_user_group_names\']' . "\t\t= \"" . $db_prefix . "user_group_names\";\t// user group names\n";
		$config_str .= "\n";
		$config_str .= "//special directories\n";
		$config_str .= '$cfg[\'img_autofill_dir\']' . "\t\t= \"" . $banner_directory . "\";\t\t// location of banners\n";
		$config_str .= "\n";
		$config_str .= "// rootURL does NOT have an ending / on it\n";
		$config_str .= '$cfg[\'rootURL\']' . "\t\t\t\t= \"" . $root_url . "\";\n";
		$config_str .= "\n";
		$config_str .= "// error*page is an input_url that is stored in the database\n";
		$config_str .= '$cfg[\'page_403\']' . "\t\t\t= \"/error403.html\";\n";
		$config_str .= '$cfg[\'page_404\']' . "\t\t\t= \"/error404.html\";\n";
		$config_str .= "\n";
		$config_str .= "\n?>";
	
		if (!write_file_contents("config.inc.php",$config_str)){
			$is_sql_error = 0;
			echo "error.</p><p>Please create the contents (shown below) of config.inc.php in the include directory!<br/>";
		}else{
			echo "success! <strong>Please move config.inc.php into the include directory.</strong><br/>";
		}
	}
	
		
	if ($validated){
		
		echo "Creating .htaccess...";
		
		$r_dir = "";
		$ua = parse_url($root_url);
		if (array_key_exists("path",$ua))
			$r_dir = $ua['path'];
		
		// create .htaccess files
		$htaccess =  "ErrorDocument 403 $r_dir/error403.html\n";
		$htaccess .= "ErrorDocument 404 $r_dir/error404.html\n\n";
		$htaccess .= "#Rewrite engine\n";
		$htaccess .= "<IfModule mod_rewrite.c>\n";
		$htaccess .= "RewriteEngine On\n";
		$htaccess .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
		$htaccess .= "RewriteRule ^(.*) $r_dir/index.php?url=/$1 [QSA,NS]\n";
		$htaccess .= "</IfModule>\n\n";
		$htaccess .= "# Uncomment this if PHP is running as an apache module and you dont already have it disabled\n";
		$htaccess .= "# php_flag magic_quotes_gpc off\n";
		$htaccess .= "# register_globals off\n";
		
		if (!write_file_contents(".htaccess",$htaccess)){
			$is_sql_error = 0;
			echo "error.</p><p>Please create the contents (shown below) of .htaccess in the root directory of the CMS.<br/>";
		}else{
			echo "success! <strong>Please move .htaccess into the root directory of the CMS.</strong><br/>";
		}
	}
	
	if ($validated){
		echo "<h4>Contents of /.htaccess</h4><pre style=\"border: 1px dashed #000000;padding:0.5em\">" . htmlentities($htaccess) . "</pre>";
		
		echo "<h4>Contents of /include/config.inc.php</h4><pre style=\"border: 1px dashed #000000;padding:0.5em\">" . htmlentities($config_str) . "</pre>";
		$success = 1;
	}
	
	// no longer needed, and doesn't work anyways if the transaction gets rolled back!
	//if (!$validated && $is_sql_error){
	//	echo "<h4>SQL Error Message:</h4><pre>" . htmlentities(db_error()) . "</pre>";
	//}
}

if (!$success){

// default values
$db_type == "" ? $db_type = "mysql" : 1;
$db_prefix == "" ? $db_prefix = "onnac_" : 1;
$db_host == "" ? $db_host = "localhost" : 1;
$banner_directory == "" ? $banner_directory = "/img/banners": 1 ;
$root_url == "" ? $root_url = "http://" . $_SERVER['HTTP_HOST'] . str_replace('/install/install.php','',$_SERVER['SCRIPT_NAME'])  : 1;

?>
<h4>Onnac installer script</h4>
<p>Fill out the information below to generate an appropriate config file, and
to create the necessary database entries. Delete this directory after use.</p>
<form action="install.php" method="post">
<table>

<tr><td><em>Name</em></td><td><em>Value</em></td><td><em>Description</em></td></tr>

<tr><td colspan="3">&nbsp;</td></tr>
<tr><td colspan="3"><strong>Database</strong></td></tr>		
<tr><td>Type</td><td><input name="db_type" size="25" value="<?php echo $db_type; ?>" /></td><td>Database type (mysql or postgre)</td></tr>
<tr><td>Table Type</td><td>
<select name="db_table_type"><?php

	foreach (array('INNODB','MYISAM','BDB') as $type){
		echo "<option value=\"$type\" ";
		if ($db_table_type == $type) echo "selected";
		echo " >$type</option>";
	}
?></select></td><td>Table type (mysql only, ignored for Postgre)</td></tr>
<tr><td>Transactions Enabled</td><td><select name="enable_transactions"><option value="true" <?php if ($enable_transactions == "true") echo 'selected'; ?> >Yes</option><option value="false" <?php if ($enable_transactions == "false") echo 'selected'; ?>>No</option></select></td><td>You should enable this for Postgre or MySQL with InnoDB tables</td></tr>
<tr><td>Host</td><td><input name="db_host" size="25" value="<?php echo $db_host; ?>" /></td><td>Database host address</td></tr>
<tr><td>User</td><td><input name="db_user" size="25" value="<?php echo $db_user; ?>" /></td><td>Username to access DB</td></tr>
<tr><td>Password</td><td><input type="password" name="db_pass" size="25" value="<?php echo $db_pass; ?>" /></td><td>Password</td></tr>
<tr><td>Database</td><td><input name="db_database" size="25" value="<?php echo $db_database; ?>" /></td><td>Database Name</td></tr>
<tr><td>Prefix</td><td><input name="db_prefix" size="25" value="<?php echo $db_prefix; ?>" /></td><td>Prefix of each table to be installed into the database.</td></tr>

<tr><td colspan="3">&nbsp;</td></tr>
<tr><td colspan="3"><strong>Information</strong></td></tr>
<tr><td>Banner Image Directory</td><td><input name="banner_directory" size="25" value="<?php echo $banner_directory; ?>" /></td><td>Directory where banner images are stored (must have leading /)</td></tr>
<tr><td>RootURL</td><td><input name="root_url" size="25" value="<?php echo $root_url; ?>" /></td><td>Root URL of website to be managed (without ending /)</td></tr>

<tr><td colspan="3">&nbsp;</td></tr>
<tr><td colspan="3"><strong>Access</strong></td></tr>
<tr><td>Administrative User</td><td><input name="username" size="25" value="<?php echo $username; ?>" /></td><td>Username</td></tr>
<tr><td>Password</td><td><input type="password" name="password" size="25" value="<?php echo $password; ?>" /></td><td>Password</td></tr>
<tr><td>Confirm Password</td><td><input type="password" name="cpassword" size="25" value="<?php echo $cpassword; ?>" /></td><td>Confirm Password</td></tr>

</table>
<input type="submit" value="Begin Install"/>
</form>

<?php
}else{
	echo "<p>Congratulations! Onnac has been installed!<br/><a href=\"$root_url\">$root_url</a>";
}

function install_db_create($validated,$query,$db_prefix,$table_suffix){

	global $cfg;

	if ($validated){
	
		if ($cfg['db_type'] == "mysql")
			$query .= " Type = " . get_post_var('db_table_type');
			
		// establish the table name in $cfg as well
		$cfg["t_" . $table_suffix] = $db_prefix . $table_suffix;
	
		echo "success!<br/>Creating " . $db_prefix . $table_suffix . "... ";
		if (!db_is_valid_result(db_query($query,true))){
			echo "</p>";
			db_rollback_transaction();
			$validated = 0;
		}
	}
	
	return $validated;
}


?></body></html>
