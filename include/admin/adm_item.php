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

	Administration tool -- Generic item editor. Designed for things with
	a pretty simple data scheme. Support is builtin to manage things like 
	item ordering, multiple fields, etc..
	
	Used to provide management interfaces for menus, banners, users
	
	-- Has transaction support 
	
	TODO: Ajax-type stuff
	
*/

class adm_item{

	// [Mandatory settings]
	var $type;				// what type of item are we managing? used for naming
	var $type_plural;		// also used
	
	var $url;
	
// 	[SQL settings]
//	ITEM_TABLE			JOIN_TABLE			GROUP_TABLE
//	item_id				item_id				group_id
//	item_data1			group_id			group_name
//	item_data2			[order_field]
//	etc...
	
	// names of the fields
	var $sql_item_table;		// table with items
	var $sql_item_id;			// item id name
	var $sql_item_data;			// array of data fields
								// displayed to the user
	
	var $sql_item_unique_keys;	// index of $sql_item_data that cannot have duplicate values. Set to -1
								// to disable duplicate checking when editing/adding values
	
	var $sql_item_desc;			// description of data
	
	var $sql_item_hidden;		// array of 'data','description'... 
								// fields that are added, but not displayed
	
	var $sql_join_table;		// table that joins them together
	var $sql_order_field;		// if there is a field that defines their order, its here
	
	var $sql_group_table;		// table with groups
	var $sql_group_id;			// group id name
	var $sql_group_name;		// group name fieldname
	
	
	// [Optional settings]
	var $do_content_update;		// if true, then anytime an item is changed then other_update
								// should be updated for any content that references it
	var $group_show_first;		// if true, then the group display screen will only use the first item of
								// sql_item_desc
	
	// [Optional hooks]
	var $item_delete_hook;		// called when item is being deleted. If returns false, then item is not deleted.
								// fn($item_id,$item_name)
	
	var $remove_hook;			// called when item is being removed from group. If returns false
								// then do not remove item. This function should output the error
								// fn($item_id,$item_name,$group_id,$group_name)
								
	var $edit_item_hook;		// called when new item is being inserted (is_new = false) or updated (is_new = true)
								// returns SQL query to be executed. item_id is invalid if is_new is set. 
								// data is in this order: 
								// sql_item_data fields, sql_item_hidden fields
								// fn($data,$is_new,$item_id);
	
	// [Optional page settings, functions]
	var $item_html;				// extra HTML shown on the item page
	var $group_html;			// extra HTML shown on the group page
	
	var $custom_functions;		// custom functions that can be accessed using action=[function name]
								
	// initialize
	function adm_item(){
		
		$sql_item_data = array();
		$sql_item_desc = array();
		$sql_item_hidden = array();
		
		$sql_item_unique_keys = array();
		
		$custom_functions = array();
		
		// initialize the optional parameters
		$order_field = false;
		$do_content_update = false;
		$group_show_first = false;
		
	}

