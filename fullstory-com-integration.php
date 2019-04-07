<?php

/**
 * Plugin Name: FS for WP - FullStory.com Integration
 * Plugin URI: https://wordpress-plugins.luongovincenzo.it/#fullstory-integration
 * Description: FS for WP - FullStory.com Integration is a wordpress plugin makes it simple to add the FullStory code snippet to your website.
 * Donate URI: https://wordpress-plugins.luongovincenzo.it/#donate
 * Version: 1.0.0
 * Author: Vincenzo Luongo
 * Author URI: https://wordpress-plugins.luongovincenzo.it/
 * License: GPLv2 or later
 * Text Domain: fs-for-wp-fullstory-com-integration
 */
class FullStoryIntegrationPlugin {

    function __construct() {
        add_action('wp_head', [$this, 'snippet_code']);
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'save_settings']);
    }

    public function save_settings() {
        register_setting('fullstory-settings-group', 'fullstory_snippet_code', [$this, 'snippet_code_validate']);
        register_setting('fullstory-settings-group', 'enable_fs_identity');
        register_setting('fullstory-settings-group', 'fullstory_plugins_enabled');
    }

    public function snippet_code_validate($value) {
        if (empty($value)) {
            add_settings_error('fullstory_snippet_code', 'fullstory_snippet_code_validate', 'You must insert at least one snippet code.', 'error');
        }

        return $value;
    }

    public function add_menu() {
        add_options_page('FullStory Settings', 'FullStory Settings', 'administrator', 'fullstory-settings', [$this, 'settings_page']);
    }

    public function snippet_code() {

        if (get_option('fullstory_plugins_enabled')) {
            
            print PHP_EOL . '<!-- FullStory.com Snippet Plugin [START] -->' . PHP_EOL;
            print PHP_EOL . get_option('fullstory_snippet_code') . PHP_EOL;


            if (get_option('enable_fs_identity') && is_user_logged_in()) {

                print PHP_EOL . '<!-- FS.identify [START] -->' . PHP_EOL;
                print PHP_EOL . '<script type="text/javascript">' . PHP_EOL;

                $current_user = wp_get_current_user();

                print PHP_EOL . "FS.identify('" . $current_user->ID . "-" . $current_user->user_email . "', {" . PHP_EOL
                        . "        displayName: '" . $current_user->display_name . "'," . PHP_EOL
                        . "        email: '" . $current_user->user_email . "'," . PHP_EOL
                        . "        // TODO: Add your own custom user variables here, details at" . PHP_EOL
                        . "        // http://help.fullstory.com/develop-js/setuservars " . PHP_EOL
                        . "        reviewsWritten_int: 14, " . PHP_EOL
                        . "    });";
                print PHP_EOL . '</script>' . PHP_EOL;
                print PHP_EOL . '<!-- FS.identify [END] -->' . PHP_EOL;
            }

            print PHP_EOL . '<!-- FullStory.com Snippet Plugin [END] -->' . PHP_EOL . PHP_EOL;
        }
    }

    public function settings_page() {
        ?>
        <div class="wrap">

            <h2 class="title"><?php _e('FullStory.com Integration Plugin'); ?></h2>
            <h5><?php _e('You can get your code snippet from <a href="https://www.fullstory.com/" target="_blank">Full Story Panel</a>'); ?></h5>

            <form method="POST" action="options.php">
                <?php settings_fields('fullstory-settings-group'); ?>
                <?php do_settings_sections('fullstory-settings-group'); ?>
                <table class="form-table">

                    <tr>
                        <th><label for="fullstory_plugins_enabled">Enable FullStory Plugin?</label></th>
                        <td>
                            <label class="radio-inline">
                                <input type="radio" name="fullstory_plugins_enabled" value="1" <?php print (get_option('fullstory_plugins_enabled') == 1) ? 'checked' : ''; ?> /> Yes 
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="fullstory_plugins_enabled" value="0"  <?php print (get_option('fullstory_plugins_enabled') == 0) ? 'checked' : ''; ?> /> No
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="fullstory_snippet_code"><?php _e('Enter your code in the text area below'); ?></label></th>
                        <td>
                            <textarea class="form-control" style="width: 100%; height: 300px;" name="fullstory_snippet_code"><?php print esc_attr(get_option('fullstory_snippet_code')); ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="enable_fs_identity">Enable FS Identity?</label></th>
                        <td>
                            <label class="radio-inline">
                                <input type="radio" name="enable_fs_identity" value="1" <?php print (get_option('enable_fs_identity') == 1) ? 'checked' : ''; ?> /> Yes 
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="enable_fs_identity" value="0"  <?php print (get_option('enable_fs_identity') == 0) ? 'checked' : ''; ?> /> No
                            </label>
                        </td>
                    </tr>

                </table>

                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Update Settings') ?>">
                </p>
            </form>
        </div>
        <?php
    }

}

$fullStoryIntegrationPlugin = new FullStoryIntegrationPlugin();
