<?php
require_once 'includes/vars.php';

session_start();

$_SESSION = array();

session_destroy();

header( "Location: $address" );
?>