<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}
delete_option('fsi_snippet_code');
delete_option('fsi_identity');
delete_option('fsi_plugin_enabled');
?> 

