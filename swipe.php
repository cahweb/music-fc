<?php
session_start();

if( !isset( $_SESSION['username'] ) || empty( $_SESSION['username'] ) )
    header( "Location: index.php" );

require_once 'init.php';
require_once 'includes/music-fc-query-ref.php';

use MusicQueryRef AS MQEnum;

define( 'CURRENT_PAGE', basename( __FILE__ ) );

require_once 'header.php';

$dt_fmt = "Y-m-d H:i:s";
$d_fmt = "m/d/y";
$t_fmt = "g:i a";

$today = date_create();

$events_result = $mfhelp->query( MQEnum::EVENT_LIST, -1, 0, -5, 'ASC' );
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
                $selected = FALSE;
                if( $events_result instanceof mysqli_result && $events_result->num_rows > 0 ) {
                    while( $event = mysqli_fetch_assoc( $events_result ) ) {
                        $datetime = date_create_from_format( $dt_fmt, $event['startdate'] );

                        $selected_str = "";

                        if( !$selected && $today <= $datetime ) {
                            $selected_str = " selected";
                            $selected = TRUE;
                        }
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
require_once 'footer.php';
?>