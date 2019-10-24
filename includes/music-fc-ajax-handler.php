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
            $response = "";

            switch( $data['action'] ) {

                case 'get-event':
                    $response = $this->_get_event( $data );
                    break;

                case 'delete-event':
                    $response = $this->_delete_event( $data );
                    break;

                default:
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
                $t_fmt = "g:ia";

                while( $row = mysqli_fetch_assoc( $result ) ) {

                    $datetime = date_create_from_format( $dt_fmt, $row['startdate'] );
                    $time = date_format( $datetime, $t_fmt );
                    ?>
                    <tr>
                        <td><?= date_format( $datetime, $d_fmt ); ?></td>
                        <td colspan="3"><?= strtoupper( $row['title'] ); ?></td>
                        <td><?= $time != "12:00am" ? $time : "TBA" ?></td>
                        <td colspan="2">
                            <div class="btn-group mx-auto">
                                <button type="button" class="btn btn-outline-primary btn-sm edit-btn mr-2">Edit</button>
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


        private function _delete_event( array $data ) : ?int {

            if( !isset( $data['target'] ) || empty( $data['target'] ) ) return -1;

            $target = intval( $data['target'] );

            $result = $this->mfhelp->query( MQEnum::EVENT_LOC_CHECK, $target, $data['title'] );

            if( $result && $result->num_rows > 0 ) {

                mysqli_free_result( $result );

                $result = $this->mfhelp->query( MQEnum::EVENT_DELETE_FC, $target );

                if( mysqli_error( $this->mfhelp->get_db() ) ) return 0;
                else return 1;

            }
            else if( $result && $result->num_rows == 0 ) {
                
                mysqli_free_result( $result );

                $result = $this->mfhelp->query( MQEnum::EVENT_DELETE, $target );

                if( mysqli_error( $this->mfhelp->get_db() ) ) return 0;

                else {
                    mysqli_free_result( $result );
                    return 1;
                }

            }
            else {
                return 0;
            }

        }
    }
}
?>