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

	Administration tool -- edit page content
	
	TODO: Clean this code up. It works, but so messy... 
		
*/

function edurl($error = "no"){

	global $cfg;
	
	// get variables
	$page_url = get_get_var('page_url');
	$ed_action = get_get_var('ed_action');
	$ajax = get_get_var('ajax');
	
	if ($error == "no" && $ajax != 'true')
		echo "<h4>Content editor</h4>";
	
	
	if ($page_url == "" || $error == "shownew"){
	
?><script type="text/javascript">
<!--

	function admu_toggle(name){
		var item = document.getElementById(name);
		var header = document.getElementById(name + "_hd");
		var span = header.firstChild;
		
		if (item.style.display == "none"){
			set_cookie("edurl_last",name);
			header.style.backgroundColor = "#cccccc";
			item.style.display = "block";
			if (span) span.innerHTML = "-";
		}else{
			item.style.display = "none";
			header.style.backgroundColor = "#eeeeee";
			if (span) span.innerHTML = "+";
		}
	}
	
	function switch_dir(current,next,val){
		next_element = document.getElementById(next);
		if (next_element){
			document.getElementById(current).style.display = 'none';
			next_element.style.display = 'block';
			document.forms['newpage'].newurl.value = val;
			set_cookie("onnac_edurl_last",next);
			set_cookie("onnac_edurl_val",val);
		}
		return false;
	}
	
	function showExtra(item){
		// todo
	}
	
	function doEdit(item){
		// todo
	}
	
	var hiddenShown = false;
	function show_hidden_files(){
		if (!hiddenShown){
			try{
				// proper selector for firefox/CSS2 browsers
				changecss('.edurl_hidden','display','table-row');
			}catch(err){
				// hack for IE
				changecss('.edurl_hidden','display','block');
			}
			document.getElementById('show_hidden_files').innerHTML = "Hide hidden files";
		}else{
			changecss('.edurl_hidden','display','none');
			document.getElementById('show_hidden_files').innerHTML = "Show hidden files";
		}
		
		hiddenShown = !hiddenShown;
	}
	
//-->
</script>
<?php
	
		$cookie_val = htmlentities(get_cookie_var('onnac_edurl_val'));
		if ($cookie_val == "")
			$cookie_val = '/';
	
		// echo form to add a new page.. 
		echo "<form action=\"##pageroot##/?mode=edurl&amp;ed_action=newpage&amp;page_url=new\" method=\"post\" name=\"newpage\"><input type=\"text\" name=\"newurl\" value=\"$cookie_val\" style=\"width:40%\"><input type=submit name=submit value=\"Create new page\"> <a id=\"show_hidden_files\" href=\"javascript:show_hidden_files()\">Show Hidden Files</a></form>";
	
		$result = db_query("SELECT url,page_title," . db_get_timestamp_query("last_update") . ",last_update_by,hidden FROM $cfg[t_content] ORDER BY url ASC");
		
		$directories = edurl_show_list($result,'edurl_shown');
		
		// combine directory list and show add items
		edurl_show_add_to_dir($directories,'template');
		edurl_show_add_to_dir($directories,'menu');
		edurl_show_add_to_dir($directories,'banner');
		
		echo '<p><a href="##pageroot##/">Administration Home</a></p>';
	
	}else{
		// do something
		$h_page_url = htmlentities($page_url);
		$s_page_url = db_escape_string($page_url);
	
		// TODO: Split this code up, too long.. 
		
		switch($ed_action){
		
			case "newpage":
				// verify the POST parameter
				if (!isset($_POST['newurl']))
					return onnac_error("Invalid parameters!");
				
				// ok, its the new page url
				$page_url = $_POST['newurl'];
				
				// compensate for the lack of /... easy mistake to make
				if ($page_url{0} != '/')
					$page_url = "/$page_url";
				
				$h_page_url = htmlentities($page_url);
					
				// there is no break here -- intentional
				
			case "edit":
				
				// verify page exists
				
				$result = db_query("SELECT page_title,page_execute,banner_id,template_id,menu_id,page_content," . db_get_timestamp_query("last_update") . " FROM $cfg[t_content] WHERE url_hash = '" . md5($s_page_url) . "'");
				
				if (!db_is_valid_result($result))
					return onnac_error("Cannot verify page existance!");
				
				$rows = db_num_rows($result);
				
				if ($rows == 0){

					echo "<p>Creating new page: $h_page_url</p><p>Absolute URL: <a href=\"##rootdir##$h_page_url\">##rootdir##$h_page_url</a></p>";
					edurl_render_editor($page_url,"",0,-1,-1,-1,"");
				
				}else{
			
					if ($rows == 1){
			
						$row = db_fetch_row($result);
						
						echo "<p>Existing page: $h_page_url</p><p>Absolute URL: <a href=\"##rootdir##$h_page_url\">##rootdir##$h_page_url</a><br/>Last updated: " . date("F j, Y, g:i a",$row[6]) . "</p>";
						edurl_render_editor($page_url,$row[0],$row[1],$row[2],$row[3],$row[4],$row[5]);
						
					}else{
						onnac_error("Error obtaining existing page information!!");
					}
				}
				
				break;
				
			case "delete":
				echo "<p>Do you really want to delete the page &quot;$h_page_url&quot;?</p><p><a href=\"##pageroot##/?mode=edurl&amp;page_url=$h_page_url&amp;ed_action=reallydelete\">Yes</a><br/><a href=\"##pageroot##/?mode=edurl\">No</a></p>";
				break;
				
			case "reallydelete":
				
				echo "Deleting page $h_page_url from database...<p>";
				$result = db_query("DELETE FROM $cfg[t_content] WHERE url_hash = '" . md5($page_url) . "'");
				
				if (db_is_valid_result($result) && db_affected_rows($result) != 0)
					echo "Done.<p>$h_page_url has been deleted.";
				else
					onnac_error("Error deleting page!");
			
				echo "<p><a href=\"##pageroot##/?mode=edurl\">Website content administration</a><br/><a href=\"##pageroot##/\">Administration Home</a></p>";
			
				break;
				
			case "change":
				// TODO: create a backup of the page...  Probably a good idea.
				if ($page_url{0} != '/')
					return onnac_error("Error in submitted URL ($h_page_url)!!!");
				
				// create the new page
				edurl_add_data($page_url,$ajax == 'true' ? false : true);
	
				break;
				
			case "hide":
				// toggle the hidden state of the url			
				$result = db_query("UPDATE $cfg[t_content] SET hidden = CASE WHEN hidden = 1 THEN 0 ELSE 1 END WHERE url_hash = '" . md5($s_page_url) . "'");
				
				// refresh page and exit
				if (db_is_valid_result($result))
					header( "Location:$cfg[page_root]/?mode=edurl");
				else
					onnac_error("Error setting 'hidden' flag!");
				
				break;
				
			case 'addtodir':
			
				global $auth;
				
				$item_id = db_escape_string(get_post_var('item_id'));
				$type = db_escape_string(get_get_var('type'));
				$dir_name = db_escape_string(trim(get_post_var('dir_name')));
				$exclude_hidden = get_post_var('exclude_hidden');
				
				// set the field we're updating
				if ($type == 'menu')
					$t_str = 'menu_id';
				else if ($type == 'banner')
					$t_str = 'banner_id';
				else if ($type == 'template')
					$t_str = 'template_id';
				else
					$t_str = false;

				
				// really SHOULD validate the id, but oh well... 
				if ($t_str != false && ctype_digit($item_id) && $dir_name != ''){
				
					$exclude = '';
					if ($exclude_hidden == 'yes')
						$exclude = ' AND hidden = 0';
				
					$result = db_query("UPDATE $cfg[t_content] SET $t_str = '$item_id', last_update = NOW(), last_update_by = '" . $auth->username . "' WHERE url LIKE '$dir_name%'$exclude");
					
					if (db_is_valid_result($result)){
						echo '<p>' . db_affected_rows($result) . ' pages updated in &quot;' . htmlentities($dir_name) . '&quot;.</p>';
					}
					
					echo "<p><a href=\"##pageroot##/?mode=edurl\">Content administration page</a><br/><a href=\"##pageroot##/\">Administration Home</a></p>";
					
				}else{
					echo "Error: Invalid input to addtodir!";
					edmenu("shownew");
				}
				break;
				
			default:
				header( "Location:$cfg[page_root]/?mode=edurl");
				break;
		}
	}
	
}

