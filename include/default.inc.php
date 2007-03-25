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

	This is the default configuration.. this SHOULD NOT be modified.
	If you include config.inc.php, this should be included before it. 

*/

global $cfg;
$cfg = array(); 

// I'll definitely forget to increment this... 
$cfg['onnac_version']		 	= "0.0.9.0";

// ************************************************************************
// items below this line may be COPIED into config.inc.php, to change the 
// default configuration
// ************************************************************************


$cfg['enable_transactions'] 	= 1;		// enable SQL transactions

// If 1, this tells the cache validation script to hash the mtimes of all 'base' scripts, to see if they have
// changed. If 0, then it will only verify the mtime of config.inc.php
// Everyone wants to set this to 0 unless you're working on render.inc.php
$cfg['etag_mtimes']				= 0;

$cfg['login_expires']			= 1800;		// time (in seconds) logins expire
$cfg['use_ssl']					= false;	// set this to true for auth pages to use SSL
$cfg['debug']					= false;	// set this to true to turn on debug messages
											// should not be set to true in a production environment

?>