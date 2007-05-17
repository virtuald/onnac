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
	
	Since this is an integral part of the installer, bugfixes will
	be forthcoming very quickly. Apparently, there are few bugs to fix, which
	is a very positve thing. :)
	
	Full imports seem to work now! :)
	
*/


function import_data(){

	global $cfg;
	
	$action = get_get_var('action');
	echo '<h4>Import Data</h4>';

	$mount_point = get_post_var('mount_point');
	// validate this
	if ($mount_point != "" && $mount_point{0} != '/'){
		$action = "";
		onnac_error("Invalid mount point specified! Must start with a '/'");
	}
	
	if ($action == ""){
	
		$option = "";
		$result = db_query("SELECT url from $cfg[t_content] ORDER BY url");
		
		// first, assemble three arrays of file information
		if (db_has_rows($result)){
		
			$last_dir = '/';
		
			while ($row = db_fetch_row($result)){
			
				// directory names, without trailing /
				$dirname = dirname($row[0] . 'x');
				if ($dirname == '\\' || $dirname == '/')
					$dirname = '';
				
				if ($last_dir != $dirname){
					if ($last_dir != '')
						$option .= "\t\t<option value=\"" . htmlentities($last_dir) . '">' . htmlentities($last_dir) . "</option>\n";
				
					$last_dir = $dirname;
				}
			}
			
			if ($last_dir != '')
				$option .= "\t\t<option value=\"" . htmlentities($last_dir) . '">' . htmlentities($last_dir) . "</option>\n";		
		}
	
		?>
		
<form name="import" action="##pageroot##/?mode=import&action=import" enctype="multipart/form-data" method="post">
	<table>
		<tr><td>File to import:</td><td><input name="data" type="file" size="40"/></td></tr>
		<tr><td>*Mount point:</td><td><input name="mount_point" type="text" size="40" value="/" /></td></tr>
		<tr><td><em>==&gt;&gt; Choose a directory</em></td><td><select onchange="document.import.mount_point.value = document.import.directory.options[document.import.directory.selectedIndex].value;" name="directory"><?php echo $option; ?></select></td></tr>
	</table>
	<input type="submit" value="Import" /><br/>
	<em>* Mount point is a value that is prefixed to all file names, including content, banner, and menu data. It is the root directory in which the data is based.</em>
</form>
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
			if (get_post_var('user_approved') == 'yes')
				$user_approved = true;
		
			// take care of SQL transaction up here
			$ret = false;
			if ($user_approved && !db_begin_transaction())
				return onnac_error("Could not begin SQL transaction!");
		
			// automatically detect import type, and do it!
			if ($imported['dumptype'] == 'content'){
				$ret = import_content($imported,$user_approved,false);
			}else if ($imported['dumptype'] == 'templates'){
				$ret = import_templates($imported,$user_approved,false);
			}else{
				echo "Invalid import file!<p><a href=\"##pageroot##/?mode=import\">Back</a></p>";
			}
			
			if ($ret && $user_approved && db_is_valid_result(db_commit_transaction()))
				echo "All imports successful!";
			
		}else{
			echo "Error importing data!<p><a href=\"##pageroot##/?mode=import\">Back</a></p>";
		}
			
	}else{
		header("Location: $cfg[page_root]?mode=import");	
	}	
	
	// footer
	echo "<p><a href=\"##pageroot##/\">Return to main administrative menu</a></p>";
	
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
	
		echo '<table><tr><td>Export Type:</td><td>'. htmlentities($imported['dumptype']) . "</td></tr><tr><td>Export Date:</td><td>" . htmlentities($imported['export_date']) . '</td></tr><tr><td>Description:</td><td>' . htmlentities($imported['export_description']) . '</td></tr>';
	
		if (array_key_exists('onnac_version',$imported))
			echo "<tr><td>Exported from:</td><td>Onnac $imported[onnac_version]</td></tr>";
		
		echo '</table>';
	
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

	$templates_key_exists = array_key_exists('templates',$imported);
	
	if (!$inside_form && !$templates_key_exists)
		return onnac_error("Key 'templates' does not exist!");
	
	// check for install mode
	if (!$install_mode)
		$username = $auth->username;
	else
		$username = "installer";

	// get needed items, different stuff for different times
	if (!$user_approved && !$install_mode)
		$templates = get_import_items('template_name',db_get_timestamp_query('last_update'),$cfg['t_templates']);
	else
		$templates = get_import_items('template_name','template_id',$cfg['t_templates']);
	
	// are we done yet?
	if (!$templates_key_exists)
		return $templates;
	
	// setup a table here
	if (!$user_approved){
		if (!$inside_form){
?><p><form method="post" action="##pageroot##/?mode=import&amp;action=import"><input type="submit" value="Continue Import" /><?php 
		} 
	?><table class="highlighted"><tr style="background:#000000;color:#ffffff"><td>Template Name</td><td>Current Modify Date</td><td>Import modify date</td><td>Overwrite?</td><td>Insert?</td></tr>
<?php
	}
	
	// keys to check
	$params = array('template_name','template','last_update');
	
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
					echo '<td><input value="yes" type="checkbox" name="';
					echo urlencode("templ-approved-$node[template_name]") . '" checked /></td><td>&nbsp;</td></tr>';
				}else{
					echo '</td><td>--</td><td>' . date("m/d/Y g:i a",$node['last_update']) . '</td>';
					echo '<td>&nbsp;</td><td><input value="yes" type="checkbox" name="';
					echo urlencode("templ-approved-$node[template_name]") . '" checked /></td></tr>';
				}
				
				// this doesn't matter a whole lot, just needs to be *something*
				$templates[$node['template_name']] = 0;
			
			}else if ($install_mode == true || get_post_var(urlencode('templ-approved-' . $node['template_name'])) == 'yes'){
				
				$insert = false;
				
				if (array_key_exists($node['template_name'],$templates))
					// update
					$query = "UPDATE $cfg[t_templates] SET template = '" . db_escape_string($node['template']) . "', last_update = NOW(), last_update_by = '" . $username . "' WHERE template_name = '" . db_escape_string($node['template_name']) . "';";
				else{
					// insert
					$query = "INSERT INTO $cfg[t_templates] (template_name,template,last_update,last_update_by) VALUES ('" . db_escape_string($node['template_name']) . "','" . db_escape_string($node['template']) . "',NOW(),'" . $username . "');";
					$insert = true;
				}
					
				if (!db_is_valid_result(db_query($query)))
					return db_rollback_transaction("Could not update template!");
			
				// get last id if it was an insert
				if ($insert){
					$result = db_get_last_id($cfg['t_templates'],'template_id');
					if (!db_has_rows($result))
						return db_rollback_transaction("Could not get last template ID!");
				
					// add the id 
					$row = db_fetch_row($result);
					$templates[$node['template_name']] = $row[0];
				}
			}
		}
	}
	
	if ($user_approved){
	
		echo "<p>Template import completed successfully!</p>";
		
	}else{
		echo '</table>';
		
		if (!$inside_form){
			echo '<p><input type="hidden" name="user_approved" value="yes" /><input type="submit" value="Continue Import" /></p></form></p>';
	
			// and save data in the session variable
			$_SESSION['imported'] = $imported;
		}
	}
	
	return $templates;
}