/*
	edurl_show_list
	
	$result is an sql result of pages, and $id is the id of the div
	
	Algorithm:
		-- grab all pages from the database, ordered by filename
		-- reorder them by the directory name, then by filename
		-- assemble the file list output for each directory, store in array
		-- display a header and identifiers for each directory and output the associated file list from 
		   the array mentioned above

	TODO: Make all this just grab the page directory stuff individually, instead
	of as a whole. Put in a seperate file or something
		   
*/
function edurl_show_list($result,$id){

	echo "<div id=\"$id\">";

	$dirs = array();
	
	if (db_has_rows($result)){
	
		// seperate it out into directories too!
		$rows = 0;
	
		echo '<p>' . db_num_rows($result) . " total pages</p>";

		// data used to assemble tree
		$t_sql_data = array();
		$t_dir = array();
		$t_file = array ();
		$t_map = array();
		$x = 0;
		
		// first, assemble three arrays of file information
		while ($row = db_fetch_row($result)){
			
			// store sql data, we'll use it later
			$t_sql_data[] = $row;
			
			// store directory names, without trailing /
			$dirname = dirname($row[0] . 'x');
			if ($dirname == '\\' || $dirname == '/')
				$t_dir[] = '';
			else
				$t_dir[] = $dirname;
			
			// store filenames
			$fname = trim(basename($row[0] . ' '));
			$t_file[] = $fname;
				
			// create mapping using the full file name, as a pointer to the SQL data
			$t_map[$row[0]] = $x++;
		}
		
		// sort by directory, then by file
		array_multisort($t_dir,$t_file);
		
		// this MUST exist, otherwise the default directory should be shown instead
		$shown_div_hash = get_cookie_var('onnac_edurl_last');
		$shown_div_found = false;
		
		// contains 5 columns:
		// 	[0] Directory file entries (table row)
		//	[1] Directory name, split by '/'
		//	[2] Directory name (full)
		// 	[3] id of its div: $id_[md5(directory name)]
		//	[4] Set to true if a directory entirely consists of hidden files
		$directories = array();
		
		// used to find the parent directory
		$dir_map[] = array();
		
		$cur_directory_info = "";
		$old_dir = ".";
		
		// next, assemble the table listings for each directory
		for ($i = 0;$i < count($t_dir);$i++){
				
			// detect directory change
			$cur_dir = $t_dir[$i];
			
			// at next directory, go up/down a level
			if ($cur_dir != $old_dir){
				
				if ($old_dir != '.'){
				
					// i wish this could somehow be a function.. 
					$ed = explode('/',$old_dir);
					
					// add parents to array, if they dont exist
					for ($j = count($ed)-1;$j > 0;$j--){
						$dir_slice = array_slice($ed,0,$j);
						$dir_parent = implode($dir_slice,'/');
						if (isset($dir_map[$dir_parent]))
							break;	// parent exists
						$directories[] = array('',$dir_slice,$dir_parent,"${id}_" . md5($dir_parent));
						$dirs[] = $dir_parent;
						$dir_map[$dir_parent] = count($directories)-1;
					}
				
					// add to array
					$directories[] = array($cur_directory_info,$ed,$old_dir,"${id}_" . md5($old_dir));
					$dirs[] = $old_dir;
					$dir_map[$old_dir] = count($directories)-1;
					
					if ($shown_div_hash == "${id}_" . md5($old_dir))
						$shown_div_found = true;
				}
				
				// the new becomes old
				$cur_directory_info = "";
				$old_dir = $cur_dir;
			}
			
			// grab sql data, create page entry and append it
			$row = $t_sql_data[$t_map[$t_dir[$i] . '/' . $t_file[$i]]];
			
			$url = htmlentities($row[0]);
			$fname = $t_file[$i] == '' ? '/' : $t_file[$i];
			
			$f_hide = '';
			if ($row[4]){
				$f_hide = ' class="edurl_hidden"';
				$hide_txt = 'Show';
			}else{
				$hide_txt = 'Hide';
			}
			
			// TODO: display different icons for different file types
			$img_src = '##pageroot##/icons/text.gif';
			
			// construct the information about the thing
			// TODO: Allow changes of simple parameters here, like menu/banner/etc
			$cur_directory_info .= "\n\t\t<tr onclick=\"showExtra();\" $f_hide>" . '<td class="edurl_icon"><img src="' . $img_src . '" alt="' . htmlentities($url) . '" /></td><td class="admu_url"><a href="##rootdir##' . $url . '">' . htmlentities($fname) . '</td><td class="admu_title">' . htmlentities($row[1]) . '&nbsp;</td><td class="admu_mod_by">' . htmlentities($row[3]) . '</td><td class="admu_mod">' . date('m/d/Y g:ia',$row[2]) . '</td><td class="admu_end"><a href="##pageroot##/?mode=edurl&amp;page_url=' . $url . '&amp;ed_action=hide">[' . $hide_txt . ']</a> <a href="##pageroot##/?mode=edurl&amp;page_url=' . $url . '&amp;ed_action=edit">[Edit]</a> <a href="##rootdir##' . $url . '?elink_mode=on">[ELink]</a> <a href="##pageroot##/?mode=edurl&amp;page_url=' . $url . '&amp;ed_action=delete">[Delete]</a></td></tr>';
		}
		
		// copy/pasted code from above.. 
		// i wish this could somehow be a function.. 
		$ed = explode('/',$old_dir);
		
		// add parents to array, if they dont exist
		for ($j = count($ed)-1;$j > 0;$j--){
			$dir_slice = array_slice($ed,0,$j);
			$dir_parent = implode($dir_slice,'/');
			if (isset($dir_map[$dir_parent]))
				break;	// parent exists
			$directories[] = array('',$dir_slice,$dir_parent,"${id}_" . md5($dir_parent));
			$dirs[] = $dir_parent;
			$dir_map[$dir_parent] = count($directories)-1;
		}
	
		// add to array
		$directories[] = array($cur_directory_info,$ed,$old_dir,"${id}_" . md5($old_dir));
		$dirs[] = $old_dir;
		$dir_map[$old_dir] = count($directories)-1;
		
		if ($shown_div_hash == "${id}_" . md5($old_dir))
			$shown_div_found = true;
		
		// make sure *something* is displayed
		if ($shown_div_found == false)
			$shown_div_hash = $directories[0][3];
		
		// new variables
		$i = -1;
		$dircount = count($directories);
		
		// TODO: Add icons, file types
		
		// ok, render the tables and stuff now 
		
		foreach ($directories as $dir){
			$i += 1;
			
			// history feature -- set display type
			if ($dir[3] == $shown_div_hash)
				$display = 'block';
			else
				$display = 'none';
				
			if ($dir[2] == "")
				$dir[2] = '/';
		
			echo "\n<div id=\"" . $dir[3] . '" style="display:' . $display . '" ><span class="edurl_tab">' . htmlentities($dir[2]) . "</span>\n\t<div class=\"edurl_browser\">\n\t<table class=\"highlighted\">";
		
			// do this before we modify the array
			$dir_level = count($dir[1]);
		
			// output a pointer to the parent directory -- crazy :)
			if ($i != 0){
				$parent = $directories[$dir_map[implode(array_slice($dir[1],0,$dir_level-1),'/')]];
				echo "\n\t\t" . '<tr onDblClick="switch_dir(\''. $dir[3] . "','" . $parent[3] . '\',\'' . htmlentities($parent[2]) . '\')"><td class="edurl_icon"><img src="##pageroot##/icons/folder.gif" alt="Parent Directory" /></td><td colspan="5">..</td></tr>';
				
			}
			
			$last_dir = '';
			
			// output all child directories first, with requisite id pointers
			for ($j = $i + 1;$j < $dircount;$j++){
				
				// special case
				$pos = strpos($directories[$j][2],$dir[2]);
				
				// directories are sorted, so we're done
				if ($pos !== 0)
					break;
				
				if ($last_dir != $directories[$j][1][$dir_level]){
					$_dir = htmlentities($directories[$j][1][$dir_level]);
					echo "\n\t\t" . '<tr onDblClick="switch_dir(\''. $dir[3] . "','" . $directories[$j][3] . '\',\'' . htmlentities($directories[$j][2]) . '\')"><td class="edurl_icon"><img src="##pageroot##/icons/folder.gif" alt="' . $_dir . '" /></td><td colspan="5">' . $_dir . '</td></tr>';
				}
			
				$last_dir = $directories[$j][1][$dir_level];
			
			}
		
			// then, output file entries
			echo "$dir[0]\n\t</table>\n</div></div>";
		
		}
		
		
	}else{
		echo "0 total pages";
	}
	
	echo "</div>\n";
	
	// return directory list
	return $dirs;
}



