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

	Administration tool -- Export content/templates/menus/etc
	
	TODO: 
		-- Unify the many SQL queries into a few with joins
		-- Add an option to do a 'per-file' export, instead of an all-or-nothing format
	
	
*/


function export_data(){

	global $cfg;
		
	$action = get_get_var('action');
	$type = get_post_var('type');
	
	if ($action == ""){

?><h4>Export data</h4>
<form action="##pageroot##/?mode=export&action=export" method="post">
	Export Type:
	<select name="type">
		<option value="content" selected>Content</option>
		<option value="templates">Templates Only</option>
		<option value="users">Users</option>
	</select><input type="checkbox" name="export_hidden" value="yes" />Export hidden data<br/>
	Description: <input type="text" name="export_description" size="50" /><br/>
	<input type="submit" value="Export" />
</form>
<p><a href="##pageroot##/">Return to administration menu</a></p>
<?php
	
	}else if ($action == "export"){

			
		switch ($type){
			case "content":
				export_content();
				break;
				
			case "templates":
				export_templates();
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

function export_content(){

	global $cfg;
	$error = false;
	

	// make special provision for hidden data
	$export_hidden = "";
	if (get_post_var('export_hidden') != "yes")
		$export_hidden = "WHERE a.hidden <> 1 ";

	// export content first
	$result = db_query("SELECT a.url_hash, a.url, a.page_execute, a.hidden, a.page_title, a.page_content,  " . db_get_timestamp_query('a.last_update') . " as last_update, b.name as banner_name, c.name as menu_name, d.template_name from $cfg[t_content] a 
	LEFT OUTER JOIN $cfg[t_banners] b ON a.banner_id = b.banner_id 
	LEFT OUTER JOIN $cfg[t_menus] c ON a.menu_id = c.menu_id
	LEFT OUTER JOIN $cfg[t_templates] d ON a.template_id = d.template_id
	$export_hidden");
	
	$content = array();
	if (db_has_rows($result)){
		// put in an array
		while ($row = db_fetch_assoc($result))
			$content[] = $row;
	}else{
		echo "<h4>Export Data</h4><p>Error retrieving content!</p><pre>" . db_error() . "</pre>";
		$error = true;
	}
	
	if ($error == false){
		$templates = get_template_array();
		if ($templates === false)
			$error = true;
	}
	
	if ($error == false){
		$menus = get_menu_array();
		if ($menus === false)
			$error = true;
	}
	
	if ($error == false){
		$banners = get_banner_array();
		if ($banners === false)
			$error = true;
	}
	
	if ($error == false){
	
		$output = array();
		$output['content'] = $content;
		$output['templates'] = $templates;
		$output['banners'] = $banners;
		$output['menus'] = $menus;
		
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

// get array representing templates
function get_template_array(){

	global $cfg;

	// templates
	
	$result = db_query("SELECT template_name,template," . db_get_timestamp_query('last_update') . " as last_update  FROM $cfg[t_templates]");
	
	$templates = array();
	if ($result){
		// put in an array
		while ($row = db_fetch_assoc($result))
			$templates[] = $row;
	}else{
		echo "<h4>Export Data</h4><p>Error retrieving templates!</p><pre>" . db_error() . "</pre>";
		return false;
	} 

	return $templates;
}

/*
	Get array representing menus
*/
function get_menu_array(){

	global $cfg;

	$result = db_query("SELECT name,menu_id FROM $cfg[t_menus]");
	$menus = array();
	if ($result){
		// make arrays
		while ($row = db_fetch_row($result)){
			$m_info = array();
			$m_info['name'] = $row[0];
			$m_info['items'] = array();
			
			// get items
			$m_result = db_query("SELECT a.text, a.href, b.rank FROM $cfg[t_menu_items] a, $cfg[t_menu_groups] b WHERE b.menu_id = $row[1] AND a.item_id = b.item_id");
			
			if ($m_result && db_num_rows($m_result) > 0)
				while ($m_row = db_fetch_assoc($m_result))
					$m_info['items'][] = $m_row;
					
			$menus[] = $m_info;
		}
	}else{
		echo "<h4>Export Data</h4><p>Error retrieving menus!</p><pre>" . db_error() . "</pre>";
		return false;
	}

	return $menus;
}

/*
	Get array representing banners
*/
function get_banner_array(){

	global $cfg;

	$result = db_query("SELECT name,banner_id FROM $cfg[t_banners]");
	$banners = array();
	if ($result){
		// make arrays
		while ($row = db_fetch_row($result)){
			$banners[$row[0]] = array();
			// get items
			$m_result = db_query("SELECT a.src, a.alt FROM $cfg[t_banner_items] a, $cfg[t_banner_groups] b WHERE b.banner_id = $row[1] AND a.item_id = b.item_id");
			
			if ($m_result && db_num_rows($m_result) > 0)
				while ($m_row = db_fetch_assoc($m_result))
					$banners[$row[0]][] = $m_row;
		}
	}else{
		echo "<h4>Export Data</h4><p>Error retrieving banners!</p><pre>" . db_error() . "</pre>";
		return false;
	}
	
	return $banners;
}

/*
	Sends the export data to the output
*/
function do_export($type,$output){

	global $cfg;
	
	// serialize it and output it
	$output['dumptype'] = $type;
	$output['export_date'] = date('r');
	$output['export_version'] = 1;
	$output['export_description'] = get_post_var('export_description');
	
	// show output array structure
	//echo "<pre>";
	//print_r($output);
	//echo "</pre>";
	//die();
	
	// export format
	echo serialize($output);
	
	// formalities
	header("Content-Type: application/octet-stream;");
	header('Content-Disposition: attachment; filename="export.' . date("m-d-Y") . '.osf"');
	$cfg['output_replace'] = false;
}

?>