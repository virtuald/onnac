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

// get parameters
$mode = get_get_var('mode');

// don't cache admin pages!
header("Cache-Control: no-cache, private");

// let them know who's logged in! except when exporting...
if ($mode != "export")
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
	
	case "edbanner":
		if ($auth->verifyMembership($perm_modify)){
			require_once("./include/admin/edbanner.inc.php");
			edbanner();
		}
		break;
		
	case "edbanneritem":
		if ($auth->verifyMembership($perm_modify)){
			require_once("./include/admin/edbanneritem.inc.php");
			edbanneritem();
		}
		break;
		
	case "edmenu":
		if ($auth->verifyMembership($perm_modify)){
			require_once("./include/admin/edmenu.inc.php");
			edmenu();
		}
		break;
		
	case "edmenuitem":
		if ($auth->verifyMembership($perm_modify)){
			require_once("./include/admin/edmenuitem.inc.php");
			edmenuitem();
		}
		break;
		
	case "manageusers":
		if ($auth->verifyMembership($perm_modify)){
			require_once("./include/admin/manageusers.inc.php");
			manageusers();
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
	
?><p>Please select a function:</p>
<ul><?php

	if ($auth->verifyMembership($perm_modify)){ ?>
	<li><a href="##pageroot##/?mode=edurl">Edit content and pages</a></li>
	<li><a href="##pageroot##/?mode=edtemplate">Edit templates</a></li>
	<li><a href="##pageroot##/?mode=edbanner">Edit banner groups</a></li>
	<li><a href="##pageroot##/?mode=edbanneritem">Edit banner items</a></li>
	<li><a href="##pageroot##/?mode=edmenu">Edit menu groups</a></li>
	<li><a href="##pageroot##/?mode=edmenuitem">Edit menu items</a></li>
	<li><a href="##pageroot##/?mode=manageusers">Manage users</a></li>
	<li><a href="##pageroot##/?mode=showerrors">Show 404 Errors</a></li>
	<li><a href="##pageroot##/?mode=import">Import Site Data</a></li>
	<li><a href="##pageroot##/?mode=export">Export Site Data</a></li>
	<?php }
	
	if ($auth->verifyMembership($perm_view)){
	?><li><a href="##pageroot##/?mode=viewcount">View site page counts</a></li>
</ul><p><a href="##rootdir##">Root directory</a></p><?php
	}
		break;
}


?>