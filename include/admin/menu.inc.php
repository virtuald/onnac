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

	Administration tool -- manage menus
		
*/

function manage_menus(){

	global $cfg;
	
	include('./include/admin/adm_item.php');
	
	$manager = new adm_item();
	
	$manager->type =				'menu';
	$manager->type_plural =			'menus';
	$manager->url = 				'##pageroot##/?mode=menu';
	
	// initialize fields
	$manager->sql_item_table = 		$cfg['t_menu_items'];		
	$manager->sql_item_id = 		'item_id';			
	$manager->sql_item_data = 		array('text','href');			
	$manager->sql_item_desc = 		array('Display Text','URL');

	$manager->sql_item_unique_keys = array(0,1);
	
	$manager->sql_item_hidden =		array();
	
	$manager->sql_join_table = 		$cfg['t_menu_groups'];		
	$manager->sql_order_field = 	'rank';
	
	$manager->sql_group_table = 	$cfg['t_menus'];		
	$manager->sql_group_id =		'menu_id';			
	$manager->sql_group_name = 		'name';
	
	$manager->do_content_update = 	true;

	// hooks
	//$manager->item_delete_hook = 	'user_delete';
	//$manager->remove_hook = 		'user_remove';
	//$manager->edit_item_hook = 	'user_edititem';
	
	$manager->ShowItems();
	
}

/*		
				
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

*/

?>
