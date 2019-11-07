<?php
/**
 * Sets some initial variables and requires some files that are common to every page.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

// For now, we want errors to show up. We'll turn this off (or comment it out) once 
// we're ready for official release.
ini_set( 'display_errors', 1 );
ini_set( 'log_errors', 1 );
ini_set( 'error_log', dirname(__FILE__) . '/debug.log');

// Defines the base directory, to avoid occasional wonkiness with relative paths.
define( 'MUSIC_FC__BASE_DIR', dirname( __FILE__ ) );

// Requires
require_once 'includes/vars.php';
require_once 'includes/dbconfig.php';
require_once 'includes/music-fc-helper.php';

// Instantiates a MusicFCHelper object, to handle stuff on the page.
$mfhelp = new MusicFCHelper( $db_server, $db_user, $db_pass, $db, $charset, $menu_items );

// For some reason, this PHP global constant doesn't seem to be set, so we set it here.
// It enables query caching, which we use for some of our bigger queries.
if( !defined( 'MYSQLND_QC_ENABLE_SWITCH' ) ) {
    define( 'MYSQLND_QC_ENABLE_SWITCH', "qc=on" );
}

?>