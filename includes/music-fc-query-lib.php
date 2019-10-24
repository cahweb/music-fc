<?php
/**
 * An SQL generation class meant to build and feed SQL queries to the MusicFCHelper class.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

require_once MUSIC_FC__BASE_DIR . '/lib/data-architecture/sql-generator.php';
require_once 'music-fc-query-ref.php';

use MusicQueryRef as MQEnum;

if( !class_exists( 'MusicFCQueryLib' ) ) {
    class MusicFCQueryLib implements SQLGenerator
    {
        private $_dept;

        private $_login_base_str, $_limit;

        public function __construct( ... $args ) {
            // In this case, the first argument will be the department number, if available. For Music, default is 13.
            $this->_dept = isset( $args[0] ) ? intval( $args[0] ) : 13;

            $this->_login_base_str = "SELECT email, nid, CONCAT_WS(' ', fname, lname) AS username FROM cah.users WHERE";
            $this->_limit = "LIMIT 1";
        }


        public function get_query_str( int $type, ... $args ) : string {

            switch( $type ) {

                case MQEnum::MQ__DEFAULT :
                    $sql = "";
                    break;

                case MQEnum::LOGIN_BASE :
                    $sql = $this->_login_base( ... $args );
                    break;

                case MQEnum::LOGIN_ADLDAP :
                    $sql = $this->_login_adLDAP( ... $args );
                    break;

                case MQEnum::EVENT_LIST :
                    $sql = $this->_event_list( ... $args );
                    break;

                case MQEnum::EVENT_COUNT :
                    $sql = $this->_event_count( ... $args );
                    break;

                case MQEnum::EVENT_DELETE:
                    $sql = $this->_event_delete( ... $args );
                    break;

                case MQEnum::EVENT_DELETE_FC:
                    $sql = $this->_event_delete_fc( ... $args );
                    break;

                case MQEnum::EVENT_LOC_CHECK:
                    $sql = $this->_event_loc_check( ... $args );

                default :
                    break;
            }

            return $sql;
        }


        private function _login_base( string $email, string $pass, ... $args ) : string {

            return "$this->_login_base_str (email = '$email' OR nid = '$email') AND passwd = AES_ENCRYPT('$pass', '" . PASSWORD_KEY . "' ) $this->_limit";
        }


        private function _login_adLDAP( string $email, ... $args ) : string {

            return "$this->_login_base_str nid = '$email' $this->_limit";
        }


        private function _event_list( int $per_page = 20, int $page = 0, int $cutoff = -5, ... $args ) : string {

            $past_cutoff = date( "Y-m-d", strtotime( "$cutoff months" ) );

            return "/*" . MYSQLND_QC_ENABLE_SWITCH . "*/ " . "SELECT c.id, c.startdate, c.title FROM cah.events AS c WHERE c.department_id = $this->_dept AND (c.enddate <= CURRENT_TIMESTAMP AND c.startdate >= '$past_cutoff') OR (c.startdate >= CURRENT_TIMESTAMP) AND c.approved = 1 UNION SELECT m.id,  m.time, m.name FROM music_fc.events AS m WHERE (m.time <= CURRENT_TIMESTAMP AND m.time >= '$past_cutoff') OR (m.time >= CURRENT_TIMESTAMP) ORDER BY startdate DESC, title ASC LIMIT $page, $per_page";
        }


        private function _event_count( int $cutoff = -5, ... $args ) : string {

            $past_cutoff = date( "Y-m-d", strtotime( "$cutoff months" ) );

            return "SELECT count(*) AS event_count FROM cah.events WHERE department_id = $this->_dept AND (enddate <= CURRENT_TIMESTAMP AND startdate >= '$past_cutoff') OR (startdate >= CURRENT_TIMESTAMP) AND approved = 1 UNION SELECT count(*) AS event_count FROM music_fc.events WHERE (time <= CURRENT_TIMESTAMP AND time >= '$past_cutoff') OR (time >= CURRENT_TIMESTAMP)";
        }


        private function _event_delete( int $target, ... $args ) : string {

            return "UPDATE cah.events SET approved = 0 WHERE id = $target";
        }


        private function _event_delete_fc( int $target, ... $args ) : string {
            
            return "DELETE FROM music_fc.events WHERE events.id = $target";
        }


        private function _event_loc_check( int $target, string $title, ... $args ) : string {

            return "SELECT * FROM music_fc.events WHERE events.id = $target AND events.name LIKE '$title' LIMIT 1";
        }
    }
}
?>