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
        // Private member variables.
        private $_dept;

        private $_login_base_str, $_limit;

        /**
         * The constructor. Sets the base values for things we'll need later on.
         * 
         * @param array $args  A potential array of arguments. The only one we care about right now
         *                      is the first, but since this is from the SQLGenerator interface, I
         *                      wanted to leave it more flexible.
         * 
         * @return void
         */
        public function __construct( ... $args ) {
            // In this case, the first argument will be the department number, if available. For Music, default is 13.
            $this->_dept = isset( $args[0] ) ? intval( $args[0] ) : 13;

            $this->_login_base_str = "SELECT email, nid, CONCAT_WS(' ', fname, lname) AS username FROM cah.users WHERE";
            $this->_limit = "LIMIT 1";
        }


        /**
         * The beating heart of this class. Basically a big switch that returns the right SQL
         * for a given query type.
         * 
         * @param int $type  The Type of query, corresponding to a value in the MusicQueryRef class.
         * @param array $args  Any additional arguments, to be passed on to the respective function.
         * 
         * @return string $sql  The SQL query that the MusicFCHelper will execute.
         */
        public function get_query_str( int $type, ... $args ) : string {

            // Here we go...
            switch( $type ) {

                // The default. Not used in this application.
                case MQEnum::MQ__DEFAULT :
                    $sql = "";
                    break;

                // The initial login query.
                case MQEnum::LOGIN_BASE :
                    $sql = $this->_login_base( ... $args );
                    break;

                // The supplemental adLDAP query.
                case MQEnum::LOGIN_ADLDAP :
                    $sql = $this->_login_adLDAP( ... $args );
                    break;

                // Queries the full list of events.
                case MQEnum::EVENT_LIST :
                    $sql = $this->_event_list( ... $args );
                    break;

                // The query for the total number of events.
                case MQEnum::EVENT_COUNT :
                    $sql = $this->_event_count( ... $args );
                    break;

                // The query to create a new event.
                case MQEnum::EVENT_CREATE :
                    $sql = $this->_event_create( ... $args );
                    break;

                // The query to "delete" an event from cah.events.
                case MQEnum::EVENT_DELETE:
                    $sql = $this->_event_delete( ... $args );
                    break;

                // The query to truly delete an event from music_fc.events.
                case MQEnum::EVENT_DELETE_FC:
                    $sql = $this->_event_delete_fc( ... $args );
                    break;

                // A query that checks to see if a given event is in the music_fc.events table.
                case MQEnum::EVENT_LOC_CHECK:
                    $sql = $this->_event_loc_check( ... $args );
                    break;

                // Gets a given user's User ID.
                case MQEnum::USER_ID_LOOKUP:
                    $sql = $this->_user_id_lookup( ... $args );
                    break;

                // Edits an event.
                case MQEnum::EVENT_EDIT:
                    $sql = $this->_event_edit( ... $args );
                    break;

                // Gets the new event ID, in case we need to erase it quickly.
                case MQEnum::EVENT_FC_ID:
                    $sql = $this->_event_fc_id( ... $args );
                    break;

                // Gets a list of swipes, either generally or for a specific event.
                case MQEnum::SWIPE_LIST:
                    $sql = $this->_swipe_list( ... $args );
                    break;

                // Adds a swipe entry.
                case MQEnum::SWIPE_ADD:
                    $sql = $this->_swipe_add( ... $args );
                    break;

                // Gets the full list of administrators for the application.
                case MQEnum::ADMIN_GET_ALL:
                    $sql = $this->_admin_get_all( ... $args );
                    break;

                // Checks to see what admin level the current user has, if any.
                case MQEnum::ADMIN_CHECK:
                    $sql = $this->_admin_check( ... $args );
                    break;

                // Gets all possible permission levels.
                case MQEnum::ADMIN_GET_LEVELS:
                    $sql = $this->_admin_get_levels( ... $args );
                    break;

                // Changes a user's Admin level.
                case MQEnum::ADMIN_CHG_PRIV:
                    $sql = $this->_admin_chg_priv( ... $args );
                    break;

                // Adds a new admin entry.
                case MQEnum::ADMIN_ADD:
                    $sql = $this->_admin_add( ... $args );
                    break;

                // Deletes an admin entry.
                case MQEnum::ADMIN_DELETE:
                    $sql = $this->_admin_delete( ... $args );
                    break;

                // Gets the total number of columns we'll need for the CSV file.
                case MQEnum::CSV_NUM_COLS:
                    $sql = $this->_csv_num_cols( ... $args );
                    break;

                // Gets the data we'll be writing to the CSV file.
                case MQEnum::CSV_LIST:
                    $sql = $this->_csv_list( ... $args );
                    break;

                default :
                    break;
            }

            // Returns the generated SQL string.
            return $sql;
        }


        /**
         * The base login check.
         * 
         * @param string $email  The user's email address, or more likely their NID.
         * @param string $pass  Their password, which we will attempt to appropriately encrypt.
         * 
         * @return string The built query string.
         */
        private function _login_base( string $email, string $pass, ... $args ) : string {

            return "$this->_login_base_str (email = '$email' OR nid = '$email') AND passwd = AES_ENCRYPT('$pass', '" . PASSWORD_KEY . "' ) $this->_limit";
        }


        /**
         * The adLDAP login check.
         * 
         * @param string $email  The user's NID.
         * 
         * @return string The built query string.
         */
        private function _login_adLDAP( string $email, ... $args ) : string {

            return "$this->_login_base_str nid = '$email' $this->_limit";
        }


        /**
         * The list of events, either in full or paginated. Caches the result, since we'll probably be
         * referring to it a bunch.
         * 
         * @param int $per_page  Number of results to display per page (i.e., in this query). Default 20.
         * @param int $page  Actually the offset for the LIMIT clause, below. Default 0.
         * @param int $cutoff The number of months to go back when we're checking. Default 5.
         * @param string $order  The oder schema for `startdate`, either "ASC" or "DESC". Default "DESC".
         * 
         * @return string The built query string.
         */
        private function _event_list( int $per_page = 20, int $page = 0, int $cutoff = -5, string $order = "DESC", ... $args ) : string {

            $past_cutoff = date( "Y-m-d", strtotime( "$cutoff months" ) );

            return "/*" . MYSQLND_QC_ENABLE_SWITCH . "*/ " . "SELECT c.id, c.startdate, c.title FROM cah.events AS c WHERE c.department_id = $this->_dept AND (c.enddate <= CURRENT_TIMESTAMP AND c.startdate >= '$past_cutoff') OR (c.startdate >= CURRENT_TIMESTAMP) AND c.approved = 1 UNION SELECT m.id,  m.time, m.name FROM music_fc.events AS m WHERE (m.time <= CURRENT_TIMESTAMP AND m.time >= '$past_cutoff') OR (m.time >= CURRENT_TIMESTAMP) ORDER BY startdate $order, title ASC " . ( $per_page > 0 ? "LIMIT $page, $per_page" : "" );
        }


        /**
         * Gets the total number of events, so we can let the user know how many there are.
         * 
         * @param int $cutoff  The number of months to look back. Should be negative. Default -5.
         * 
         * @return string The built query string.
         */
        private function _event_count( int $cutoff = -5, ... $args ) : string {

            $past_cutoff = date( "Y-m-d", strtotime( "$cutoff months" ) );

            return "SELECT count(*) AS event_count FROM cah.events WHERE department_id = $this->_dept AND (enddate <= CURRENT_TIMESTAMP AND startdate >= '$past_cutoff') OR (startdate >= CURRENT_TIMESTAMP) AND approved = 1 UNION SELECT count(*) AS event_count FROM music_fc.events WHERE (time <= CURRENT_TIMESTAMP AND time >= '$past_cutoff') OR (time >= CURRENT_TIMESTAMP)";
        }


        /**
         * Creates a new event from user input.
         * 
         * @param DateTime  $datetime  A DateTime object representing the time of the event.
         * @param string    $title     A description of the event.
         * @param int       $user_id   The ID (in the cah.users table) of the user creating the event.
         * 
         * @return string The built query string.
         */
        private function _event_create( DateTime $datetime, string $title, int $user_id, ... $args ) : string {

            return "INSERT INTO music_fc.events ( name, time, addedby, addedon ) VALUES ( '$title', '" . date_format( $datetime, "Y-m-d H:i:s") . "', $user_id, NOW())";
        }


        /**
         * "Deletes" an event that is on the cah.events table, making it so that it doesn't appear
         * in the Music Forum Credit app.
         * 
         * @param int $target  The ID (in the cah.events table) of the event to be "deleted".
         * 
         * @return string The built query string.
         */
        private function _event_delete( int $target, ... $args ) : string {

            return "UPDATE cah.events SET approved = 0 WHERE id = $target";
        }


        /**
         * Deletes an event that is on the music_fc.events table. This one literally deletes it, so
         * it will be gone forever.
         * 
         * @param int $target  The ID (in the music_fc.events table) of the event to be deleted.
         * 
         * @return string The built query string.
         */
        private function _event_delete_fc( int $target, ... $args ) : string {
            
            return "DELETE FROM music_fc.events WHERE events.id = $target";
        }


        /**
         * Checks to see if an event is in the music_fc.events table. If not, it's in the
         * cah.events table.
         * 
         * @param int    $target  The Event ID that we're checking.
         * @param string $title   The "title" field of the event, which we'll be comparing to
         *                          make sure we have the right one.
         * 
         * @return string The built query string.
         */
        private function _event_loc_check( int $target, string $title, ... $args ) : string {

            return "SELECT * FROM music_fc.events WHERE events.id = $target AND events.name LIKE '$title' LIMIT 1";
        }


        /**
         * Get a user's ID from their NID.
         * 
         * @param string $username  The user's NID.
         * 
         * @return string The built query string.
         */
        private function _user_id_lookup( string $username, ... $args ) : string {

            return "SELECT users.id FROM cah.users WHERE users.nid LIKE '$username' LIMIT 1";
        }


        /**
         * Edits an event that is on the music_fc.events table.
         *
         * @param integer  $target    The Event ID to be updated.
         * @param DateTime $datetime  The new event time.
         * @param string   $title     The new event Title.
         * 
         * @return string The built query string.
         */
        private function _event_edit( int $target, DateTime $datetime, string $title, ... $args ) : string {
            
            return "UPDATE music_fc.events SET events.name = '$title', events.time = '" . date_format( $datetime, "Y-m-d H:i:s") . "' WHERE events.id = $target";
        }


        /**
         * Finds the ID of an event that has just been created. Used in one of the event edit
         * scripts in music-fc-ajax-handler.php
         * 
         * @param string $title  The title of the event, for comparison.
         * 
         * @return string The built query string.
         */
        private function _event_fc_id( string $title, ... $args ) : string {

            return "SELECT events.id FROM music_fc.events WHERE events.name LIKE '$title' AND addedon >= DATE_SUB(NOW(), INTERVAL 2 SECONDS) ORDER BY events.id DESC LIMIT 1";
        }


        /**
         * Gets a list of swipes, either in its entirety or for a specific event.
         * 
         * @param int $event_id  The ID of the event, if any. Default NULL.
         * @param int $cutoff    The number of months back to check. Default -5.
         * 
         * @return string The built query string.
         */
        private function _swipe_list( int $event_id = NULL, int $cutoff = -5, ... $args ) : string {

            return "SELECT swipe.event, swipe.fname, swipe.lname, swipe.card, swipe.time FROM music_fc.swipe LEFT JOIN music_fc.events ON swipe.event = events.id WHERE events.time >= '" . date( 'Y-m-d', strtotime( "$cutoff months" ) ) . "'" . ( !is_null( $event_id ) ? " AND swipe.event = $event_id" : "" ) . " ORDER BY events.time DESC, swipe.lname ASC, swipe.fname ASC";
        }


        /**
         * Adds an entry to the music_fc.swipe table. Used both in swipe.php and in the "Add Student Entry"
         * button in admin.php.
         * 
         * @param int      $event_id   The ID of the event.
         * @param string   $fname      The student's first name.
         * @param string   $lname      The student's last name.
         * @param string   $card_num   The number of the card the student swiped.
         * @param string   $raw_input  The raw data retrieved from the card.
         * @param string   $email      The supervisor's email address. Defaults to "john.parker@ucf.edu".
         * @param DateTime $time       The time of the swipe. Default NULL (which translates to NOW()).
         * 
         * @return string The built query string.
         */
        private function _swipe_add( int $event_id, string $fname, string $lname, string $card_num, string $raw_input, string $email = "john.parker@ucf.edu", DateTime $time = NULL,  ... $args ) : string {

            return "INSERT INTO music_fc.swipe (event, swipe, fname, lname, card, time, email, ip) VALUES ( $event_id, '$raw_input', '$fname', '$lname', '$card_num', " . ( !is_null( $time ) ? "'" . date_format( $time, "Y-m-d H:i:s") . "'" : "NOW()" ) . ", '$email', '{$_SERVER['REMOTE_ADDR']}')";
        }


        /**
         * Gets the list of admins to display on admin.php.
         * 
         * @return string The built query string.
         */
        private function _admin_get_all( ... $args ) : string {

            return "SELECT a.user_id, CONCAT_WS(' ', u.fname, u.lname) AS fullname, u.nid, p.level FROM music_fc.admins AS a LEFT JOIN cah.users AS u ON a.user_id = u.id LEFT JOIN music_fc.permissions AS p ON a.level = p.id ORDER BY p.id, u.lname, u.fname";
        }


        /**
         * Checks to see if a user is on the admin list, and if so, what their admin level is.
         * 
         * @param string $nid  The user's NID to check.
         * 
         * @return string The built query string.
         */
        private function _admin_check( string $nid, ... $args ) : string {

            return "SELECT a.level FROM music_fc.admins AS a LEFT JOIN cah.users AS u ON a.user_id = u.id WHERE u.nid LIKE '$nid'";
        }


        /**
         * Gets all the possible levels for system admins.
         * 
         * @return string The built query string.
         */
        private function _admin_get_levels( ... $args ) : string {
            
            return "SELECT * FROM music_fc.permissions ORDER BY permissions.id";
        }


        /**
         * Changes an admin's privilege level.
         * 
         * @param int $user_id    The User ID of the admin to be changed.
         * @param int $new_level  The new admin level.
         * 
         * @return string The built query string.
         */
        private function _admin_chg_priv( int $user_id, int $new_level, ... $args ) : string {

            return "UPDATE music_fc.admins SET admins.level = $new_level WHERE admins.user_id = $user_id";
        }


        /**
         * Adds a new admin to the app's list of approved admins.
         * 
         * @param string $nid    The new individual's NID.
         * @param int    $level  The new admin's privilege level.
         * 
         * @return string The built query string.
         */
        private function _admin_add( string $nid, int $level, ... $args ) : string {

            return "INSERT INTO music_fc.admins ( admins.user_id, admins.level ) VALUES ( ( SELECT users.id FROM cah.users WHERE users.nid LIKE '$nid' ), $level )";
        }


        /**
         * Deletes an admin from the list.
         * 
         * @param int $user_id  The user ID of the admin to be deleted.
         * 
         * @return string The built query string.
         */
        private function _admin_delete( int $user_id, ... $args ) : string {

            return "DELETE FROM music_fc.admins WHERE admins.user_id = $user_id";
        }


        /**
         * Determines the number of event columns a generated CSV file will need.
         * 
         * @param int $cutoff  The number of months back to check. Default -5.
         * 
         * @return string The built query string.
         */
        private function _csv_num_cols( int $cutoff = -5, ... $args ) : string {

            $past_cutoff = date( "Y-m-d", strtotime( "$cutoff months" ) );

            return "SELECT MAX( sq.event_count ) AS num_columns FROM (SELECT COUNT(ssq.event) AS event_count FROM (SELECT DISTINCT swipe.event, c.nid FROM music_fc.swipe LEFT JOIN cards_10182019 AS c ON c.card = swipe.card WHERE swipe.time >= '$past_cutoff' AND c.nid IS NOT NULL AND c.nid NOT LIKE 'jparker' ) AS ssq GROUP BY ssq.nid ORDER BY event_count DESC) AS sq";
        }


        /**
         * Gets the list of students and the events they attended, in preparation for writing to
         * a CSV file.
         * 
         * @param int $cutoff  The number of months back to check. Default -5.
         * 
         * @return string The build query string.
         */
        private function _csv_list( int $cutoff = -5, ... $args ) : string {

            $past_cutoff = date( "Y-m-d", strtotime( "$cutoff months" ) );

            return "SELECT DISTINCT CONCAT_WS(' ', csv.fname, csv.lname) AS student, csv.nid, csv.pid, csv.title FROM (SELECT swipe.event, swipe.lname, swipe.fname, c.nid, c.pid, event_list.title, swipe.time FROM music_fc.swipe LEFT JOIN cards_10182019 AS c ON c.card = swipe.card LEFT JOIN (SELECT c.id, c.title FROM cah.events AS c WHERE c.department_id = 13 AND (c.enddate <= CURRENT_TIMESTAMP AND c.startdate >= '$past_cutoff') OR (c.startdate >= CURRENT_TIMESTAMP) AND c.approved = 1 UNION SELECT m.id, m.name FROM music_fc.events AS m WHERE (m.time <= CURRENT_TIMESTAMP AND m.time >= '$past_cutoff') OR (m.time >= CURRENT_TIMESTAMP)) AS event_list ON event_list.id = swipe.event WHERE swipe.time >= DATE_SUB( CURRENT_DATE, INTERVAL 5 MONTH ) AND c.nid IS NOT NULL AND c.nid NOT LIKE 'jparker') AS csv ORDER BY csv.lname, csv.fname, csv.time ASC";
        }
    }
}
?>