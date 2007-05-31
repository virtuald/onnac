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

	Administration tool -- edit page templates
		
*/

function edtemplate($error = "no"){

	global $cfg;
	
	// get variables
	$template_id = get_get_var('template_id');
	$template_name = get_get_var('template_name');
	$ed_action = get_get_var('ed_action');
	$ajax = get_get_var('ajax');
	$preview = get_post_var('preview');
	
	if ($error == "no" && $ajax != 'true' && $preview != 'yes')
		echo "<h4>Template Editor</h4>";
	
	if ($template_id == "" || $error == "shownew"){
	
		// echo form to add a new page.. 
		echo "<form action=\"##pageroot##/?mode=edtemplate&amp;ed_action=newtemplate&amp;template_id=new\" method=\"post\"><input type=text name=newtemplate value=\"\"><input type=submit name=submit value=\"Create new template\"></form>";
	
		$result = db_query("SELECT template_id,template_name," . db_get_timestamp_query("last_update") . ",last_update_by FROM $cfg[t_templates] ORDER BY last_update ASC");
		
		if (db_has_rows($result)){
		
			echo db_num_rows($result) . " total pages";
			// get all items, list them in a table
			echo "<table><thead><tr><td>Name</td><td>Used By</td><td>Modified</td><td>By</td><td>&nbsp;</td><td>&nbsp;</td></tr></thead>";

			while ($row = db_fetch_row($result)){
				echo "<tr><td>" . htmlentities($row[1]) . "</td><td><ul>";
				
				// display the pages that use this template
				$rresult = db_query("SELECT url FROM $cfg[t_content] WHERE template_id = '" . db_escape_string($row[0]) . "'");
				
				if ($rresult && db_num_rows($rresult) > 0)
					while ($rrow = db_fetch_row($rresult))
						echo "<li><a href=\"##rootdir##" . htmlentities($rrow[0]) . "\">" . htmlentities($rrow[0]) . "</a></li>";
				
				echo "</ul></td><td>" . date("m/d/Y g:ia",$row[2]) . "</td><td>" . htmlentities($row[3]) . "</td><td><a href=\"##pageroot##/?mode=edtemplate&amp;template_id=" . htmlentities($row[0]) . "&amp;template_name=" . htmlentities($row[1]) . "&amp;ed_action=edit\">[Edit]</a></td><td><a href=\"##pageroot##/?mode=edtemplate&amp;template_id=" . htmlentities($row[0]) . "&amp;template_name=" . htmlentities($row[1]) . "&amp;ed_action=delete\">[Delete]</a></td></tr>";
			}
			
			echo "</table>";
		}
		
		echo "<p><a href=\"##pageroot##/\">Administration Home</a></p>";
	
	}else{
		// do something
		$template_id = db_escape_string($template_id);
	
		switch($ed_action){
		
			case "newtemplate":
				// verify the POST parameter
				if (!isset($_POST['newtemplate'])){
					edtemplate("shownew");
					db_close();
					return;
				}
				
				// ok, its the new template name
				$template_name = $_POST['newtemplate'];
					
				// there is no break here -- intentional
				
			case "edit":
				
				// verify template exists
				
				$result = db_query("SELECT template_name,template," . db_get_timestamp_query("last_update") . " FROM $cfg[t_templates] WHERE template_id = '$template_id'");
				
				if (!db_is_valid_result($result))
					return onnac_error("Cannot verify template existance!");
				
				
				$rows = db_num_rows($result);
			
				if ($rows == 0){

					echo "Creating new template: $template_name<p>";
					edtemplate_render_editor(-1,$template_name,"");
				
				}else{
			
					if ($rows == 1){
			
						$row = db_fetch_row($result);
						
						echo "Existing template: $template_name<p>Last updated: " . date("F j, Y, g:i a",$row[2]) . "<p>";
						edtemplate_render_editor($template_id,$row[0],$row[1]);
						
					}else{
						onnac_error("Error obtaining existing template information!!");
						edtemplate("shownew");
					}
				}
				
				
				break;
				
			case "delete":
				echo "<p>Do you really want to delete the template &quot;$template_name&quot;?</p><p><a href=\"##pageroot##/?mode=edtemplate&amp;template_id=$template_id&amp;template_name=$template_name&amp;ed_action=reallydelete\">Yes</a><br/><a href=\"##pageroot##/?mode=edtemplate\">No</a></p>";
				break;
				
			case "reallydelete":
				
				// check for dependencies here
				$result = db_query("SELECT url FROM $cfg[t_content] WHERE template_id = '$template_id'");
				
				if (db_is_valid_result($result)){
					
					if (db_num_rows($result) > 0){
						onnac_error("Pages depend on this template! Cannot delete &quot$template_name&quot.");
						
						echo "List of URL's:<p><ul>";
						while($row = db_fetch_row($result))
							echo "<li><a href=\"##rootdir##" . htmlentities($row[0]) . "\">" . htmlentities($row[0]) . "</a></li>";
						echo "</ul>";
					
					}else{
						echo "Deleting page $template_name from database...<p>";
						$result = db_query("DELETE FROM $cfg[t_templates] WHERE template_id = '$template_id'");
						
						if (db_is_valid_result($result) && db_affected_rows($result) != 0)
							echo "Done.<p>$template_name has been deleted.";
						else
							onnac_error("Error deleting page!");
					}
				}
			
				echo "<p><a href=\"##pageroot##/?mode=edtemplate\">Template administration</a><br/><a href=\"##pageroot##/?mode=edtemplate\">Website content administration</a><br/><a href=\"##pageroot##/\">Administration Home</a></p>";
			
				break;
				
			case "change":
			
				// create the new page
				edtemplate_add_data($template_id,$ajax == 'true' ? false : true);
				break;
				
			default:
				edtemplate("shownew");
				break;
		}
	}
	
}

