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

	Administrative page manager for website
	Entry page, everything goes through here. 
	
*/

// first, setup the template
global $cfg,$render;
	
$render['template'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>##title##</title>
	<link rel="stylesheet" type="text/css" href="##pageroot##/admin.css" />
	<link rel="stylesheet" type="text/css" href="##pageroot##/admin-print.css" media="print" />
	<script type="text/javascript" src="##pageroot##/admin.js"></script>
</head>
<body>
<div id="adm_body">
	<div id="adm_header">Onnac Administration Interface</div>
	<div id="adm_header_menu">
		<ul>
			##menu##
		</ul>
	</div>
	<div id="adm_content">
		##content##
	</div>
	<div id="adm_footer">Onnac &copy;2006-2007 Dustin Spicuzza &amp; contributors<br/>
	##db_queries## queries executed in ##time## seconds.</div>
</div>
</body>
</html>';

$render['menu'] = '<li><a href="##pageroot##">Home</a></li>';

// don't cache admin pages!
header("Cache-Control: no-cache, private");

// check for ajax-style pages.. 
$ajax = get_get_var('ajax') == 'true' ? true : false;
if ($ajax)
	$cfg['output_replace'] = false;
	

require_once "./include/auth.inc.php";

// permissions
$perm_modify = "Modify site";
$perm_view = "View pagecounts";

global $auth;
$auth = new authentication("$perm_modify;$perm_view");
	
if (!$auth->loggedIn){
	
	$auth->authenticate();
	return;
}

if (get_get_var('logout') == 'dologout'){
	$auth->logout();
	return;
}

// change around the menu depending on who it is
if ($auth->verifyMembership($perm_modify)){
	$render['menu'] .= 
'<li><a href="##pageroot##/?mode=edurl">Content</a></li>
<li><a href="##pageroot##/?mode=edtemplate">Templates</a></li>
<li><a href="##pageroot##/?mode=banner">Banners</a></li>
<li><a href="##pageroot##/?mode=menu">Menus</a></li>
<li><a href="##pageroot##/?mode=users">Users</a></li>
<li><a href="##pageroot##/?mode=import">Import</a></li>
<li><a href="##pageroot##/?mode=export">Export</a></li>
<li><a href="##pageroot##/?mode=showerrors">Errors</a></li>';
}
	
if ($auth->verifyMembership($perm_view)){
	$render['menu'] .= '<li><a href="##pageroot##/?mode=viewcount">Statistics</a></li>';
}

// get parameters
$mode = get_get_var('mode');

// show this
if (!$ajax)
	echo '<div id="adm_logout">Logged in as: ' . $auth->username . '<br/><a href="##pageroot##/?logout=dologout">Logout</a></div>';
	
	
switch ($mode){
	case "edurl":
		if ($auth->verifyMembership($perm_modify)){
			require_once("./include/admin/edurl.inc.php");
			edurl();
		}
		break;

	case "edtemplate":
		if ($auth->verifyMembership($perm_modify)){
			require_once("./include/admin/edtemplate.inc.php");
			edtemplate();
		}
		break;
	
	case "banner":
		if ($auth->verifyMembership($perm_modify)){
			require_once("./include/admin/banner.inc.php");
			manage_banners();
		}
		break;

		
	case "menu":
		if ($auth->verifyMembership($perm_modify)){
			require_once("./include/admin/menu.inc.php");
			manage_menus();
		}
		break;
		
	case "users":
		if ($auth->verifyMembership($perm_modify)){
			require_once("./include/admin/users.inc.php");
			manage_users();
		}
		break;
		
	case "preview":
		if ($auth->verifyMembership($perm_modify)){
			require_once("./include/admin/preview.inc.php");
			preview();
		}
		break;
		
	case "showerrors":
		if ($auth->verifyMembership($perm_modify)){
			require_once("./include/admin/showerrors.inc.php");
			showerrors();
		}
		break;
		
	case "import":
		if ($auth->verifyMembership($perm_modify)){
			require_once("./include/admin/import.inc.php");
			import_data();
		}
		break;
		
	case "export":
		if ($auth->verifyMembership($perm_modify)){
			require_once("./include/admin/export.inc.php");
			export_data();
		}
		break;
		
	case "viewcount":
		if ($auth->verifyMembership($perm_view)){
			require_once("./include/admin/viewcount.inc.php");
			viewcount();
		}
		break;

	default:
	
	// inane warnings
	if (get_magic_quotes_gpc()){
		echo "<p><strong>Warning!</strong> <em>magic_quotes_gpc</em> is enabled! This may significantly degrade performance, and it is <em>highly</em> recommended that you disable them!</p>";
	}
	
?></ul><p><a href="##rootdir##">Root directory</a></p><?php

}


?>