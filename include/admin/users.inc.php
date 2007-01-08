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

function manage_users(){

	global $cfg;
	
	include('./include/admin/adm_item.php');
	
	$manager = new adm_item();
	
	$manager->type =				'user';
	$manager->type_plural =			'users';
	$manager->url = 				'##pageroot##/?mode=users';
	
	// initialize fields
	$manager->sql_item_table = 		$cfg['t_users'];		
	$manager->sql_item_id = 		'user_id';			
	$manager->sql_item_data = 		array('username','description');			
	$manager->sql_item_desc = 		array("Username","Description");

	$manager->sql_item_unique_keys = array(0);
	
	$manager->sql_item_hidden =		array('hash','Password');
	
	$manager->sql_join_table = 		$cfg['t_user_groups'];		
	$manager->sql_order_field = 	false;
	
	$manager->sql_group_table = 	$cfg['t_user_group_names'];		
	$manager->sql_group_id =		'group_id';			
	$manager->sql_group_name = 		'group_name';
	
	$manager->group_show_first =	true;

	// hooks
	$manager->item_delete_hook = 	'user_delete';
	$manager->remove_hook = 		'user_remove';
	$manager->edit_item_hook = 		'user_edititem';
	
	$manager->ShowItems();
	
}

// callback function - delete user
function user_delete($item_id,$item_name){

	global $cfg;
	
	// see if the user is root or not
	$result = db_query("SELECT a.group_id FROM $cfg[t_user_groups] a, $cfg[t_user_group_names] b WHERE a.user_id = $item_id AND b.group_name = 'root' AND a.group_id = b.group_id");
	
	if (!db_is_valid_result($result))
		return false;
	
	if (db_num_rows($result) == 0)
		return true;
	
	$row = db_fetch_row($result);
	$group_id = $row[0];
	
	// check to see if there is only one root user
	$result = db_query("SELECT COUNT(a.group_id) FROM $cfg[t_user_groups] a, $cfg[t_user_group_names] b WHERE a.group_id = '$group_id' AND b.group_name = 'root' AND a.group_id = b.group_id");
	
	if (!db_has_rows($result))
		return false;
	
	$row = db_fetch_row($result);
	
	// if there is only one root user, this is an error
	if ($row[0] <= 1)
		return onnac_error("Cannot delete last root user <strong>&quot;$item_name&quot;</strong>!"); 
	

	return true;
}

// callback function - removing user from group
function user_remove($item_id,$item_name,$group_id,$group_name){

	global $cfg;

	// check to see if its root
	if ($group_name == 'root'){
	
		// if its root, then make sure there is at least one person in the root group
		$result = db_query("SELECT COUNT(group_id) FROM $cfg[t_user_groups] WHERE group_id = '$group_id'");
		
		if (!db_has_rows($result))
			return false;
		
		$row = db_fetch_row($result);
		if ($row[0] <= 1)
			return onnac_error("Cannot remove user <strong>&quot;$item_name&quot;</strong> from group <strong>&quot;root&quot;</strong>, there must be at least one root user!");
		
	}
	
	return true;
}

// returns SQL query to insert data
function user_edititem($data,$is_new,$item_id){

	global $cfg;
	
	// username must be at least 5 characters
	if (strlen($data[0]) < 5)
		return onnac_error("Username must be at least 5 characters long!");
	
	// implement simplistic password security here:
	else if (strlen($data[2]) < 6)
		return onnac_error("Password must be at least 6 characters long!");
	else if ($data[0] == $data[2])
		return onnac_error("Password should NOT be the same as the username!");
	
	if ($is_new)
		return "INSERT INTO $cfg[t_users] (username,description,hash) VALUES ('$data[0]','$data[1]','" . md5($data[0] . ':' . $data[2]) . "')";
			
	return "UPDATE $cfg[t_users] SET username = '$data[0]', description = '$data[1]', hash = '" . md5($data[0] . ':' . $data[2]) . "' WHERE user_id = '$item_id'";
}


?>