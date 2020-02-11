<?php
/**
 * Handles creating a CSV file of student swipe data and offering it for download to the user.
 * Called from admin.php.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

// Start the session.
session_start();

// We want to make sure that the user is a Super Admin (music_fc.admins.level == 1), and that
// the canvas variable is set in the $_GET request.
if( !isset( $_SESSION['username'] ) || empty( $_SESSION['username'] ) || $_SESSION['level'] > 1 || !isset( $_GET['canvas'] ) ) {
    header( 'Location: ../index.php' );
}

// Requires
require_once '../init.php';

// The footer uses this to load the right JavaScript.
define( 'CURRENT_PAGE', basename(__FILE__) );

// Get the variable. Because of PHP's weird truth tables, "0" evaluates to FALSE, so the 1 or 0
// will turn into an actual boolean, here.
$canvas = $_GET['canvas'] ? TRUE : FALSE;
$term = $_GET['term'];
$year = $_GET['year'];


$filename = "music_fc_" . ( $canvas ? 'canvas_' : '' ) . ucfirst( $term ) . $year . "_" . date('Ymd') . ".csv";
// Set the headers to force a download rather than stream it to the browser, and provide the default
// file name.
header( 'Content-Type: text/csv; charset=utf-8' );
header( "Content-Disposition: attachment; filename=$filename" );

// Call the method in our MusicFCHelper instance that will generate and stream the CSV file
// to the user. 
$mfhelp->get_csv( $term, $year, $canvas );

?>