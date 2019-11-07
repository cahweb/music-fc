<?php
/**
 * An interface that defines methods (really *a* method) in order to standardize an SQL lookup library.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

if( !interface_exists( 'SQLGenerator' ) ) {
    interface SQLGenerator
    {
        // Because of the way I planned this, the SQL Generator
        // should be instantiated, rather than static.
        public function __construct( ... $args );

        // The core method that this kind of object needs.
        public function get_query_str( int $type, ... $args ) : string;
    }
}
?>