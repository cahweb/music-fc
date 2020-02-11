<?php
/**
 * The main page of the Music Forum Credit web app. Handles user login, for the most part,
 * and, once logged in, presents links to the other pages.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

// Requires
require_once 'init.php';
require_once 'includes/music-fc-helper.php';
require_once 'includes/music-fc-query-ref.php';

// Aliasing, because long classnames are long.
use MusicQueryRef as MQEnum;

// The footer uses this to determine which JavaScript to load.
define( 'CURRENT_PAGE', basename( __FILE__ ) );

// Get the header.
require_once 'header.php';

// Start the session.
session_start();

// Require the adLDAP module, too.
//require_once "lib/adLDAP/lib/adLDAP/adLDAP.php";
require_once 'NET/adLDAP.php';

// Exit if we can't find it.
if( is_null( $mfhelp->get_adLDAP() ) ) exit( "Couldn't instantiate authenticator." );

// Reset some $_SESSION variables, if necessary.
if( isset( $SESSION[ 'event' ] ) )
    if( $_SESSION[ 'event' ] != '' )
        $_SESSION[ 'event' ] = '';

if( isset( $_SESSION[ 'email' ] ) )
    if( $_SESSION[ 'email' ] != '' )
        $_SESSION[ 'email' ] = '';

// If the user has submitted stuff to the Login form, process it.
if( (isset( $_POST['nid'] ) && $_POST['nid'] != '' ) && ( isset( $_POST['pw'] ) && $_POST['pw'] != '' ) ) {

    // Initialize the variables.
    $email = $mfhelp->scrub( $_POST['nid'] );
    $pass  = $mfhelp->scrub( $_POST['pw'] );
    $authorized = FALSE;

    // Check the basic login (probably a legacy step--most users will authenticate with adLDAP).
    $result = $mfhelp->query( MQEnum::LOGIN_BASE, $email, $pass );

    // If we fail, we do adLDAP
    if( !$result || ( $result instanceof mysqli_result && $result->num_rows < 1 ) ) {
        // Make sure we have an adLDAP object.
        if( $mfhelp->get_adLDAP() != NULL ) {
            // Try to authenticate.
            if( $mfhelp->get_adLDAP()->authenticate( $email, $pass ) ) {

                // Request the information we need from the DB.
                $result_email = $mfhelp->query( MQEnum::LOGIN_ADLDAP, $email );

                // Error handling.
                if( !$result_email || ($result_email instanceof mysqli_result && $result_email->num_rows < 1 ) ) {
                    echo "Invalid Login.";
                    echo "<meta http-equiv=\"REFRESH\" content=\"1;url=$address\">";
                }
                // Otherwise, keep going.
                else {

                    $row_email = mysqli_fetch_assoc( $result_email );

                    // Set the $_SESSION variables we'll need.
                    $_SESSION['email'] = $row_email['email'];
                    $_SESSION['username'] = $row_email['username'];
                    $_SESSION['nid'] = $mfhelp->scrub( $_POST['nid'] );

                    // Check to see if the user is an admin. If they're not, we're still not letting
                    // them in, even if they successfully authenticate.
                    $result_nid = $mfhelp->query( MQEnum::ADMIN_CHECK, $_SESSION['nid'] );

                    // If this, they're not in the list.
                    if( !( $result_nid instanceof mysqli_result ) || $result_nid->num_rows == 0 ) {
                        echo "You are not authorized to interact with this application.";

                        unset( $_SESSION['username'], $_SESSION['email'], $_SESSION['nid'] );
                    }
                    // Otherwise, they're good to go.
                    else {
                        $row = mysqli_fetch_assoc( $result_nid );

                        // Setting the last $_SESSION variable we'll need.
                        $_SESSION['level'] = intval( $row['level'] );
                    }
                }
            }
            // Error handling.
            else {
                echo "Invalid Login";
            }
        }
        // Error handling.
        else {
            echo "Invalid Login";
        }
    }
    // In the off chance they can authenticate the old way, move forward. (Again, I don't think this ever
    // actually fires anymore).
    else {
        $row = mysqli_fetch_assoc( $result );
        echo "Your login information has been verified.";
        $_SESSION['email'] = $email;
        $_SESSION['username'] = $row['username'];
    }
}

// If the user hasn't successfully logged in, show them the login form.
if( !isset( $_SESSION['username'] ) || empty( $_SESSION['username'] ) ) {
?>
    <div id="main" class="container mt-5">
        <div class="row justify-content-center">
            <div id="main-title" class="jumbotron p-4">
                <h1 class="text-secondary text-center font-condensed mb-4">Music Forum Credit</h1>
                <p class="lead text-secondary text-center mb-0">Please Login</p>
                <p class="text-secondary text-center">to access page functions.</p>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-8 col-md-6 col-lg-4 bg-faded p-3 option-box">
                <form action="index.php" method="post">
                    <div class="form-group">
                        <label for="nid">NID</label>
                        <input type="text" class="form-control" id="nid" name="nid" aria-describedby="nidHelp" placeholder="Enter NID">
                        <small id="nidHelp" class="form-text text-muted">Use your normal UCF NID.</small>
                    </div>
                    <div class="form-group">
                        <label for="pw">Password</label>
                        <input type="password" class="form-control" id="pw" name="pw" aria-describedby="pwHelp" placeholder="*****">
                        <small id="pwHelp" class="form-text text-muted">Your NID Password. <span class="float-right"><a href="https://mynid.ucf.edu">Forgot Password?</a></span></small>
                    </div>
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
            </div>
        </div>
    </div>

<?php
// Otherwise, show them the main menu.
} else {

    // Checking this enables us to enable or diable links based on the user's admin level.
    $level = $_SESSION['level'];
    ?>

    <div id="main" class="container mt-5">
        <div class="row justify-content-center">
            <div class="jumbotron option-box pt-4 pb-4">
                <h1 class="text-secondary text-center font-condensed mb-3">Music Forum Credit</h1>
                <h4 class="heading-underline text-secondary text-center font-slab-serif mb-4">Welcome, <?= $_SESSION[ 'username' ] ?>!</h4>
                <p class="lead text-secondary mb-1">Please select an option:</p>
                <div class="list-group mb-3">
                    <a href="<?= $level <= 3 ? "swipe.php" : "#" ?>" class="list-group-item list-group-item-action<?= $level <= 3 ? "" : " disabled" ?>">Start Event Swipe</a>
                    <a href="<?= $level <= 2 ? "events.php" : "#" ?>" class="list-group-item list-group-item-action<?= $level <= 2 ? "" : " disabled" ?>">Manage Events</a>
                    <a href="<?= $level == 1 ? "admin.php" : "#" ?>" class="list-group-item list-group-item-action<?= $level == 1 ? "" : " disabled" ?>">Admin Tools</a>
                </div>
                <a href="logout.php">
                    <button type="button" class="btn btn-primary" id="logout-button">Logout</button>
                </a>
            </div>
        </div>
    </div>
    <?php
}

// Get the footer.
require_once 'footer.php';
?>