// shows more items
function edurl_show_add_to_dir($d_tree,$type){

	global $cfg;
	
	if ($type == 'menu')
		$query = "SELECT menu_id,name FROM $cfg[t_menus] ORDER BY name ASC";
	else if ($type == 'banner')
		$query = "SELECT banner_id,name FROM $cfg[t_banners] ORDER BY name ASC";
	else if ($type == 'template')
		$query = "SELECT template_id, template_name FROM $cfg[t_templates] ORDER BY template_name ASC";
	
	// do the query
	$result = db_query($query);
	
	if (db_has_rows($result)){
	
		echo "<p><a href=\"javascript:toggle_hidden('adm_" . $type . "_add')\">Add a $type to all pages in a directory</a></p><div id=\"adm_" . $type . "_add\" style=\"display:none\"><form class=\"adm_form\" method=post action=\"##pageroot##/?mode=edurl&amp;page_url=invalid&amp;ed_action=addtodir&amp;type=$type\">" . ucfirst($type) . ":  <select name=\"item_id\">";
		
		// show first selection box
		while ($item = db_fetch_row($result))
			echo "<option value=\"$item[0]\">" . htmlentities(special_item_strip($item[1])) . '</option>';
		
		echo "</select><br/>Directory: <select name=\"dir_name\">";

		// show directory selection box
		reset($d_tree);
		foreach ($d_tree as $item){
			if ($item == '')
				$item = '/';
			echo '<option value="' . htmlentities($item) . '">' . htmlentities($item) . '</option>';
		}
		
		echo "</select><input name=\"exclude_hidden\" type=\"checkbox\" value=\"yes\" checked />Exclude hidden pages<br/><input type=\"submit\" value=\"Add " . ucfirst($type) . "\" /></form></div>";
	}
}


