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

	Administration tool -- view page counts
	
	TODO:
		Add function to edit page counts
		Add function to view counts for only certain directory
		
*/

function viewcount(){

	global $cfg;
	
	$row[0] = 0;
	
	$result = db_query("SELECT " . db_get_timestamp_query('NOW()'));
	if (db_has_rows($result))
		$row = db_fetch_row($result);
	
	echo "<h4>Site counts</h4>Current time:<br>Web Server: ". date("F j, Y, g:i a") . "<br>DB Server: " . date("F j, Y, g:i a",$row[0]) . "<p><em>Note: All pages are relative to the root of the website, and not the domain name</em><p>";

	// verify the sort type
	if (!isset($_GET['sort']) || ($_GET['sort'] != "url" && $_GET['sort'] != "visited_count" && $_GET['sort'] != "last_visit") )
		$sort = "url";
	else
		$sort = $_GET['sort'];
	
	// verify whether its ascending or descending
	if (!isset($_GET['by']) || ($_GET['by'] != "ASC" && $_GET['by'] != "DESC"))
		$by = "ASC";
	else
		$by = $_GET['by'];

	$result = db_query("SELECT url,visited_count," . db_get_timestamp_query("last_visit") . " FROM $cfg[t_content] ORDER BY $sort $by");
	
	// switch around sort order for future queries
	if ($by == "ASC")
		$by = "DESC";
	else
		$by = "ASC";
	
	if (db_has_rows($result)){
		echo "<table class=\"highlighted\"><thead><tr><td><strong><a href=\"##pageroot##/?mode=viewcount&amp;sort=url&amp;by=$by\">Page</a></strong></td><td><strong><a href=\"##pageroot##/?mode=viewcount&amp;sort=visited_count&amp;by=$by\">Count</a></strong></td><td><strong><a href=\"##pageroot##/?mode=viewcount&amp;sort=last_visit&amp;by=$by\">Last access</a></strong></td></tr></thead>";
		
		while($row = db_fetch_row($result))
			echo "<tr><td>$row[0]</td><td>$row[1]</td><td>" . date("F j, Y, g:i a",$row[2]) . "</td></tr>";
			
		echo "</table>";
	}else{
		onnac_error("No page counts found!");
	}
	
?><p><a href="##pageroot##/">Return to administration menu</a><?php
}

?>