	// call this externally!
	function ShowItems(){

		global $cfg;
		
		$action = get_get_var('action');
		$d = get_cookie_var('d_' . $this->type);
		
		// determine initial view, using cookies
		if ($d == 'g'){
			$iv = 'style="display:none"';
			$gv = '';
			$ila = '';
			$gla = ' style="background-color:#aaaaaa"';
		}else{
			// default to item view
			$iv = '';
			$gv = 'style="display:none"';
			$ila = ' style="background-color:#aaaaaa"';
			$gla = '';
		}
		
?><script type="text/javascript">
<!--
	
function switch_to(type){
	var li_items = document.getElementById('adm_item_li');
	var li_groups = document.getElementById('adm_group_li');
	var items = document.getElementById('adm_item');
	var groups = document.getElementById('adm_group');
	
	if(type == 'groups' ){
		items.style.display = 'none';
		m_unhighlight(li_items);
		groups.style.display = 'block';
		m_highlight(li_groups);
		set_cookie('d_<?php echo $this->type; ?>','g');
	}else{
		items.style.display = 'block';
		m_highlight(li_items);
		groups.style.display = 'none';
		m_unhighlight(li_groups);
		set_cookie('d_<?php echo $this->type; ?>','i');
	}
}

function m_highlight(item){
	item.firstChild.style.color = '#000000';
	item.firstChild.style.backgroundColor = '#aaaaaa';
	item.firstChild.style.border = '1px dotted #000000';
}

function m_unhighlight(item){
	item.firstChild.style.color = '#000000';
	item.firstChild.style.backgroundColor = '#eeeeee';
	item.firstChild.style.border = '';
}
	
//--></script><?php
		
		if ($action == ""){
		
?><ul id="adm_list">
	<li id="adm_item_li"><a href="javascript:switch_to('items')"<?php echo $ila . '>' . ucfirst($this->type_plural); ?></a></li>
	<li id="adm_group_li"><a href="javascript:switch_to('groups')"<?php echo $gla;?>>Groups</a></li>
</ul><hr /><?php
		
			// items first
			echo "<div id=\"adm_item\" $iv>";
			
			// generate the query
			$query = "	
				SELECT a." . implode(', a.',$this->sql_item_data) . ", a.$this->sql_item_id, c.$this->sql_group_name, c.$this->sql_group_id
				FROM $this->sql_item_table a
				LEFT OUTER JOIN $this->sql_join_table b ON a.$this->sql_item_id = b.$this->sql_item_id
				LEFT OUTER JOIN $this->sql_group_table c ON b.$this->sql_group_id = c.$this->sql_group_id
				ORDER BY a." . $this->sql_item_data[0] . ", c.$this->sql_group_name ASC";
			
			$this->show_primary($query,'item');
			
			// show group content next
			echo "$this->item_html</div><div id=\"adm_group\" $gv>";
			
			if ($this->group_show_first)
				$grp_fields = $this->sql_item_data[0];
			else
				$grp_fields = implode(', c.',$this->sql_item_data);	
			
			$query = "
				SELECT c.$grp_fields, a.$this->sql_group_id, a.$this->sql_group_name, c.$this->sql_item_id
				FROM $this->sql_group_table a
				LEFT OUTER JOIN $this->sql_join_table b ON a.$this->sql_group_id = b.$this->sql_group_id
				LEFT OUTER JOIN $this->sql_item_table c ON b.$this->sql_item_id = c.$this->sql_item_id
				ORDER BY a.$this->sql_group_name, ";
				
			if ($this->sql_order_field !== false)
				$query .= "b.$this->sql_order_field ASC";
			else
				$query .= "c." . $this->sql_item_data[0] . " ASC";
			
			$this->show_primary($query,'group');
			
			echo "$this->group_html</div>";

		}else{
		
			// thought: in general, this would be VERY unsafe code. However, the person
			// using this interface will always be the administrator of the site, so
			// if they screw it up then I think its their fault. 
		
			if (method_exists($this,"fn_$action")){
				call_user_func(array($this,"fn_$action"));
			}else if(in_array($action,$this->custom_functions)){
				call_user_func($action);
			}else{
				onnac_error('Undefined action!');
			}
			
			echo "<p><a href=\"$this->url\">" . ucfirst($this->type) . " management page</a></p>";
		}
		
		echo "<p><a href=\"##pageroot##/\">Administration Home</a></p>";
	}
	
