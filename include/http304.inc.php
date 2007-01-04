<?php
/*Optimisation: Enable support for HTTP/1.x conditional requests in PHP.*/
// This code is licensed under "Creative-Commons Attribution-ShareAlike 2.0 France"
// http://creativecommons.org/licenses/by-sa/2.0/fr/
  
// Modified: Removed compression stuff, reformatted some comments and messy code

//In RSS/ATOM feedMode, contains the date of the clients last update.
$clientCacheDate=0; //Global variable because PHP4 does not allow conditional arguments by reference
$_sessionMode=false; //Global private variable

// returns true if you need to exit
function httpConditional($UnixTimeStamp, $input_url, $no_cache = false, $isPrivate = false,$feedMode=false)
{	//Credits: http://alexandre.alapetite.net/doc-alex/php-http-304/
	//RFC2616 HTTP/1.1: http://www.w3.org/Protocols/rfc2616/rfc2616.html
	//RFC1945 HTTP/1.0: http://www.w3.org/Protocols/rfc1945/rfc1945.txt
	
	//If HTTP headers are already sent, too late, nothing to do.
	if (headers_sent()) return false;
	
	//The modification date must be older than the current time on the server
	$UnixTimeStamp=min($UnixTimeStamp,time());
	
	//If the conditional request allows to use the client's cache
	$is304=true;
	//If the conditions are refused
	$is412=false;
	//There is a need for at least one condition to allow a 304 Not Modified response
	$nbCond=0;
	
	/*
	 Date format: http://www.w3.org/Protocols/rfc2616/rfc2616-sec3.html#sec3.3.1
	 To smallest common divisor between the different standards that have been used is like: Mon, 28 Jun 2004 18:31:54 GMT
	 It is compatible HTTP/1.1 (RFC2616,RFC822,RFC1123,RFC733) and HTTP/1.0 (Usenet getdate(3),RFC850,RFC1036).
	*/
	$dateLastModif = gmdate('D, d M Y H:i:s \G\M\T',$UnixTimeStamp);
	$dateCacheClient='Tue, 10 Jan 1980 20:30:40 GMT';
	
	// Entity tag (Etag) of the returned document.
	// http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.19
	// Must be modified if the filename or the content have been changed
	if (isset($_SERVER['QUERY_STRING'])) 
		$myQuery='?'.$_SERVER['QUERY_STRING'];
	else 
		$myQuery='';
	
	if (isset($_SESSION))
	{	//In the case of sessions, integrate the variables of $_SESSION in the ETag calculation
		global $_sessionMode;
		$_sessionMode=true;
		$myQuery.= print_r($_SESSION,true).session_name().'='.session_id();
	}
	
	// include the mtimes of any 'base' scripts, and 'other' dates, so we can keep track of templates/menus changing
	$etagServer='"'.md5($input_url . $myQuery . '#' . $dateLastModif . get_generator_mtimes()).'"'; //='"0123456789abcdef0123456789abcdef"'
	
	if (isset($_SERVER['HTTP_IF_MATCH']))
	{	//http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.24
		$etagsClient=$_SERVER['HTTP_IF_MATCH'];
		//Compare the current Etag with the ones provided by the client
		$is412=(($etagClient!='*')&&(strpos($etagsClient,$etagServer)===false));
	}
	
	if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
	{	//http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.25
		//http://www.w3.org/Protocols/rfc1945/rfc1945.txt
		//Get the date of the client's cache version
		//No need to check for consistency, since a string comparison will be made.
		$nbCond++;
		$dateCacheClient = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
		$p = strpos($dateCacheClient,';'); //Internet Explorer is not standard complient
		
		if ($p !== false) //IE6 might give "Sat, 26 Feb 2005 20:57:12 GMT; length=134"
			$dateCacheClient=substr($dateCacheClient,0,$p); //Removes the information after the date added by IE
		
		//Compare the current document's date with the date provided by the client.
		//Must be identical to return a 304 Not Modified
		$is304= ($dateCacheClient == $dateLastModif);
	}
	
	if ($is304 && isset($_SERVER['HTTP_IF_NONE_MATCH']))
	{	//http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.26
		//Compare Etags to check if the client has already the current version
		$nbCond++;
		$etagClient=$_SERVER['HTTP_IF_NONE_MATCH'];
		$is304=(($etagClient==$etagServer)||($etagClient=='*'));
	}
	
	//$_SERVER['HTTP_IF_RANGE']
	//This library does not handle this condition. Returns a normal 200 in all the cases.
	//http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.27
	
	if ((!$is412)&&isset($_SERVER['HTTP_IF_UNMODIFIED_SINCE']))
	{	//http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.28
		$dateCacheClient=$_SERVER['HTTP_IF_UNMODIFIED_SINCE'];
		$p=strpos($dateCacheClient,';');
		
		if ($p!==false)
			$dateCacheClient=substr($dateCacheClient,0,$p);
			
		$is412=(strcasecmp($dateCacheClient,$dateLastModif)!=0);
	}
	
	if ($feedMode)
	{	//Special RSS
		$out_dateRSSclient=strtotime($dateCacheClient);
		$cachePrivacy=0;
	}
	
	if ($is412)
	{	//http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.4.13
		header('HTTP/1.1 412 Precondition Failed');
		header('Content-Type: text/plain');
		header('Cache-Control: private, max-age=0, must-revalidate');
		echo "HTTP/1.1 Error 412 Precondition Failed: Precondition request failed positive evaluation\n";
		//The response is finished; the request has been aborted
		//because the document has been modified since the client has decided to do an action
		//(avoid edition conflicts for example)
		return true;
	}elseif ($is304 && ($nbCond>0))
	{	//http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.3.5
		header('HTTP/1.0 304 Not Modified');
		header('Etag: '.$etagServer);
		
		if ($feedMode) 
			header('Connection: close'); //You should comment this line when running IIS
			
		return true; //The response is over, the client will use the version in their cache
		
	}else //The request will be handled normally, without condition
	{	
		//header('HTTP/1.0 200 OK'); //By default in PHP
		//http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9
		if ($isPrivate)
			header('Cache-Control: private, no-cache');
		else if ($no_cache)
			header('Cache-Control: no-cache');
		
		header('Last-Modified: '.$dateLastModif);
		header('Etag: '.$etagServer);
		
		//http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.10
		//No need to keep a connection opened for RSS/ATOM feeds
		//since most of the time clients take only one file
		if ($feedMode) 
			header('Connection: close');  //You should comment this line when running IIS
			
		//http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
		//In the case of a HEAD request,
		//the same headers as a GET request must be returned,
		//but the script does not need to calculate any content
		return $_SERVER['REQUEST_METHOD']=='HEAD';
	}
}

