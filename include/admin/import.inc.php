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

	Administration tool -- Import content/menus/etc 
	
	This is very messy code and should be cleaned up/verified.. templates and content
	should *mostly* work. Since this is an integral part of the installer, bugfixes will
	be forthcoming very quickly. 
	
*/


function import_data(){

	global $cfg;
		
	$action = get_get_var('action');
	echo '<h4>Import Data</h4>';
	
	if ($action == ""){
	
		// collection of links showing stuff that you can do
		?>
<form action="##pageroot##/?mode=import&action=import" enctype="multipart/form-data" method="post">
	<h4>Import data</h4>
	File to import: <input name="data" type="file" size="30"/><br/>
	<input type="submit" value="Import" />
</form>
<p><a href="##pageroot##/">Return to administration menu</a></p>
<?php
	
	}else if ($action == "import"){
		
		$filename = "";
		
		// get the contents of the uploaded file
		if (isset($_FILES['data']) && is_uploaded_file($_FILES['data']['tmp_name'])){
			$filename = $_FILES['data']['tmp_name'];
		}
		
		$imported = get_import_data($filename,false);
		
		if ($imported !== false){
		
			// check to see if we need to ask the user to approve anything
			$user_approved = false;
			if ($install_mode == true || get_post_var('user_approved') == 'yes')
				$user_approved = true;
		
			// automatically detect import type, and do it!
			if ($imported['dumptype'] == 'content'){
				import_content($imported,$user_approved,false);
			}else if ($imported['dumptype'] == 'templates'){
				import_templates($imported,$user_approved,false);
			}else{
				echo "Invalid import file!<p><a href=\"##pageroot##/?mode=import\">Back</a></p>";
			}
		}else{
			echo "Error importing data!<p><a href=\"##pageroot##/?mode=import\">Back</a></p>";
		}
			
	}else{
		header("Location: $cfg[page_root]?mode=import");	
	}
}

function get_import_data($filename){
	
	$imported = false;

	// get the contents of the import file
	if ($filename != ""){
		$orig_imported = file_get_contents($filename);
		$imported = unserialize($orig_imported);
		
	}elseif (isset($_SESSION['imported'])){
		$imported = $_SESSION['imported'];
	}
	
	// validate data
	if ($imported !== false && 
		array_key_exists('export_date',$imported) && 
		array_key_exists('dumptype',$imported) &&
		array_key_exists('export_description',$imported)){
	
		echo '<table><tr><td>Export Type:</td><td>'. htmlentities($imported['dumptype']) . "</td></tr><tr><td>Export Date:</td><td>" . htmlentities($imported['export_date']) . '</td></tr><tr><td>Description:</td><td>' . htmlentities($imported['export_description']) . '</table>';
	
	}else{
		return false;
	}
	
	return $imported;
}


// grab information about items into arrays
function get_import_items($field1,$field2,$table){
	$output = array();
	$result = db_query("SELECT $field1,$field2 FROM $table");
	if (db_has_rows($result))
		while ($row = db_fetch_row($result))
			$output[$row[0]] = $row[1];
	return $output;
}

// find out if keys exist
function keys_exist_in($array,$params,$section,$tbl = true){

	foreach($params as $item){
		if (!array_key_exists(trim($item),$array)){
			if ($tbl)
				echo "<tr><td colspan=\"5\"><strong>Error</strong>: key '$item' not found in $section!</td></tr>";
			else
				echo "<strong>Error</strong>: key '$item' not found in $section!</form>";
			return false;
		}
	}
	return true;
}