	// shows the table that shows groups and items
	// $result -- a JOIN'ed SQL query
	// $type -- 'item' or 'group'
	function show_primary($query, $type){
	
		$result = db_query($query);
	
		if (!db_has_rows($result)){
			echo "No $this->type ${type}s found!";
		}else{
		
			if ($type == 'group' && $this->group_show_first){
				$desc = array($this->sql_item_desc[0]);
				$data = array($this->sql_item_data[0]);
			}else{
				$desc = $this->sql_item_desc;
				$data = $this->sql_item_data;
			}
		
			if ($type == 'item')
				$other_type = 'group';
			else
				$other_type = 'item';
				
			echo '<table class="highlighted"><thead><tr><td>';
			if ($type == 'item')
				echo implode('</td><td>',$desc) . '<td>Groups</td>';
			else
				echo 'Group Name</td><td>' . implode(', ',$desc) . '</td>';
				
			echo '<td>&nbsp;</td></tr></thead><tbody>';
		
			$last_this = array();
			$last_id = 0;
			$first = true;
			
			
			while ($row = db_fetch_row($result)){
				
				$c = count($row);
				
				// setup array
				$this_id = $row[$c-3];						
				$group_name = htmlentities($row[$c-2]);		
				$other_id = $row[$c-1];
				
				array_splice($row,-3);						// items
					
				// create valid html, strip entities
				array_walk($row, create_function('&$a,$b', '$a = special_item_strip(htmlentities($a));'));
				
				$is_same = true;
				if ($type == 'item'){
					if ($last_this != $row)
						$is_same = false;
				}else{
					if ($last_this != $group_name)
						$is_same = false;
				}
				
				if ($first || !$is_same){
				
					if (!$first){
						echo "</table></td><td>";
			
						if ($type == 'group')
							echo "<a href=\"$this->url&amp;group_id=$last_id&amp;item_id=$other_id&amp;action=additem\">[Add $this->type]</a> ";
						
						echo "<a href=\"$this->url&amp;${type}_id=$last_id&amp;action=edit${type}\">[Edit]</a> <a href=\"$this->url&amp;${type}_id=$last_id&amp;action=delete${type}\">[Delete]</a></td></tr>";	
					}
					
					// show next row (overall)
					if ($type == 'item')
						echo "<tr><td>" . implode('</td><td>',$row) . "</td><td><table>";
					else
						echo "<tr><td>$group_name</td><td><table>";
				}
				
				$first = false;
				
				// determine whether or not to show the internal table object
				$show_me = true;
				if ($type == 'item')
					if ($group_name != null)
						echo "<tr><td>$group_name";
					else
						$show_me = false;
				else
					// all of the items cannot be blank, might be a better way to do this
					if (implode($row) != '')
						// the 'name' thing is so that when the user returns to this page they don't have
						// to scroll back and find this link (in firefox at least) :)
						echo "<a name=\"mvg_${this_id}\"></a><tr><td>" . implode('</td><td>',$row);
					else
						$show_me = false;
				
				// show the internal table object, with remove link?
				if ($show_me){
					echo "</td><td><a href=\"$this->url&amp;${type}_id=$this_id&amp;${other_type}_id=$other_id&amp;action=remove\">[Remove]</a></td>";
					
					if ($type == 'group' && $this->sql_order_field != "")
						echo "<td><a href=\"$this->url&amp;${type}_id=$this_id&amp;${other_type}_id=$other_id&amp;action=changerank&amp;newrank=up\">[U]</a></td><td><a href=\"$this->url&amp;${type}_id=$this_id&amp;${other_type}_id=$other_id&amp;action=changerank&amp;newrank=down\">[D]</a></td>";
						
					echo "</tr>";
				}
				
				$last_id = $this_id;
				if ($type == 'item')
					$last_this = $row;
				else
					$last_this = $group_name;
			}
			
			echo "</tbody></table></td><td>";
			
			if ($type == 'group')
				echo "<a href=\"$this->url&amp;group_id=$last_id&amp;item_id=$other_id&amp;action=additem\">[Add $this->type]</a> ";
			
			echo "<a href=\"$this->url&amp;${type}_id=$last_id&amp;action=edit${type}\">[Edit]</a> <a href=\"$this->url&amp;${type}_id=$last_id&amp;action=delete${type}\">[Delete]</a></td></tr></table>\r\n";	
		}
		
		// add new item
		$grp = '';
		if ($type == 'group')
			$grp = ' group';
			
		echo "<p><a href=\"javascript:toggle_hidden('adm_add${type}')\">Add new $this->type$grp</a></p><div id=\"adm_add${type}\" style=\"display:none\">";
		
		$this->show_edit("action=edit${type}&amp;is_new=true",$type); 
		
		echo "</div>";

	}
	
	
	// outputs a box that lets you edit things
	function show_edit($param,$type,$values = false,$item_id = -1){
	
		echo "<form action=\"$this->url&amp;doit=true&amp;$param\" method=\"post\" class=\"adm_form\"><table border=\"0\">";
		
		if ($type == 'item'){
			
			// normal items first
			$c = count($this->sql_item_data);
			for ($i = 0;$i < $c;$i++){
				echo "<tr><td>" . $this->sql_item_desc[$i] . "</td><td><input name=\"" . htmlentities($this->sql_item_data[$i]) . "\" value=\"";

				if ($values !== false)
					echo special_item_strip(htmlentities($values[$i]));
				
				echo '" /></td></tr>';
			}
			
			// 'hidden' items next
			$c = count($this->sql_item_hidden)/2;
			for ($i = 0;$i < $c;$i++)
				echo "<tr><td>" . $this->sql_item_hidden[$i*2+1] . "</td><td><input type=\"password\" name=\"" . htmlentities($this->sql_item_hidden[$i*2]) . "\" /></td></tr><tr><td>Confirm " . $this->sql_item_hidden[$i*2+1] . "</td><td><input type=\"password\" name=\"c_" . htmlentities($this->sql_item_hidden[$i*2]) . "\" /></td></tr>";

		}else{
			echo "<tr><td>" . ucfirst($this->type) . " group name</td><td><input name=\"group_name\" value=\"";
			if ($values !== false)
				echo special_item_strip(htmlentities($values));
			
			echo '" /></td></tr>';
		}
				
		echo "</table><input type=\"submit\" value=\"Submit Item\" /></form>";
	
	}

