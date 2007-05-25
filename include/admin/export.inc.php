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

	Administration tool -- Export content/templates/menus/etc
	
	TODO: 
		-- Unify the many SQL queries into a few with joins
		-- Add an option to do a 'per-file' export, instead of an all-or-nothing format
	
	
*/

require('./include/tar.inc.php');


function export_data(){

	global $cfg;
		
	$action = get_get_var('action');
	$type = get_post_var('type');
	$gzip = get_get_var('gzip');
	
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
	
?><h4>Export data (single file, can be imported again)</h4>
<form action="##pageroot##/?mode=export&amp;action=export" method="post">
	<table>
	<tr><td>Export Type:</td><td>
	<select name="type">
		<option value="all" selected>All</option>
		<option value="content">Content Only</option>
		<option value="templates">Templates Only</option>
		<option value="users">Users</option>
	</select><input type="checkbox" name="export_hidden" value="yes" checked />Export hidden data</td></tr>
	<tr><td>Directory</td><td><select name="directory"><?php echo $option; ?></select> <input type="checkbox" name="export_root" value="yes" />Export directory as /</td></tr>
	<tr><td>Description:</td><td><input type="text" name="export_description" size="50" /></td></tr>
	<tr><td>Filename:</td><td><input type="text" name="export_filename" size="50" value="<?php echo htmlentities(str_replace(array("https://","http://","/"),array('','','_'),$cfg['rootURL'])) . '.' . date("m-d-Y"); ?>.osf" /></td></tr>
	</table>
	<input type="submit" value="Export" />
</form>
<h4>Export as rendered HTML files (tar.gz file, cannot be imported again)</h4>
<form action="##pageroot##/?mode=export&amp;action=export&amp;ajax=true" method="post">
	<table>
	<tr><td>Directory</td><td><select name="directory"><?php echo $option; ?></select>
	<input type="checkbox" name="export_hidden" value="yes" checked />Export hidden data</td></tr>
	<tr><td>Filename:</td><td><input type="text" name="export_filename" size="50" value="<?php echo htmlentities(str_replace(array("https://","http://","/"),array('','','_'),$cfg['rootURL'])) . '.' . date("m-d-Y"); ?>.tar.gz" /></td></tr>
	</table>
	<input type="hidden" name="type" value="gzip" />
	<input type="submit" value="Export HTML" />
</form>
<p><a href="##pageroot##/">Return to administration menu</a></p>
<?php
	
	}else if ($action == "export"){

			
		switch ($type){
			case "all":
				export_all();
				break;
		
			case "content":
				export_content();
				break;
				
			case "templates":
				export_templates();
				break;
			
			case "gzip":
				export_gzip();
				break;
			
			//case "users":
			//	export_users();
			//	break;
			
				
			default:
				echo "<h4>Export Data</h4><p>Export type not supported at this time.</p>";
		}
	}else{
		header("Location: $cfg[page_root]?mode=export");	
	}
}

function export_gzip(){

	global $cfg;

	$directory = get_post_var('directory');
	if ($directory == "" || $directory[0] != '/' || (strlen($directory) != 1 && $directory[strlen($directory)-1] == '/'))
		return onnac_error("Invalid directory specified!");
	
	// make special provision for hidden data
	$export_hidden = "";
	if (get_post_var('export_hidden') != "yes")
		$export_hidden = "AND hidden <> 1 ";

	// get filenames first
	$result = db_query("SELECT url FROM $cfg[t_content] WHERE url LIKE '" . db_escape_string($directory) . "%' $export_hidden ORDER BY url");
	
	if (db_has_rows($result)){
	
		$files = array();
	
		$dirlen = strlen($directory);
		if ($dirlen > 1)
			$dirlen += 1;
	
		while ($row = db_fetch_row($result)){
		
			// render them over and over again
			$content = render_page($row[0],0,true);
			if ($content === false)
				return onnac_error("Could not render page &quot;" . htmlentities($row[0]) . "&quot;");
			
			$content[1] = output_callback($content[1],true);
			
			// mangle the filename if need be
			// WARNING: This could cause problems if someone already has an index page.. 
			if ($content[0][strlen($content[0])-1] == '/')
				if ($content[3])
					$content[0] .= 'index.php';
				else
					$content[0] .= 'index.html';
			
			// remove directory name
			$content[0] = substr($content[0],$dirlen);
			
			// add to array
			$files[] = $content;
		}
		
		// get file name
		$fName = urlencode(get_post_var('export_filename'));
		if ($fName == "")
			$fName = 'export.' . date("m-d-Y") . '.tar.gz';
		
		// output as tgz file
		output_tar_file($files,$fName);
	
	}else{
		return onnac_error("No files found in directory to export.");
	}
}

