<?php

$title = "UCF Music | Card Scanner";
$address = "http://localhost:8080/musicfc";

require_once 'header.php';

session_start();

if( isset( $SESSION[ 'event' ] ) )
    if( $_SESSION[ 'event' ] != '' )
        $_SESSION[ 'event' ] = '';

if( isset( $_SESSION[ 'email' ] ) )
    if( $_SESSION[ 'email' ] != '' )
        $_SESSION[ 'email' ] = '';

//$_SESSION[ 'username' ] = "Test User";


if( !isset( $_SESSION[ 'username' ] ) || empty( $_SESSION[ 'username' ] ) ) {
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
                <form>
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
    ?>

    <div id="main" class="container mt-5">
        <div class="row justify-content-center">
            <div class="jumbotron option-box pt-4 pb-4">
                <h1 class="text-secondary text-center font-condensed mb-3">Music Forum Credit</h1>
                <h4 class="heading-underline text-secondary text-center font-slab-serif mb-4">Welcome, <?= $_SESSION[ 'username' ] ?>!</h4>
                <p class="lead text-secondary mb-1">Please select an option:</p>
                <div class="list-group">
                    <a href="swipe.php" class="list-group-item list-group-item-action">Start Event Swipe</a>
                    <a href="events.php" class="list-group-item list-group-item-action">Manage Events</a>
                    <a href="admin.php" class="list-group-item list-group-item-action">Admin Tools</a>
                </div>
            </div>
        </div>
    </div>
    <?php
}

if( isset( $_SESSION[ 'username' ] ) )
    unset( $_SESSION[ 'username' ] );

require_once 'footer.php';
?>