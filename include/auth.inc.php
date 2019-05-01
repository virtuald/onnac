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

	Authentication module
	
	Password is kept in a session variable, and transmitted like so:
	
		md5( session_id + ':' + md5( username + ':' + password ) )

	If it matches, then the session is regenerated and the user is granted access.
	Otherwise, we just return and eventually a login screen is shown to them.
	
	If use_ssl is enabled, then if we are not being accessed through SSL, this
	page will redirect to the page of the exact same name, except using https. 
	
	Loosely based off a digest authentication scheme at 
	http://www.xiven.com/sourcecode/digestauthentication.php by Thomas Pike
	
*/

require_once('config.inc.php');
require_once('db.inc.php');


class authentication {
    var $loggedIn;
    var $username;
	var $user_id;
    var $session_id;
	var $last_session_id;
	var $expired;
	var $login_message;
	var $auth_debug;
	var $auth_groups;
	
	// you must pass the name of the group(s) that users must belong to. 
	// 	seperate the name of each group with a semicolon, no spaces inbetween!
	
	// if blank, allow any valid user
	//	always allows the root group!
	
	
    function authentication($group_names) {
        
		global $cfg;
		
        $this->loggedIn = false;
		$this->expired = false;
		$this->user_id = "";
		$this->auth_groups = null;
		$this->login_message = "You must login to access this area."; 
        
		// only set this to be true if you have a reason
		$this->auth_debug = false;
		
		$key = "";
		
		// use SSL if directed
		if ($cfg['use_ssl'] && !isset($_SERVER['HTTPS'])){
			header('Location: https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
			die();
		}
		
		// XXX: This may be a bad way to do this, need to figure that out.. 
		$urlinfo = parse_url($cfg['rootURL']);
		if (!isset($urlinfo['path']))
			$urlinfo['path'] = '/';
			
		session_set_cookie_params(0,$urlinfo['path'],'');
		
		session_start();
		$this->session_id = session_id();
		
		if (isset($_POST['x' . $this->session_id]) && $_POST['x' . $this->session_id]  == "tx"){
			// first authentication, no session variables set
			if (isset($_POST['x' . $this->session_id . 'username']))
				$username = $_POST['x' . $this->session_id . 'username'];
			else
				$username = "";
				
			if (isset($_POST['x' . $this->session_id . 'key']))
				$key = $_POST['x' . $this->session_id . 'key'];
			else
				$key = "";
				
			if (isset($_POST['x' . $this->session_id . 'expires']) && 
				is_numeric($_POST['x' . $this->session_id . 'expires']))
				
				$expires = $_POST['x' . $this->session_id . 'expires'];
			else
				$expires = 0;
			
		}else{
			if (isset($_SESSION['username']))
				$username = $_SESSION['username'];
			else
				$username = "";
				
			if (isset($_SESSION['key']))
				$key = $_SESSION['key'];
			else
				$key = "";
				
			if (isset($_SESSION['expires']) && is_numeric($_SESSION['expires']))
				$expires = $_SESSION['expires'];
			else
				$expires = 0;
		}
		
		$this->username = $username;
		
		// debug
		if ($this->auth_debug){
			echo "<strong>WARNING:</strong> Authentication debugging enabled!<br/><pre>";
			print_r($_POST);
			print_r($_SESSION);
			print_r($_COOKIE);
			echo "</pre>";
		}
		
		// authentication
		if ($username == "" || $key == "" || $expires == 0){
			
			if ($this->auth_debug){
				echo "Auth failure point: Expected a username (" . htmlentities($username) . ") and key (" . htmlentities($key) . ") and expires (" . htmlentities($expires) . ") to be not zero!<br/>";
			}
			
		}else{
			
			// determine if this has expired or not (there is also a 5-second grace period, otherwise
			// really quick refreshes of pages will break!)
			if ($expires >= (time() + $cfg['login_expires'] + 5) || $expires <= time()){
			
				if ($this->auth_debug){
					echo "Auth failure point: expires (" . htmlentities($expires) . ") >= time + login_expires + 5 (" . (time() + $cfg['login_expires'] + 5) . ") || expires <= time (" . time() . ")<br/>"; 
				}
			
				$this->expired = true;
				$this->login_message = "Login expired. You must login to access this area.";
				$_SESSION['key'] = "";			// remove password from session
				return;
			}
			
			// figure out if we have a valid user or not
			if ($group_names != ""){
				$query = "SELECT ut.username, ut.hash, ut.user_id FROM $cfg[t_users] ut, $cfg[t_user_groups] ugt, $cfg[t_user_group_names] ugnt WHERE (ugnt.group_name = 'root'";

				// allow multiple groups here
				$groups = explode(";",$group_names);
				for ($i = 0;$i < count($groups);$i++)
					$query .= " OR ugnt.group_name = '" . db_escape_string(trim($groups[$i])) . "'";

				$query .= ") AND ugnt.group_id = ugt.group_id AND ugt.user_id = ut.user_id AND ut.username = '" . db_escape_string($username)."'";
			}else{
				$query = "SELECT ut.username, ut.hash, ut.user_id FROM $cfg[t_users] ut WHERE ut.username = '". db_escape_string($username)."'";
			}
			
			// grab user information
			$result = db_query($query);

			if (db_has_rows($result) > 0) {

				$rdUser = db_fetch_array($result);

				// Username is valid - determine password validity
				$expected = md5($this->session_id . ":" . $rdUser['hash']);
				
				if($key == $expected) {
				
					// Everything is good! Let them through
					$this->loggedIn = true;
					$this->username = $rdUser['username'];
					$this->user_id = $rdUser['user_id'];
		
					// set session varibles
					$this->regenerate_session(true);		// prevent possible session hijacking
					$_SESSION['username'] = db_escape_string($username);
					$_SESSION['key'] = md5($this->session_id . ":" . $rdUser['hash']);
					$_SESSION['expires'] = time() + $cfg['login_expires'];
					
					// set post/files up correctly
					if (isset($_SESSION['post'])){
						$_POST = $_SESSION['post'];
						unset($_SESSION['post']);
					}
					
					if (isset($_SESSION['files'])){
						$_FILES = $_SESSION['files'];
						unset($_SESSION['files']);
					}
				} else {
					if ($this->auth_debug){
						echo "Auth failure point: key != expected ('" . htmlentities($key) . "' != '" . htmlentities($expected) . "')<br/>";
					}
				}
			} else {
				if ($this->auth_debug){
					echo "Auth failure point: no rows found for " . htmlentities($query) . "<br/>"; 
				}
			}
		}
		
		if (!$this->loggedIn){
			$_SESSION['key'] = "";			// remove password from session
			$this->regenerate_session();
		}
    }
    
	// only specify one group at a time
	// if excludeCheckingRoot is false, then it will return true
	// for members of the root group. Otherwise, it will return false.
	function verifyMembership($groupName, $excludeCheckingRoot = false) {
	
		global $cfg;
		
		// if results aren't cached, then do so
		if ($this->auth_groups == null){
	
			// get all the groups that the person is a member of
			$result = db_query("
				SELECT ugnt.group_name FROM  
					$cfg[t_user_group_names] ugnt, $cfg[t_user_groups] ugt 
				WHERE  ugnt.group_id = ugt.group_id AND ugt.user_id = '" . $this->user_id . "'");
				
			if (!$result){
				if ($this->auth_debug){
					echo "SQL Error: " . db_error();
				}else{
					echo "The authentication provider could not be contacted.";
				}
			
			}else if (db_num_rows($result) > 0){
				// put the groups into an array
				$this->auth_groups = array();
				while ($row = db_fetch_row($result))
					$this->auth_groups[] = $row[0];
			}
		}
		
		// debug code
		if ($this->auth_debug){
			echo "Verifying: $groupName<pre>";
			print_r($this->auth_groups);
			echo "</pre>";
		}
		
		$ret = false;
		
		// see if it worked
		if ($this->auth_groups == null || count($this->auth_groups) == 0)
			$ret = false;
			
		else if (in_array($groupName,$this->auth_groups))
			$ret = true;
			
		else if (!$excludeCheckingRoot && in_array('root',$this->auth_groups))
			$ret = true;
			
		if ($this->auth_debug)
			echo "<p>Returned " . ($ret == true ? 'true' : 'false') . "</p>";
			
		return $ret;
	}
    
	// TODO: This is probably buggy, it needs to be fixed. don't you love confidence?
    function authenticate(){
        
		global $cfg;
		
		$ajax = get_get_var('ajax') == 'true' ? true : false;
		
		// save submitted data
		$_SESSION['post'] = $_POST;
		$_SESSION['files'] = $_FILES;
		
		$key_key = htmlentities('x' . $this->session_id . 'key');
		$user_key = htmlentities('x' . $this->session_id . 'username');
		
		if ($ajax)
			echo '<div id="auth_ajaxframe">';
		
		?>
<form name="frm_login" method="post" action="<?php echo htmlentities($_SERVER['REQUEST_URI']); ?>" onsubmit="return auth_form_submit();"><?php echo $this->login_message; ?> Javascript and cookies must be enabled to continue.
		<table>
			<tr><td>Username</td><td><input name="<?php echo htmlentities($user_key); ?>" size="20" value="<?php echo htmlentities($this->username);?>" /></td></tr>
			<tr><td>Password</td><td><input name="<?php echo htmlentities($key_key); ?>" size="20" type="password" /></td></tr>
		</table>
		<input type="hidden" name="x<?php echo htmlentities($this->session_id); ?>expires" value="<?php echo (time() + $cfg['login_expires']);?>" />
		<input type="hidden" name="x<?php echo htmlentities($this->session_id); ?>" value="tx" /><input type="submit" value="Login" /></form>
		
<SCRIPT type="text/javascript">
/* this can be reused -- the script element must go here, because of IE... */
var session_id = unescape('<?php echo rawurlencode($this->session_id); ?>');
var requestURI = unescape('<?php echo rawurlencode($_SERVER['REQUEST_URI']); ?>');
		
var auth_form_submit = function(){

	document.frm_login.<?php echo $key_key; ?>.value = hex_md5('<?php echo htmlentities($this->session_id);?>:' + hex_md5(document.frm_login.<?php echo $user_key; ?>.value + ':' + document.frm_login.<?php echo $key_key; ?>.value));
	
	<?php if ($ajax){?>
	
	/* assume that the ajax library has already been loaded since we're called through ajax.. */
	auth_ajax.setVar('x' + session_id + 'expires','<?php echo (time() + $cfg['login_expires']);?>');
	auth_ajax.setVar('x' + session_id,'tx');
	auth_ajax.setVar('x' + session_id + 'key',document.frm_login.<?php echo $key_key; ?>.value);
	auth_ajax.setVar('x' + session_id + 'username',document.frm_login.<?php echo $user_key; ?>.value);

	auth_ajax.requestFile = requestURI;
	auth_ajax.method = 'POST';
	auth_ajax.element = 'auth_ajaxframe';
	auth_ajax.onCompletion = auth_ajax_complete;
	auth_ajax.runAJAX();

	return false;
}

/* this will be in a global scope */
var auth_ajax = new sack();

function auth_ajax_complete(){
	execJS(document.getElementById('auth_ajaxframe'));
<?php }?>
}

/* ensure the session ID is valid! */
var sessions = 0;
var cArray = document.cookie.split(';');
for (var i = 0;i < cArray.length;i++)
	if (cArray[i].indexOf('<?php echo session_name(); ?>') != -1)
		sessions += 1;

<?php if (!$ajax){ ?>
if (sessions > 1)
	document.write('<p><strong>Warning:</strong> Previous sessions detected. Please reload your browser and/or clear your cookies to continue if login fails.</p>');
<?php } ?>

<?php require('jsmd5.inc.php'); ?>

</SCRIPT>
<?php
		if ($ajax)
			echo '</div>';
    }
	
	// logout function borrowed from PHP docs
	function logout(){
		// Unset all of the session variables.
		$_SESSION = array();

		// If it's desired to kill the session, also delete the session cookie.
		// Note: This will destroy the session, and not just the session data!
		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time()-42000, '/');
		}

		// Finally, destroy the session.
		@session_destroy();
		
		echo "<p>Logged out.</p><p><a href=\"##rootdir##\">Home</a></p>";
	}
	
	
	// regenerates a session ID
	function regenerate_session(){
		if (function_exists('session_regenerate_id')){
			session_regenerate_id();
		} // if it doesn't exist, oh well... you probably have other issues anyways. :)
		
		$this->last_session_id = $this->session_id;
		$this->session_id = session_id();
	}
}

?>