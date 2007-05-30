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

	This is the default configuration.. this SHOULD NOT be modified.
	If you include config.inc.php, this should be included before it. 

*/

global $cfg;
$cfg = array(); 

$cfg['enable_transactions'] 	= 1;		// enable SQL transactions

// If 1, this tells the cache validation script to hash the mtimes of all 'base' scripts, to see if they have
// changed. If 0, then it will only verify the mtime of config.inc.php
// Everyone wants to set this to 0 unless you're working on render.inc.php
$cfg['etag_mtimes']				= 0;

$cfg['login_expires']			= 1800;		// time (in seconds) logins expire
$cfg['use_ssl']					= false;	// set this to true for auth pages to use SSL
$cfg['debug']					= false;	// set this to true to turn on debug messages
											// should not be set to true in a production environment
											
$cfg['editarea_compress']		= true;		// use the editarea compression program to attempt
											// to compress the editarea components. Otherwise, it
											// will include edit_area_loader.js instead. Some
											// setups have been found to not work correctly with
											// the compression script
											
// error*page is an input_url that is stored in the database
$cfg['page_403']			= "/error403.html";
$cfg['page_404']			= "/error404.html";


//special directories
$cfg['img_autofill_dir']		= "/img/banners";		// location of banners

// this is the list of keywords that will get replaced, excluding ##content## and ##time##.
// basically, the global $render[$x] contains the value to be replaced by ##$x##, where $x is an element in
// this array. the order *does* matter -- pageroot and rootdir should generally be LAST 											
$cfg['replace_keywords'] = array('title','banner','menu','db_queries','pageroot','rootdir');

// ************************************************************************
// do NOT copy items below this line into config.inc.php
// ************************************************************************

// I'll definitely forget to increment this... 
$cfg['onnac_version']		 	= "0.0.9.5";
// turn replacements off, until needed -- this is set to true right before the
// current webpage is evaluated. If you need to disable output_replace in your page,
// you MUST do it in the page itself, not in the conf file
$cfg['output_replace'] = false;

?>