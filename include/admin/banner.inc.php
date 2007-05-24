<?php
/*
* $Id$
*
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

	Administration tool -- manage banners
	
*/

function manage_banners(){

	global $cfg;
	
	include('./include/admin/adm_item.php');
	
	$manager = new adm_item();
	
	$manager->type =				'banner';
	$manager->type_plural =			'banners';
	$manager->url = 				'##pageroot##/?mode=banner';
	
	// initialize fields
	$manager->sql_item_table = 		$cfg['t_banner_items'];		
	$manager->sql_item_id = 		'item_id';			
	$manager->sql_item_data = 		array('alt','src');			
	$manager->sql_item_desc = 		array('Display Text','URL');

	$manager->sql_item_unique_keys = array(0,1);
	
	$manager->sql_item_hidden =		array();
	
	$manager->sql_join_table = 		$cfg['t_banner_groups'];		
	$manager->sql_order_field = 	false;
	
	$manager->sql_group_table = 	$cfg['t_banners'];		
	$manager->sql_group_id =		'banner_id';			
	$manager->sql_group_name = 		'name';

	$manager->do_content_update = 	true;
	
	// hooks
	//$manager->item_delete_hook = 	'user_delete';
	//$manager->remove_hook = 		'user_remove';
	//$manager->edit_item_hook = 	'user_edititem';
	
	$manager->custom_functions = 	array('banner_autofill');
	$manager->item_html =			
	"<p><a href=\"$manager->url&amp;action=banner_autofill\">Autofill banner images from $cfg[img_autofill_dir]/</a></p>";
	
	$manager->ShowItems();
	
}

function banner_autofill(){

	global $cfg;

	$count = 0;
	$banners = array();
	
	// get banner items first, stick into an array
	$result = db_query("SELECT src FROM $cfg[t_banner_items]");
	
	if (!db_is_valid_result($result))
		return onnac_error("Could not detect existing banner items!");
		
	while ($row = db_fetch_row($result))
		$banners[] = $row[0];
	
	$dir = "$cfg[basedir]$cfg[img_autofill_dir]/";
	if (is_dir($dir)){
	
		if (!db_is_valid_result(db_begin_transaction()))
			return onnac_error("Could not begin SQL transaction");
	
		$hdir = opendir($dir);
		while (false !== ($file = readdir($hdir))){
		
			// see if it already exists
			if (is_file("$dir/$file") && !in_array("##rootdir##$cfg[img_autofill_dir]/$file",$banners)){
			
				// doesn't exist, add defaults
				if (!db_is_valid_result(db_query("INSERT INTO $cfg[t_banner_items] (alt, src) VALUES ('default', '##rootdir##$cfg[img_autofill_dir]/" . db_escape_string($file) . "')"))){
					onnac_error("Error adding banner item to database!");
					if (!db_rollback_transaction())
						onnac_error("Error rolling back transaction! Some items may have already been inserted into the database!");
					return;
				}
					
				$count++;
			}
		}

		if (db_is_valid_result(db_commit_transaction()))
			if ($count)
				echo "$count banners automatically added.";
			else
				echo "All banners already in database.";
		else
			onnac_error("Could not commit SQL transaction");
	}else{
		onnac_error("Could not open directory: $dir");
	}
}

?>
