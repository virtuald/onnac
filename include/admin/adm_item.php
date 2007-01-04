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
	a pretty simple data scheme.
	
	Support is builtin to manage things like item ordering, extra fields, etc..
	
	Used to provide management interfaces for menus, banners, users
	
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
	
	var $sql_item_desc;			// description of data
	
	var $sql_join_table;		// table that joins them together
	var $sql_order_field;		// if there is a field that defines their order, its here
	
	var $sql_group_table;		// table with groups
	var $sql_group_id;			// group id name
	var $sql_group_name;		// group name fieldname
	
	
	// [Optional settings]
	
	


	// not sure what the constructor does yet
	function adm_item(){
		
		$sql_item_data = array();
		
		// initialize the optional parameters
		$order_field = false;
	
	}


	function ShowItems(){

		global $cfg;
		
		$action = get_get_var('action');
		
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
			
		}else{
			items.style.display = 'block';
			m_highlight(li_items);
			groups.style.display = 'none';
			m_unhighlight(li_groups);
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
	<li id="adm_item_li"><a href="javascript:switch_to('items')"><?php echo ucfirst($this->type_plural); ?></a></li>
	<li id="adm_group_li"><a href="javascript:switch_to('groups')">Groups</a></li>
</ul><hr /><?php
		
			// items first
			echo '<div id="adm_item">';
			
			$result = db_query("SELECT text,href,item_id FROM $cfg[t_menu_items] ORDER BY href");
			
			// generate the query
			$result = db_query("	
				SELECT a." . implode(', a.',$this->sql_item_data) . ", a.$this->sql_item_id, c.$this->sql_group_name
				FROM $this->sql_item_table a
				LEFT OUTER JOIN $this->sql_join_table b ON a.$this->sql_item_id = b.$this->sql_item_id
				LEFT OUTER JOIN $this->sql_group_table c ON b.$this->sql_group_id = c.$this->sql_group_id
				ORDER BY a." . $this->sql_item_data[0] . ", c.$this->sql_group_name ASC");
			
			if (db_has_rows($result)){
			
				echo '<table><thead><tr><td>' . implode('</td><td>',$this->sql_item_desc) . '<td>Groups</td><td>&nbsp;</td></tr></thead>';
			
				$last_item = '';
				$first = true;
				
				while ($row = db_fetch_row($result)){
					
					// setup array
					$item_id = $row[count($row)-2];
					$group_name = htmlentities($row[count($row)-1]);
					array_splice($row,-2);
					
					// create valid html
					array_walk($row, create_function('&$a,$b', '$a = special_item_strip(htmlentities($a));'));
					
					if ($last_item != $row[0]){
						if (!$first){
							echo "</table></td><td><a href=\"$this->url&amp;item_id=$item_id&amp;action=delete\">[Delete]</a></td></tr>";	
						}
						
						echo '<tr><td>' . implode('</td><td>',$row) . '</td><td><table>';
					}
					
					$first = false;
							
					echo "<tr><td>$group_name</td><td><a href=\"$this->url&amp;item_id=$item_id&amp;group=$group_name&amp;action=remove\">[Remove]</a></td></tr>";
					$last_item = $row[0];
				}
				
				echo "</table></td><td><a href=\"$this->url&amp;item_id=$item_id&amp;action=delete\">[Delete]</a></td></tr></table>";	
					
				
			}else{
				echo "No $this->type_plural found!";
			}
			
			echo '</div><div id="adm_group" style="display:none">';
			
			echo "This is group content.</div>";

		}else{

			if (method_exists($this,"fn_$action")){
				call_user_func(array($this,"fn_$action"));
			}else{
			
				// check custom functions
			
				echo "<strong>Error</strong>: Undefined action!";
			}
		}
		
		echo "<p><a href=\"##pageroot##/\">Administration Home</a></p>";
	}
	
	// delete an item
	function fn_delete(){
	
		$doit = get_get_var('doit');
		$item_id = get_get_var('item_id');

		if ($doit != ''){
			$item_name = $this->get_item_name($item_id);
			echo "Are you sure you want to delete <strong>&quot;$item_name&quot;</strong>?<ul><li><a href=\"$this->url&amp;action=delete&amp;item_id=$item_id&amp;doit=true\">Yes</a></li><li><a href=\"$this->url\">No</a></li></p>";	
		
		}else{
			// do the deletion
			
			if (is_numeric($item_id) && $item_id != ""){
				
				$item_name = $this->get_item_name($item_id);
				echo "Deleting <strong>&quot;$item_name&quot;</strong>... ";
				
				// part one -- delete the item
				$result = db_query("DELETE FROM $this->sql_item_table, $this-> WHERE item_id = '$item_id'");
				
				if (db_is_valid_result($result) && db_affected_rows($result) == 1){
					echo "Item deleted.";
				
					update_menus($item_id);
				
					// delete the group references to the item
					$result = db_query("DELETE FROM $cfg[t_menu_groups] WHERE item_id = '$item_id'");
					if ($result && db_affected_rows($result) == 1)
						echo " References to item also deleted.";
					// otherwise, probably no references
					
				}else{
					echo "error deleting item!";
				}
				
				echo "<p><a href=\"$this->url\">Menu Item administration page</a><br><a href=\"##pageroot##/?mode=edmenu\">Menu Group administration page</a><br><a href=\"##pageroot##/\">Administration Home</a></p>";
				
			}
		}
	}
	
	
	function fn_remove(){
	
	
	
	}
	
	
	
}
	
	/*
			// verify input params
			if (!isset($_POST['item_text']))
				$item_text = "";
			else
				$item_text = db_escape_string(html_entity_decode($_POST['item_text'],ENT_NOQUOTES));
				
			if (!isset($_POST['item_url']))
				$item_url = "";
			else
				$item_url = db_escape_string(html_entity_decode($_POST['item_url'],ENT_NOQUOTES));
		
			// choose an action
			switch($ed_action){
				case "newitem":
					
					if ($item_text != ""){
					
						// preserve item changes -- is this necessary? 
						$item_text = htmlentities($item_text,ENT_NOQUOTES);

						// verify the item doesn't exist already -- though, technically it doesn't matter
						$result = db_query("SELECT item_id FROM $cfg[t_menu_items] WHERE text = '$item_text' AND href = '$item_url'");
						if (db_has_rows($result)){
						
							echo "Error: Menu Item <strong>&quot;$item_text&quot;</strong> already exists!<p>";
							edmenuitem("shownew");
							
						}else{
							
							// insert new item
							$result = db_query("INSERT INTO $cfg[t_menu_items] (text, href) VALUES ('$item_text', '$item_url')");
							if ($result && db_affected_rows($result))
								echo "Menu Item <strong>&quot;$item_text&quot;</strong> was inserted successfully!";
							else
								echo "Error adding new Menu Item <strong>&quot;$item_text&quot;</strong>:" . db_error();
								
							echo "<p><a href=\"$this->url\">Menu Item administration page</a><br><a href=\"##pageroot##/?mode=edmenu\">Menu Group administration page</a><br><a href=\"##pageroot##/\">Administration Home</a></p>";

						}
					
					}else{
						echo "Error in new item!<p>";
						edmenuitem("shownew");
					}
					
					break;
					
			case "edit":
			
				// item id is passed in.  Get params
				$result = db_query("SELECT text,href FROM $cfg[t_menu_items] WHERE item_id = '" . db_escape_string($item_id) . "'");
				if ($result && db_num_rows($result) == 1){
				
					$row = db_fetch_row($result);
					echo "<form method=post action=\"$this->url&amp;ed_action=doedit&amp;item_id=$item_id\"><table><tr><td>Item Title (text text)</td><td><input name=\"item_text\" type=\"text\" size=\"50\" value=\"" . special_item_strip($row[0]) . "\"></td></tr><tr><td>Item URL</td><td><input name=\"item_url\" type=\"text\" size=\"50\" value=\"" . special_item_strip($row[1]) . "\"></td></tr><tr><td>&nbsp;</td><td><input type=submit value=\"Edit Menu Item\"></td></tr></table></form>";
				
				}else{
				
					header( "location:$cfg[page_root]/?mode=edmenuitem");
				
				}
				break;
				
			case "doedit";
			
				// actually update the database
				if (is_numeric($item_id) && $item_id != "" && $item_text != "" && $item_url != ""){
			
					// ok, we have valid data. So... 
					$result = db_query("UPDATE $cfg[t_menu_items] SET text = '$item_text', href = '$item_url' WHERE item_id = '$item_id'");
					
					if ($result && db_affected_rows($result) == 1)
						echo "Change complete.";
					else
						echo "Error: " . db_error();
					
					update_menus($item_id);
					
					echo "<p><a href=\"$this->url\">Menu Item administration page</a><br><a href=\"##pageroot##/?mode=edmenu\">Menu Group administration page</a><br><a href=\"##pageroot##/\">Administration Home</a></p>";
				
				}else{
					echo "Error adding item!";
				}
		
				break;
			
			
				
			case "autofill":

				$count = 0;
				$result = db_query("SELECT page_title,url FROM $cfg[t_content] WHERE hidden = '0'");
				if (db_has_rows($result)){
					
					while($row = db_fetch_row($result)){
					
						// see if it already exists
						$iresult = db_query("SELECT text FROM $cfg[t_menu_items] WHERE href = '##rootdir##" . db_escape_string($row[1]) . "'");
						if (!$iresult || db_num_rows($iresult) == 0){
							// doesn't exist, add defaults
							
							db_query("INSERT INTO $cfg[t_menu_items] (text, href) VALUES ('" . db_escape_string($row[0]) . "', '##rootdir##" . db_escape_string($row[1]) . "')");
							$count++;
						}
					}
			
					echo "$count Items automatically added. :)<p>";
				}else{
					echo "No pages in database.<p>";
				}
				edmenuitem("shownew");
				break;
				
			default:
				header( "location:$cfg[page_root]/?mode=edmenuitem");
				break;
			}
		
		}
		
	}

function get_menu_item_name($id){
	global $cfg;
	
	if (!is_numeric($id))
		return;
		
	$result = db_query("SELECT text FROM $cfg[t_menu_items] WHERE item_id = '$id'");
	if ($result && db_num_rows($result) == 1){
		$row = db_fetch_row($result);
		return special_item_strip($row[0]);
	}

	return;
}

function update_menus($item_id){

	global $cfg;

	// this can probably be done in one query, oh well.. 
	$result = db_query("SELECT menu_id FROM $cfg[t_menu_groups] WHERE item_id = '$item_id'");
	if (db_has_rows($result))
		while ($row = db_fetch_row($result))
			db_query("UPDATE $cfg[t_content] SET other_update = NOW() WHERE menu_id = '$row[0]'");

}

}
*/

?>
