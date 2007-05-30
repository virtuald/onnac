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

	Rendering engine for website

*/

// global rendering engine variables
unset($render);

global $perform_additional_substitution;
$perform_additional_substitution = false;	// provides for a way to display ## variables... 

// utility functions
require_once('./include/util.inc.php');

// caching stuff 
require_once("./include/http304.inc.php");


/*
	actually render the page -- pass it the input URL
	
	TODO: Merge SQL queries, possibly using a join?
	
	If simulate = true, then it will execute the necessary queries
	to render the page and will return an array of ('filename','content','filetime','executable');
	
*/
function render_page($input_url, $error_url = false, $simulate = false){
	
	// globals -- this allows more flexibility, because we can include this from multiple files, and use multiple 
	// databases and such... 
	global $cfg, $render; 
	
	$safe_input_url = db_escape_string($input_url);
	$input_url_md5 = md5($safe_input_url);				// cache this
	
	// optimization: get two rows here so we don't have to make another query
	// get the last modified date of the page, db specific!
	
	// TODO: Add index.htm/etc support
	
	// get the content of the page then -- we use a hash to access it with
	$result = db_query("SELECT url_hash," . db_get_timestamp_query("last_update") . "," . db_get_timestamp_query("other_update") . ",page_execute,page_content,page_title,menu_id,banner_id,template_id FROM $cfg[t_content] WHERE url_hash = '$input_url_md5' OR url_hash = '" . md5($safe_input_url . '/') . "'");
	
	if (!$result){
		$errmsg = "#R0, unspecified database error at " . htmlentities($input_url) ."!";
		if ($cfg['debug'] == true)
			$errmsg .= " Message: " . db_error();
				
		show_internal_error($errmsg);
		return false;	
	}
	
	// get the rows -- this could return two rows
	$num_rows = db_num_rows($result);
	$content = false;
	$is_404 = true;
	
	while($row = db_fetch_row($result)){
		
		// is it the requested page?
		if ($row[0] == $input_url_md5){
			$is_404 = false;
			$content = $row;
			break;
			
		// or did we find the redirect?
		}else if ($row[0] == md5($safe_input_url . '/')){
			$is_404 = false;
		}
	}
	
	
	if ($is_404){
		
		if ($error_url === false){
			
			// page does not exist!!! load the error page. redirect.. but first, send this header.  
			header("HTTP/1.x 404 Not Found");
			render_page($cfg['page_404'],$input_url);
		
		}else{
		
			$info = parse_url($cfg['rootURL']);
		
			// send a simple not found message... header has already been sent
			echo "<html><title>Not Found</title><body><h1>Not Found</h1><p>The requested URL " . htmlentities($info['path'] . $error_url) . " was not found</p><p>Additionally, a 404 Not Found error was encountered while trying to use an ErrorDocument to handle the request.</p>";
			echo "<hr><address>Powered by Onnac $cfg[onnac_version]</address></body></html>";
			
		}
		
		return false;
		
	}else if ($content === false){

		header("Location: $cfg[rootURL]" . str_replace("%2F","/",str_replace("%2f","/",urlencode($input_url))) . "/");
		return false;
	
	}

	// configure elink mode
	$cfg['elink_mode'] = false;
	if (get_get_var('elink_mode') == 'on' && $simulate == false)
		$cfg['elink_mode'] = true;

	// TODO: Optimize the caching stuff.. 
		
	// do not enable this stuff if the page is allowed to execute. Let it manually decide its
	// own caching stuff, if need be.. 
	if (!$simulate){
		if (!$content[1] && !$cfg['elink_mode']){
		
			// of the two update dates, send the latest one to the client to ensure cache consistency
			if ($content[1] > $content[2] && httpConditional($content[1],$input_url))
				return true;
			else if ($content[1] <= $content[2] && httpConditional($content[2],$input_url))
				return true;
		}
		
		// set the current MIME type depending on the extension of the file we're serving
		set_mime_type($input_url);
	}
	
	// TODO: Inhibit counters on some pages
	// update the page counter -- visited_count, last_visit
	if (!$simulate)
		db_query("UPDATE $cfg[t_content] SET visited_count = visited_count + 1, last_visit = NOW() WHERE url_hash = '$input_url_md5'");

	// finish the page render
	return render_partial($input_url,$content,$simulate);
	
}