// very simple function actually
function export_all(){
	
	$user_approved = false;
	
	if (get_get_var('ajax') == 'true')
		$user_approved = true;
	
	show_export_form_start($user_approved);
	
	$content = get_content_array($user_approved);
	if ($content === false)
		return;
	
	$templates = get_template_array($user_approved);
	if ($templates === false)
		return;

	$menus = get_menu_array($user_approved);
	if ($menus === false)
		return;
	
	$banners = get_banner_array($user_approved);
	if ($banners === false)
		return;
	
	show_export_form_end($user_approved);
	
	if ($user_approved){
		$output = array();
		$output['content'] = $content;
		$output['templates'] = $templates;
		$output['banners'] = $banners;
		$output['menus'] = $menus;
			
		do_export('all',$output);
	}
}


function export_content(){

	$content = get_content_array();
	if ($content != false){
		$output = array();
		$output['content'] = $content;
		do_export('content',$output);
	}
}

function export_templates(){

	$templates = get_template_array();
	if ($templates != false){
		$output = array();
		$output['templates'] = $templates;
		do_export('templates',$output);
	}
}

// shows an export selection form
function show_export_form_start($user_approved){
	if ($user_approved)
		return;
	
	?><script type="text/javascript"><!--
	
	// use CSS classes to distinguish the checkboxes
	function select_all_chk(val){
		var chk = getElementsByClassName('chk');
		for (var i = 0;i < chk.length;i++){
			chk[i].checked = val;
		}
		
	}
	
	//--></script>
	<form method="post" action="##pageroot##/?mode=export&amp;action=export&amp;ajax=true">
	<p>
	Export Type: <?php echo htmlentities(get_post_var('type')); ?><br/>
	Export Hidden: <?php echo htmlentities(get_post_var('export_hidden')); ?><br/>
	Export Directory: <?php echo htmlentities(get_post_var('directory')); ?><br/>
	Export Description: <?php echo htmlentities(get_post_var('export_description')); ?><br/>
	Export Filename: <?php echo htmlentities(get_post_var('export_filename')); ?></p>
	<input type="submit" value="Continue Export" /> <a href="javascript:select_all_chk(false);">Deselect all</a> | <a href="javascript:select_all_chk(true);">Select all</a>
	<input type="hidden" name="type" value="<?php echo htmlentities(get_post_var('type')); ?>" />
	<input type="hidden" name="export_hidden" value="<?php echo htmlentities(get_post_var('export_hidden')); ?>" />
	<input type="hidden" name="directory" value="<?php echo htmlentities(get_post_var('directory')); ?>" />
	<input type="hidden" name="export_description" value="<?php echo htmlentities(get_post_var('export_description')); ?>" />
	<input type="hidden" name="export_filename" value="<?php echo htmlentities(get_post_var('export_filename')); ?>" />
<?php
		
}

function show_export_form_end($user_approved){
	if ($user_approved)
		return;
	echo '<input type="submit" value="Continue Export" /> <a href="javascript:select_all_chk(false);">Deselect all</a> | <a href="javascript:select_all_chk(true);">Select all</a></form>';
}


