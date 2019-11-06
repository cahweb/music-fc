<?php
/**
 * An Athena-powered modal for manually providing a student entry to
 * the swipe table.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

require_once MUSIC_FC__BASE_DIR . "/admin.php";

use MusicQueryRef as MQEnum;
?>

<div class="modal fade" id="student-entry-modal" tabindex="-1" role="dialog" aria-labelledby="student-entry-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="student-entry-modal-label">Add Student Entry</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <form id="student-entry">
                        <div class="form-group">
                            <label for="event">Choose an event:</label>
                            <select id="event" name="eventID" class="form-control">
                                <?php
                                $result_event = $mfhelp->query( MQEnum::EVENT_LIST, -1, 0, -5, 'ASC' );

                                if( $result_event instanceof mysqli_result && $result_event->num_rows > 0 ) {
                                    
                                    $dt_fmt = "Y-m-d H:i:s";
                                    $d_fmt = "m/d/y";
                                    $t_fmt = "g:i a";
                                    $today = date_create();
                                    $selected = FALSE;

                                    while( $event = mysqli_fetch_assoc( $result_event ) ) {
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
                                else {
                                    ?>
                                    <option value="0" class="my-1" selected>-- No Events Found --</option>
                                    <?php
                                }
                                ?>
                            </select>
                        </div>
                        <fieldset>
                            <legend>Student Information</legend>
                            <div class="form-group row">
                                <label for="fname" class="col-4 col-md-2 col-form-label">First Name:</label>
                                <div class="col-8 col-md-4">
                                    <input type="text" id="fname" name="fname" class="form-control" placeholder="John Q">
                                </div>
                                <label for="lname" class="col-4 col-md-2 col-form-label">Last Name:</label>
                                <div class="col-8 col-md-4">
                                    <input type="text" id="lname" name="lname" class="form-control" placeholder="Average">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="date" class="col-4 col-md-2 col-form-label">Date:</label>
                                <div class="col-8 col-md-4">
                                    <input type="date" id="date" name="date" class="form-control">
                                </div>
                                <label for="time" class="col-4 col-md-2 col-form-label">Time:</label>
                                <div class="col-8 col-md-4">
                                    <input type="time" id="time" name="time" class="form-control">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="card" class="col-4 col-md-2 col-form-label">Card Number:</label>
                                <div class="col-8 col-md-4">
                                    <input type="number" id="card" name="cardNum" class="form-control" maxlength="16">
                                </div>
                            </div>
                        </fieldset>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal" aria-label="Cancel">Cancel</button>
                <button type="submit" class="btn btn-primary" form="student-entry" id="student-entry-submit-btn" aria-label="Add">Add</button>
            </div>
        </div>
    </div>
</div>