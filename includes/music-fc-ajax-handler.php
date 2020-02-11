<?php
/**
 * AJAX handler class for the Music Forum Credit web app.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */
require_once MUSIC_FC__BASE_DIR . '/lib/data-architecture/ajax-handler.php';
require_once 'music-fc-helper.php';
require_once 'music-fc-query-ref.php';

use MusicFCHelper as MFHelp;
use MusicQueryRef as MQEnum;

if( !class_exists( 'MusicFCAjaxHandler') ) {
    class MusicFCAjaxHandler implements AJAXHandler
    {

        // For an instance of the MusicFCHelper class.
        protected $mfhelp;

        // Constructor stores the helper (which is created in the individual page script).
        public function __construct( MFHelp $mfhelp) {
            $this->mfhelp = $mfhelp;
        }


        /**
         * The meat of the AJAX handler. Basically a big switch that routes the data to
         * the correct function, then returns the results.
         *
         * @param array $data  The data passed from the front-end JavaScript.
         * @param boolean $return  Whether to return or echo the response. Default TRUE (return).
         * 
         * @return string|null $response  The processed reponse, or nothing (if echoing).
         */
        public function process( array $data, bool $return = TRUE ) : ?string
        {

            // Sanitizes the data, much of which may have come from user input.
            $data = $this->_wash( $data );

            // Initialize the response variable.
            $response = "";

            // Looks at the 'action' key and runs the switch...
            switch( $data['action'] ) {

                // Retrieves the list of events.
                case 'get-event':
                    $response = $this->_get_event( $data );
                    break;

                // Creates a new event.
                case 'create-event':
                    $response = $this->_create_event( $data );
                    break;

                // Edits an existing event.
                case 'edit-event':
                    $response = $this->_edit_event( $data );
                    break;

                // Deletes an event.
                case 'delete-event':
                    $response = $this->_delete_event( $data );
                    break;

                // Adds a swipe entry to the music_fc.swipe table.
                case 'add-swipe':
                    $response = $this->_add_swipe( $data );
                    break;

                // Retrieves a list of swipes.
                case 'swipe-list':
                    $response = $this->_swipe_list( $data );
                    break;

                // Gets the list of admins.
                case 'admin-get-all':
                    $response = $this->_admin_get_all();
                    break;

                // Changes and admin's privilege level.
                case 'admin-chg-priv':
                    $response = $this->_admin_chg_priv( $data );
                    break;

                // Adds a new admin.
                case 'admin-add':
                    $response = $this->_admin_add( $data );
                    break;

                // Deletes and admin.
                case 'admin-delete':
                    $response = $this->_admin_delete( $data );
                    break;

                // Default response.
                default:
                    $response = 0;
                    break;
            }

            // Return the response, or echo it if $return is FALSE.
            if( $return ) {
                return $response;
            }
            else {
                echo $response;
                return NULL;
            }
        }


        /**
         * Runs through an array of AJAX data and scrubs it clean.
         *
         * @param  array  $data       The data to scrub.
         * 
         * @return array $clean_data  The newly-scrubbed data.
         */
        private function _wash( array $data ) : array {

            $clean_data = array();

            // Just runs each element through the MusicFCHelper::scrub() method.
            foreach( $data as $key => $value ) {
                $clean_data[$key] = $this->mfhelp->scrub( $value );
            }

            return $clean_data;
        }


        /**
         * Gets a list of events, for displaying on a page. Can handle retrieving the entire
         * list or a paginated section of it.
         *
         * @param array $data  The AJAX data that we'll use to set the query parameters.
         * 
         * @return string|null  The string of built HTML to send back.
         */
        private function _get_event( array $data ) : ?string {
            
            // These keys should be set in order for this method to run. If they're not, we kick it back.
            if( ( !isset( $data['limit'] ) || empty( $data['limit'] ) ) || !isset( $data['offset'] ) ) return NULL;

            // These are the arguments we'll need, in the order we'll need to pass them to the function.
            $args = array(
                intval( $data['limit'] ),
                intval( $data['offset'] )
            );

            // Query the database.
            $result = $this->mfhelp->query( MQEnum::EVENT_LIST, ... $args );

            // If we get a valid response, build the thing.
            if( $result instanceof mysqli_result && $result->num_rows > 0 ) {

                // Start the output buffer.
                ob_start();

                // Setting some date format variables.
                $dt_fmt = "Y-m-d H:i:s";
                $d_fmt = "m/d/y";
                $t_fmt = "g:i a";

                // Loop through the results to create the entries.
                while( $row = mysqli_fetch_assoc( $result ) ) {

                    // Creates the date so we can format it however we need to.
                    $datetime = date_create_from_format( $dt_fmt, $row['startdate'] );
                    $time = date_format( $datetime, $t_fmt );
                    ?>
                    <tr>
                        <td><?= date_format( $datetime, $d_fmt ); ?></td>
                        <td colspan="3"><?= strtoupper( $row['title'] ); ?></td>
                        <td><?= $time != "12:00 am" ? $time : "TBA" ?></td>
                        <td colspan="2">
                            <div class="btn-group mx-auto">
                                <button type="button" class="btn btn-outline-primary btn-sm edit-btn mr-2" data-toggle="modal" data-target="#new-event-modal">Edit</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm delete-btn">Delete</button>
                            </div>
                            <input type="hidden" class="event-meta" data-id="<?= $row['id'] ?>">
                        </td>
                    </tr>
                    <?php
                }
                // Free the DB memory.
                mysqli_free_result( $result );

                // Return the buffered HTML.
                return ob_get_clean();
            }
            else {
                return "There was a problem with the query.\n\t" . mysqli_errno( $this->mfhelp->get_db() ) . ": " . mysqli_error( $this->mfhelp->get_db() );
            }
        }


        /**
         * Creates a new event to display on events.php and to choose from when managing swipes in
         * the other pages.
         *
         * @param array $data  The AJAX data that we'll use to set the query parameters.
         * 
         * @return integer A numeric representation of the (usually binary) success of the operation.
         */
        private function _create_event( array $data ) : int {

            // We'll need access to some $_SESSION variables here.
            session_start();

            // This one is required. There's no point doing this without a title.
            if( !isset( $data['event-title'] ) ) return -1;

            // Creating the date from the fields sent by JavaScript.
            $d_fmt = "Y-m-d H:i";
            $datetime = date_create_from_format( $d_fmt, "{$data['event-date']} {$data['event-time']}");

            // Look up the User ID, so we can know who created a given event.
            $user_result = $this->mfhelp->query( MQEnum::USER_ID_LOOKUP, $_SESSION['nid'] );

            // If we find something, we can continue.
            if( $user_result instanceof mysqli_result && $user_result->num_rows > 0 ) {
                
                // Store the User ID.
                $row = mysqli_fetch_assoc( $user_result );
                mysqli_free_result( $user_result );

                $user_id = intval( $row['id'] );

                // Run the query to create the event.
                $result = $this->mfhelp->query( MQEnum::EVENT_CREATE, $datetime, $data['event-title'], $user_id );

                // Return 1 if TRUE (success), 0 if FALSE (error).
                if( $result ) return 1;
                else return 0;
            }
            else return 0;
        }


        /**
         * Edits an existing event. There's some weird stuff that goes on if the user tries to
         * edit an event that's on the cah.events table, because I didn't want to give them the
         * power to change anything on the master table too drastically, but hopefully it doesn't
         * get too complicated.
         *
         * @param array $data  The AJAX data that we'll use to set the query parameters.
         * 
         * @return integer The success or fail state.
         */
        private function _edit_event( array $data ) : int {
            
            // We'll need some of the $_SESSION variables.
            session_start();

            // Checks to make sure we've got usable data. This field should always be set
            // for this method.
            if( !isset( $data['old-title'] ) ) return -1;

            // Check if the entry is in the music_fc.events table (also compares the existing title),
            // to allow for the [relatively remote] posibility of duplicate event IDs.
            $result = $this->mfhelp->query( MQEnum::EVENT_LOC_CHECK, intval( $data['event-id'] ), $data['old-title'] );

            // If we get an empty set, the event is in the cah.events table.
            if( $result instanceof mysqli_result && $result->num_rows == 0 ) {

                // But, instead of modifying the cah.events table directly, we're going to
                // "shadow" the event on the music_fc.events table. So we create a
                // new event...
                $result_create = $this->_create_event( $data );

                // We want to error out and not change anything irrevocably if something
                // doesn't work right.
                if( !$result_create ) return 0;

                // Creates the array of data that we'll need to pass to $this->_delete_event().
                $delete_data = array(
                    'target' => $data['event-id'],
                    'title' => $data['old-title']
                );

                // "Delete" the old cah.events entry, so it doesn't show up on the events list.
                $result_delete = $this->_delete_event( $delete_data );
                
                // If this fails, we need to put everything back the way it was, by deleting
                // the event we just created.
                if( !$result_delete ) {

                    // Get the newly-created Event ID.
                    $result = $this->mfhelp->query( MQEnum::EVENT_FC_ID, $data['event-title'] );

                    // If we find it...
                    if( $result && intval( $result->fetch_array()[0] ) > 0 ) {

                        $row = mysqli_fetch_assoc( $result );
                        mysqli_free_result( $result );

                        // Modify our deletion data array.
                        $delete_data['target'] = $row['id'];
                        $delete_data['title'] = $data['event-title'];

                        // Delete the newly-created event.
                        $result = $this->_delete_event( $delete_data );
                    }
                    // Report failure.
                    return 0;
                }

                // Report success.
                return 1;
            }
            // If we get a hit, the event is in the music_fc.events table, and this
            // gets much simpler.
            else if ( $result instanceof mysqli_result && $result->num_rows > 0 ) {

                // Do some date formatting.
                $d_fmt = "Y-m-d H:i";
                $datetime = date_create_from_format( $d_fmt, "{$data['event-date']} {$data['event-time']}");

                // Run the query to edit the event directly.
                $result = $this->mfhelp->query( MQEnum::EVENT_EDIT, intval( $data['event-id'] ), $datetime, $data['event-title'] );

                // If the query returns TRUE, report success.
                if( $result ) return 1;
            }

            // If we've gotten this far, we've failed.
            return 0;
        }


        /**
         * Deletes an event from the list. If the event is in cah.events, make it so that it
         * doesn't appear in the app, but otherwise leave it unchanged.
         *
         * @param array $data  The AJAX data that we'll use to set the query parameters.
         * 
         * @return integer  Report success or failure.
         */
        private function _delete_event( array $data ) : int {

            // We'll always need this one to be set.
            if( !isset( $data['target'] ) || empty( $data['target'] ) ) return -1;

            // Sets the Event ID that we're trying to delete.
            $target = intval( $data['target'] );

            // Checks to see if the event is in the music_fc.events table or not, just like
            // in $this->_edit_event().
            $result = $this->mfhelp->query( MQEnum::EVENT_LOC_CHECK, $target, $data['title'] );

            // If we find something, we just delete the entry.
            if( $result && intval( $result->fetch_array()[0] ) > 0 ) {

                mysqli_free_result( $result );

                $result = $this->mfhelp->query( MQEnum::EVENT_DELETE_FC, $target );

                if( $result ) return 1;
                else return 0;

            }
            // If we don't, then the event is on cah.events, so we'll have to "soft delete" it.
            else if( $result instanceof mysqli_result && $result->num_rows == 0 ) {
                
                mysqli_free_result( $result );

                // Runs the query, which sets the `approved` field in cah.events for that event
                // to 0. This field is only rarely used, so it shouldn't have an adverse effect
                // on anything else.
                $result = $this->mfhelp->query( MQEnum::EVENT_DELETE, $target );

                if( $result ) return 1;
                // Report the error, if we have one.
                else {
                    if( mysqli_error( $this->mfhelp->get_db() ) ) {
                        $msg = mysqli_errno( $this->mfhelp->get_db() ) . ": " . mysqli_error( $this->mfhelp->get_db() ) . "\n";
                        error_log( $msg );
                    }
                    return 0;
                }
            }
            // In any other case, report failure.
            else {
                return 0;
            }

        }


        /**
         * Adds a new entry to the swipe table. Called both from swipe.php and from admin.php.
         *
         * @param array $data  The AJAX data that we'll use to set the query parameters.
         * 
         * @return integer  Report success or failure.
         */
        private function _add_swipe( array $data ) : int {
            
            $sqlData = array();

            if( isset( $data['studentNID'] ) ) {
                $result = $this->mfhelp->query( MQEnum::CARD_NAME_LOOKUP, $data['studentNID'] );

                if( $result instanceof mysqli_result && $result->num_rows > 0 && $row = mysqli_fetch_assoc( $result ) ) {
                    $sqlData = array(
                        intval( $data['eventID'] ),
                        $row['fname'],
                        $row['lname'],
                        ( isset( $row['card'] ) ? $row['card'] : '' ),
                        $data['rawInput'],
                    );

                    mysqli_free_result( $result );
                }
            }
            else {
                // Arrange the data we'll pass to the query method.
                $sqlData = array(
                    intval( $data['eventID'] ),
                    $data['fname'],
                    $data['lname'],
                    $data['cardNum'],
                    $data['rawInput'],
                    "john.parker@ucf.edu",
                    isset( $data['datetime'] ) ? $data['datetime'] : NULL
                );
            }

            // Run it and report success/failure.
            $result = $this->mfhelp->query( MQEnum::SWIPE_ADD, ... $sqlData );

            if( $result ) return 1;
            else {
                error_log("Problem with table update\n\tData: " . print_r( $data, TRUE ) );
                return 0;
            }
        }


        /**
         * Gets a list of swipes. Can return a complete list, or only the swipes for a
         * specific event.
         *
         * @param array $data  The AJAX data that we'll use to set the query parameters.
         * 
         * @return string  The string of HTML that represents the returned swipes.
         */
        private function _swipe_list( array $data ) : string {

            // Checks to see if we're selecting a specific event or not, then runs the query.
            if( isset( $data['event-select'] ) ) {
                $result = $this->mfhelp->query( MQEnum::SWIPE_LIST, intval( $data['event-select'] ) );
            }
            else {
                $result = $this->mfhelp->query( MQEnum::SWIPE_LIST );
            }

            // If we get something, we'll do stuff.
            if( $result instanceof mysqli_result && $result->num_rows > 0 ) {

                // Start output buffer and build the responses.
                ob_start();
                ?>
                <table class="table table-striped table-hover table-responsive">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Student Name</th>
                            <th>Card Number</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while( $row = mysqli_fetch_assoc( $result ) ) {
                            ?>
                            <tr>
                                <td><?= $row['event'] ?></td>
                                <td><?= $row['fname'] . " " . $row['lname'] ?></td>
                                <td><?= $row['card'] ?></td>
                                <td><?= $row['time'] ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>

                <?php
                // Return the buffered HTML
                return ob_get_clean();
            }
            // Or report an empty set/error.
            else {
                return "<p>No swipes found.</p>";
            }
        }


        /**
         * Get the complete list of users who have admin privileges on the site. Called from admin.php.
         *
         * @return string  The HTML representing the list of admins.
         */
        private function _admin_get_all() : string {

            // Run the query.
            $result = $this->mfhelp->query( MQEnum::ADMIN_GET_ALL );

            // If we have something, we build the thing.
            if( $result instanceof mysqli_result && $result->num_rows > 0 ) {

                // Initialize the response variable.
                $response = '';

                // And iterate through the results.
                while( $row = mysqli_fetch_assoc( $result ) ) {

                    // Start the output buffer
                    ob_start();
                    ?>
                    <tr>
                        <td><?= $row['fullname'] ?></td>
                        <td><?= $row['nid'] ?></td>
                        <td><?= $row['level'] ?></td>
                        <td>
                            <div class="admin-action-btns">
                                <button type="button" class="btn btn-sm btn-outline-primary edit-bn" data-toggle="modal" data-target="#edit-admin-modal">Edit</button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn">Delete</button>
                            </div>
                            <input type="hidden" value="<?= $row['user_id'] ?>">
                        </td>
                    </tr>
                    <?php
                    $response .= ob_get_clean();
                }

                // Free the DB memory
                mysqli_free_result( $result );

                // Return buffered HTML
                return $response;
            }
            // Otherwise, we report failure.
            else {
                return "<p>There was a problem getting the Admin list.</p>";
            }
        }


        /**
         * Change an administrator's privilege level.
         *
         * @param array $data  The AJAX data that we'll use to set the query parameters.
         * 
         * @return integer Report success or failure.
         */
        private function _admin_chg_priv( array $data ) : int {

            // Run the query, with the data we need.
            $result = $this->mfhelp->query( MQEnum::ADMIN_CHG_PRIV, intval( $data['edit-target'] ), intval( $data['new-level'] ) );

            // Report success or failure.
            if( $result ) return 1;
            else return 0;
        }


        /**
         * Add a new user to the list of authorized admins, by NID.
         *
         * @param array $data  The AJAX data that we'll use to set the query parameters.
         * 
         * @return integer Report success or failure.
         */
        private function _admin_add( array $data ) : int {

            // This always needs to be set.
            if( !isset( $data['nid'] ) || empty( $data['nid'] ) ) return 0;

            // Run the query.
            $result = $this->mfhelp->query( MQEnum::ADMIN_ADD, $data['nid'], intval( $data['level'] ) );

            // Report success or failure.
            if( $result ) return 1;
            else return 0;
        }


        /**
         * Delete an admin from the list.
         *
         * @param array $data  The AJAX data that we'll use to set the query parameters.
         * 
         * @return integer Report success or failure.
         */
        private function _admin_delete( array $data ) : int {

            // This always needs to be set.
            if( !isset( $data['id'] ) || empty( $data['id'] ) ) return 0;

            // Run the query.
            $result = $this->mfhelp->query( MQEnum::ADMIN_DELETE, intval( $data['id'] ) );

            // Report success or failure.
            if( $result ) return 1;
            else return 0;
        }
    }
}
?>