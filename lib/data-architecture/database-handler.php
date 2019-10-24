<?php
/**
 * An interface to standardize methods for database interaction.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

if( !interface_exists( 'DatabaseHandler' ) ) {
    interface DatabaseHandler
    {
        public function __construct( string $db_server, string $db_user, string $db_pass, string $db, string $charset );

        public function get_db() : mysqli;

        public function close_db();

        public function query( int $type, ... $args ) : ?mysqli_result;

        public function validate( $result, string $sql ) : bool;
    }
}
?>