function httpConditionalRefresh($UnixTimeStamp, $input_url)
{	//Update HTTP headers if the content has just been modified by the client's request
	//See an example on http://alexandre.alapetite.net/doc-alex/compteur/
	if (headers_sent()) 
		return false;
	
	$dateLastModif = gmdate('D, d M Y H:i:s \G\M\T',$UnixTimeStamp);
	
	if (isset($_SERVER['QUERY_STRING'])) 
		$myQuery = '?'.$_SERVER['QUERY_STRING'];
	else 
		$myQuery = '';
		
	global $_sessionMode;
	if ($_sessionMode && isset($_SESSION))
		$myQuery .= print_r($_SESSION,true).session_name().'='.session_id();
	
	$etagServer = '"'.md5($input_url . $myQuery.'#' . $dateLastModif . get_generator_mtimes()).'"';
	
	header('Last-Modified: '.$dateLastModif);
	header('Etag: '.$etagServer);
}

// gets the mtimes of all 'generator' scripts to ensure that if one of those changes/is updated, that
// it is shown in the client as well. 
function get_generator_mtimes(){

	global $cfg;
	$mtime = '';
	
	// always reflect configuration changes!
	$mtime .= filemtime('./include/config.inc.php');
	
	if ($cfg['etag_mtimes'] == 1){
		$mtime .= filemtime('./index.php');
		$mtime .= filemtime('./include/auth.inc.php');
		$mtime .= filemtime('./include/db.inc.php');
		$mtime .= filemtime('./include/http304.inc.php');
		$mtime .= filemtime('./include/render.inc.php');
		$mtime .= filemtime('./include/util.inc.php');
	}
	
	return $mtime;
}

?>
