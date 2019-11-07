<?php
/**
 * Page to handle AJAX requests from the various pages in the app.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

// Requires
require_once 'init.php';
require_once 'includes/music-fc-helper.php';
require_once 'includes/music-fc-ajax-handler.php';

// Aliasing, because long classnames are long.
use MusicFCAjaxHandler as MFAjax;

// Instantiate a new MusicFCAjaxHandler, and feed the constructor our MusicFCHelper instance.
$mfajax = new MFAjax( $mfhelp );

// Feed the $_POST data the script received to the AJAX Handler's process() method.
echo $mfajax->process( $_POST );

// Make sure to kill the script when we're done.
die();
?>