/*
	render a page (used for previewing)
	
	$content = url_hash, last_update, other_update, page_execute, page_content, page_title, menu_id, banner_id, template_id
*/
function render_partial($input_url,$content,$simulate){

	global $cfg,$render;
	
	// ok, initialization here
	$render['menu'] = "";
	$render['banner'] = "";
	$render['template'] = "";
	$render['title'] = $content[5];
	$render['input_url'] = $input_url;
	
	// determine the page root from the passed URL
	// this is here now instead of in index to facilitate HTML exporting/previewing
	$pos = strrpos($input_url,'/');
	if ($pos === false)
		$cfg['page_root'] = $cfg['rootURL'];
	else
		$cfg['page_root'] = $cfg['rootURL'] . substr($input_url,0,$pos);
	
	// check to see if we need to render a menu
	if ($content[6] >= 0){
		// get menu
		$result = db_query("SELECT mit.href,mit.text,mgt.rank FROM $cfg[t_menu_items] mit,$cfg[t_menu_groups] mgt WHERE mgt.menu_id = $content[6] AND mgt.item_id = mit.item_id ORDER BY mgt.rank ASC");
		
		if (!$result){
			$errmsg = "#R2, unspecified database error at " . htmlentities($input_url) ."!";
			if ($cfg['debug'] == true)
				$errmsg .= " Message: " . db_error();
					
			show_internal_error($errmsg);
			return false;
			
		}else if (db_num_rows($result) > 0){
		
			//render menus
			while($row = db_fetch_row($result)){
				if ($row[0] == "")
					$render['menu'] .= "<li>$row[1]</a></li>";
				else
					$render['menu'] .= "<li><a href=\"$row[0]\">$row[1]</a></li>";
			}
		}
	}
	
	// check to see if we need to get a banner
	if ($content[7] >= 0){
		// select a random banner image from the db
		$query = "SELECT bit.src, bit.alt FROM $cfg[t_banner_items] bit, $cfg[t_banner_groups] bgt WHERE  bgt.banner_id = $content[7] AND bgt.item_id = bit.item_id ORDER BY ";
		
		if ($cfg['db_type'] == "mysql")
			$query .= "RAND() LIMIT 1";
		else if ($cfg['db_type'] == "postgre")
			$query .= "RANDOM() LIMIT 1";
		else
			// this really should never happen
			return show_internal_error("Invalid DB type specified for " . htmlentities($input_url) . "!!!");
		
		$result = db_query($query);
		
		if (!$result){
			$errmsg = "#R3, unspecified database error at " . htmlentities($input_url) ."!";
			if ($cfg['debug'] == true)
				$errmsg .= " Message: " . db_error();
					
			show_internal_error($errmsg);
			return false;
			
		}else if (db_num_rows($result) == 1){
				
			$row = db_fetch_row($result);	// get the banner, then display it
			$render['banner'] = "<img src=\"$row[0]\" alt=\"$row[1]\" />";
		
		}
	}
	
	// some pages just have a blank template
	if ($content[8] < 0){
		$render['template'] = "##content##";
	}else{
		// get template
		$result = db_query("SELECT template FROM $cfg[t_templates] WHERE template_id = $content[8]");
		
		// if invalid template specified, then we should fail
		if (!$result){
			$errmsg = "#R4, unspecified database error at " . htmlentities($input_url) ."!";
			if ($cfg['debug'] == true)
				$errmsg .= " Message: " . db_error();
					
			show_internal_error($errmsg);
			return false;
			
		}else if (db_num_rows($result) != 1)
			return show_internal_error("Error #X4 at " . htmlentities($input_url) . "!!!");

		// so.. now, load the template
		$template = db_fetch_row($result);
		$render['template'] = $template[0];
	}
	
	// signals the output handler
	$cfg['output_replace'] = true;
	
	// simulation
	if ($simulate)
		return array($input_url,$content[4],$content[1],$content[3]);
	
	// do we need to interpret any PHP in the content? Do it here. This also echos out
	// all the content as well, so we get two things in one package! :)
	if ($content[3])
		eval(';?>' . $content[4]);		// execute the php code on the page
	else
		echo $content[4];				// for portions of the site that don't need any dynamic content...
		
	return true;

}

