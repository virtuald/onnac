<?php

// use this to create a password hash for the authentication, since user management has not
// been implemented at this time

$username = "";
$password = "";

echo " User: $username<p>hash: ". md5("$username:$password");

?>