// import templates
function import_templates($imported,$user_approved,$install_mode,$inside_form = false){

	global $cfg,$auth;

	if (!keys_exist_in($imported,array('templates'),'template import',false))
		return false;
	
	// check for install mode
	if (!$install_mode)
		$username = $auth->username;
	else
		$username = "installer";

	// get needed items
	$templates = get_import_items('template_name',db_get_timestamp_query('last_update'),$cfg['t_templates']);
	
	// setup a table here
	if (!$user_approved){
		if (!$inside_form){
?><p><form method="post" action="##pageroot##/?mode=import&amp;action=import"><input type="submit" value="Continue Import" /><?php 
		} 
	?><table border="1"><tr style="background:#000000;color:#ffffff"><td>Template Name</td><td>Current Modify Date</td><td>Import modify date</td><td>Overwrite?</td><td>Insert?</td></tr>
<?php
	}
	
	// keys to check
	$params = array('template_name','template','last_update');
	
	$query = array();
	$i = 0;
	
	foreach($imported['templates'] as $node){
		$i += 1;
		
		if (!is_array($node) || !keys_exist_in($node,$params,'template array')){
			echo "<tr><td colspan=\"5\">Row $i failed sanity check!</td></tr>";
		}else{
		
			if (!$user_approved){
				// ask the user for approval
			
				echo "<tr><td>" . htmlentities($node['template_name']);
					
				// show dates and give them either an overwrite or insert option
				if (array_key_exists($node['template_name'],$templates)){
					echo '</td><td>' . date("m/d/Y g:i a",$templates[$node['template_name']]) . '</td>';
					echo '<td>' . date("m/d/Y g:i a",$node['last_update']) . '</td>';
					echo '<td><input value="yes" type="checkbox" name="templ-approved-';
					echo htmlentities($node['template_name']) . '" checked /></td><td>&nbsp;</td></tr>';
				}else{
					echo '</td><td>--</td><td>' . date("m/d/Y g:i a",$node['last_update']) . '</td>';
					echo '<td>&nbsp;</td><td><input value="yes" type="checkbox" name="templ-approved-';
					echo htmlentities($node['template_name']) . '" checked /></td></tr>';
				}
			
			}else if ($install_mode == true || get_post_var('templ-approved-' . $node['template_name']) == 'yes'){
				
				if (array_key_exists($node['template_name'],$templates))
					// update
					$query[] = "UPDATE $cfg[t_templates] SET template = '" . db_escape_string($node['template']) . "', last_update = NOW(), last_update_by = '" . $username . "' WHERE template_name = '" . db_escape_string($node['template_name']) . "';";
				else
					// insert
					$query[] = "INSERT INTO $cfg[t_templates] (template_name,template,last_update,last_update_by) VALUES ('" . db_escape_string($node['template_name']) . "','" . db_escape_string($node['template']) . "',NOW(),'" . $username . "');";
				
			}
		}
	}
	
	if ($user_approved){
	
		// perform the import
		$error = do_import($query,'Template');
		//echo "Did import $error";
		if ($install_mode == false && !$error && !$inside_form )
			echo '<p>Template import successful!</p><p><a href="##pageroot##/?mode=import">Back to Import Menu</a><br/><a href="##pageroot##/">Main administration menu</a></p>';

		return $error;
		
	}else{
		echo '</table>';
		
		if (!$inside_form){
			echo '<p><input type="hidden" name="user_approved" value="yes" /><input type="submit" value="Continue Import" /></p></form></p>';
	
			// and save data in the session variable
			$_SESSION['imported'] = $imported;
		}
	}
	
}


