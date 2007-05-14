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

	Administration tool -- preview menus, banners, pages
	
	TODO:
		-- Add page preview!
		-- This page/feature may or may not be used anymore! Check on that.. 
*/

function preview(){

	global $cfg;


	if (!isset($_GET['type']))
		return;
	else
		$type = $_GET['type'];
		
	$group = db_escape_string(get_get_var('group'));
	$h_group = htmlentities(get_get_var('group'));
	$item_id = db_escape_string(get_get_var('item_id'));
	
	switch ($type){
	
		case "banner":
		
			if ($group != ""){
				$result = db_query("SELECT name FROM $cfg[t_banners] WHERE banner_id = '$group'");
				if ($result && db_num_rows($result) == 1){
			
					$row = db_fetch_row($result);
					echo "Preview of banner group $row[0] (ID: $h_group)<p>";
				
					$result = db_query("SELECT bit.src,bit.alt FROM $cfg[t_banner_items] bit,$cfg[t_banner_groups] bgt  WHERE bgt.banner_id = '$h_group' AND bit.item_id = bgt.item_id");
				
					if (db_has_rows($result)){
						while($row = db_fetch_row($result)){
							echo "<img src=\"" . htmlentities($row[0]) . "\" alt=\"" . htmlentities($row[1]) . "\"><br>";
							echo "Src: " . htmlentities($row[0]) . "<br>Alt: " . htmlentities($row[1]) . "<p>";
						}
					}else{
						echo "No banners found for group $h_group.";
					}
				}else{
					if ($group == -1)
						echo "This is the no banners group.";
					else
						echo "No banners found for group $h_group.";
				}
			}
			
			break;
			
		case "banneritem":
			
			if ($item_id != ""){
			
				$result = db_query("SELECT src,alt FROM $cfg[t_banner_items] WHERE item_id = '$item_id'");
				if ($result && db_num_rows($result) == 1){
					$row = db_fetch_row($result);
					
					$row[0] = htmlentities($row[0]);
					$row[1] = htmlentities($row[1]);
					
					echo "<img src=\"$row[0]\" alt=\"$row[1]\"><br>";
					echo "Src: $row[0]<br>Alt: $row[1]<p>";
				}else{
					echo "Item id &quot;$item_id&quot; does not exist!";
				}
			}
			
			break;
		
		case "menu":
			
			$result = db_query("SELECT mt.name FROM $cfg[t_menus] WHERE menu_id = '$group'");
			if ($result && db_num_rows($result) == 1){
		
				$row = db_fetch_row($result);
				$row[0] = htmlentities($row[0]);
				$row[1] = htmlentities($row[1]);
				
				echo "Preview of menu group $row[0] (ID: $h_group)<p>";
			
				$result = db_query("SELECT mit.text,mit.href,mgt.rank FROM $cfg[t_menu_groups] mgt, $cfg[t_menu_items] mit WHERE mgt.menu_id = '$group' AND mit.item_id = mgt.item_id ORDER BY mgt.rank ASC");
			
				if (db_has_rows($result)){
					while($row = db_fetch_row($result))
						echo "Text: $row[0], Alt: $row[1]<p>";
						
				}else{
					echo "No menus found for group $h_group.";
				}
			}else{
				echo "No menus found for group $h_group.";
			}
			
			break;
	}



}

?>