function import_content($imported,$user_approved,$install_mode){
	
	global $auth,$cfg;
	
	$mount_point = get_post_var('mount_point');
	
	if ($install_mode){
		$username = "installer";
		$mount_point = "/";
	}else{
	
		$username = $auth->username;
		if ($mount_point == "" || $mount_point[0] != '/' || (strlen($mount_point) != 1 && $mount_point[strlen($mount_point)-1] == '/'))
			return onnac_error("Invalid mount point specified!");
	}
	
	// sanity check for array
	if (!array_key_exists('content',$imported))
		return onnac_error("Key 'content' does not exist!");

	// this must be first, so things work out nicely
	if (!$user_approved)
		echo '<p><form method="post" action="##pageroot##/?mode=import&amp;action=import"><input type="hidden" name="mount_point" value="' . htmlentities($mount_point) . '" />';
		
	if ($mount_point == '/')		// special case goes after we echo it
		$mount_point = '';
	
	// import other items first, so things match after import
	$templates = import_templates($imported,$user_approved,$install_mode,true);
	if ($templates === false) return false;
	
	$menus = import_menus($imported,$user_approved,$install_mode,$mount_point);
	if ($menus === false) return false;
	
	$banners = import_banners($imported,$user_approved,$install_mode,$mount_point);
	if ($banners === false) return false;
	
	// get all needed items into an array
	$content = get_import_items('url_hash',db_get_timestamp_query('last_update'),$cfg['t_content']);
	
	// this comes next
	if (!$user_approved){
?><table class="highlighted"><tr style="background:#000000;color:#ffffff"><td>URL/Warnings</td><td>Current Modify Date</td><td>Import modify date</td><td>Overwrite?</td><td>Insert?</td></tr>
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
		if (!is_array($node) || !keys_exist_in($node,$params,'content array') || $node['url_hash'] != md5(db_escape_string($node['url']))){
			echo "<tr><td colspan=\"5\">Row $i failed sanity check!</td></tr>";
		}else{
		
			// now, screw with the data :)
			$node['url'] = $mount_point . $node['url'];
			$node['url_hash'] = md5(db_escape_string($node['url']));
		
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
			
				// escape EVERYTHING, we dont need useless errors
				array_walk($node,'db_total_escape');
				
				// if it already exists, then give it the modified date that is 
					// greatest -- always touch the other_update, however
				if (array_key_exists($node['url_hash'],$content) && $node['last_update'] < $content[$node['url_hash']])
					$date = date("Y-m-d h:i:s",$content[$node['url_hash']]);
				else
					$date = date("Y-m-d h:i:s",$node['last_update']);
			
				// add to the query string
				// if it exists, then do an UPDATE, otherwise do an INSERT	
				if (array_key_exists($node['url_hash'],$content)){
				
					$query[] = "UPDATE $cfg[t_content] SET last_update_by = '" . $username . "', page_execute = '$node[page_execute]', page_title = '$node[page_title]', hidden = '$node[hidden]', page_content = '$node[page_content]', other_update = NOW(), last_update = '$date', banner_id = '$banner_id', menu_id = '$menu_id', template_id = '$template_id' WHERE url_hash = '$node[url_hash]';";
				}else{
					$query[] = 
"INSERT INTO $cfg[t_content] (last_update_by, url_hash, url, page_execute, page_title, hidden, page_content, last_update, other_update, last_visit, banner_id, menu_id, template_id) 
VALUES('" . $username . "', '$node[url_hash]', '$node[url]', '$node[page_execute]', '$node[page_title]', '$node[hidden]', '$node[page_content]', '$date', NOW(), NOW(), '$banner_id', '$menu_id', '$template_id');";
				}
			}
		}
	}
	
	if ($user_approved){
	
		// execute our query
		foreach($query as $q_str){
			
			$result = db_query($q_str);
			if (!db_is_valid_result($result))
				return db_rollback_transaction("DB Error performing Content import");
		}

		echo "<p>Content import completed successfully!</p>";
		
		// erase session data
		$_SESSION['imported'] = null;
			
	}else{
		// ask for approval
		echo '</table><p><input type="hidden" name="user_approved" value="yes" />';
		echo '<input type="submit" value="Continue Import" /></p></form></p>';
	
		// and save data in the session variable
		$_SESSION['imported'] = $imported;
	}

	// signal success
	return true;
}



