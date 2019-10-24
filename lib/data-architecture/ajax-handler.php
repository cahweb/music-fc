<?php
/**
 * An interface to standardize handling AJAX requests on the back-end.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

if( !interface_exists( 'AJAXHandler' ) ) {
    interface AJAXHandler
    {
        public function process( array $data, bool $return ) : ?string;
    }
}
?>