function import_content($imported,$user_approved,$install_mode){
	
	global $auth,$cfg;
	
	if (!$install_mode)
		$username = username;
	else
		$username = "installer";
	
	// sanity check for arrays
	if (!keys_exist_in($imported,array('content','templates','menus','banners'),'import',false))
		return false;

	// this must be first, so things work out nicely
	if (!$user_approved)
		echo '<p><form method="post" action="##pageroot##/?mode=import&amp;action=import">';
	
	// import other items first, so things match after import
	$r_templates = import_templates($imported,$user_approved,$install_mode,true);
	if ($r_templates === false) return false;
	
	//$r_menus = import_menus($imported,$user_approved,$install_mode);
	//if ($r_menus === false) return false;
	
	//$r_banners = import_banners($imported,$user_approved,$install_mode);
	//if ($r_banners === false) return false;
	
	// get all needed items into an array
	$menus = get_import_items('name','menu_id',$cfg['t_menus']);
	$banners = get_import_items('name','banner_id',$cfg['t_banners']);
	$templates = get_import_items('template_name','template_id',$cfg['t_templates']);
	$content = get_import_items('url_hash',db_get_timestamp_query('last_update'),$cfg['t_content']);
	
	// this comes next
	if (!$user_approved){
?><input type="submit" value="Continue Import" /><table border="1"><tr style="background:#000000;color:#ffffff"><td>URL/Warnings</td><td>Current Modify Date</td><td>Import modify date</td><td>Overwrite?</td><td>Insert?</td></tr>
<?php
	}
	
	// for each node in the import tree, write it to the DB
	// -- our strategy is build a huge request, and then execute that
	// instead of a bunch of individual queries
	
	$params = explode(',','url_hash, url,page_execute, hidden, page_title, page_content, last_update, banner_name, menu_name, template_name');
	
	$query = array();
	$i = 0;
	
	foreach ($imported['content'] as $node){

		$i += 1;
		$menu_id = -1;
		$banner_id = -1;
		$template_id = -1;
		
		$warning = "";
		
		// is it valid?
		if (!is_array($node) || !keys_exist_in($node,$params,'content array') || $node['url_hash'] != md5($node['url'])){
			echo "<tr><td colspan=\"5\">Row $i failed sanity check!</td></tr>";
		}else{
		
			// match the menu group id
			if ($node['menu_name'] != null)
				if (array_key_exists($node['menu_name'],$menus))
					$menu_id = $menus[$node['menu_name']];
				else 		// no match? emit a warning
					$warning .= "<li>menu &quot;" . htmlentities($node['menu_name']) . "&quot; does not exist</li>";
			
			// match the banner group id
			if ($node['banner_name'] != null)
				if (array_key_exists($node['banner_name'],$banners))
					$banner_id = $banners[$node['banner_name']];
				else 		// no match? emit a warning
					$warning .= "<li>banner &quot;" . htmlentities($node['banner_name']) . "&quot; does not exist</li>";
			
			// match the template group id
			if ($node['template_name'] != null)
				if (array_key_exists($node['template_name'],$templates))
					$template_id = $templates[$node['template_name']];
				else 		// no match? emit a warning
					$warning .= "<li>template &quot;" . htmlentities($node['template_name']) . "&quot; does not exist</li>";
			
			// escape EVERYTHING, we dont need useless errors
			array_walk($node,'db_total_escape');
			
			if (!$user_approved){
				// ask the user for approval
			
				echo "<tr><td>" . htmlentities($node['url']);
				if ($warning != "")
					echo "<ul>$warning</ul>";
					
				// show dates and give them either an overwrite or insert option
				if (array_key_exists($node['url_hash'],$content)){
					echo '</td><td>' . date("m/d/Y g:i a",$content[$node['url_hash']]) . '</td>';
					echo '<td>' . date("m/d/Y g:i a",$node['last_update']) . '</td>';
					echo '<td><input value="yes" type="checkbox" name="approved-';
					echo htmlentities($node['url_hash']) . '" checked /></td><td>&nbsp;</td></tr>';
				}else{
					echo '</td><td>--</td><td>' . date("m/d/Y g:i a",$node['last_update']) . '</td>';
					echo '<td>&nbsp;</td><td><input value="yes" type="checkbox" name="approved-';
					echo htmlentities($node['url_hash']) . '" checked /></td></tr>';
				}
			
			}else if ($install_mode == true || get_post_var('approved-' . $node['url_hash']) == 'yes'){
			
				// add to the query string
				// if it exists, then do an UPDATE, otherwise do an INSERT
				if (array_key_exists($node['url_hash'],$content)){
					$query[] = "UPDATE $cfg[t_content] SET last_update_by = '" . $username . "', page_execute = '$node[page_execute]', page_title = '$node[page_title]', hidden = '$node[hidden]', page_content = '$node[page_content]', last_update = NOW(), banner_id = '$banner_id', menu_id = '$menu_id', template_id = '$template_id' WHERE url_hash = '$node[url_hash]';";
				}else{
					$query[] = 
"INSERT INTO $cfg[t_content] (last_update_by, url_hash, url, page_execute, page_title, hidden, page_content, last_update, other_update, last_visit, banner_id, menu_id, template_id) 
VALUES('" . $username . "', '$node[url_hash]', '$node[url]', '$node[page_execute]', '$node[page_title]', '$node[hidden]', '$node[page_content]', NOW(), NOW(), NOW(), '$banner_id', '$menu_id', '$template_id');";
				}
			}
		}
	}
	
	if ($user_approved){
	
		// execute our query
		$success = do_import($query,'Content');
		if ($success && $install_mode == false){
			echo '<p><a href="##pageroot##/?mode=import">Back to Import Menu</a><br/>';
			echo '<a href="##pageroot##/">Main administration menu</a></p>';
		}
			
	}else{
		// ask for approval
		echo '</table><p><input type="hidden" name="user_approved" value="yes" />';
		echo '<input type="submit" value="Continue Import" /></p></form></p>';
	
		// and save data in the session variable
		$_SESSION['imported'] = $imported;
	}

	// signal success to installer
	return true;
}