// get array representing content
function get_content_array($user_approved){

	global $cfg;

	$directory = get_post_var('directory');
	if ($directory == "" || $directory[0] != '/' || (strlen($directory) != 1 && $directory[strlen($directory)-1] == '/'))
		return onnac_error("Invalid directory specified!");
	
	$as_root = 0;
	if (get_post_var('export_root') == 'yes')
		$as_root = strlen($directory);
	
	// make special provision for hidden data
	$export_hidden = "";
	if (get_post_var('export_hidden') != "yes")
		$export_hidden = "AND hidden <> 1 ";

	// export content
	$result = db_query("SELECT a.url_hash, a.url, a.page_execute, a.hidden, a.page_title, a.page_content,  " . db_get_timestamp_query('a.last_update') . " as last_update, b.name as banner_name, c.name as menu_name, d.template_name from $cfg[t_content] a 
	LEFT OUTER JOIN $cfg[t_banners] b ON a.banner_id = b.banner_id 
	LEFT OUTER JOIN $cfg[t_menus] c ON a.menu_id = c.menu_id
	LEFT OUTER JOIN $cfg[t_templates] d ON a.template_id = d.template_id WHERE url LIKE '" . db_escape_string($directory) . "%'	$export_hidden ORDER BY url");
	
	$content = array();
	if (db_has_rows($result)){
	
		// display information
		if (!$user_approved)
			echo '<table class="highlighted"><tr style="background:#000000;color:#ffffff"><td>URL</td><td>Title</td><td>Modify Date</td><td>Export?</td></tr>';
	
		// put in an array
		while ($row = db_fetch_assoc($result)){
			// fix this up, recalculate it
			$row['url'] = substr($row['url'],$as_root);
			$row['url_hash'] = md5($row['url']);
			
			
			if (!$user_approved){
				$content[] = $row;
				echo "<tr><td>" . htmlentities($row['url']) . "</td>";
				echo "<td>" . htmlentities($row['page_title']) . "</td>";
				echo "<td>" . date("m/d/Y g:i a",$row['last_update']) . "</td>";
				echo "<td><input class=\"chk chk_content\" type=\"checkbox\" value=\"yes\" name=\"content_" . $row['url_hash'] . "\" checked /></td></tr>";
				
			}else if (get_post_var('content_' . $row['url_hash']) == 'yes')
				$content[] = $row;
		}
		
		if (!$user_approved)
			echo "</table>";
		
	}else{
		return onnac_error("Error retrieving content!");
	}

	return $content;
}


// get array representing templates
function get_template_array($user_approved){

	global $cfg;

	// templates
	
	$result = db_query("SELECT template_name,template," . db_get_timestamp_query('last_update') . " as last_update  FROM $cfg[t_templates]");
	
	$templates = array();
	if (db_is_valid_result($result)){
	
		// display information
		if (!$user_approved)
			echo '<table class="highlighted"><tr style="background:#000000;color:#ffffff"><td>Name</td><td>Modify Date</td><td>Export?</td></tr>';
	
		// put in an array
		while ($row = db_fetch_assoc($result)){
			$hash = urlencode($row['template_name']);
			
			if (!$user_approved){
				$templates[] = $row;
				echo "<tr><td>" . htmlentities($row['template_name']) . "</td>";
				echo "<td>" . date("m/d/Y g:i a",$row['last_update']) . "</td>";
				echo "<td><input class=\"chk chk_template\" type=\"checkbox\" value=\"yes\" name=\"templates_$hash\" checked /></td></tr>";
				
			}else if (get_post_var("templates_$hash") == 'yes')
				$templates[] = $row;
			
		}
		
		if (!$user_approved)
			echo "</table>";
		
	}else{
		return onnac_error("Error retrieving templates!");
	} 

	return $templates;
}

/*
	Get array representing menus
*/
function get_menu_array($user_approved){

	global $cfg;

	$result = db_query("SELECT name,menu_id FROM $cfg[t_menus]");
	$menus = array();
	if (db_is_valid_result($result)){
	
		// display information
		if (!$user_approved)
			echo '<table class="highlighted"><tr style="background:#000000;color:#ffffff"><td>Menu Name</td><td>Text</td><td>Link</td><td>Export?</td></tr>';
	
		// make arrays
		while ($row = db_fetch_row($result)){
			
			$m_info = array();
			$m_info['name'] = $row[0];
			$m_info['items'] = array();
			
			$hash = md5($m_info['name']);
			$enable_menu = true;
			
			if (!$user_approved){
				echo "<tr><td>" . htmlentities($m_info['name']) . "</td>";
				echo "<td>--</td><td>--</td>";
				echo "<td><input class=\"chk chk_menu\" type=\"checkbox\" value=\"yes\" name=\"menu_$hash\" checked /></td></tr>";
				
			}else if (get_post_var("menu_$hash") != 'yes')
				$enable_menu = false;
			
			// get items
			if ($enable_menu){
				$m_result = db_query("SELECT a.text, a.href, b.rank FROM $cfg[t_menu_items] a, $cfg[t_menu_groups] b WHERE b.menu_id = $row[1] AND a.item_id = b.item_id");
				
				if (db_has_rows($m_result))
					while ($m_row = db_fetch_assoc($m_result)){
						
						// second hash
						$hash2 = md5($m_row['text'] . ':' . $m_row['href']);
						
						if (!$user_approved){
							$m_info['items'][] = $m_row;
							echo "<tr><td>--</td>";
							echo "<td>" . htmlentities($m_row['text']) . "</td>";
							echo "<td>" . htmlentities($m_row['href']) . "</td>";
							echo "<td><input class=\"chk chk_menu\" type=\"checkbox\" value=\"yes\" name=\"menu_" . $hash . $hash2 ."\" checked /></td></tr>";
							
						}else if (get_post_var("menu_" . $hash . $hash2) == 'yes')
							$m_info['items'][] = $m_row;
					
					}
					
				$menus[] = $m_info;
			}
		}
		
		if (!$user_approved)
			echo "</table>";
		
	}else{
		return onnac_error("Error retrieving menus!");
	}

	return $menus;
}

/*
	Get array representing banners
*/
function get_banner_array($user_approved){

	global $cfg;

	$result = db_query("SELECT name,banner_id FROM $cfg[t_banners]");
	$banners = array();
	if (db_is_valid_result($result)){
	
		// display information
		if (!$user_approved)
			echo '<table class="highlighted"><tr style="background:#000000;color:#ffffff"><td>Banner Name</td><td>Alt</td><td>Src</td><td>Export?</td></tr>';
	
		// make arrays
		while ($row = db_fetch_row($result)){
			$m_info = array();
			$m_info['name'] = $row[0];
			$m_info['items'] = array();
			
			$hash = md5($m_info['name']);
			$enable_menu = true;
			
			if (!$user_approved){
				echo "<tr><td>" . htmlentities($m_info['name']) . "</td>";
				echo "<td>--</td><td>--</td>";
				echo "<td><input class=\"chk chk_banner\" type=\"checkbox\" value=\"yes\" name=\"banner_$hash\" checked /></td></tr>";
				
			}else if (get_post_var("banner_$hash") != 'yes')
				$enable_menu = false;
			
			// get items
			if ($enable_menu){
				$m_result = db_query("SELECT a.src, a.alt FROM $cfg[t_banner_items] a, $cfg[t_banner_groups] b WHERE b.banner_id = $row[1] AND a.item_id = b.item_id");
				
				if (db_has_rows($m_result))
					while ($m_row = db_fetch_assoc($m_result)){
					
						// second hash
						$hash2 = md5($m_row['src'] . ':' . $m_row['alt']);
						
						if (!$user_approved){
							$m_info['items'][] = $m_row;
							echo "<tr><td>--</td>";
							echo "<td>" . htmlentities($m_row['src']) . "</td>";
							echo "<td>" . htmlentities($m_row['alt']) . "</td>";
							echo "<td><input class=\"chk chk_banner\" type=\"checkbox\" value=\"yes\" name=\"banner_" . $hash . $hash2 ."\" checked /></td></tr>";
							
						}else if (get_post_var("banner_" . $hash . $hash2) == 'yes')
							$m_info['items'][] = $m_row;
				
					}
					
				$banners[] = $m_info;
			}
		}
		if (!$user_approved)
			echo "</table>";
		
	}else{
		return onnac_error("Error retrieving banners!");
	}

	return $banners;
}

/*
	Sends the export data to the output
*/
function do_export($type,$output){

	global $cfg;
	
	// serialize it and output it
	$output['dumptype'] = 			$type;
	$output['export_date'] = 		date('r');
	$output['export_version'] = 	2;
	$output['export_description'] = get_post_var('export_description');
	$output['onnac_version'] = 		$cfg['onnac_version'];
	
	// crazy hack to use subversion revision strings.. 
	$RevStr = '$Revision$';
	$output['svn_version'] = substr($RevStr,11,strlen($RevStr)-13);
	
	// show output array structure, if you really want to know
	echo "<pre>";
	print_r($output);
	echo "</pre>";
	die();
	
	// export format
	echo serialize($output);
	
	// get filename
	$fName = urlencode(get_post_var('export_filename'));
	if ($fName == "")
		$fName = 'export.' . date("m-d-Y") . '.osf';
		
	
	// formalities
	header("Content-Type: application/octet-stream;");
	header('Content-Disposition: attachment; filename="' . $fName . '"');
	
}

?>