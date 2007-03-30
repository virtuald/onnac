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

	Administration tool -- show 404 errors
	
*/

function showerrors(){

	global $cfg;
	
	echo "<h4>404 Errors</h4><em>Note: All pages are relative to the root of the website, and not the domain name</em><p>";

	// verify the sort type
	if (!isset($_GET['sort']) || ($_GET['sort'] != "referer" && $_GET['sort'] != "ip" && $_GET['sort'] != "url" && $_GET['sort'] != "time") )
		$sort = "url";
	else
		$sort = $_GET['sort'];
	
	// verify whether its ascending or descending
	if (!isset($_GET['by']) || ($_GET['by'] != "ASC" && $_GET['by'] != "DESC"))
		$by = "ASC";
	else
		$by = $_GET['by'];
		
	if (isset($_GET['clear'])){
		// clear all? 
		if ($_GET['clear'] == "all"){
			db_is_valid_result(db_query("DELETE FROM $cfg[t_errors]"));
		}else{
		
			if (is_numeric($_GET['clear'])){
				db_is_valid_result(db_query("DELETE FROM $cfg[t_errors] WHERE error_id = '" . db_escape_string($_GET['clear']) . "'"));
			}
		}		
	
	}

	$result = db_query("SELECT url,referer,ip," . db_get_timestamp_query("time") . ",error_id FROM $cfg[t_errors] ORDER BY $sort $by");
	
	
	// switch around sort order for future queries
	if ($by == "ASC")
		$by = "DESC";
	else
		$by = "ASC";
	
	if (db_has_rows($result)){
		echo "<a href=\"##pageroot##/?mode=showerrors&amp;clear=all\">Clear all 404 errors</a><p><table border=\"1\"><thead><tr><td><strong><a href=\"##pageroot##/?mode=showerrors&amp;sort=url&amp;by=$by\">URL</a></strong></td><td><strong><a href=\"##pageroot##/?mode=showerrors&amp;sort=referer&by=$by\">Referer</a></strong></td><td><strong><a href=\"##pageroot##/?mode=showerrors&amp;sort=ip&amp;by=$by\">IP</a></strong></td><td><strong><a href=\"##pageroot##/?mode=showerrors&amp;sort=time&amp;by=$by\">Time</a></strong></td><td>&nbsp;</td></tr></thead>";
		
		while($row = db_fetch_row($result))
			echo "<tr><td>" . htmlentities($row[0]) . "</td><td>" . htmlentities($row[1]) . "</td><td>$row[2]</td><td>" . date("F j, Y, g:i a",$row[3]) . "</td><td><a href=\"##pageroot##/?mode=showerrors&amp;clear=$row[4]\">Remove</a></td></tr>";
			
		echo "</table>";
	}else{
		echo "No 404 Errors exist!";
	}
	
?><p><a href="##pageroot##/">Return to administration menu</a><?php
}

?>