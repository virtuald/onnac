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

	Administration tool -- edit banner items
		
*/

function edbanneritem($error = "no"){

	global $cfg;
	
	// make db connection
	if ($error == "no"){
		
		echo "<h4>Banner Item editor</h4>";
	
	}
	
	//get variables
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
	

	if ($item_id == "" || $error == "shownew"){
	
		echo "<form method=post action=\"##pageroot##/?mode=edbanneritem&amp;ed_action=newitem&amp;item_id=0\"><table><tr><td>Item Title (alt text)</td><td><input name=\"item_alt\" type=\"text\" size=\"50\"></td></tr><tr><td>Item URL</td><td><input name=item_url type=text value=\"" . special_item_strip("##rootdir##/img/banners/") . "\" size=\"50\"></td></tr><tr><td>&nbsp;</td><td><input type=submit value=\"Add new banner item\"></td></tr></table></form><a href=\"##pageroot##/?mode=edbanneritem&amp;item_id=0&amp;ed_action=autofill\">Autofill list from $cfg[img_autofill_dir]</a><p>Existing items:<p>";
	
		$result = db_query("SELECT alt,src,item_id FROM $cfg[t_banner_items] ORDER BY src");
		
		if (db_has_rows($result)){

			echo "<table><thead><tr><td>Item Title (Alt text)</td><td>Item URL</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr></thead>";
		
			while( $row = db_fetch_row($result))
				echo "<tr><td>" . special_item_strip($row[0]) . "</td><td>" . special_item_strip($row[1]) . "</td><td><a href=\"##pageroot##/?mode=edbanneritem&amp;item_id=$row[2]&amp;ed_action=edit\">[Edit Item]</a></td><td><a href=\"##pageroot##/?mode=preview&amp;type=banneritem&amp;item_id=$row[2]\">[Show Image]</a></td><td><a href=\"##pageroot##/?mode=edbanneritem&amp;item_id=$row[2]&amp;ed_action=delete\">[Delete Item]</a></td></tr>";
		
			echo "</table>";
		
		}else{
		
			echo "No items found.";
		
		}
	
		echo "<p><a href=\"##pageroot##/\">Administration Home</a></p>";
	
	}else{

		// verify input params
		if (!isset($_POST['item_alt']))
			$item_alt = "";
		else
			$item_alt = db_escape_string(html_entity_decode($_POST['item_alt'],ENT_NOQUOTES));
			
		if (!isset($_POST['item_url']))
			$item_url = "";
		else
			$item_url = db_escape_string(html_entity_decode($_POST['item_url'],ENT_NOQUOTES));
	
		// choose an action
		switch($ed_action){
			case "newitem":
				
				if ($item_alt != "" && $item_url != ""){
				
					// preserve item changes
					$item_alt = htmlentities($item_alt,ENT_NOQUOTES);
				
					// verify the item doesn't exist already -- though, technically it doesn't matter
					$result = db_query("SELECT item_id FROM $cfg[t_banner_items] WHERE alt = '$item_alt' && src = '$item_url'");
					if (db_has_rows($result)){
					
						echo "Error: Banner item <strong>&quot;$item_alt&quot;</strong> already exists!<p>";
						edbanneritem("shownew");
						
					}else{
						
						// insert new group
						$result = db_query("INSERT INTO $cfg[t_banner_items] (alt, src) VALUES ('$item_alt', '$item_url')");
						if ($result && db_affected_rows($result))
							echo "Banner item <strong>&quot;$item_alt&quot;</strong> was inserted successfully!";
						else
							echo "Error adding new banner item <strong>&quot;$item_alt&quot;</strong>:" . db_error();
							
						echo "<p><a href=\"##pageroot##/?mode=edbanneritem\">Banner Item administration page</a><br><a href=\"##pageroot##/?mode=edbanner\">Banner group administration page</a><br><a href=\"##pageroot##/\">Administration Home</a></p>";

					}
				
				}else{
					echo "Error in new item!<p>";
					edbanneritem("shownew");
				}
				
				break;
				
		case "edit":
		
			// item id is passed in.  Get params
			$result = db_query("SELECT alt,src FROM $cfg[t_banner_items] WHERE item_id = '" . db_escape_string($item_id) . "'");
			if ($result && db_num_rows($result) == 1){
			
				$row = db_fetch_row($result);
				echo "<form method=post action=\"##pageroot##/?mode=edbanneritem&amp;ed_action=doedit&amp;item_id=$item_id\"><table><tr><td>Item Title (alt text)</td><td><input name=\"item_alt\" type=\"text\" size=\"50\" value=\"" . special_item_strip($row[0]) . "\"></td></tr><tr><td>Item URL</td><td><input name=\"item_url\" type=\"text\" size=\"50\" value=\"" . special_item_strip($row[1]) . "\"></td></tr><tr><td>&nbsp;</td><td><input type=submit value=\"Edit banner item\"></td></tr></table></form>";
			
			}else{
			
				echo "Error: could not find existing item to edit!<p>";
				edbanneritem("shownew");
			
			}
			break;
			
		case "doedit";
		
			// actually update the database
			if (is_numeric($item_id) && $item_id != "" && $item_alt != "" && $item_url != ""){
		
				// ok, we have valid data. So... 
				$result = db_query("UPDATE $cfg[t_banner_items] SET alt = '$item_alt', src = '$item_url' WHERE item_id = '$item_id'");
				
				if ($result && db_affected_rows($result) == 1)
					echo "Change complete.";
				else
					echo "Error: " . db_error();
					
				echo "<p><a href=\"##pageroot##/?mode=edbanneritem\">Banner Item administration page</a><br><a href=\"##pageroot##/?mode=edbanner\">Banner group administration page</a><br><a href=\"##pageroot##/\">Administration Home</a></p>";
			
			}else{
				echo "Error adding item!";
			}
	
			break;
		
		case "delete":

			$item_name = get_banner_item_name($item_id);
			echo "Are you sure you want to delete banner item <strong>&quot;$item_name&quot;</strong>?<p><a href=\"##pageroot##/?mode=edbanneritem&amp;ed_action=reallydelete&amp;item_id=$item_id\">Yes</a><br><a href=\"##pageroot##/?mode=edbanneritem\">No</a></p>";	
			break;
			
		case "reallydelete":
		
			if (is_numeric($item_id) && $item_id != ""){
			
				$item_name = get_banner_item_name($item_id);
				echo "Deleting <strong>&quot;$item_name&quot;</strong>...<p>";
				
				// part one -- delete the item
				$result = db_query("DELETE FROM $cfg[t_banner_items] WHERE item_id = '$item_id'");
				if ($result && db_affected_rows($result) == 1){
					echo "Item deleted.";
				
					// part two -- delete the references to the item
					$result = db_query("DELETE FROM $cfg[t_banner_groups] WHERE item_id = '$item_id'");
					if ($result && db_affected_rows($result) == 1)
						echo " References to item also deleted.";
					// otherwise, probably no references
					
				}else{
					echo "Error: " . db_error();
				}
				
				echo "<p><a href=\"##pageroot##/?mode=edbanneritem\">Banner Item administration page</a><br><a href=\"##pageroot##/?mode=edbanner\">Banner group administration page</a><br><a href=\"##pageroot##/\">Administration Home</a></p>";
				
			}
			break;
			
		case "autofill":

			$count = 0;

			$dir = "$cfg[basedir]$cfg[img_autofill_dir]/";
			if (is_dir($dir)){
			
				$hdir = opendir($dir);
				while (false !== ($file = readdir($hdir))){
				
					if (is_file("$dir/$file")){
					
						// see if it already exists
						$result = db_query("SELECT alt FROM $cfg[t_banner_items] WHERE src = '##rootdir##$cfg[img_autofill_dir]/" . db_escape_string($file) . "'");
						if (!$result || db_num_rows($result) == 0){
							// doesn't exist, add defaults
							
							db_query("INSERT INTO $cfg[t_banner_items] (alt, src) VALUES ('default', '##rootdir##$cfg[img_autofill_dir]/" . db_escape_string($file) . "')");
							$count++;
						}

					}
				}

				echo "<p>$count Items automatically added. :)</p>";
			}else{
				echo "<p>Could not open directory: $dir</p>";
			}
			edbanneritem("shownew");
			break;
		
		default:
			edbanneritem("shownew");
			break;
		}
	
	}
	
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
