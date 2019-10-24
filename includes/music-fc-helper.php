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

        protected $_dept;

        protected $_db_server, $_db_user, $_db_pass, $_db, $_charset;

        protected $_db_connection, $_adLDAP, $_query_lib;

        private $_link, $_menu_items;

        public function __construct( string $db_server, string $db_user, string $db_pass, string $db = "", string $charset = "utf8", $menu_items = array(), $dept = 13 ) {
            $this->_db_server = $db_server;
            $this->_db_user = $db_user;
            $this->_db_pass = $db_pass;
            $this->_db = $db;
            $this->_charset = $charset;

            $this->_link = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == "on" ? "https" : "http" ) . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

            $this->_menu_items = $menu_items;

            $this->_query_lib = $this->_get_query_lib();

            $this->_dept = $dept;
        }


        public function __destruct()
        {
            $this->close_db();
            unset( $this->_query_lib );
        }


        public function get_db() : mysqli {

            if( !isset( $this->_db_connection ) || is_null( $this->_db_connection ) ) {
                $this->_db_connection = mysqli_connect( $this->_db_server, $this->_db_user, $this->_db_pass, $this->_db );
                mysqli_set_charset( $this->_db_connection, $this->_charset );
            }

            return $this->_db_connection;
        }


        public function close_db() {

            if( $this->_db_connection ) {
                mysqli_close( $this->_db_connection );
                $this->_db_connection = FALSE;
            }
        }


        public function query( int $type = MQEnum::MQ__DEFAULT, ... $args ) : ?mysqli_result {

            $sql = $this->_get_query_lib()->get_query_str( $type, ... $args );

            $result = mysqli_query( $this->get_db(), $sql );

            if( $this->validate( $result, $sql ) ) return $result;
            else return NULL;
        }


        public function validate( $result, string $sql, $debug = TRUE ) : bool {

            if( !$result ) {
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
            else if ( $result->num_rows < 1 ) {
                if( $debug ) {
                    $msg = "Query returned an empty set. Original query:\n\t$sql\n";
                    error_log( $msg );
                }
                return FALSE;
            }
            else return TRUE;
        }


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


        protected function _get_query_lib() : MFQLib {
            
            if( is_null( $this->_query_lib ) ) {
                $this->_query_lib = new MFQLib( $this->_dept );
            }

            return $this->_query_lib;
        }
    }
}
?>