/*
	Displays an internal server error to the user, with a message. 
*/
function show_internal_error($msg){

	global $cfg;
	

	if (!headers_sent()) header("HTTP/1.x 500 Internal server error");
	echo "<html><title>Server Error</title><body><h1>Internal server error</h1><p>The server has experienced an unexpected internal error. Please contact the administrator of this site. The error message is:";
	onnac_error($msg);
	echo "<hr><address>Powered by Onnac $cfg[onnac_version]</address></body></html>";
	
}

/*
	output_callback
	
	This callback is used to parse the outputted HTML and replace the necessary elements... 
		
*/

function output_callback($buffer,$simulate = false){

	global $render,$execution_time,$cfg,$perform_additional_substitution;

	// only do replacement if told to do so
	if ($cfg['output_replace'] == true){
	
		// template replacement first
		$buffer = str_replace('##content##',$buffer,$render['template']);
	
		$render['pageroot'] = $cfg['page_root'];
		$render['rootdir'] = $cfg['rootURL'];
		$render['db_queries'] = $cfg['db_queries'];
	
		// TODO: There is probably a better way to do this
		foreach ($cfg['replace_keywords'] as $r){
			
			$buffer = str_replace("##$r##",$render[$r],$buffer);
			
			if ($perform_additional_substitution == true)
				$buffer = str_replace("#$r#","##$r##",$buffer);
		}
		
		// if elink_mode is set, then add little [edit] links after each link! Neat. :)
		if ($cfg['elink_mode']){
			$x = str_replace('/','\/',addslashes($cfg['rootURL']));
			// crazy regular expression -- it works, nuff said, dont ask how
			$buffer = preg_replace(
				"/<a([^>]+)href=(\"|')$x([\/]?)([^\?\"\']*)([\?]?)([^\"']*)(\"|')([^>]*)>([^<]*)<\/a\>/is",
				"<a\\1href=\\2$cfg[rootURL]/\\4?elink_mode=on&amp;\\6\\7\\8>\\9</a> <a\\1href=\\2$cfg[rootURL]/interface/?mode=edurl&amp;page_url=/\\4&amp;ed_action=edit\\7>[Edit]</a>",
				$buffer);
			
			// show a link to edit that page on the bottom 
			$buffer .= '<p><a href="' . $cfg['rootURL'] . '/interface/?mode=edurl&amp;page_url=' . htmlentities($render['input_url']) . '&amp;ed_action=edit">[Edit this page]</a></p>';
		}
		
		// do this at the last second
		$buffer = str_replace('##time##',((int)((microtime_float() - $execution_time) * 1000))/1000,$buffer);
		if ($perform_additional_substitution == true)
			$buffer = str_replace('#time#','##time##',$buffer);
	}
	
	// Allows persistant connections
	// http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.13
	if (!$simulate)
		header('Content-Length: '.strlen($buffer));
		
	return $buffer;

}



function set_mime_type($url){

	// not comprehensive, just some common types
	$mime = array(
		'doc' => "application/msword",
		'exe' => "application/octet-stream",
		'bin' => "application/octet-stream",
		'dll' => "application/octet-stream",
		'pdf' => "application/pdf",
		'xls' => "application/vnd.ms-excel",
		'xhtml' => "application/xhtml+xml",
		'js' => "application/javascript",
		'swf' => "application/x-shockwave-flash",
		'tar' => "application/x-tar",
		'xml' => "application/xml",
		'xsl' => "application/xml",
		'xslt' => "application/xslt+xml",
		'zip' => "application/zip",
		'mp3' => "audio/mpeg",
		
		'bmp' => "image/bmp",
		'gif' => "image/gif",
		'jpg' => "image/jpeg",
		'jpeg' => "image/jpeg",
		'png' => "image/png",
		'svg' => "image/svg+xml",
		
		'css' => "text/css",
		'txt' => "text/plain",
		'html' => "text/html",
		'htm' => "text/html"
	);
		
	
	// send content type depending on the file extension
	$info = pathinfo($url);
	if (array_key_exists('extension',$info) && array_key_exists($info['extension'],$mime)){
		header('Content-Type: ' . $mime[$info['extension']]);
	}
}


?>
