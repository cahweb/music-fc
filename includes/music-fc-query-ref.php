<?php
/**
 * A de facto Enum to make sorting and processing calls to MusicQueryLib::get_query_str() more human readable.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

if( !class_exists( 'MusicQueryRef' ) ) {
    class MusicQueryRef
    {
        private function __construct() {}
        
        const MQ__DEFAULT = 0;
        const LOGIN_BASE = 1;
        const LOGIN_ADLDAP = 2;
        const EVENT_LIST = 3;
        const EVENT_COUNT = 4;
        const EVENT_CREATE = 5;
        const EVENT_DELETE = 6;
        const EVENT_DELETE_FC = 7;
        const EVENT_LOC_CHECK = 8;
    }
}
?>