// import menus
function import_menus($imported,$user_approved,$install_mode,$mount_point){

	global $cfg;

	$menus = new import_module('menus','menu',$imported,$install_mode,$mount_point);
	
	// setup parameters
	$menus->sql_item_table		= $cfg['t_menu_items'];
	$menus->sql_item_id			= 'item_id';
	$menus->sql_item_data		= array('text','href');
	
	$menus->sql_join_table		= $cfg['t_menu_groups'];
	$menus->sql_order_field		= 'rank';
	
	$menus->sql_group_table		= $cfg['t_menus'];
	$menus->sql_group_id		= 'menu_id';
	$menus->sql_group_name		= 'name';
	
	$menus->mount_point			= $mount_point;
	$menus->mount_item			= 1;
	
	// execute it
	return $menus->Execute($user_approved);
}

// import banners
function import_banners($imported,$user_approved,$install_mode,$mount_point){

	global $cfg;
	
	// compatibility
	if ($imported['export_version'] < 2)
		return onnac_error("This export file does not support banner exporting correctly! Skipping banners.",
		get_import_items('name','banner_id',$cfg['t_banners'])
		);

	$banners = new import_module('banners','banner',$imported,$install_mode);
	
	// setup parameters
	$banners->sql_item_table		= $cfg['t_banner_items'];
	$banners->sql_item_id			= 'item_id';
	$banners->sql_item_data			= array('src','alt');
	
	$banners->sql_join_table		= $cfg['t_banner_groups'];
	$banners->sql_order_field		= '';
	
	$banners->sql_group_table		= $cfg['t_banners'];
	$banners->sql_group_id			= 'banner_id';
	$banners->sql_group_name		= 'name';
	
	$banners->mount_point			= $mount_point;
	$banners->mount_item			= 0;
	
	// execute it
	return $banners->Execute($user_approved);
}



