<?php

if( !class_exists( 'MusicFunctionHelper' ) ) {
    class MusicFunctionHelper {

        // Holds the menu items.
        private $menu_items = array(
            "Home" => "index.php",
            "Swipe" => "swipe.php",
            "Events" => "events.php",
            "Admin" => "admin.php"
        );
        // Holds the current page address
        private $link = "";

        function __construct() {

            if( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == "on" )
                $this->link = "https";
            else
                $this->link = "http";
            
            $this->link .= "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

        }

        public function menu_gen() {

            ob_start();
            
            foreach( $this->menu_items as $label=>$page ) {

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
    }
}

?>