<?php

class WordPress_To_Markdown_Exporter {

    private function init_hooks() {
        // Load admin class
        if ( is_admin() ) {
            require_once WP_TO_MD_PLUGIN_DIR . 'admin/class-wp-to-md-admin-page.php';
            new WP_To_MD_Admin_Page();
        }
    }
} 
