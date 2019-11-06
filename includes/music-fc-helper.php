<?php
/**
 * A helper class for managing the Music FC site.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

require_once MUSIC_FC__BASE_DIR . '/lib/data-architecture/database-handler.php';
require_once MUSIC_FC__BASE_DIR . '/lib/adLDAP/lib/adLDAP/adLDAP.php';
require_once MUSIC_FC__BASE_DIR . '/lib/data-architecture/adLDAP-authenticator.php';
require_once 'music-fc-query-lib.php';
require_once 'music-fc-query-ref.php';

use MusicFCQueryLib as MFQLib;
use MusicQueryRef as MQEnum;

if( !class_exists( 'MusicFCHelper' ) ) {
    class MusicFCHelper implements DatabaseHandler, AdLDAPAuthenticator
    {

        // Setting member variables, protected in case we need to extend the class.
        protected $_dept;

        protected $_db_server, $_db_user, $_db_pass, $_db, $_charset;

        protected $_db_connection, $_adLDAP, $_query_lib;

        // These ones are likely only for this use case.
        private $_link, $_menu_items;

        /**
         * The constructor. Sets all the variables in preparation for building the db connection,
         * and instantiates the Query Library.
         *
         * @param string $db_server  The server hostname.
         * @param string $db_user    The username for logging into the DB.
         * @param string $db_pass    The password.
         * @param string $db         The name of the database we'll primarily be using.
         * @param string $charset    The Unicode character set. Defaults to UTF-8.
         * @param array $menu_items  The menu items called by $this->menu_gen().
         * @param integer $dept      The department, which we'll use for instantiating the Query Lib.
         * 
         * @return void
         */
        public function __construct( string $db_server, string $db_user, string $db_pass, string $db = "", string $charset = "utf8", $menu_items = array(), $dept = 13 ) {
            $this->_db_server = $db_server;
            $this->_db_user = $db_user;
            $this->_db_pass = $db_pass;
            $this->_db = $db;
            $this->_charset = $charset;

            $this->_link = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == "on" ? "https" : "http" ) . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

            $this->_menu_items = $menu_items;

            $this->_dept = $dept;

            $this->_query_lib = $this->_get_query_lib();
        }


        /**
         * The destructor. Closes the database connection and deletes the Query Library instance.
         * 
         * @return void
         */
        public function __destruct()
        {
            $this->close_db();
            unset( $this->_query_lib );
        }


        /**
         * Gets the Database connection, or creates one if it doesn't exist.
         *
         * @return mysqli $_db_connection  The mysqli object that contains the connection info.
         */
        public function get_db() : mysqli {

            if( !isset( $this->_db_connection ) || is_null( $this->_db_connection ) ) {
                $this->_db_connection = mysqli_connect( $this->_db_server, $this->_db_user, $this->_db_pass, $this->_db );
                mysqli_set_charset( $this->_db_connection, $this->_charset );
            }

            return $this->_db_connection;
        }


        /**
         * Closes the database connection, because we don't need it anymore.
         *
         * @return void
         */
        public function close_db() {

            if( $this->_db_connection ) {
                mysqli_close( $this->_db_connection );
                $this->_db_connection = FALSE;
            }
        }


        /**
         * The function called in order to build and execute an SQL query.
         * 
         * @param int $type   The type of query to implement, represented by one of the
         *                       values in the MusicQueryRef faux-enum class.
         * @param array $args Any other arguments to be passed. Variadic because these will
         *                       differ based on which query the user is trying to run.
         * 
         * @return mysqli_result|bool|null The result of the query, or NULL.
         */
        public function query( int $type = MQEnum::MQ__DEFAULT, ... $args ) {

            $sql = $this->_get_query_lib()->get_query_str( $type, ... $args );

            $result = mysqli_query( $this->get_db(), $sql );

            if( $this->validate( $result, $sql, $type ) ) return $result;
            else return NULL;
        }


        /**
         * Validates the response from the database, so we don't end up passing unusable data back.
         *
         * @param [type] $result The result of the query, which is either a mysqli_result or a boolean.
         * @param string $sql    The SQL string that fueled the query.
         * @param integer $type  The type of query it was, because some of them get special treatement.
         * @param boolean $debug Whether to output verbose error reporting.
         * 
         * @return boolean  Whether or not the result is valid and should be returned.
         */
        public function validate( $result, string $sql, int $type, $debug = FALSE ) : bool {

            // Check if it's a valid result (though this doesn't rule out an empty set)
            if( $result instanceof mysqli_result ) {

                // If it's an empty set and it's not one specific kind of query...
                if( $result->num_rows < 1 && $type != MQEnum::EVENT_LOC_CHECK) {
                    if( $debug ) {
                        $msg = "Query returned an empty set. Original query:\n\t$sql\n";
                        error_log( $msg );
                    }
                    return FALSE;
                }
                else return TRUE;
            }
            // Will be TRUE if doing INSERT, UPDATE, DELETE, etc.
            else if( $result ) {
                return TRUE;
            }
            // Will be FALSE on error.
            else {
                if( $debug ) {
                    $msg  = "There was an error with the SQL query.\n";
                    $msg .= mysqli_errno( $this->get_db() ) . ": " . mysqli_error( $this->get_db() ) . "\n";
                    $msg .= "Original query:\n\t$sql\n";
                    error_log( $msg );
                }
                else {
                    $msg = "Database query error. Please contact CAH Web Team";
                }

                die( $msg );
            }
        }


        /**
         * Attempts to create an instance of an adLDAP object
         *
         * @return adLDAP|null
         */
        public function get_adLDAP(): ?adLDAP
        {
            if( is_null( $this->_adLDAP ) ) {
                try {
                    $this->_adLDAP = new adLDAP();
                }
                catch( adLDAPException $e ) {
                    $this->_adLDAP = NULL;
                }
            }

            return $this->_adLDAP;
        }


        public function menu_gen() : string {

            ob_start();
            
            foreach( $this->_menu_items as $label=>$page ) {

                $classes = array( 
                    "nav-item",
                    "nav-link",
                    "text-inverse" 
                );

                if( $_SERVER['REQUEST_URI'] == $page || basename( __FILE__ ) == $page)
                    array_push( $classes, "active" );
                ?>
                <a class="<?= implode( " ", $classes ); ?>" href="<?= $page ?>"><?= $label ?></a>
                <?php
            }

            return ob_get_clean();
        }


        public function scrub( string $item ) : string {

            return mysqli_real_escape_string( $this->get_db(), htmlentities( trim( $item ) ) );
        }


        public function get_csv( bool $canvas = FALSE ) {

            $output = fopen( 'php://output', 'w' );

            $result = $this->query( MQEnum::CSV_NUM_COLS );

            if( $result instanceof mysqli_result && $result->num_rows > 0 ) {
                $row = mysqli_fetch_assoc( $result );

                $num_cols = intval( $row['num_columns'] );

                mysqli_free_result( $result );
            }
            else {
                $num_cols = 0;
            }

            if( $num_cols > 0 ) {

                $result = $this->query( MQEnum::CSV_LIST );

                if( $result instanceof mysqli_result && $result->num_rows > 0 ) {

                    if( $canvas ) {

                        $headers = array(
                            "Student",
                            "ID",
                            "SIS User ID",
                            "SIS Login ID",
                            "Section"
                        );
                    }
                    else {

                        $headers = array(
                            "NID",
                            "Name"
                        );
                    }

                    for( $i = 1; $i <= $num_cols; $i++ ) {

                        array_push( $headers, "Event $i" );
                    }

                    fputcsv( $output, $headers );

                    if( $canvas ) {

                        $points = array( "Points Possible", "", "" ,"", "" );

                        for( $i = 0; $i < $num_cols; $i++ ) {
                            array_push( $points, "1" );
                        }

                        fputcsv( $output, $points );
                    }

                    $curr_nid = "";
                    $line = array();

                    $num_rows = $result->num_rows;
                    $counter = 0;
                    while( $row = mysqli_fetch_assoc( $result ) ) {

                        if( $row['nid'] != $curr_nid ) {
                            $curr_nid = $row['nid'];

                            if( !empty( $line ) ) {
                                fputcsv( $output, $line );
                            }

                            $line = array();

                            if( $canvas ) {
                                array_push( $line, $row['student'],
                                                   "",
                                                   $row['pid'],
                                                   $row['nid'],
                                                   ""
                                );
                            }
                            else {
                                array_push( $line, $row['nid'], $row['student'] );
                            }
                        }

                        array_push( $line, $row['title'] );

                        if( ++$counter == $num_rows ) {
                            fputcsv( $output, $line );
                        }
                    }

                }
            }
            else {
                fputcsv( $output, array("NO DATA") );
            }

            fclose( $output );
        }


        protected function _get_query_lib() : MFQLib {
            
            if( is_null( $this->_query_lib ) ) {
                $this->_query_lib = new MFQLib( $this->_dept );
            }

            return $this->_query_lib;
        }
    }
}
?>