/*

	edtemplate_render_editor
	
	This function outputs a form that can be used to modify webpages

*/
function edtemplate_render_editor($template_id,$template_name,$content){
	
	require './include/admin/editor.inc.php';
	editor_render('edtemplate',$template_id,$template_name,null,null,null,null,$content);
}

// validates and adds data to database
function edtemplate_add_data($template_id,$be_verbose){

	// ensures valid input information
	global $cfg, $auth;	
	
	$url = db_escape_string($template_id);
	
	// fail on any error -- the only reason one of these wouldn't be passed, is if you were
	// screwing with the input. 
	if (!isset($_POST['editor_content'])){
		onnac_error("No content received!");
		return 1;
	}else{
		if (strstr( $_POST['editor_content'], "##content##" ) === FALSE){
			onnac_error("No &quot;&#35;&#35;content&#35;&#35;&quot; found in template!");
			return 1;
		}

		$content = db_escape_string($_POST['editor_content']);
	}
	
	if (!isset($_POST['editor_title'])){
		onnac_error("Error in template name!");
		return 1;
	}else{
		$template_name = db_escape_string($_POST['editor_title']);
		$h_template_name = htmlentities($_POST['editor_title']);
	}
	
	// try preview
	if (get_post_var('preview') == 'yes'){
	
		$content = array();
		$content[0] = md5('/test/');
		$content[1] = time();
		$content[2] = time(); 
		$content[3] = false;
		$content[4] = lorem_ipsum();
		$content[5] = '[Preview] ' . $h_template_name;
		$content[6] = -1; 
		$content[7] = -1;
		$content[8] = -1;
		$content[9] = $_POST['editor_content'];
		
		render_partial('/test/',$content,false);
		return 0;
	}
	
	
	// ok, thats all good. Now, lets update, and if that fails, we shall do an input -- unless we know that its a new template
	if ($template_id == -1){
		$result = db_query("INSERT INTO $cfg[t_templates] (template_name,template,last_update,last_update_by) VALUES ('$template_name','$content',NOW(),'" . db_escape_string($auth->username) . "')");
			
		if (!db_is_valid_result($result)){
			onnac_error("Error adding information to database for $h_template_name!!!");
			
			if ($be_verbose)
				edtemplate_render_editor($template_id,$template_name,$_POST['editor_content']);
			return 1;
		}
	
	}else{
	
		$result = db_query("UPDATE $cfg[t_templates] SET template_name = '$template_name', template = '$content', last_update = NOW(), last_update_by = '" . db_escape_string($auth->username) . "'  WHERE template_id = '$template_id'");
		
		if (!db_is_valid_result($result) || db_affected_rows($result) != 1){
			onnac_error("Update failed!");
			if ($be_verbose)
				edtemplate_render_editor($template_id,$template_name,$_POST['editor_content']);
			return 1;	
		}
		
	}
	
	// now, update any page that is using our template -- if it failed then this wont hurt anything really
	$result = db_query("UPDATE $cfg[t_content] SET other_update = NOW() WHERE template_id = '$template_id'");
	db_is_valid_result($result);
	
	if ($be_verbose)
		echo "Database was updated successfully for &quot;$h_template_name&quot;!<p><a href=\"##pageroot##/?mode=edtemplate\">Edit another template</a><br/><a href=\"##pageroot##/?mode=edtemplate&amp;template_name=$h_template_name<a href=\"##pageroot##/\">Return to main administrative menu</a>";
	else
		echo 'Last saved at ' . date("g:i.s a");

	return 0;	// success
}

// I just didn't want to put this text in the middle of code..
function lorem_ipsum(){
	return "<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Integer aliquam risus eu lorem. Etiam ut justo a velit fringilla euismod. Pellentesque auctor, augue id pretium hendrerit, sapien lectus consectetuer nulla, eu tempor nibh nunc eget est. Praesent eu tortor eget mi dignissim elementum. In sed lectus ut urna vestibulum luctus. Vivamus at sapien et nibh lacinia consectetuer. Vestibulum pede. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. In enim odio, egestas at, varius sit amet, consectetuer sed, lorem. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Sed et ante vel odio mattis molestie. Integer et arcu. Ut hendrerit. Donec augue orci, euismod eu, mollis vitae, varius vel, libero. Vestibulum in lectus a mi tincidunt tincidunt. Aenean sed mi eu orci commodo tincidunt. Etiam quis sem.</p>
<p>Donec auctor, justo et condimentum porta, nisi quam tempus purus, vel sagittis lacus nulla a tellus. Nam semper ullamcorper dolor. Donec gravida, dui vitae condimentum congue, quam neque scelerisque metus, at consequat ante lorem et ligula. Suspendisse id leo. Suspendisse justo. Vestibulum ac nisl ut tellus pellentesque faucibus. Praesent vitae magna et sapien dignissim vehicula. Proin volutpat posuere diam. Nulla commodo suscipit tortor. Nam scelerisque bibendum nibh. Maecenas ut justo. Nulla consequat. Fusce tincidunt convallis est. Ut eget neque. Integer ultrices justo suscipit ante. Pellentesque dui massa, varius in, aliquam at, accumsan sed, tellus.</p>";

}

?>
