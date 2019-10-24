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
        public function __construct( ... $args );

        public function get_query_str( int $type, ... $args ) : string;
    }
}
?>