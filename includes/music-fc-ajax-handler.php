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

        protected $mfhelp;

        public function __construct( MFHelp $mfhelp) {
            $this->mfhelp = $mfhelp;
        }


        public function process( array $data, bool $return = TRUE ) : ?string
        {

            $data = $this->_wash( $data );

            $response = "";

            switch( $data['action'] ) {

                case 'get-event':
                    $response = $this->_get_event( $data );
                    break;

                case 'create-event':
                    $response = $this->_create_event( $data );
                    break;

                case 'edit-event':
                    $response = $this->_edit_event( $data );
                    break;

                case 'delete-event':
                    $response = $this->_delete_event( $data );
                    break;

                case 'add-swipe':
                    $response = $this->_add_swipe( $data );
                    break;

                case 'swipe-list':
                    $response = $this->_swipe_list( $data );
                    break;

                case 'admin-get-all':
                    $response = $this->_admin_get_all();
                    break;

                case 'admin-chg-priv':
                    $response = $this->_admin_chg_priv( $data );
                    break;

                case 'admin-add':
                    $response = $this->_admin_add( $data );
                    break;

                case 'admin-delete':
                    $response = $this->_admin_delete( $data );
                    break;

                default:
                    $response = 0;
                    break;
            }

            if( $return ) {
                return $response;
            }
            else {
                echo $response;
                return NULL;
            }
        }


        private function _wash( array $data ) : array {

            $clean_data = array();

            foreach( $data as $key => $value ) {
                $clean_data[$key] = $this->mfhelp->scrub( $value );
            }

            return $clean_data;
        }


        private function _get_event( array $data ) : ?string {
            
            if( ( !isset( $data['limit'] ) || empty( $data['limit'] ) ) || !isset( $data['offset'] ) ) return NULL;

            $args = array(
                intval( $data['limit'] ),
                intval( $data['offset'] )
            );

            $result = $this->mfhelp->query( MQEnum::EVENT_LIST, ... $args );

            if( $result ) {

                ob_start();

                $dt_fmt = "Y-m-d H:i:s";
                $d_fmt = "m/d/y";
                $t_fmt = "g:i a";

                while( $row = mysqli_fetch_assoc( $result ) ) {

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
                mysqli_free_result( $result );

                return ob_get_clean();
            }
            else {
                return "There was a problem with the query.\n\t" . mysqli_errno( $this->mfhelp->get_db() ) . ": " . mysqli_error( $this->mfhelp->get_db() );
            }
        }


        private function _create_event( array $data ) : int {

            session_start();

            if( !isset( $data['event-title'] ) ) return -1;

            $d_fmt = "Y-m-d H:i";
            $datetime = date_create_from_format( $d_fmt, "{$data['event-date']} {$data['event-time']}");

            $user_result = $this->mfhelp->query( MQEnum::USER_ID_LOOKUP, $_SESSION['nid'] );

            if( $user_result instanceof mysqli_result && $user_result->num_rows > 0 ) {
                $row = mysqli_fetch_assoc( $user_result );

                $user_id = intval( $row['id'] );

                $result = $this->mfhelp->query( MQEnum::EVENT_CREATE, $datetime, $data['event-title'], $user_id );

                if( $result ) return 1;
                else return 0;
            }
            else return 0;
        }


        private function _edit_event( array $data ) : int {
            
            session_start();

            if( !isset( $data['old-title'] ) ) return -1;

            // Check if the entry 
            $result = $this->mfhelp->query( MQEnum::EVENT_LOC_CHECK, intval( $data['event-id'] ), $data['old-title'] );

            if( $result instanceof mysqli_result && $result->num_rows == 0 ) {

                $result_create = $this->_create_event( $data );

                if( !$result_create ) return 0;

                $delete_data = array(
                    'target' => $data['event-id'],
                    'title' => $data['old-title']
                );

                $result_delete = $this->_delete_event( $delete_data );
                
                if( !$result_delete ) {
                    $result = $this->mfhelp->query( MQEnum::EVENT_FC_ID, $data['event-title'] );

                    if( $result && intval( $result->fetch_array()[0] ) > 0 ) {
                        $row = mysqli_fetch_assoc( $result );

                        mysqli_free_result( $result );

                        $delete_data['target'] = $row['id'];
                        $delete_data['title'] = $data['event-title'];

                        $result = $this->_delete_event( $delete_data );
                    }
                    return 0;
                }

                return 1;
            }
            else if ( $result instanceof mysqli_result && $result->num_rows > 0 ) {

                $d_fmt = "Y-m-d H:i";
                $datetime = date_create_from_format( $d_fmt, "{$data['event-date']} {$data['event-time']}");

                $result = $this->mfhelp->query( MQEnum::EVENT_EDIT, intval( $data['event-id'] ), $datetime, $data['event-title'] );

                if( $result ) return 1;
            }

            return 0;
        }


        private function _delete_event( array $data ) : int {

            if( !isset( $data['target'] ) || empty( $data['target'] ) ) return -1;

            $target = intval( $data['target'] );

            $result = $this->mfhelp->query( MQEnum::EVENT_LOC_CHECK, $target, $data['title'] );

            if( $result && intval( $result->fetch_array()[0] ) > 0 ) {

                //mysqli_free_result( $result );

                $result = $this->mfhelp->query( MQEnum::EVENT_DELETE_FC, $target );

                if( $result ) return 1;
                else return 0;

            }
            else if( $result instanceof mysqli_result && $result->num_rows == 0 ) {
                
                mysqli_free_result( $result );

                $result = $this->mfhelp->query( MQEnum::EVENT_DELETE, $target );

                if( $result ) return 1;
                else {
                    if( mysqli_error( $this->mfhelp->get_db() ) ) {
                        $msg = mysqli_errno( $this->mfhelp->get_db() ) . ": " . mysqli_error( $this->mfhelp->get_db() ) . "\n";
                        error_log( $msg );
                    }
                    return 0;
                }
            }
            else {
                return 0;
            }

        }


        private function _add_swipe( array $data ) : int {
            
            if( ( isset( $data['date'] ) && !empty( $data['date'] ) ) && ( isset( $data['time'] ) && !empty( $data['time'] ) ) ) {
                $dt_fmt = "Y-m-d H:i:s";
                $d_fmt = "m/d/y";
                $t_fmt = "g:i a";

                $datetime = date_create_from_format( $dt_fmt, $data['date'] . " " . $data['time'] . ":00" );

                $data['datetime'] = $datetime;
            }

            $sqlData = array(
                intval( $data['eventID'] ),
                $data['fname'],
                $data['lname'],
                $data['cardNum'],
                $data['rawInput'],
                "john.parker@ucf.edu",
                isset( $data['datetime'] ) ? $data['datetime'] : NULL
            );

            $result = $this->mfhelp->query( MQEnum::SWIPE_ADD, ... $sqlData );

            if( $result ) return 1;
            else {
                error_log("Problem with table update\n\tData: " . print_r( $data, TRUE ) );
                return 0;
            }
        }


        private function _swipe_list( array $data ) : string {

            if( isset( $data['event-select'] ) ) {
                $result = $this->mfhelp->query( MQEnum::SWIPE_LIST, intval( $data['event-select'] ) );
            }
            else {
                $result = $this->mfhelp->query( MQEnum::SWIPE_LIST );
            }

            if( $result instanceof mysqli_result && $result->num_rows > 0 ) {

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
                return ob_get_clean();
            }
            else {
                return "<p>No swipes found.</p>";
            }
        }


        private function _admin_get_all() : string {
            $result = $this->mfhelp->query( MQEnum::ADMIN_GET_ALL );

            if( $result instanceof mysqli_result && $result->num_rows > 0 ) {

                $response = '';

                while( $row = mysqli_fetch_assoc( $result ) ) {

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

                mysqli_free_result( $result );

                return $response;
            }
            else {
                return "<p>There was a problem getting the Admin list.</p>";
            }
        }


        private function _admin_chg_priv( array $data ) : int {

            $result = $this->mfhelp->query( MQEnum::ADMIN_CHG_PRIV, intval( $data['edit-target'] ), intval( $data['new-level'] ) );

            if( $result ) return 1;
            else return 0;
        }


        private function _admin_add( array $data ) : int {

            if( !isset( $data['nid'] ) || empty( $data['nid'] ) ) return 0;

            $result = $this->mfhelp->query( MQEnum::ADMIN_ADD, $data['nid'], intval( $data['level'] ) );

            if( $result ) return 1;
            else return 0;
        }


        private function _admin_delete( array $data ) : int {

            if( !isset( $data['id'] ) || empty( $data['id'] ) ) return 0;

            $result = $this->mfhelp->query( MQEnum::ADMIN_DELETE, intval( $data['id'] ) );

            if( $result ) return 1;
            else return 0;
        }
    }
}
?>