//
//	import_module
//
//	At the moment, this is setup to generically import menus/banners, but
//	in the future it may be used for more generic purposes.. 
//
class import_module {

	// [Mandatory settings]
	var $type;				// what type of item are we managing? used for naming
	var $imported;			// imported
	var $install_mode;		// install mode set? 
	var $import_key;
	
	var $mount_point;		// mount point, must be "" or start with /
	var $mount_item;		// which item might have a mount point on it?

	
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
	
	var $sql_join_table;		// table that joins them together
	var $sql_order_field;		// if there is a field that defines their order, its here
	
	var $sql_group_table;		// table with groups
	var $sql_group_id;			// group id name
	var $sql_group_name;		// group name fieldname
	
	// setup defaults
	function import_module($import_key,$type,$imported,$install_mode){
		$this->import_key = $import_key;
		$this->type = $type;
		$this->imported = $imported;
		$this->install_mode = $install_mode;
		$this->mount_point = "";
		$this->mount_item = 0;
	}

	// switches depending on user approval or not
	function Execute($user_approved){
				
		$exists = array_key_exists($this->import_key,$this->imported);
				
		if ($user_approved)
			return $this->HaveApproval($exists);
			
		return $this->GetApproval($exists);
		
	}
	
	// get approval from the user
	function GetApproval($exists){
		
		// get needed items
		$groups = get_import_items($this->sql_group_name,$this->sql_group_id,$this->sql_group_table);
		if (!$exists)
			return $groups;
		
		$items = $this->get_keyed_import_items($this->sql_item_data,$this->sql_item_table);
		
		$item_params = $this->sql_item_data;
		if ($this->sql_order_field != '')
			$item_params[] = $this->sql_order_field;
		
		$items_exist = false;
		
		// calculate this only once
		$c = count($this->sql_item_data);
		$i = 0;
		
		//	for each group
		foreach($this->imported[$this->import_key] as $node){
			$i += 1;
			
			// sanity check here
			if (!keys_exist_in($node, array('name','items'), 'content array')){
				echo "<tr><td colspan=\"5\">Row $i failed sanity check!</td></tr>";
			}else{
				
				// setup the table here if items exist
				if (!$items_exist){
					echo '<table class="highlighted"><tr style="background:#000000;color:#ffffff"><td>' . ucfirst($this->type) . ' Group Name</td><td>Items</td><td>Change Group</td></tr>';
					$items_exist = true;
				}
				
				// show group name
				echo '<tr><td>' . htmlentities($node['name']) . '</td><td><table>';	
				
				// for each item
				foreach($node['items'] as $n_item){
				
					if (!is_array($n_item) || !keys_exist_in($n_item,$item_params,false)){
						echo "<tr><td colspan=" . ($c + 1) . "><strong>Invalid item!</strong></td></tr>";
					}else{

						// create the item key and output the stuff
						$ikey = '';
						echo "<tr><td>";
						
						for($x = 0;$x < $c; $x++){
							
							// modify the data now
							if ($x == $this->mount_item)
								$n_item[$this->sql_item_data[$x]] = $this->append_mount_point($n_item[$this->sql_item_data[$x]]);
							
							$t_item = $n_item[$this->sql_item_data[$x]];
							
							echo htmlentities($t_item);
							$ikey .= trim($t_item);
							
							if ($x + 1 < $c)
								$ikey .= ':';
							
							echo "</td><td>";
						}
						
						$ikey = md5($ikey);

						// check to see if the item exists already
						if (!array_key_exists($ikey,$items)){
							echo "<input type=\"checkbox\" name=\"$this->type-appr-" . $ikey . '" value="yes" checked />';
						}else{
							echo '<strong>X</strong>';
						}
							
						echo '</td></tr>';
					}
				}
				
				// show the option
				echo "</table></td><td><input type=\"checkbox\" name=\"$this->type-gappr-" . md5($node['name']) . '" value="yes" checked /></td></tr>';
					
				// the group id doesn't really matter when confirming user input, it just needs to be there
				$groups[$node['name']] = 0;
			}
		}
		
		echo "</table>";
		
		return $groups;
	}
	