	// remove an item from a group
	function fn_remove(){
	
		$group_id = get_get_var('group_id');
		$item_id = db_escape_string(get_get_var('item_id'));
		// these could be optimized into one SQL query, but im lazy
		$group_name = $this->get_group_name($group_id);
		$item_name = $this->get_item_name($item_id);
		
		if ($item_name === false || $group_name === false)
			return onnac_error("Invalid item id!");
	
		// remove item hook
		if (function_exists($this->remove_hook)){
			if (!call_user_func($this->remove_hook,$item_id,$item_name,$group_id,$group_name))
				return;
		}
		
		// dont bother confirming, just do it
		// one operation, don't bother with transactions
		$result = db_query("DELETE FROM $this->sql_join_table WHERE $this->sql_item_id = $item_id AND $this->sql_group_id = $group_id");
		
		if (db_is_valid_result($result))
			echo "Item <strong>&quot;$item_name&quot;</strong> removed from group <strong>&quot;$group_name&quot;</strong>.";
		else
			onnac_error( "Error removing item <strong>&quot;$item_name&quot;</strong> from group <strong>&quot;$group_name&quot;</strong>.");
	
	}
	
	// add item to a group
	function fn_additem(){
	
		$doit = get_get_var('doit');
		$group_id = db_escape_string(get_get_var("group_id"));
		$group_name = $this->get_group_name($group_id);
		
		if ($group_name === false)
			return onnac_error("Invalid group name");
		
		if ($doit == ''){
		
			echo "<p>Add $this->type_plural to <strong>&quot;$group_name&quot;</strong></p>";
		
			// show all possible items, with checkboxes next to them
			$result = db_query("SELECT a.$this->sql_item_id, a." . implode(',a.',$this->sql_item_data) . "
				FROM $this->sql_item_table a
				LEFT JOIN $this->sql_join_table b ON a.$this->sql_item_id = b.$this->sql_item_id AND b.$this->sql_group_id = $group_id
				WHERE b.$this->sql_item_id IS NULL ORDER BY a." . implode(',a.',$this->sql_item_data));
		
			if (!db_has_rows($result))
				return onnac_error("No $this->type_plural found.");
			
			echo '<form method="post" action="' . "$this->url&amp;action=additem&amp;group_id=$group_id" . '&amp;doit=true"><table border="0"><thead><tr><td>' . implode('</td><td>',$this->sql_item_desc) . '</td><td>&nbsp;</td></tr></thead>';
			
			while ($row = db_fetch_row($result)){
				// sanitize it
				$id = $row[0];
				$row = array_splice($row,1);
				array_walk($row, create_function('&$a,$b', '$a = special_item_strip(htmlentities($a));'));
				
				echo '<tr><td>' . implode('</td><td>',$row) . "</td><td><input type=\"checkbox\" name=\"item_$id\" value=\"yes\" /></td></tr>";
			}
			
			echo "</table><input type=\"Submit\" value=\"Add $this->type_plural\" /></form>";
			return;
		}
		
		// make sure to set the order field correctly, if it exists
		if ($this->sql_order_field != ""){
			$result = db_query("SELECT $this->sql_order_field FROM $this->sql_join_table WHERE $this->sql_group_id = '$group_id'");
			if (!db_is_valid_result($result))
				return onnac_error("Could not retrieve the $this->type order");
			else if (db_num_rows($result) > 0){
				$rank = db_fetch_row($result);
				$rank = $rank[0];
			}else
				$rank = 0;
		}
		
		// get item id's
		$result = db_query("SELECT a.$this->sql_item_id
				FROM $this->sql_item_table a
				LEFT JOIN $this->sql_join_table b ON a.$this->sql_item_id = b.$this->sql_item_id AND b.$this->sql_group_id = $group_id
				WHERE b.$this->sql_item_id IS NULL");
	
		if (!db_has_rows($result))
			return onnac_error("No $this->type_plural found.");
	
		$query = array();
		
		while ($row = db_fetch_row($result)){
			if (get_post_var("item_$row[0]") == "yes"){
				if ($this->sql_order_field != ""){
					$rank += 1;
					$qstr = "INSERT INTO $this->sql_join_table ($this->sql_item_id,$this->sql_group_id,$this->sql_order_field) VALUES ($row[0],$group_id,$rank);";
				}else{
					$qstr = "INSERT INTO $this->sql_join_table ($this->sql_item_id,$this->sql_group_id) VALUES ($row[0],$group_id);";
				}
				
				$query[] = $qstr;
			}
		}
		
		if (count($query) == 0)
			return onnac_error('No items selected!');
		
		// begin transaction
		if (!db_is_valid_result(db_begin_transaction()))
			return onnac_error("Error starting SQL transaction!");
		
		foreach ($query as $q){
			$result = db_query($q);
			
			if (!db_is_valid_result($result)){
				onnac_error("Could not add $this->type_plural to <strong>&quot;$group_name&quot;</strong>!");
				if (!db_rollback_transaction())
					onnac_error("Some $this->type_plural may have been added!");
				return;
			}
		}
		
		if (db_commit_transaction()){
			$og = $this->type_plural;
			if (count($query) == 1)
				$og = $this->type;
			echo count($query) . " $og added to <strong>&quot;$group_name&quot;</strong>";
		}else
			onnac_error("Transaction did not complete successfully!");
	
	}
	
	// placeholder functions, just call another function
	function fn_deleteitem(){
		$this->delete(true);
	}
	
	function fn_deletegroup(){
		$this->delete(false);
	}
	
	function fn_edititem(){
		$this->edit(true);
	}
	
	function fn_editgroup(){
		$this->edit(false);
	}
	
	// type is either 'item' or 'group'
	function edit($is_item){
	
		if ($is_item){
			$type = 'item';
			$grp_text = '';
			$sql_table = $this->sql_item_table;
			$sql_id = $this->sql_item_id;
		}else{
			$type = 'group';
			$grp_text = ' group';
			$sql_table = $this->sql_group_table;
			$sql_id = $this->sql_group_id;
		}
	
		$is_new = get_get_var('is_new');
		$doit = get_get_var('doit');
		
		if (!$is_new){
		
			if ($is_item){
				$id = db_escape_string(get_get_var("item_id"));
				$name = $this->get_item_name($id);
			}else{
				$id = db_escape_string(get_get_var("group_id"));
				$name = $this->get_group_name($id);
			}
				
			if ($name == '')
				return onnac_error("Invalid $type id");			
		}
		
		if ($doit == ""){
			
			echo "<p>Editing $this->type$grp_text <strong>&quot;$name&quot;</strong>...</p>";
		
			// id is passed in.  Get params
			if ($is_item){
				$result = db_query("SELECT " . implode(',',$this->sql_item_data) . " FROM $this->sql_item_table WHERE $this->sql_item_id = '$id'");
			
				if (!db_is_valid_result($result))
					return;
			
				$this->show_edit("action=edititem&amp;item_id=$id",'item',db_fetch_row($result));
			}else{
				// skip the SQL: we already have the name
				$this->show_edit("action=editgroup&amp;group_id=$id",'group',$name);
			}
			
			return;

		}
		
		$data = array();
		$incoming = array();
	
		if (!$is_item){
			$data[] = $this->sql_group_name;
			$incoming[] = db_escape_string(get_post_var('group_name'));
			
			if ($incoming[0] == "")
				return onnac_error("Group name must have a value!");
			
			// verify its not a duplicate
			$query = "SELECT $sql_id FROM $sql_table WHERE $this->sql_group_name = '$incoming[0]'";
			
			if (!$is_new)
				$query .= " AND $sql_id <> $id";
			
			$result = db_query($query);
			
			if (!db_is_valid_result($result))
				return;
				
			if (db_num_rows($result) != 0)
				return onnac_error("Group name already exists!");
			
		}else{
			// passed in variables 
			$c = count($this->sql_item_data);
			for ($i = 0;$i < $c;$i++){		
				$data[] = $this->sql_item_data[$i];
				$incoming[] = db_escape_string(get_post_var($data[$i]));
				
				if ($incoming[$i] == "")
					return onnac_error( $this->sql_item_desc[$i] . " must have a value!");
								
			}
			
			// hidden variables
			$d = count($this->sql_item_hidden)/2;
			for ($i = 0;$i < $d;$i++){
				
				// get the first, and confirmation item
				$x1 = db_escape_string(get_post_var($this->sql_item_hidden[$i*2]));
				$x2 = db_escape_string(get_post_var('c_' . $this->sql_item_hidden[$i*2]));
				
				if ($x1 != $x2)
					return onnac_error( $this->sql_item_hidden[$i*2+1] . ' does not match!');
					
				
				// cannot have blank values!
				if ($x1 == "")
					return onnac_error( $this->sql_item_hidden[$i*2+1] . ' must have a value!');
					
				$data[] = $this->sql_item_hidden[$i*2];
				$incoming[] = $x1;
			}

		
			$c = count($this->sql_item_unique_keys);
			
			// see if we need to have unique keys
			if ($c > 0){
			
				// creative query construction
				$query = "SELECT ";
				$equery = " FROM $this->sql_item_table WHERE ";
			
				for ($i = 0;$i < $c;$i++){
				
					$query .= $this->sql_item_data[$this->sql_item_unique_keys[$i]];
					$equery .= $this->sql_item_data[$this->sql_item_unique_keys[$i]] . " = '" . $incoming[$this->sql_item_unique_keys[$i]] . "'";
					
					if ($i != $c-1){
						$query .= ',';
						$equery .= ' AND ';
					}
				}
			
				// exclude the current item, if editing
				if (!$is_new)
					$equery .= " AND $sql_id <> $id";
			
				// verify the item doesn't exist already
				$result = db_query($query . $equery);

				if (!db_is_valid_result($result))
					return;
					
				else if (db_num_rows($result) > 0)
					return onnac_error( "Duplicate item already exists!");
			}
		}
		
		if ($is_new)
			$id = -1;
		
		// insertion hook -- item only, not group
		if ($is_item && function_exists($this->edit_item_hook)){
			$query = call_user_func($this->edit_item_hook,$incoming,$is_new,$id);
		}else{
			if ($is_new)
				$query = "INSERT INTO $sql_table (" . implode(',',$data) . ") VALUES ('" . implode("','",$incoming) . "')";
			else{
				$query = "UPDATE $sql_table SET ";
				$c = count($data);
				for ($i = 0;$i < $c;$i++){
					$query .= $data[$i] . " = '" . $incoming[$i] . "'";
					if ($i != $c - 1)
						$query .= ',';
				}
				
				$query .= " WHERE $sql_id = $id";
			}
		}
		
		// error from user function
		if ($query === false)
			return;
		
		if (!db_is_valid_result(db_begin_transaction())){
			onnac_error("Error beginning SQL transaction");
			return;
		}
		
		// execute SQL statement
		$result = db_query($query);
		
		if (!db_is_valid_result($result))
			return db_rollback_transaction("Action canceled.");
		
		
		if (!$is_new && !$this->update_content($id,$is_item))
			return db_rollback_transaction("Error occured updating affected content!");
		
		
		if (db_is_valid_result(db_commit_transaction()))
			if ($is_new)
				echo "New $this->type$grp_text inserted successfully!";
			else
				echo "Existing $this->type$grp_text <strong>&quot;$name&quot</strong> edited successfully!";
		else
			onnac_error("Transaction could not be comitted!");
	
	}
	
	// delete an item
	function delete($is_item){
	
	
		if ($is_item){
			$type = 'item';
			$grp_text = '';
			$sql_table = $this->sql_item_table;
			$sql_id = $this->sql_item_id;
			
			$id = db_escape_string(get_get_var('item_id'));
			$name = $this->get_item_name($id);
		}else{
			$type = 'group';
			$grp_text = ' group';
			$sql_table = $this->sql_group_table;
			$sql_id = $this->sql_group_id;
			
			$id = db_escape_string(get_get_var('group_id'));
			$name = $this->get_group_name($id);
		}
	
		$doit = get_get_var('doit');

		if ($name === false)
			return onnac_error("Invalid $type id");
	
		// delete item hook
		if (function_exists($this->item_delete_hook)){
			if (!call_user_func($this->item_delete_hook,$id,$name))
				return;
		}
	
		if ($doit == ''){
			
			echo "<p>Are you sure you want to delete $this->type$grp_text <strong>&quot;$name&quot;</strong>?<ul><li><a href=\"$this->url&amp;action=delete$type&amp;${type}_id=$id&amp;doit=true\">Yes</a></li><li><a href=\"$this->url\">No</a></li></ul></p>";	
			return;
		}
		
		// do the deletion
		echo "Deleting $this->type$grp_text <strong>&quot;$name&quot;</strong>... ";
		
		if (!db_is_valid_result(db_begin_transaction()))
			return onnac_error("Error beginning transaction! " . ucfirst($this->type) . "$grp_text not deleted.");
			
		
		// part one -- delete the item
		$result = db_query("DELETE FROM $sql_table WHERE $sql_id = '$id'");
		
		if (!db_is_valid_result($result) || db_affected_rows($result) != 1 )
			return db_rollback_transaction("Error deleting $this->type$grp_text!");

		
		if (!$this->update_content($id,$is_item))
			if (db_rollback_transaction())
				return onnac_error( "$this->type$grp_text not deleted!");
			else
				return onnac_error("$this->type$grp_text partially deleted!");

		$affected = false;

		// delete the references to the item
		$result = db_query("DELETE FROM $this->sql_join_table WHERE $sql_id = '$id'");
		
		if (!db_is_valid_result($result)){
			if (db_rollback_transaction())
				onnac_error("$this->type not deleted.");
			else
				onnac_error("$this->type partially deleted!");	 // uh-oh.. 
				
			return;
		}
		
		if (db_affected_rows($result) > 0)
			$affected = true;
		
		if (db_is_valid_result(db_commit_transaction())){
		
			echo "$this->type$grp_text deleted.";
			
			if ($affected)
				echo " References to $this->type also deleted.";
		
		}else
			onnac_error("Error committing transaction.");

	}
	
	// change the rank of an item that has a sort field
	function fn_changerank(){

		// sanity check
		if ($this->sql_order_field == "")
			return;
	
		$group_id = db_escape_string(get_get_var('group_id'));
		$item_id = get_get_var('item_id');
		$newrank = get_get_var('newrank');
				
		// get all menu items
		$result = db_query("SELECT $this->sql_item_id,$this->sql_order_field FROM $this->sql_join_table WHERE $this->sql_group_id = '$group_id' ORDER BY $this->sql_order_field ASC");

		$found = false;
		
		if (db_is_valid_result($result) && db_num_rows($result) > 1){
		
			// iterate and find self
			while($row = db_fetch_row($result)){
				
				if ($row[0] == $item_id){
					$found = true;
					break;
				}
				$last_row = $row;
			}
			
			if ($found){
			
				$success = true;
			
				// if valid, switch items around
				if ($newrank == "up" || $newrank == "down"){
					
					if ($newrank == "down")
						$last_row = db_fetch_row($result);
					
					// update that item with our rank, if we're not first already
					if (isset($last_row)){
					
						if (!db_is_valid_result(db_begin_transaction()))
							return onnac_error("Error beginning transaction! Rank not changed!");
					
						// this should never happen, but it did in Onnac 0.0.9.1 and below.. oops
						if ($row[1] == $last_row[1])
							if ($newrank == "up")
								$row[1] = $last_row[1] + 1;
							else
								$row[1] = $last_row[0] - 1;
					
						// update other item, then current item
						$success &= db_is_valid_result(db_query("UPDATE $this->sql_join_table SET $this->sql_order_field = '$row[1]' WHERE $this->sql_group_id = '$group_id' AND $this->sql_item_id = '$last_row[0]'")) 
						
						&& db_is_valid_result(db_query("UPDATE $this->sql_join_table SET $this->sql_order_field = '$last_row[1]' WHERE $this->sql_group_id = '$group_id' AND $this->sql_item_id = '$item_id'"))
						
						&& $this->update_content($group_id,false);
						
						if (!$success)
							return db_rollback_transaction("Error updating rank!");
							
						if (!db_is_valid_result(db_commit_transaction()))
							return onnac_error("Could not complete transaction!");
					}
				}
				
				header("Refresh: 0;url='?mode=" . get_get_var('mode') . "#mvg_${group_id}'"); 
			}else{
				echo "Error! $this->type item not found!";
			}
		}
	}

	
	//
	//	Utility functions
	//
	
	function get_item_name($id){
		return $this->get_name($id,$this->sql_item_data[0],$this->sql_item_id,$this->sql_item_table);
	}
	
	function get_group_name($id){
		return $this->get_name($id,$this->sql_group_name,$this->sql_group_id,$this->sql_group_table);
	}
	
	function get_name($id,$sql_name,$sql_item_name,$sql_table){
	
		if ($id == '' || !ctype_digit($id))
			return false;
			
		$result = db_query("SELECT $sql_name FROM $sql_table WHERE $sql_item_name = '$id'");
		if (db_has_rows($result)){
			$row = db_fetch_row($result);
			return special_item_strip(htmlentities($row[0]));
		}

		return false;
	}

	// updates the other_update() field in the content
	function update_content($id,$is_item){

		global $cfg;
	
		if ($this->do_content_update){
	
			if ($is_item){
				// this can probably be done in one query, oh well.. 
				$result = db_query("SELECT $this->sql_group_id FROM $this->sql_join_table WHERE $this->sql_item_id = '$id'");
			
				if (!db_is_valid_result($result))
					return false;
					
				else if (db_num_rows($result) > 0)
					while ($row = db_fetch_row($result)){
						$result2 = db_query("UPDATE $cfg[t_content] SET other_update = NOW() WHERE $this->sql_group_id = '$row[0]'");
						if (!db_is_valid_result($result2))
							return false;
					}
			}else{
				$result = db_query("UPDATE $cfg[t_content] SET other_update = NOW() WHERE $this->sql_group_id = '$id'");
				if (!db_is_valid_result($result))
					return false;
			}	
		}
		
		return true;
	}
	
}
	

?>
