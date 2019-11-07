<?php
/**
 * The page that handles allowing students to swipe into a given event. The user must be at least
 * a Supervisor (music_fc.admins.level == 3) to use this page.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

// Start the session.
session_start();

// Make sure the user is logged in. If they are, they're at least a Supervisor, so we don't actually
// have to check their level.
if( !isset( $_SESSION['username'] ) || empty( $_SESSION['username'] ) )
    header( "Location: index.php" );

// Requires
require_once 'init.php';
require_once 'includes/music-fc-query-ref.php';

// Aliasing because long classnames are long.
use MusicQueryRef AS MQEnum;

// The footer uses this to load the right JavaScript
define( 'CURRENT_PAGE', basename( __FILE__ ) );

// Get the header.
require_once 'header.php';

// Date formatting variables
$dt_fmt = "Y-m-d H:i:s";
$d_fmt = "m/d/y";
$t_fmt = "g:i a";

// Today's date, for comparison purposes later on.
$today = date_create();

// Get the list of events, so we can build the select box.
$events_result = $mfhelp->query( MQEnum::EVENT_LIST, -1, 0, -5, 'ASC' );

// Build the rest of the page:
?>
<div id="main" class="container mt-5">
    <div class="row">
        <div class="col-12 bg-faded py-3">
            <h3 class="heading-underline">Event Card Swipe</h3>
            <p>Please choose an event:</p>
            <form id="choose-swipe-event" class="mb-5">
                <input type="hidden" name="swipe-active" value="0">
                <select id="event-select" name="event" class="form-control mb-3 mx-auto">
                <?php
                // We only want to pre-select the first <option> that's close to the current date.
                $selected = FALSE;
                // If we get results...
                if( $events_result instanceof mysqli_result && $events_result->num_rows > 0 ) {
                    // ...loop through them and create the <option>s.
                    while( $event = mysqli_fetch_assoc( $events_result ) ) {
                        // Build the date.
                        $datetime = date_create_from_format( $dt_fmt, $event['startdate'] );

                        // Initialize
                        $selected_str = "";

                        // If we haven't selected an option yet, check to see if the date is
                        // close to (but still before) the event date/time. If so, that option
                        // is selected in the select box by default.
                        if( !$selected && $today <= $datetime ) {
                            $selected_str = " selected";

                            // Set $selected to TRUE, so we only do this once.
                            $selected = TRUE;
                        }

                        // Build the option entry:
                        ?>
                        <option value="<?= $event['id'] ?>" class="my-1"<?= $selected_str ?>>
                            <?= date_format( $datetime, $d_fmt ); ?> | <?= strtoupper( trim( $event['title'] ) ); ?> | <?= strpos( $event['startdate'], '00:00:00' ) !== FALSE ? 'TBA' : date_format( $datetime, $t_fmt ) ?>
                        </option>
                        <?php
                    }
                }
                ?>
                </select>
                <div id="buttons-div" class="d-flex flex-row justify-content-center mx-auto w-75">
                    <button type="button" id="start-btn" class="btn btn-primary mx-3">Begin Swiping</button>
                    <button type="button" id="done-btn" class="btn btn-primary mx-3" disabled>Done Swiping</button>
                </div>
            </form>
            <form id="swipe-card">
                <div class="form-group mx-auto">
                    <label for="swipe" class="form-label">Swipe Card</label>
                    <input type="password" name="swipe" id="swipe" class="form-control" disabled>
                    <small id="swipe-help" class="form-text text-muted">Click "<span id="swipe-state-1">Begin</span> Swiping" to <span id="swipe-state-2">enable</span> card input.</small>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
// Get the footer.
require_once 'footer.php';
?>