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

	Wrapper functions to allow us to use postgre and mysql, without using PEAR::DB or something like
	that... currently supports MySQL, Postgre. 
	
*/


function db_connect($server, $username, $password, $db_name){
	
	global $cfg;
	switch($cfg['db_type']){
	
		case "mysql": 	
			$ret=mysql_connect($server, $username, $password);
			if(!mysql_select_db($db_name)) 
				return false;
			return $ret;
		
		case "postgre": 
			return pg_connect("host=".$server." user=".$username." password=".$password." dbname=".$db_name);
		
		default: 
			die("Database type not specified in configuration.");
	}
}

// php doesn't support function overloading :(
function db_close($link = null){

	global $cfg;
	if ($link){
		switch($cfg['db_type']){
			case "mysql": 	
				return mysql_close($link);
				
			case "postgre":	
				return pg_close($link);
				
			default: 
				die("Database type not specified in configuration.");
		}
	}else{
		switch($cfg['db_type']){
			case "mysql": 	
				return mysql_close();
				
			case "postgre":	
				return pg_close();
				
			default: 
				die("Database type not specified in configuration.");
		}
	}
}

function db_query($query){

	global $cfg;
	
	if ($cfg['debug'] == true)
		$cfg['db_last_sql_query'] = $query;
	
	switch($cfg['db_type']){
	
		case "mysql": 	
			return mysql_query($query);
			
		case "postgre": 
			return pg_query($query);
		
		default: 
			die("Database type not specified in configuration.");
	}
}

function db_num_rows($result){

	global $cfg;
	switch($cfg['db_type']){
	
		case "mysql": 	
			return mysql_num_rows($result);
			
		case "postgre":
			return pg_num_rows($result);
			
		default: 
			die("Database type not specified in configuration.");
	}
}

function db_fetch_row($result){
	
	global $cfg;
	switch($cfg['db_type']){
		case "mysql": 	
			return mysql_fetch_row($result);
			
		case "postgre": 
			return pg_fetch_row($result);
			
		default: 
			die("Database type not specified in configuration.");
	}
}

function db_fetch_array($result){

	global $cfg;
	switch($cfg['db_type']){
		case "mysql": 	
			return mysql_fetch_array($result);
		
		case "postgre": 
			return pg_fetch_array($result);
		default: 
			die("Database type not specified in configuration.");
	}
}

function db_fetch_assoc($result){
	global $cfg;
	switch($cfg['db_type']){
		case "mysql": 	
			return mysql_fetch_assoc($result);
		
		case "postgre": 
			return pg_fetch_assoc($result);
		default: 
			die("Database type not specified in configuration.");
	}
}

function db_escape_string($unescaped_string){
	
	global $cfg;
	switch($cfg['db_type']){
		case "mysql": 	
			return mysql_escape_string($unescaped_string);
			
		case "postgre": 
			return pg_escape_string($unescaped_string);
			
		default: 
			die("Database type not specified in configuration.");
	}
}

// intended to be used with array_walk
function db_total_escape(&$item,$key){
	$item = db_escape_string($item);
}

function db_affected_rows($result){
	
	global $cfg;
	switch($cfg['db_type']){
		case "mysql": 	
			return mysql_affected_rows();
			
		case "postgre": 
			return pg_affected_rows($result);
			
		default: 
			die("Database type not specified in configuration.");
	}
}

function db_error(){

	global $cfg;
	switch($cfg['db_type']){
		case "mysql": 	
			return mysql_error();
			
		case "postgre": 
			return pg_last_error();
			
		default: 
			die("Database type not specified in configuration.");
	}
}

// functions needed to specify database-specific items (timestamps and such!)
function db_get_timestamp_query($field_name){
	global $cfg;
	switch ($cfg['db_type']){
		case "mysql":
			return "UNIX_TIMESTAMP($field_name)";
		
		case "postgre":
			// which way  is correct?
			//return "EXTRACT(EPOCH FROM TIMESTAMP $field_name)";
			return "EXTRACT(EPOCH FROM $field_name)";
		
		default:
			die("Database type not specified in configuration.");
	}
}

// transactional code
function db_begin_transaction(){

	global $cfg;
	if ($cfg['enable_transactions'] == true)
		return db_query("BEGIN");
	return true;
}

function db_commit_transaction(){
	
	global $cfg;
	if ($cfg['enable_transactions'] == true)
		return db_query("COMMIT");
	return true;
}

function db_rollback_transaction(){

	global $cfg;
	if ($cfg['enable_transactions'] == true)
		return db_query("ROLLBACK");
	return false;
}

// Use this to check if there is more than zero rows, and a valid returned result.
//
// If $show_error = true, then if there is an SQL error ($result == false) then
// it will display the SQL error 
//
function db_has_rows($result,$show_error = true){

	if (db_is_valid_result($result,$show_error) && db_num_rows($result) > 0)
		return true;
		
	return false;
}

// used primarily in admininistrative functions to show SQL errors in a uniform manner
function db_is_valid_result($result,$show_error = true){

	global $cfg;

	if (!$result && $show_error){
		$err = db_error();
		if ($err == "")
			$err = "No SQL error occurred. This may be an invalid error message!";
			
		echo "<p class=\"sqlerror\"><strong>SQL Error:</strong> " . htmlentities($err);
		
		if (isset($cfg['db_last_sql_query']))
			echo "<br /><br /><strong>Last SQL Query:</strong><br />" . htmlentities($cfg['db_last_sql_query']);
		echo '</p>';
	}
	return $result;
}

?>
