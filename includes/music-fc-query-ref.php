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
        const USER_ID_LOOKUP = 9;
        const EVENT_EDIT = 10;
        const EVENT_FC_ID = 11;
        const SWIPE_LIST = 12;
        const SWIPE_ADD = 13;
        const ADMIN_GET_ALL = 14;
        const ADMIN_CHECK = 15;
        const ADMIN_GET_LEVELS = 16;
        const ADMIN_CHG_PRIV = 17;
        const ADMIN_ADD = 18;
        const ADMIN_DELETE = 19;
        const CSV_NUM_COLS = 20;
        const CSV_LIST = 21;
    }
}
?>