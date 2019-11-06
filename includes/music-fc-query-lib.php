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

                case MQEnum::EVENT_CREATE :
                    $sql = $this->_event_create( ... $args );
                    break;

                case MQEnum::EVENT_DELETE:
                    $sql = $this->_event_delete( ... $args );
                    break;

                case MQEnum::EVENT_DELETE_FC:
                    $sql = $this->_event_delete_fc( ... $args );
                    break;

                case MQEnum::EVENT_LOC_CHECK:
                    $sql = $this->_event_loc_check( ... $args );
                    break;

                case MQEnum::USER_ID_LOOKUP:
                    $sql = $this->_user_id_lookup( ... $args );
                    break;

                case MQEnum::EVENT_EDIT:
                    $sql = $this->_event_edit( ... $args );
                    break;

                case MQEnum::EVENT_FC_ID:
                    $sql = $this->_event_fc_id( ... $args );
                    break;

                case MQEnum::SWIPE_LIST:
                    $sql = $this->_swipe_list( ... $args );
                    break;

                case MQEnum::SWIPE_ADD:
                    $sql = $this->_swipe_add( ... $args );
                    break;

                case MQEnum::ADMIN_GET_ALL:
                    $sql = $this->_admin_get_all( ... $args );
                    break;

                case MQEnum::ADMIN_CHECK:
                    $sql = $this->_admin_check( ... $args );
                    break;

                case MQEnum::ADMIN_GET_LEVELS:
                    $sql = $this->_admin_get_levels( ... $args );
                    break;

                case MQEnum::ADMIN_CHG_PRIV:
                    $sql = $this->_admin_chg_priv( ... $args );
                    break;

                case MQEnum::ADMIN_ADD:
                    $sql = $this->_admin_add( ... $args );
                    break;

                case MQEnum::ADMIN_DELETE:
                    $sql = $this->_admin_delete( ... $args );
                    break;

                case MQEnum::CSV_NUM_COLS:
                    $sql = $this->_csv_num_cols( ... $args );
                    break;

                case MQEnum::CSV_LIST:
                    $sql = $this->_csv_list( ... $args );
                    break;

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


        private function _event_list( int $per_page = 20, int $page = 0, int $cutoff = -5, string $order = "DESC", ... $args ) : string {

            $past_cutoff = date( "Y-m-d", strtotime( "$cutoff months" ) );

            return "/*" . MYSQLND_QC_ENABLE_SWITCH . "*/ " . "SELECT c.id, c.startdate, c.title FROM cah.events AS c WHERE c.department_id = $this->_dept AND (c.enddate <= CURRENT_TIMESTAMP AND c.startdate >= '$past_cutoff') OR (c.startdate >= CURRENT_TIMESTAMP) AND c.approved = 1 UNION SELECT m.id,  m.time, m.name FROM music_fc.events AS m WHERE (m.time <= CURRENT_TIMESTAMP AND m.time >= '$past_cutoff') OR (m.time >= CURRENT_TIMESTAMP) ORDER BY startdate $order, title ASC " . ( $per_page > 0 ? "LIMIT $page, $per_page" : "" );
        }


        private function _event_count( int $cutoff = -5, ... $args ) : string {

            $past_cutoff = date( "Y-m-d", strtotime( "$cutoff months" ) );

            return "SELECT count(*) AS event_count FROM cah.events WHERE department_id = $this->_dept AND (enddate <= CURRENT_TIMESTAMP AND startdate >= '$past_cutoff') OR (startdate >= CURRENT_TIMESTAMP) AND approved = 1 UNION SELECT count(*) AS event_count FROM music_fc.events WHERE (time <= CURRENT_TIMESTAMP AND time >= '$past_cutoff') OR (time >= CURRENT_TIMESTAMP)";
        }


        private function _event_create( DateTime $datetime, string $title, int $user_id, ... $args ) : string {

            return "INSERT INTO music_fc.events ( name, time, addedby, addedon ) VALUES ( '$title', '" . date_format( $datetime, "Y-m-d H:i:s") . "', $user_id, NOW())";
        }


        private function _event_delete( int $target, ... $args ) : string {

            return "UPDATE cah.events SET approved = 0 WHERE id = $target";
        }


        private function _event_delete_fc( int $target, ... $args ) : string {
            
            return "DELETE FROM music_fc.events WHERE events.id = $target";
        }


        private function _event_loc_check( int $target, string $title, ... $args ) : string {

            //return "SELECT * FROM music_fc.events WHERE events.id = $target AND events.name LIKE '$title' LIMIT 1";
            $sql = "SELECT * FROM music_fc.events WHERE events.id = $target AND events.name LIKE '$title' LIMIT 1";
            return $sql;
        }


        private function _user_id_lookup( string $username, ... $args ) : string {

            return "SELECT users.id FROM cah.users WHERE users.nid LIKE '$username' LIMIT 1";
        }


        private function _event_edit( int $target, DateTime $datetime, string $title) : string {
            
            return "UPDATE music_fc.events SET events.name = '$title', events.time = '" . date_format( $datetime, "Y-m-d H:i:s") . "' WHERE events.id = $target";
        }


        private function _event_fc_id( string $title, ... $args ) : string {

            return "SELECT events.id FROM music_fc.events WHERE events.name LIKE '$title' AND addedon >= DATE_SUB(NOW(), INTERVAL 2 SECONDS) ORDER BY events.id DESC LIMIT 1";
        }


        private function _swipe_list( int $event_id = NULL, ... $args ) : string {

            return "SELECT swipe.event, swipe.fname, swipe.lname, swipe.card, swipe.time FROM music_fc.swipe LEFT JOIN music_fc.events ON swipe.event = events.id WHERE events.time >= '" . date( 'Y-m-d', strtotime( "-5 months" ) ) . "'" . ( !is_null( $event_id ) ? " AND swipe.event = $event_id" : "" ) . " ORDER BY events.time DESC, swipe.lname ASC, swipe.fname ASC";
        }


        private function _swipe_add( int $event_id, string $fname, string $lname, string $card_num, string $raw_input, string $email = "john.parker@ucf.edu", DateTime $time = NULL,  ... $args ) : string {

            return "INSERT INTO music_fc.swipe (event, swipe, fname, lname, card, time, email, ip) VALUES ( $event_id, '$raw_input', '$fname', '$lname', '$card_num', " . ( !is_null( $time ) ? "'" . date_format( $time, "Y-m-d H:i:s") . "'" : "NOW()" ) . ", '$email', '{$_SERVER['REMOTE_ADDR']}')";
        }


        private function _admin_get_all( ... $args ) : string {

            return "SELECT a.user_id, CONCAT_WS(' ', u.fname, u.lname) AS fullname, u.nid, p.level FROM music_fc.admins AS a LEFT JOIN cah.users AS u ON a.user_id = u.id LEFT JOIN music_fc.permissions AS p ON a.level = p.id ORDER BY p.id, u.lname, u.fname";
        }


        private function _admin_check( string $nid, ... $args ) : string {

            return "SELECT a.level FROM music_fc.admins AS a LEFT JOIN cah.users AS u ON a.user_id = u.id WHERE u.nid LIKE '$nid'";
        }


        private function _admin_get_levels( ... $args ) : string {
            
            return "SELECT * FROM music_fc.permissions ORDER BY permissions.id";
        }


        private function _admin_chg_priv( int $user_id, int $new_level, ... $args ) : string {

            return "UPDATE music_fc.admins SET admins.level = $new_level WHERE admins.user_id = $user_id";
        }


        private function _admin_add( string $nid, int $level, ... $args ) : string {

            return "INSERT INTO music_fc.admins ( admins.user_id, admins.level ) VALUES ( ( SELECT users.id FROM cah.users WHERE users.nid LIKE '$nid' ), $level )";
        }


        private function _admin_delete( int $user_id, ... $args ) : string {

            return "DELETE FROM music_fc.admins WHERE admins.user_id = $user_id";
        }


        private function _csv_num_cols( ... $args ) : string {

            return "SELECT MAX( sq.event_count ) AS num_columns FROM (SELECT COUNT(ssq.event) AS event_count FROM (SELECT DISTINCT swipe.event, c.nid FROM music_fc.swipe LEFT JOIN cards_10182019 AS c ON c.card = swipe.card WHERE swipe.time >= DATE_SUB( CURRENT_DATE, INTERVAL 5 MONTH ) AND c.nid IS NOT NULL AND c.nid NOT LIKE 'jparker' ) AS ssq GROUP BY ssq.nid ORDER BY event_count DESC) AS sq";
        }


        private function _csv_list( int $cutoff = -5, ... $args ) : string {

            $past_cutoff = date( "Y-m-d", strtotime( "$cutoff months" ) );

            return "SELECT DISTINCT CONCAT_WS(' ', csv.fname, csv.lname) AS student, csv.nid, csv.pid, csv.title FROM (SELECT swipe.event, swipe.lname, swipe.fname, c.nid, c.pid, event_list.title, swipe.time FROM music_fc.swipe LEFT JOIN cards_10182019 AS c ON c.card = swipe.card LEFT JOIN (SELECT c.id, c.title FROM cah.events AS c WHERE c.department_id = 13 AND (c.enddate <= CURRENT_TIMESTAMP AND c.startdate >= '$past_cutoff') OR (c.startdate >= CURRENT_TIMESTAMP) AND c.approved = 1 UNION SELECT m.id, m.name FROM music_fc.events AS m WHERE (m.time <= CURRENT_TIMESTAMP AND m.time >= '$past_cutoff') OR (m.time >= CURRENT_TIMESTAMP)) AS event_list ON event_list.id = swipe.event WHERE swipe.time >= DATE_SUB( CURRENT_DATE, INTERVAL 5 MONTH ) AND c.nid IS NOT NULL AND c.nid NOT LIKE 'jparker') AS csv ORDER BY csv.lname, csv.fname, csv.time ASC";
        }
    }
}
?>