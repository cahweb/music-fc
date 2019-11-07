<?php
/**
 * Logs the user out and destroys the session.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

// Requires
require_once 'includes/vars.php';

// We need to start the session in order to destroy it.
session_start();

// Wipe all the values.
$_SESSION = array();

// Then blow it up.
session_destroy();

// Redirect back to the homepage.
header( "Location: $address" );
?>