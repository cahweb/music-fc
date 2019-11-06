<?php

session_start();

if( !isset( $_SESSION['username'] ) || empty( $_SESSION['username'] ) || $_SESSION['level'] > 1 || !isset( $_GET['canvas'] ) ) {
    header( 'Location: ../index.php' );
}

require_once '../init.php';
require_once MUSIC_FC__BASE_DIR . '/includes/music-fc-query-ref.php';

use MusicQueryRef as MQEnum;

define( 'CURRENT_PAGE', basename(__FILE__) );

$canvas = $_GET['canvas'] ? TRUE : FALSE;

header( 'Content-Type: text/csv; charset=utf-8' );
header( 'Content-Disposition: attachment; filename=music_fc_' . ( $canvas ? 'canvas_' : '' ) . date('Ymd') . '.csv' );

$mfhelp->get_csv( $canvas );

//header( 'Location: ../admin.php' );
?>