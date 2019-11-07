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
        // Makes sure we get everything we need for a DB connection.
        public function __construct( string $db_server, string $db_user, string $db_pass, string $db, string $charset );

        // Makes sure we can return the DB.
        public function get_db() : mysqli;

        // Makes sure we can close the DB connection.
        public function close_db();

        // Makes sure we can run queries.
        public function query( int $type, ... $args );

        // Makes sure we can validate queries.
        public function validate( $result, string $sql, int $type ) : bool;
    }
}
?>