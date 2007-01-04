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
* DISCLAIMED. IN NO EVENT SHALL DUSTIN SPICUZZA AND CONTRIBUTORS BE LIABLE FOR ANY
* DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

	Administration tool -- Administer users 
	
*/

function manageusers(){

	global $cfg;

	// this isn't 100% true actually
	echo "This feature does not work at this time.";
	return;
	
	include('./include/admin/adm_item.php');
	
	$manager = new adm_item();
	
	$manager->type =				'user';
	$manager->type_plural =			'users';
	$manager->url = 				'##pageroot##/?mode=manageusers';
	
	// initialize fields
	$manager->sql_item_table = 		$cfg['t_users'];		
	$manager->sql_item_id = 		'user_id';			
	$manager->sql_item_data = 		array('username');			
	$manager->sql_item_desc = 		array("Username");		
	
	$manager->sql_join_table = 		$cfg['t_user_groups'];		
	$manager->sql_order_field = 	false;
	
	$manager->sql_group_table = 	$cfg['t_user_group_names'];		
	$manager->sql_group_id =		'group_id';			
	$manager->sql_group_name = 		'group_name';

	$manager->ShowItems();
	
}
	
/*
	$action = get_get_var('action');

	if ($action == ""){
		
?><script type="text/javascript">
<!--
	function toggle_hidden(item){
		var hidden = document.getElementById(item);
		
		if (hidden.style.display == "none")
			hidden.style.display = "block";
		else
			hidden.style.display = "none";
	}
	
//--></script><p><a href="javascript:toggle_hidden('adm_adduser')">Add new user</a></p>
<div id="adm_adduser">
	<form method="post" action="##pageroot##?mode=manageusers&amp;action=adduser">
		Username <input name="new_username" size="40" /><br />
		Password <input name="new_password" size="40" type="password" /><br />
		Confirm: <input name="c_new_password" size="40" type="password" /><br />
		<input type="submit" value="Add user" />
	</form>
</div>
<p><a href="javascript:toggle_hidden('adm_addgroup')">Add new group</a></p>
<div id="adm_addgroup">
	<form method="post" action="##pageroot##?mode=manageusers&amp;action=adduser">
		Group Name: <input name="new_groupname" size="40" /><br />
		<input type="submit" value="Add group" />
	</form>
</div>
		<?php
		
		// display user information
		$result = db_query("	
			SELECT a.username, c.group_name
			FROM $cfg[t_users] a
			LEFT OUTER JOIN $cfg[t_user_groups] b ON a.user_id = b.user_id
			LEFT OUTER JOIN $cfg[t_user_group_names] c ON b.group_id = c.group_id
			ORDER BY a.username,c.group_name ASC");
			
		if (db_has_rows($result)){
		
			echo "<table><thead><tr><td>Username</td><td>Groups</td><td>&nbsp;</td></tr></thead>";
		
			$last_user = '';
			$first = true;
			
			while ($row = db_fetch_row($result)){
				
				$user = htmlentities($row[0]);
				$group = htmlentities($row[1]);
				
				if ($last_user != $row[0]){
					if (!$first){
						echo "</table></td><td><a href=\"##pageroot##?mode=manageusers&amp;user=$user&amp;action=chpwd\">[change pwd]</a> <a href=\"##pageroot##?mode=manageusers&amp;user=$user&amp;action=deluser\">[delete]</a></td></tr>";	
					}
					
					echo "<tr><td>$user</td><td><table>";
				}
				
				$first = false;
						
				echo "<tr><td>$group</td><td><a href=\"##pageroot##?mode=manageusers&amp;user=$user&amp;group=$group&amp;action=remove\">[Remove]</a></td></tr>";
				$last_user = $row[0];
			}
			
			echo "</table></td><td><a href=\"##pageroot##?mode=manageusers&amp;user=$user&amp;action=chpwd\">[change pwd]</a> <a href=\"##pageroot##?mode=manageusers&amp;user=$user&amp;action=deluser\">[delete]</a></td></tr></table>";

			
		}else{
			echo "No users found. SERIOUS ERROR!!!";
		}
		
		// display group information
		$result = db_query("	
			SELECT a.username, c.group_name
			FROM $cfg[t_users] a
			LEFT OUTER JOIN $cfg[t_user_groups] b ON a.user_id = b.user_id
			LEFT OUTER JOIN $cfg[t_user_group_names] c ON b.group_id = c.group_id
			ORDER BY a.username,c.group_name ASC");
			
		if (db_has_rows($result)){
		
			echo "<table><thead><tr><td>Group Name</td><td>Users</td><td>&nbsp;</td></tr></thead>";
		
			$last_user = '';
			$first = true;
			
			while ($row = db_fetch_row($result)){
				
				$user = htmlentities($row[0]);
				$group = htmlentities($row[1]);
				
				if ($last_user != $row[0]){
					if (!$first){
						echo "</table></td><td><a href=\"##pageroot##?mode=manageusers&amp;user=$user&amp;action=chpwd\">[change pwd]</a> <a href=\"##pageroot##?mode=manageusers&amp;user=$user&amp;action=deluser\">[delete]</a></td></tr>";	
					}
					
					echo "<tr><td>$user</td><td><table>";
				}
				
				$first = false;
						
				echo "<tr><td>$group</td><td><a href=\"##pageroot##?mode=manageusers&amp;user=$user&amp;group=$group&amp;action=remove\">[Remove]</a></td></tr>";
				$last_user = $row[0];
			}
			
			echo "</table></td><td><a href=\"##pageroot##?mode=manageusers&amp;user=$user&amp;action=chpwd\">[change pwd]</a> <a href=\"##pageroot##?mode=manageusers&amp;user=$user&amp;action=deluser\">[delete]</a></td></tr></table>";

			
		}else{
			echo "No groups found. SERIOUS ERROR!!!";
		}
		
	}else{
		// this would be a security issue IF it wasn't for the fact that
		// if you're here, you're already admin so it doesn't matter. So, it just
		// saves lots of typing. :)
		if (function_exists("mu_$action")){
			call_user_func("mu_$action");
		}
	
	}
}

// change user password
function mu_chpwd(){

}

// remove user from group
function mu_remove(){

}

// add user
function mu_adduser(){

}

// add group
function mu_addgroup(){

}

// delete user
function mu_deluser(){

}

// confirmation received
function mu_c_deluser(){

}

function mu_delgroup(){

}

function mu_c_delgroup(){

}

function mu_addgroupuser(){

}

function mu_c_addgroupuser(){

}
*/
?>