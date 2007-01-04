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

	Administration tool -- edit Menu Groups
		
*/

function edmenu($error = "no"){

	global $cfg;
	
	// make db connection
	if ($error == "no"){
		
		echo "<h4>Menu editor</h4>";

	}
	
	$menu_id = get_get_var('menu_id');
	$ed_action = get_get_var('ed_action');
		
	if (!isset($_GET['item_id']))
		if (!isset($_POST['item_id']))
			$item_id = "";
		else
			$item_id = $_POST['item_id'];
	else
		$item_id = $_GET['item_id'];
	
	
	if ($menu_id == "" || $error == "shownew"){
	
		// echo new group
		// echo goto item editor
		echo "<form method=post action=\"##pageroot##/?mode=edmenu&amp;menu_id=newmenu&amp;ed_action=newmenu\"><input name=item_id type=text>&nbsp;<input type=submit value=\"Add new group\"></form>";
	
		$result = db_query("SELECT name,menu_id FROM $cfg[t_menus] ORDER BY name ASC");
		
		if (db_has_rows($result)){
		
			echo "<table><thead><tr><td>Group name</td><td>Items</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr></thead>";
		
			while( $row = db_fetch_row($result)){
			
				echo "<tr><td>" . special_item_strip($row[0]) . "</td><td>";
		
				// get each item for the result, and nest it in
			
				$iresult = db_query("SELECT mit.text,mit.href,mgt.item_id,mgt.rank FROM $cfg[t_menu_items] mit, $cfg[t_menu_groups] mgt WHERE mgt.menu_id = '$row[1]' AND mgt.item_id = mit.item_id ORDER BY mgt.rank ASC");
				if ($iresult && db_num_rows($iresult) > 0){
				
					// table of items
					echo "<table>";
					while ($irow = db_fetch_row($iresult))
						echo "<tr><td>" . special_item_strip($irow[0]) . "</td><td>" . special_item_strip($irow[1]) . "</td><td><a href=\"##pageroot##/?mode=edmenuitem&amp;ed_action=edit&amp;item_id=$irow[2]\">[Edit]</a></td><td><a href=\"##pageroot##/?mode=preview&amp;type=menuitem&amp;item_id=$irow[2]\">[Show]</a></td><td><a href=\"##pageroot##/?mode=edmenu&amp;ed_action=removeitem&amp;menu_id=$row[1]&amp;item_id=$irow[2]\">[Remove]</a></td><td><a href=\"##pageroot##/?mode=edmenu&amp;ed_action=changerank&amp;newrank=up&amp;menu_id=$row[1]&item_id=$irow[2]\">[U]</a></td><td><a href=\"##pageroot##/?mode=edmenu&amp;ed_action=changerank&amp;newrank=down&amp;menu_id=$row[1]&amp;item_id=$irow[2]\">[D]</a></td></tr>";
					
					echo "</table>";
				
				}else{
					echo "No items.";
				}
			
				echo "</td><td><a href=\"##pageroot##/?mode=edmenu&amp;menu_id=$row[1]&amp;ed_action=add\">[Add Item]</a></td><td><a href=\"##pageroot##/?mode=preview&amp;type=menu&amp;group=$row[1]\">[Show]</a></td><td><a href=\"##pageroot##/?mode=edmenu&amp;menu_id=$row[1]&amp;ed_action=delete\">[Delete Group]</a></td></tr>";
			
			}
		
			echo "</table>";
		
		}else{
		
			echo "No Menu Groups found.";
		
		}
	
		echo "<p><a href=\"##pageroot##/\">Administration Home</a></p>";
	
	}else{

	
		switch($ed_action){
			case "newmenu":
				
				// make it safe and confusing, $item_id is really menu_id
				$item_id = db_escape_string($item_id);
				
				if ($item_id != ""){
					// verify the name doesn't exist already
					$result = db_query("SELECT menu_id FROM $cfg[t_menus] WHERE name='$item_id'");
					if (db_has_rows($result)){
					
						echo "Error: Menu Group <strong>&quot;$item_id&quot;</strong> already exists!<p>";
						edmenu("shownew");
						
					}else{
						
						// insert new group
						$result = db_query("INSERT INTO $cfg[t_menus] (name) VALUES ('$item_id')");
						if ($result && db_affected_rows($result))
							echo "Menu Group <strong>&quot;$item_id&quot;</strong> was inserted successfully!";
						else
							echo "Error adding new Menu Group <strong>&quot;$item_id&quot;</strong>: " . db_error();
							
						echo "<p><a href=\"##pageroot##/?mode=edmenu\">Menu Group administration page</a><br><a href=\"##pageroot##/\">Administration Home</a></p>";
						
					}
				
				}else{
					edmenu("shownew");
				}
				
				break;
				
		case "add":
	
			// add an item to a group
			$menu_name = get_menu_name($menu_id);
			
			$result = db_query("SELECT a.item_id,a.text,a.href FROM $cfg[t_menu_items] a
	LEFT JOIN $cfg[t_menu_groups] b ON a.item_id =  b.item_id AND b.menu_id = '6'
	WHERE b.item_id IS NULL ORDER BY href ASC");
			
			if (db_has_rows($result)){
			
				echo "Add an item to Menu Group <strong>&quot;$menu_name&quot;</strong>: <p><table>";
				
				while($row = db_fetch_row($result))
					echo "<tr><td>" . special_item_strip($row[1]) . "</td><td>" . special_item_strip($row[2]) . "</td><td><a href=\"##pageroot##/?mode=edmenu&amp;ed_action=doadd&amp;menu_id=$menu_id&item_id=$row[0]\">[Add]</a></td></tr>";
					
				echo "</table>";
				
			}else{
				if (!$result)
					echo "DB Error: " . db_error();
				else
					echo "There are no items to add.";
			}
			echo "<p><a href=\"##pageroot##/?mode=edmenu\">Menu Group administration page</a><br><a href=\"##pageroot##/\">Administration Home</a></p>";			
		
			break;
			
		case "doadd";
			// actually do the adding part
			if (is_numeric($item_id) && is_numeric($menu_id) && $item_id != ""){
		
				$rank = 0;	// default rank
				$menu_name = get_menu_name($menu_id);
				$item_name = get_menu_item_name($item_id);
				echo "Adding Menu Item <strong>&quot;$item_name&quot;</strong> to Menu Group <strong>&quot;$menu_name&quot;</strong>...<p>";
				// ok, we have valid data. So... 
				
				// first, we need to determine the largest existing rank, and do one better
				$result = db_query("SELECT rank FROM $cfg[t_menu_groups] WHERE menu_id = '$menu_id' ORDER BY rank DESC LIMIT 1");
				if ($result && db_num_rows($result) == 1){
					$row = db_fetch_row($result);
					$rank = $row[0] + 1;
				}
				
				// next, we can insert the item into the table, once its rank is determined.. 
				$result = db_query("INSERT INTO $cfg[t_menu_groups] (menu_id, item_id, rank) VALUES ('$menu_id', '$item_id', '$rank')");
				if ($result && db_affected_rows($result) == 1){
					echo "Complete. <a href=\"##pageroot##/?mode=edmenu&amp;menu_id=$menu_id&amp;ed_action=add\">[Add another item]</a>";
					// update content
					db_query("UPDATE $cfg[t_content] SET other_update = NOW() WHERE menu_id = '$menu_id'");
				}else{
					echo "Error: " . db_error();
				}
					
				echo "<p><a href=\"##pageroot##/?mode=edmenu\">Menu Group administration page</a><br><a href=\"##pageroot##/\">Administration Home</a></p>";
			
			}else{
				echo "Error adding item!";
			}
	
			break;
		
		case "delete":

			$menu_name = get_menu_name($menu_id);
		
			echo "Are you sure you want to delete Menu Group <strong>&quot;$menu_name&quot;</strong>? This will only delete the group, and not the contents of the group!<p><a href=\"##pageroot##/?mode=edmenu&amp;ed_action=reallydelete&amp;menu_id=$menu_id\">Yes</a><br><a href=\"##pageroot##/?mode=edmenu\">No</a></p>";	
			break;
			
		case "reallydelete":
		
			if (is_numeric($menu_id)){
			
				$menu_name = get_menu_name($menu_id);
				echo "Deleting <strong>&quot;$menu_name&quot;</strong>...<p>";
				
				$result = db_query("DELETE FROM $cfg[t_menus] WHERE menu_id = '$menu_id'");
				if ($result && db_affected_rows($result) == 1){
					echo "Deleted.";
					// update the content
					db_query("UPDATE $cfg[t_content] SET other_update = NOW(), menu_id = '-1' WHERE menu_id = '$menu_id'");
				}else
					echo "Error: " . db_error();
					
				echo "<p><a href=\"##pageroot##/?mode=edmenu\">Menu Group administration page</a><br><a href=\"##pageroot##/\">Administration Home</a></p>";
				
			}
			break;
			
		case "removeitem":
		
			if ($item_id == ""){
				edmenu("shownew");
			}else{
		
				$menu_name = get_menu_name($menu_id);
				$item_name = get_menu_item_name($item_id);
				echo "Are you sure you want to remove item <strong>&quot;$item_name&quot;</strong> from Menu Group <strong>&quot;$menu_name&quot;</strong>? This will only remove the item from the group. The contents of the group, and the item itself, will not be deleted.<p><a href=\"##pageroot##/?mode=edmenu&amp;ed_action=reallyremoveitem&amp;menu_id=$menu_id&amp;item_id=$item_id\">Yes</a><br><a href=\"##pageroot##/?mode=edmenu\">No</a></p>";	
			}
		
			break;
			
		case "reallyremoveitem":
			
			if (is_numeric($menu_id) && is_numeric($item_id) && $item_id != ""){
			
				$menu_name = get_menu_name($menu_id);
				$item_name = get_menu_item_name($item_id);
				
				echo "Removing <strong>&quot;$item_name&quot;</strong> from <strong>&quot;$menu_name&quot;</strong>...<p>";
				
				$result = db_query("DELETE FROM $cfg[t_menu_groups] WHERE menu_id = '$menu_id' && item_id = '$item_id'");
				if ($result && db_affected_rows($result) == 1){
					echo "Removed.";
					db_query("UPDATE $cfg[t_content] SET other_update = NOW() WHERE menu_id = '$menu_id'");
				}else
					echo "Error: " . db_error();
					
				echo "<p><a href=\"##pageroot##/?mode=edmenu\">Menu Group administration page</a><br><a href=\"##pageroot##/\">Administration Home</a></p>";
				
			}
			break;
			
		case "changerank":
		
			if (is_numeric($menu_id) && is_numeric($item_id) && $item_id != "" && $menu_id != ""){ 
		
				if (isset($_GET['newrank'])){
					$newrank = $_GET['newrank'];	// 'up' or 'down'
					
					// get all menu items
					$result = db_query("SELECT item_id,rank FROM $cfg[t_menu_groups] WHERE menu_id = '$menu_id' ORDER BY rank ASC");
		
					$found = 0;
					
					if ($result && db_num_rows($result) > 1){
						// iterate and find self
						while($row = db_fetch_row($result)){
							
							if ($row[0] == $item_id){
								$found = 1;
								break;
							}
							$last_row = $row;
						}
						
						if ($found == 1){
							// if up, find last item
							if ($newrank == "up"){
								// update that item with our rank, if we're not first already
								if (isset($last_row)){
									// update previous item
									db_query("UPDATE $cfg[t_menu_groups] set rank = '$row[1]' WHERE menu_id = '$menu_id' AND item_id = '$last_row[0]'"); 
									// update ourself
									db_query("UPDATE $cfg[t_menu_groups] set rank = '$last_row[1]' WHERE menu_id = '$menu_id' AND item_id = '$item_id'");
								}
							}else if ($newrank == "down"){
								echo "Moving down..<p>";
								// if down, find next item
								if ($last_row = db_fetch_row($result)){
									// update that item
									db_query("UPDATE $cfg[t_menu_groups] set rank = '$row[1]' WHERE menu_id = '$menu_id' AND item_id = '$last_row[0]'");
									// update ourself
									db_query("UPDATE $cfg[t_menu_groups] set rank = '$last_row[1]' WHERE menu_id = '$menu_id' AND item_id = '$item_id'");
								}
							}
						}
						
						// update the content
						db_query("UPDATE $cfg[t_content] SET other_update = NOW() WHERE menu_id = '$menu_id'");	
					}
				}
			}
		
			header( "Location:$cfg[page_root]/?mode=edmenu");
			break;
		
		default:
			edmenu("shownew");
			break;
		}
	
	}
	
}


function get_menu_name($id){
	
	global $cfg;
	
	if (!is_numeric($id))
		return;
		
	$result = db_query("SELECT name FROM $cfg[t_menus] WHERE menu_id = '$id'");
	if ($result && db_num_rows($result) == 1){
		$row = db_fetch_row($result);
		return special_item_strip($row[0]);
	}

	return;
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

?>