// grab information about items into arrays
function get_keyed_import_items($field1,$field2,$field3,$table){
	$output = array();
	$result = db_query("SELECT $field1,$field2,$field3 FROM $table");
	if (db_has_rows($result))
		while ($row = db_fetch_row($result)){
			$key = md5("$row[0]:$row[1]");
			$output[$key] = array();
			$output[$key][$field1] = $row[0];
			$output[$key][$field2] = $row[1];
			$output[$key][$field3] = $row[2];
		}
	return $output;
}

// import menus -- this doesn't work yet
function import_menus($imported,$user_approved,$install_mode){

	global $cfg,$auth;
	
	if (!$install_mode)
		$username = username;
	else
		$username = "installer";

	if (!keys_exist_in($imported,array('menus'),'menu import',false))
		return false;
	
	// get needed items
	$menus = get_import_items('menu_name','menu_id',$cfg['t_menus']);
	$items = get_keyed_import_items('text','href','item_id',$cfg['t_menu_items']);
	
	// setup a table here
	if (!$user_approved){
	?><table border="1"><tr style="background:#000000;color:#ffffff"><td>Menu Group Name</td><td>Items</td><td>Insert Group</td></tr><?php
	}
	
	// keys to check
	$params = array('name','items');
	$iparams = array('text','href','rank');
	
	$query = array();
	$i = 0;
	
	// index to create that links the groups with its keys
	// FUCK
	$link_index = array();
	
	//	for each menu group
	foreach($imported['menus'] as $node){
		$i += 1;
		
		// sanity check here
		if (!keys_exist_in($node,$params,'content array')){
			echo "<tr><td colspan=\"5\">Row $i failed sanity check!</td></tr>";
		}else{
			
			// for each menu item
			if (!$user_approved) echo '<tr><td>' . htmlentities($node['name']) . '</td><td><table>';
			foreach($node['items'] as $n_item){

				$ikey = md5("$n_item[text]:$n_item[href]");
				
				if (is_array($n_item) && keys_exist_in($n_item,$iparams,false)){
					
					if (!$user_approved){
					
						echo '<tr><td>' . htmlentities($n_item['text']) . '</td><td>' . htmlentities($n_item['href']) . '</td><td>';
						// check to see if item exists already
						if (!array_key_exists($ikey,$items))
							echo '<input type="checkbox" name="mappr-' . $ikey . '" value="yes" checked />';
						else
							echo '<strong>X</strong>';
							
						echo '</td></tr>';
					}else if(get_post_var("mappr-$ikey") == "yes"){
					
						// generate queries
						//if (!array_key_exists($ikey,$items))
						
							// cant generate the same thing twice! there can be duplicate items, sometimes
							
							
					}
				}
			}
			
			if (!$user_approved){
				echo '</table></td><td>';

				// add an option if the group doesn't exist
				if (array_key_exists($node['name'],$menus))
					echo '<input type="checkbox" name="mgappr-' . md5($node['name']) . '" value="yes" checked /></td></tr>';
				else
					echo '<strong>X</strong></td></tr>';
			}
				
			// 
	//	insert each menu group, and get its menu_id
	//		put menu_id into an array so we can cross-reference later
	//	insert each menu item, and get its item_id
	//		put item_id into an array
	//	
		}
	}
	
	if ($user_approved){
		unset($menus);
		unset($items);
		
		// refresh the menu information
		$menus = get_import_items('name','menu_id');
		
		//	after we make the previous inserts, then cross reference everything and
		//  create the groups table
		//		for each menu_group, 
		//			$insert_id = $items[$groups['item'][0]];

		// this is annoying
	
	}else{
		echo "</table>";
	}
}


// this function actually does the importing -- query is an array of queries
function do_import($query,$type){
	//prn($query);
	
	db_begin_transaction();
	$error = false;
	foreach($query as $q_str){
		if ($error == false){

			$result = db_query($q_str);
			if (!$result){
				echo "DB Error performing $type import: <pre>" .htmlentities(db_error()) . "</pre>";
				$error = true;
				if (!db_rollback_transaction())
					echo "<p><strong>Warning!</strong> Some changes <em>may</em> have been committed to the database and could not be rolled back!</p>";
			}
		}
	}
	
	if ($error == false){
		db_commit_transaction();
		echo "<p>$type import completed successfully!</p>";
	}
	
	// erase session data
	$_SESSION['imported'] = null;
	
	return !$error;
}


// returns all the rows (associatively) from a query
function get_all_db_rows($query){

	$result_array = array();
	
	$result = db_query($query);
	if (db_has_rows($result))
		while ($row = db_fetch_assoc($result))
			$result_array[] = $row;
	else
		return false;

	return $result_array;
}

?>