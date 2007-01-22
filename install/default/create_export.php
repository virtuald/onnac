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
* DISCLAIMED. IN NO EVENT SHALL THE REGENTS AND CONTRIBUTORS BE LIABLE FOR ANY
* DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

	Creates an export file to be used with the installer
	
	This is definitely a duplication of code, but at the moment the export
	routines aren't really all that modular so this will suffice. 
	
*/

require_once('../../include/default.inc.php');
require_once('../../include/util.inc.php');

// array of files/filenames to be installed
$files = array();
//$files[] = array(filename,url,page_execute,hidden,page_title,template_name,banner_name,menu_name)
$files[] = array("index.html",'/',0,0,'Onnac CMS Default Page','Default','','');
$files[] = array("error403.html",'/error403.html',0,0,'403','Default','','');
$files[] = array("error404.html",'/error404.html',1,0,'403','Default','','');
$files[] = array("admin.php",'/interface/',1,1,'### Onnac Administration Interface','Admin','','');


// array of templates to be installed
// $tfiles[] = array(filename,template_name)
$tfiles = array();
$tfiles[] = array('default.tpl','Default');
$tfiles[] = array('admin.tpl','Admin');


// content expects these keys:
// 'url_hash, url,page_execute, hidden, page_title, page_content, last_update, banner_name, menu_name, template_name'
$content = array();
foreach ($files as $item){

	if (!file_exists($item[0]))
		die("$item[0] does not exist!");
		
	$page_content = get_file_contents($item[0]);
	$last_update = filemtime($item[0]);

	$content[] = array(
		'url_hash' => md5($item[1]),
		'url' => $item[1],
		'page_execute' => $item[2],
		'hidden' => $item[3], 
		'page_title' => $item[4], 
		'page_content' => $page_content,
		'last_update' => $last_update,
		'template_name' => $item[5],
		'banner_name' => $item[6],
		'menu_name' => $item[7]
	);	
}

// template expects these keys:
//'template_name','template','last_update'
$templates = array();
foreach ($tfiles as $item){

	if (!file_exists($item[0]))
		die("$item[0] does not exist!");
		
	$template_content = get_file_contents($item[0]);
	$last_update = filemtime($item[0]);

	$templates[] = array(
		'template_name' => $item[1],
		'template' => $template_content,
		'last_update' => $last_update
	);	
}

// create the output data
$output = array();
$output['content'] = $content;
$output['templates'] = $templates;
$output['banners'] = array();
$output['menus'] = array();

$output['dumptype'] = 'content';
$output['export_date'] = date('r');
$output['export_version'] = 2;
$output['export_description'] = "Onnac installation file";

global $cfg;
$output['onnac_version'] = $cfg['onnac_version'];


// Now, send the export data to the output

// show output array structure
//echo "<pre>";
//print_r($output);
//echo "</pre>";
//die();

// export the data
header("Content-Type: application/octet-stream;");
header('Content-Disposition: attachment; filename="install.osf"');
echo serialize($output);


?>