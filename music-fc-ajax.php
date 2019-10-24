<?php
/**
 * Page to handle AJAX requests from the various pages in the app.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

require_once 'init.php';
require_once 'includes/music-fc-helper.php';
require_once 'includes/music-fc-ajax-handler.php';

use MusicFCAjaxHandler as MFAjax;

$mfajax = new MFAjax( $mfhelp );

echo $mfajax->process( $_POST );

die();
?>