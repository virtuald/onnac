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

	Administration tool -- edit banner groups
	
*/

function edbanner($error = "no"){

	global $cfg;
	
	// make db connection
	if ($error == "no"){
		
		echo "<h4>Banner editor</h4>";
	
	}
	
	// get variables
	if (!isset($_GET['banner_id']))
		$banner_id = "";
	else
		$banner_id = $_GET['banner_id'];
		
	if (!isset($_GET['item_id']))
		if (!isset($_POST['item_id']))
			$item_id = "";
		else
			$item_id = $_POST['item_id'];
	else
		$item_id = $_GET['item_id'];
	
	if (!isset($_GET['ed_action']))
		$ed_action = "";
	else
		$ed_action = $_GET['ed_action'];
	
	
	if ($banner_id == "" || $error == "shownew"){
	
		// echo new group
		// echo goto item editor
		echo "<form method=post action=\"##pageroot##/?mode=edbanner&amp;banner_id=newbanner&amp;ed_action=newbanner\"><input name=item_id type=text>&nbsp;<input type=submit value=\"Add new group\"></form>";
	
		$result = db_query("SELECT name,banner_id FROM $cfg[t_banners] ORDER BY name ASC");
		
		if (db_has_rows($result)){

			echo "<table><thead><tr><td>Group name</td><td>Items</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr></thead>";
		
			while( $row = db_fetch_row($result)){
			
				echo "<tr><td>" . special_item_strip($row[0]) . "</td><td>";
		
				// get each item for the result, and nest it in
			
				$iresult = db_query("SELECT bit.alt,bit.src,bgt.item_id FROM $cfg[t_banner_items] bit, $cfg[t_banner_groups] bgt WHERE bgt.banner_id = '$row[1]' AND bgt.item_id = bit.item_id ORDER BY bit.src ASC");
				if ($iresult && db_num_rows($iresult) > 0){
				
					echo "<table>";
					while ($irow = db_fetch_row($iresult))
						echo "<tr><td>" . special_item_strip($irow[0]) . "</td><td>" . special_item_strip($irow[1]) . "</td><td><a href=\"##pageroot##/?mode=edbanneritem&amp;ed_action=edit&amp;item_id=$irow[2]\">[Edit]</a></td><td><a href=\"##pageroot##/?mode=preview&amp;type=banneritem&amp;item_id=$irow[2]\">[Show]</a></td><td><a href=\"##pageroot##/?mode=edbanner&amp;ed_action=removeitem&amp;banner_id=$row[1]&item_id=$irow[2]\">[Remove]</a></td></tr>";
					
					echo "</table>";
				
				}else{
					echo "No items.";
				}
			
				echo "</td><td><a href=\"##pageroot##/?mode=edbanner&banner_id=$row[1]&amp;ed_action=add\">[Add Item]</a></td><td><a href=\"##pageroot##/?mode=preview&amp;type=banner&amp;group=$row[1]\">[Show]</a></td><td><a href=\"##pageroot##/?mode=edbanner&amp;banner_id=$row[1]&amp;ed_action=delete\">[Delete Group]</a></td></tr>";
			
			}
		
			echo "</table>";
		
		}else{
		
			echo "No banner groups found.";
		
		}
	
		echo "<p><a href=\"##pageroot##/\">Administration Home</a></p>";
	
	}else{

	
		switch($ed_action){
			case "newbanner":
				
				// make it safe
				$item_id = db_escape_string($item_id);
				
				if ($item_id != ""){
					// verify the name doesn't exist already
					$result = db_query("SELECT banner_id FROM $cfg[t_banners] WHERE name='$item_id'");
					if (db_is_valid_result($result)){
						if (db_num_rows($result) > 0){
						
							echo "Error: Banner group <strong>&quot;$item_id&quot;</strong> already exists!<p>";
							edbanner("shownew");
							
						}else{
							
							// insert new group
							$result = db_query("INSERT INTO $cfg[t_banners] (name) VALUES ('$item_id')");
							if (db_is_valid_result($result) && db_affected_rows($result))
								echo "Banner group <strong>&quot;$item_id&quot;</strong> was inserted successfully!";
							else
								echo "Error adding new banner group <strong>&quot;$item_id&quot;</strong>!";
										
							echo "<p><a href=\"##pageroot##/?mode=edbanner\">Banner group administration page</a><br><a href=\"##pageroot##/\">Administration Home</a></p>";
							
						}
					}
				
				}else{
					edbanner("shownew");
				}
				
				break;
				
		case "add":
	
			// add an item to a group
			$banner_name = get_banner_name($banner_id);
			
			// XXX: This wont work in MySQL 4.0! 
			$result = db_query("SELECT item_id,alt,src FROM $cfg[t_banner_items] WHERE item_id NOT IN (SELECT item_id FROM $cfg[t_banner_groups] WHERE banner_id = '$banner_id') ORDER BY src ASC");
	
			if (db_has_rows($result)){
			
				echo "Add an item to banner group <strong>&quot;$banner_name&quot;</strong>: <p><table>";
				
				while($row = db_fetch_row($result))
					echo "<tr><td>" . special_item_strip($row[1]) . "</td><td>" . special_item_strip($row[2]) . "</td><td><a href=\"##pageroot##/?mode=edbanner&amp;ed_action=doadd&amp;banner_id=$banner_id&amp;item_id=$row[0]\">[Add]</a></td></tr>";
					
				echo "</table>";
				
			}else{
				echo "There are no items to add.";
			}
			echo "<p><a href=\"##pageroot##/?mode=edbanner\">Banner group administration page</a><br><a href=\"##pageroot##/\">Administration Home</a></p>";			
		
			break;
			
		case "doadd";
			// actually do the adding part
			if (is_numeric($item_id) && is_numeric($banner_id) && $item_id != ""){
		
				$banner_name = get_banner_name($banner_id);
				$item_name = get_banner_item_name($item_id);
				echo "Adding banner item <strong>&quot;$item_name&quot;</strong> to banner group <strong>&quot;$banner_name&quot;</strong>...<p>";
				// ok, we have valid data. So... 
				
				$result = db_query("INSERT INTO $cfg[t_banner_groups] (banner_id, item_id) VALUES ('$banner_id', '$item_id')");
				
				if (db_is_valid_result($result) && db_affected_rows($result) == 1)
					echo "Complete. <a href=\"##pageroot##/?mode=edbanner&amp;banner_id=$banner_id&ed_action=add\">[Add another item]</a>";
					
				echo "<p><a href=\"##pageroot##/?mode=edbanner\">Banner group administration page</a><br><a href=\"##pageroot##/\">Administration Home</a></p>";
			
			}else{
				echo "Error adding item!";
			}
	
			break;
		
		case "delete":

			$banner_name = get_banner_name($banner_id);
		
			echo "Are you sure you want to delete banner group <strong>&quot;$banner_name&quot;</strong>? This will only delete the group, and not the contents of the group!<p><a href=\"##pageroot##/?mode=edbanner&amp;ed_action=reallydelete&amp;banner_id=$banner_id\">Yes</a><br><a href=\"##pageroot##/?mode=edbanner\">No</a></p>";	
			break;
			
		case "reallydelete":
		
			if (is_numeric($banner_id)){
			
				$banner_name = get_banner_name($banner_id);
				echo "Deleting <strong>&quot;$banner_name&quot;</strong>...<p>";
				
				$result = db_query("DELETE FROM $cfg[t_banners] WHERE banner_id = '$banner_id'");
				if (db_is_valid_result($result) && db_affected_rows($result) == 1)
					echo "Deleted.";
				else
					echo "Error.";
					
				echo "<p><a href=\"##pageroot##/?mode=edbanner\">Banner group administration page</a><br><a href=\"##pageroot##/\">Administration Home</a></p>";
				
			}
			break;
			
		case "removeitem":
		
			if ($item_id == ""){
				edbanner("shownew");
			}else{
		
				$banner_name = get_banner_name($banner_id);
				$item_name = get_banner_item_name($item_id);
				echo "Are you sure you want to remove item <strong>&quot;$item_name&quot;</strong> from banner group <strong>&quot;$banner_name&quot;</strong>? This will only remove the item from the group. The contents of the group, and the item itself, will not be deleted.<p><a href=\"##pageroot##/?mode=edbanner&amp;ed_action=reallyremoveitem&amp;banner_id=$banner_id&amp;item_id=$item_id\">Yes</a><br><a href=\"##pageroot##/?mode=edbanner\">No</a></p>";	
			}
		
			break;
			
		case "reallyremoveitem":
			
			if (is_numeric($banner_id) && is_numeric($item_id) && $item_id != ""){
			
				$banner_name = get_banner_name($banner_id);
				$item_name = get_banner_item_name($item_id);
				
				echo "Removing <strong>&quot;$item_name&quot;</strong> from <strong>&quot;$banner_name&quot;</strong>...<p>";
				
				$result = db_query("DELETE FROM $cfg[t_banner_groups] WHERE banner_id = '$banner_id' and item_id = '$item_id'");
				if (db_is_valid_result($result) && db_affected_rows($result) == 1)
					echo "Removed.";
				else
					echo "Error.";
					
				echo "<p><a href=\"##pageroot##/?mode=edbanner\">Banner group administration page</a><br><a href=\"##pageroot##/\">Administration Home</a></p>";
				
			}
			break;
		
		default:
			edbanner("shownew");
			break;
		}
	
	}
	
}


function get_banner_name($id){
	
	global $cfg;
	
	if (!is_numeric($id))
		return;
		
	$result = db_query("SELECT name FROM $cfg[t_banners] WHERE banner_id = '$id'");
	if ($result && db_num_rows($result) == 1){
		$row = db_fetch_row($result);
		return special_item_strip($row[0]);
	}

	return;
}

function get_banner_item_name($id){
	global $cfg;
	
	if (!is_numeric($id))
		return;
		
	$result = db_query("SELECT alt FROM $cfg[t_banner_items] WHERE item_id = '$id'");
	if ($result && db_num_rows($result) == 1){
		$row = db_fetch_row($result);
		return special_item_strip($row[0]);
	}

	return;
}

?>
