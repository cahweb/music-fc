<?php
/**
 * An interface to standardize anything that deals with Active Directory authentication.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

require_once MUSIC_FC__BASE_DIR . '/lib/adLDAP/lib/adLDAP/adLDAP.php';

if( !interface_exists( 'AdLDAPAuthenticator' ) ) {
    interface AdLDAPAuthenticator
    {
        public function get_adLDAP() : ?adLDAP;
    }
}
?>