/*

	edurl_render_editor
	
	This function outputs a form that can be used to modify webpages
	
	TODO: This is ridiculously ugly code, need to clean it up

*/
function edurl_render_editor($url,$title,$execute,$bannerID,$templateID,$menuID,$content){

	require './include/admin/editor.inc.php';
	editor_render('edurl',$url,$title,$execute,$bannerID,$templateID,$menuID,$content);
	
}

// validates and adds data to database
// 		if be_verbose is true, then its an AJAX call!
function edurl_add_data($url,$be_verbose){

	// ensures valid input information
	global $cfg,$auth;	
	
	$url = db_escape_string($url);
	
	// fail on any error -- the only reason one of these wouldn't be passed, is if you were
	// screwing with the input. 
	if (!isset($_POST['editor_content'])){
		onnac_error( "Error in content!");
		return 1;
	}else{
	
		// question: to unescape chars or not? 
		//$content = db_escape_string(html_entity_decode($_POST['editor_content'],ENT_NOQUOTES));
		$content = db_escape_string($_POST['editor_content']);
	}
	
	// new url
	if (!isset($_POST['editor_url'])){
		onnac_error("No URL specified!");
		return 1;
	}else{
		$h_new_url = htmlentities($_POST['editor_url']);
		$new_url = db_escape_string($_POST['editor_url']);
		
		if ($new_url == ""){
			onnac_error("Empty URL specified!");
			return 1;
		}
		
		if ($new_url{0} != '/'){
			$new_url = "/$new_url";
			$h_new_url = "/$h_new_url";
		}
		
		if ($new_url != $url){
			$result = db_query("SELECT url FROM $cfg[t_content] WHERE url = '$new_url'");
			if (db_is_valid_result($result) && db_num_rows($result) != 0){
				onnac_error("URL already exists!");
				return 1;
			}
		}
	}
	
	if (!isset($_POST['editor_title'])){
		onnac_error("Error in title!");
		return 1;
	}else{
		$title = db_escape_string($_POST['editor_title']);
	}
	
	if (!isset($_POST['editor_execute'])){
		onnac_error("Error in parameter 'execute'!");
		return 1;
	}else{
		$execute = db_escape_string($_POST['editor_execute']);
	}
	
	if ($execute == "yes"){
		$execute = 1;
	}else if ($execute == "no"){
		$execute = 0;
	}else{
		onnac_error("Invalid value for parameter 'execute'!");
		return 1;
	}
		
	if (!isset($_POST['editor_template'])){
		onnac_error("Error in parameter 'template!'");
		return 1;
	}else{
		$templateID = db_escape_string($_POST['editor_template']);
	}
	
	if ($templateID[0] == '-'){
		if (!ctype_digit(substr($templateID,1))){
			onnac_error("Invalid value for parameter 'templateID': $templateID");
			return 1;
		} 
	}else if (!ctype_digit($templateID)){
		onnac_error("Invalid value for parameter 'templateID': $templateID");
		return 1;
	}
		
	if (!isset($_POST['editor_banner'])){
		onnac_error("Error in parameter 'banner'!");
		return 1;
	}else{
		$bannerID = db_escape_string($_POST['editor_banner']);
	}
	
	if ($bannerID[0] == '-'){
		if (!ctype_digit(substr($bannerID,1))){
			onnac_error("Invalid value for parameter 'bannerID': $bannerID");
			return 1;
		} 
	}else if (!ctype_digit($bannerID)){
		onnac_error("Invalid value for parameter 'bannerID': $bannerID");
		return 1;
	}
		
	if (!isset($_POST['editor_menu'])){
		onnac_error("Error in menu");
		return 1;
	}else{
		$menuID = db_escape_string($_POST['editor_menu']);
	}
	
	if ($menuID[0] == '-'){
		if (!ctype_digit(substr($menuID,1))){
			onnac_error("Invalid value for parameter 'menuID': $menuID");
			return 1;
		} 
	}else if (!ctype_digit($menuID)){
		onnac_error("Invalid value for parameter 'menuID': $menuID");
		return 1;
	}
	
	if (get_post_var('preview') == 'yes'){
	
		$content = array();
		$content[0] = md5($new_url);
		$content[1] = time();
		$content[2] = time(); 
		$content[3] = $execute;
		$content[4] = $_POST['editor_content'];
		$content[5] = '[Preview] ' . $title;
		$content[6] = $menuID; 
		$content[7] = $bannerID;
		$content[8] = $templateID;
		$content[9] = "##content##";
		
		render_partial($new_url,$content,false,false);
		return 0;
	}
	
	// ok, thats all good. Now, lets update, and if that fails, we shall do an input
	$result = db_query("UPDATE $cfg[t_content] SET url = '$new_url', url_hash = '" . md5($new_url) . "', page_execute = '$execute', page_title = '$title', banner_id = '$bannerID', template_id = '$templateID', menu_id = '$menuID', page_content = '$content', last_update = NOW(), last_update_by = '" . $auth->username . "' WHERE url_hash = '" . md5($url) . "'");
	
	if (db_is_valid_result($result)){
		
		if (db_affected_rows($result) != 1){
		
			// update failed, we need to insert a new row
			$result = db_query("INSERT INTO $cfg[t_content] (url_hash,url,page_execute,page_title,banner_id,template_id,menu_id,page_content,last_update,last_update_by) VALUES ('" . md5($new_url) . "','$new_url','$execute','$title','$bannerID','$templateID','$menuID','$content',NOW(),'" . $auth->username . "')");
			
			if (!db_is_valid_result($result)){
				onnac_error("Error adding information to database for $h_url!!!");
				if ($be_verbose)
					edurl_render_editor($url,$title,$execute,$bannerID,$templateID,$menuID,$_POST['editor_content']);
				return 1;
			}
			
			if (!$be_verbose)
				echo "New page created: ";
		}
		
		if ($be_verbose)
			echo '<p>Database was updated successfully for <a href="##rootdir##' . $h_new_url . '" target="_blank">##rootdir##' . $h_new_url . '</a><p><a href="##pageroot##/?mode=edurl">Edit another page</a><br/><a href="##pageroot##/?mode=edurl&amp;ed_action=edit&amp;page_url=' . $h_new_url . '">Edit same page</a><br/><a href="##rootdir##' . $h_new_url . '?elink_mode=on">View in ELink mode</a><br/><a href="##pageroot##/">Return to main administrative menu</a></p>';
		else
			echo 'Last saved at ' . date("g:i.s a");
		return 0;	// success
	}
	
	// invalid result
	if ($be_verbose)
		edurl_render_editor($url,$title,$execute,$bannerID,$templateID,$menuID,$_POST['editor_content']);
	return 1;
}



?>
