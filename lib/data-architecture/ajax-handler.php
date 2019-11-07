<?php
/**
 * An interface to standardize handling AJAX requests on the back-end. Mediates between
 * the front-end and the actual AJAX handler class.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

if( !interface_exists( 'AJAXHandler' ) ) {
    interface AJAXHandler
    {
        // The method that handles the actual reuqests.
        public function process( array $data, bool $return ) : ?string;
    }
}
?>