	// called when we already have approval, just import the correct data
	function HaveApproval($exists){
		
		// get needed items
		$groups = get_import_items($this->sql_group_name,$this->sql_group_id,$this->sql_group_table);
		if (!$exists)
			return $groups;
		
		$items = $this->get_keyed_import_items($this->sql_item_data,$this->sql_item_table);
		
		// figure out what items are already connected
		$join_table = array();
		
		$result = db_query("SELECT a." . implode(',a.', $this->sql_item_data) . ", c.$this->sql_group_name FROM $this->sql_item_table a, $this->sql_join_table b, $this->sql_group_table c WHERE a.$this->sql_item_id = b.$this->sql_item_id AND b.$this->sql_group_id = c.$this->sql_group_id");

		if (!db_is_valid_result($result))
			return db_rollback_transaction("Could not retrieve $this->type link information.");
		else if (db_num_rows($result) > 0)
			while ($row = db_fetch_row($result))
				$join_table[ md5(implode(':',$row)) ] = true;	
				
	
		// keys to check
		$item_params = $this->sql_item_data;
		if ($this->sql_order_field != '')
			$item_params[] = $this->sql_order_field;
		
		$items_exist = false;
		
		// calculate this only once
		$c = count($this->sql_item_data);
		$i = 0;
		
		// index to create that links the groups with its keys
		$link_index = array();
		
		//	for each group
		foreach($this->imported[$this->import_key] as $node){
			$i += 1;
			
			// setup approval
			if (get_post_var("$this->type-gappr-" . md5($node['name'])) == "yes" || $this->install_mode)
				$node_approved = true;
			else
				$node_approved = false;
			
			// sanity check here
			if (!keys_exist_in($node, array('name','items'), 'content array')){
				echo "<tr><td colspan=\"5\">Row $i failed sanity check!</td></tr>";
			}else{
				
				
				foreach($node['items'] as $n_item){
					
					if (!is_array($n_item) || !keys_exist_in($n_item,$item_params,false)){
						// not serious, dont abort this
						onnac_error("Skipping invalid $this->type item!");
					}else{
						
						// create the item/node key
						$ikey = '';
						
						for($x = 0;$x < $c; $x++){
						
							// modify the data now
							if ($x == $this->mount_item)
								$n_item[$this->sql_item_data[$x]] = $this->append_mount_point($n_item[$this->sql_item_data[$x]]);
						
							$ikey .= trim($n_item[$this->sql_item_data[$x]]);
							if ($x + 1 < $c)
								$ikey .= ':';
						}
						
						$nkey = md5($ikey . ':' . $node['name']);
						$ikey = md5($ikey);
						
						if($this->install_mode == true || get_post_var("$this->type-appr-$ikey") == "yes"){
						
							// if it doesn't exist already, then insert it -- else dont bother!
							if (!array_key_exists($ikey,$items)){
						
								// escape EVERYTHING, we dont need useless errors
								array_walk($n_item,'db_total_escape');
								
								$tn_item = $n_item;
								if ($this->sql_order_field != '')
									$tn_item = array_splice($tn_item,0,$c);
						
								if (!db_is_valid_result(db_query("INSERT INTO $this->sql_item_table (" . implode(',',$this->sql_item_data) . ") VALUES ('" . implode("','",$tn_item) . "')")))
									return db_rollback_transaction("Could not insert $this->type item!");
							
								$result = db_get_last_id($this->sql_item_table,$this->sql_item_id);
								if (!db_has_rows($result))
									return db_rollback_transaction("Could not get last $this->type item ID!");
							
								// add the key
								$row = db_fetch_row($result);
								$items[$ikey] = $row[0];
							}
						}
						
						// add to the link_index if exists, and the group has been enabled, and if
						// the item does not already belong to the group
						if ($node_approved && array_key_exists($ikey,$items) && !array_key_exists($nkey,$join_table)){
						
							//group name, ikey, order field (if it exists)
							$new_link = array($node['name'],$ikey);
				
							// add order field if it exists
							if ($this->sql_order_field != ''){
								if (is_numeric($n_item[$this->sql_order_field]))
									$new_link[] = $n_item[$this->sql_order_field];
								else
									return db_rollback_transaction("Error inserting $this->type item! Invalid '$this->sql_order_field' field in import file!");
							}
							
							$link_index[] = $new_link;
						}
					}
				}
				
				
				if ($node_approved && !array_key_exists($node['name'],$groups)){
				
					// insert the group
					if (!db_is_valid_result(db_query("INSERT INTO $this->sql_group_table ($this->sql_group_name) VALUES ('" . db_escape_string($node['name']) . "')")))
						return db_rollback_transaction("Could not insert $this->type group!");
				
					// get last id
					$result = db_get_last_id($this->sql_group_table,$this->sql_group_id);
					if (!db_has_rows($result))
						return db_rollback_transaction("Could not get last $this->type group ID!");
				
					// add the id 
					$row = db_fetch_row($result);
					$groups[$node['name']] = $row[0];
				}
			}
		}
			
		// after we make the previous inserts, then cross reference everything and join the items
		// to the groups
		foreach ($link_index as $item){
			// group_name, ikey, order field
			if (!array_key_exists($item[0],$groups) || !array_key_exists($item[1],$items))
				return db_rollback_transaction(ucfirst($this->type) . " $item[0] not found!");
			
			$query = "INSERT INTO $this->sql_join_table ($this->sql_group_id,$this->sql_item_id";
			
			if ($this->sql_order_field != '')
				$query .= ",$this->sql_order_field";
				
			$query .= ") VALUES (" . $groups[$item[0]] . "," . $items[$item[1]];
			
			if ($this->sql_order_field != '')
				$query .= "," . db_escape_string($item[2]);
			
			if (!db_is_valid_result(db_query($query . ')')))
				return db_rollback_transaction("Error joining $this->type items to group!");
		
		}
		
		//prn($join_table);
		//prn($link_index);
		//prn($groups);
		//prn($items);
	
		echo "<p>" . ucfirst($this->type) . " import completed successfully!</p>";

		return $groups;
	}
	
	// grab information about items into arrays
	function get_keyed_import_items($fields,$table){
		
		$output = array();
		$c = count($fields);
		
		$result = db_query("SELECT " . implode(',',$fields) . ",$this->sql_item_id FROM $table");
		if (db_has_rows($result))
			while ($row = db_fetch_row($result)){
				// last item is always the id
				$id = $row[$c];
	
				// pick the items to hash, and use the hash as the key
				$row = array_splice($row,0,$c);
				array_walk($row,'trim');
				$output[ md5(implode(':',$row)) ] = $id;
			}	
		return $output;
	}
	
	function append_mount_point($item){
		if ($this->mount_point == "")
			return $item;
		
		if (substr($item,0,11) == "##rootdir##")
			return '##rootdir##' . $this->mount_point . substr($item,11);
		if (substr($item,0,12) == "##pageroot##")
			return '##pageroot##' . $this->mount_point . substr($item,12);
		
		return $this->mount_point . $item;
	}
}






?>