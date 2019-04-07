<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}
delete_option('fullstory_snippet_code');
delete_option('enable_fs_identity');
delete_option('fullstory_plugins_enabled');
?> 

