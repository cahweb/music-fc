<?php
ini_set( 'display_errors', 1 );
ini_set( 'log_errors', 1 );
ini_set( 'error_log', dirname(__FILE__) . '/debug.log');

define( 'MUSIC_FC__BASE_DIR', dirname( __FILE__ ) );

require_once 'includes/vars.php';
require_once 'includes/dbconfig.php';
require_once 'includes/music-fc-helper.php';

$mfhelp = new MusicFCHelper( $db_server, $db_user, $db_pass, $db, $charset, $menu_items );

if( !defined( 'MYSQLND_QC_ENABLE_SWITCH' ) ) {
    define( 'MYSQLND_QC_ENABLE_SWITCH', "qc=on" );
}

?>