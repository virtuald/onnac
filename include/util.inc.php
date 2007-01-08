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

	Utility functions used in many different locations

*/

/*

	Easy to use get/post variable functions

*/

function get_post_var($varname){

	if (!isset($_POST[$varname]))
		return "";
	
	return $_POST[$varname];
}

function get_get_var($varname){

	if (!isset($_GET[$varname]))
		return "";
		
	return $_GET[$varname];
}

function get_cookie_var($varname){
	
	if (!isset($_COOKIE[$varname]))
		return "";
		
	return $_COOKIE[$varname];
}


/*
	
	compatibility wrappers for file_get_contents and put_file_contents
	
*/

function get_file_contents($filename){
	
	if (function_exists("file_get_contents"))
		return file_get_contents($filename);
	
	$retbuffer = "";
	
	$handle = @fopen($filename, "r");
	if ($handle)
		$retbuffer = fread($handle,filesize($filename));
	
	fclose($handle);
	return $retbuffer;
}

function write_file_contents($filename,$contents){

	if (function_exists("file_put_contents"))
		file_put_contents($filename,$contents);

	$handle = @fopen($filename,"wb");
	if ($handle){
		if (fwrite($handle,$contents))
			return 1;
	}
	return 0;
}

/*

	special_item_strip

	Use this function to strip display items. You should use 
	html_entity_decode($item,ENT_NOQUOTES) to decode it!
	
*/

function special_item_strip($str){

	global $perform_additional_substitution;
	$perform_additional_substitution = true;	// turn this on
	
	$rendered_html = $str;
	$rendered_html = str_replace("##title##","#title#",$rendered_html);			// title
	$rendered_html = str_replace("##menu##","#menu#",$rendered_html);			// menu
	$rendered_html = str_replace("##banner##","#banner#",$rendered_html);		// banner
	$rendered_html = str_replace("##pageroot##","#pageroot#",$rendered_html);	// page root
	$rendered_html = str_replace("##rootdir##","#rootdir#",$rendered_html);		// any links
	return htmlentities($rendered_html,ENT_NOQUOTES);
}

/*
	generate_select_option
	
	$id 			html id of <select> element
	$matchItem 		item to match
	$query			select element value is created from the first column of the SQL query
					select element text is created from the second column of the SQL query
	$none			if $none = true, then generate a "None" element

*/
function generate_select_option($id,$matchItem,$query,$none = false){

	$matched = false;

	// get all items
	$result = db_query($query);
	
	if (db_is_valid_result($result)){
	
		$rows = array();
	
		if (db_num_rows($result) > 0)
			while($row = db_fetch_row($result))
				$rows[] = $row;
		
		generate_select_from_array($id,$matchItem,$rows,$none);
		
	}
}

/*
	generate_select_from_array
	
	$id 			html id of <select> element
	$matchItem 		item to match
	$items			select element value is created from the first element of the array row
					select element text is created from the second element of the array row
	$none			if $none = true, then generate a "None" element

*/
function generate_select_from_array($id,$matchItem,$items,$none = false){

	$matched = false;
	
	echo "<select name=\"$id\">";
	
	for ($i = 0;$i < count($items);$i++){
		echo '<option value="' . $items[$i][0] . '" ';
			
		if ($items[$i][0] == $matchItem){
			echo "selected";
			$matched = true;
		}
		
		echo '>' . special_item_strip(htmlentities($items[$i][1])) . '</option>';
	}
	
	if ($none == true){
		echo '<option value="-1"';
	
		if (!$matched)
			echo 'selected';
			
		echo '>None</option>';
	}

	echo '</select>';
}

// at some point in the future, we could use this for logging of some kind
function onnac_error($message, $retval = false){

	echo "<p class=\"error\"><strong>Error:</strong> $message";
	
	return $retval;
	
}

// debugging item
function prn($var){
	echo "<pre>";
	print_r($var);
	echo "</pre>";
}

?>