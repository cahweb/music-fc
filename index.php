<?php
require_once 'init.php';
require_once 'includes/music-fc-helper.php';
require_once 'includes/music-fc-query-ref.php';

use MusicQueryRef as MQEnum;

define( 'CURRENT_PAGE', basename( __FILE__ ) );

require_once 'header.php';

session_start();
require_once "lib/adLDAP/lib/adLDAP/adLDAP.php";

if( is_null( $mfhelp->get_adLDAP() ) ) exit( "Couldn't instantiate authenticator." );


if( isset( $SESSION[ 'event' ] ) )
    if( $_SESSION[ 'event' ] != '' )
        $_SESSION[ 'event' ] = '';

if( isset( $_SESSION[ 'email' ] ) )
    if( $_SESSION[ 'email' ] != '' )
        $_SESSION[ 'email' ] = '';

if( (isset( $_POST['nid'] ) && $_POST['nid'] != '' ) && ( isset( $_POST['pw'] ) && $_POST['pw'] != '' ) ) {
    $email = $mfhelp->scrub( $_POST['nid'] );
    $pass  = $mfhelp->scrub( $_POST['pw'] );
    $authorized = FALSE;

    $result = $mfhelp->query( MQEnum::LOGIN_BASE, $email, $pass );

    if( !$result || ( $result instanceof mysqli_result && $result->num_rows < 1 ) ) {
        if( $mfhelp->get_adLDAP() != NULL ) {
            if( $mfhelp->get_adLDAP()->authenticate( $email, $pass ) ) {

                $result_email = $mfhelp->query( MQEnum::LOGIN_ADLDAP, $email );

                if( !$result_email || ($result_email instanceof mysqli_result && $result_email->num_rows < 1 ) ) {
                    echo "Invalid Login.";
                    echo "<meta http-equiv=\"REFRESH\" content=\"1;url=$address\">";
                }
                else {

                    $row_email = mysqli_fetch_assoc( $result_email );

                    //echo "Your login information has been verified.";
                    $_SESSION['email'] = $row_email['email'];
                    $_SESSION['username'] = $row_email['username'];
                    $_SESSION['nid'] = $mfhelp->scrub( $_POST['nid'] );

                    $result_nid = $mfhelp->query( MQEnum::ADMIN_CHECK, $_SESSION['nid'] );

                    if( !( $result_nid instanceof mysqli_result ) || $result_nid->num_rows == 0 ) {
                        echo "You are not authorized to interact with this application.";

                        unset( $_SESSION['username'], $_SESSION['email'], $_SESSION['nid'] );
                    }
                    else {
                        $row = mysqli_fetch_assoc( $result_nid );

                        $_SESSION['level'] = intval( $row['level'] );
                    }
                }
            }
            else {
                echo "Invalid Login";
            }
        }
        else {
            echo "Invalid Login";
        }
    }
    else {
        $row = mysqli_fetch_assoc( $result );
        echo "Your login information has been verified.";
        $_SESSION['email'] = $email;
        $_SESSION['username'] = $row['username'];
    }
}


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
} else {

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

require_once 'footer.php';
?>