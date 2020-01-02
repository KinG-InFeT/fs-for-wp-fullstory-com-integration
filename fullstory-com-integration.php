<?php

/**
 * Plugin Name: FS for WP - FullStory.com Integration
 * Plugin URI: https://wordpress-plugins.luongovincenzo.it/#fullstory-integration
 * Description: FS for WP - FullStory.com Integration is a wordpress plugin makes it simple to add the FullStory code snippet to your website.
 * Donate URI: https://donate.luongovincenzo.it/
 * Version: 1.2.1
 * Author: Vincenzo Luongo
 * Author URI: https://wordpress-plugins.luongovincenzo.it/
 * License: GPLv2 or later
 * Text Domain: fs-for-wp-fullstory-com-integration
 */
class FSforWPFullStoryIntegrationPlugin {

    function __construct() {
        add_action('wp_head', [$this, 'snippet_code']);
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'save_settings']);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_plugin_actions']);

        add_filter('fsi_additional_data', function($dataFields) {
            if (class_exists('woocommerce')) {
                $userID = get_current_user_id();

                $dataFields["totalWooOrders_int"] = wc_get_customer_order_count($userID);
                $dataFields["totalWooAmountSpent_real"] = wc_get_customer_total_spent($userID);
            }
            return $dataFields;
        });
    }

    public function add_plugin_actions($links) {

        $links[] = '<a href="' . esc_url(get_admin_url(null, 'options-general.php?page=fullstory-settings')) . '">Settings</a>';
        $links[] = '<a href="https://wordpress-plugins.luongovincenzo.it/#donate" target="_blank">Donate</a>';

        return $links;
    }

    public function save_settings() {
        register_setting('fullstory-settings-group', 'fsi_snippet_code', [$this, 'snippet_code_validate']);
        register_setting('fullstory-settings-group', 'fsi_identity');
        register_setting('fullstory-settings-group', 'fsi_plugin_enabled');
    }

    public function snippet_code_validate($value) {

        if (empty($value)) {
            add_settings_error('fsi_snippet_code', 'fsi_snippet_code_validate', 'You must insert at least one snippet code.', 'error');
        }

        return $value;
    }

    public function add_menu() {
        add_options_page('FullStory Settings', 'FullStory Settings', 'administrator', 'fullstory-settings', [$this, 'settings_page']);
    }

    public function snippet_code() {

        if (get_option('fsi_plugin_enabled')) {

            print PHP_EOL . '<!-- FS for WP - FullStory.com Integration Snippet [START] -->' . PHP_EOL;
            print PHP_EOL . get_option('fsi_snippet_code') . PHP_EOL;


            if (get_option('fsi_identity') && is_user_logged_in()) {

                print PHP_EOL . '<!-- FS.identify [START] -->' . PHP_EOL;
                print PHP_EOL . '<script type="text/javascript">' . PHP_EOL;

                $current_user = wp_get_current_user();
                $current_user_data = get_userdata(get_current_user_id());

                $fullStoryUserData = [
                    "displayName" => $current_user->display_name,
                    "email" => $current_user->user_email,
                    "roles_str" => implode(', ', $current_user_data->roles)
                ];

                //http://help.fullstory.com/develop-js/setuservars

                foreach (apply_filters('fsi_additional_data', []) as $key => $value) {
                    $fullStoryUserData[$key] = $value;
                }

                print PHP_EOL . "window.document.onload = function(e) { " . PHP_EOL;
                print PHP_EOL . "\t var FSIAdditionalData = " . json_encode($fullStoryUserData) . "; " . PHP_EOL;
                print PHP_EOL . "\t FS.identify('" . $current_user->ID . "-" . $current_user->user_email . "', FSIAdditionalData);";
                print PHP_EOL . " } " . PHP_EOL;

                print PHP_EOL . '</script>' . PHP_EOL;
                print PHP_EOL . '<!-- FS.identify [END] -->' . PHP_EOL;
            }

            print PHP_EOL . '<!-- FS for WP - FullStory.com Integration Snippet [END] -->' . PHP_EOL . PHP_EOL;
        }
    }

    public function settings_page() {
        ?>
        <div class="wrap">

            <h2 class="title"><?php _e('FS for WP - FullStory.com Integration'); ?></h2>
            <h5><?php _e('You can get your code snippet from <a href="https://app.fullstory.com" target="_blank">Full Story Panel</a>'); ?></h5>

            <form method="POST" action="options.php">
                <?php settings_fields('fullstory-settings-group'); ?>
                <?php do_settings_sections('fullstory-settings-group'); ?>
                <table class="form-table">

                    <tr>
                        <th><label for="fsi_plugin_enabled">Enable Integration?</label></th>
                        <td>
                            <label class="radio-inline">
                                <input type="radio" name="fsi_plugin_enabled" value="1" <?php print (get_option('fsi_plugin_enabled') == 1) ? 'checked' : ''; ?> /> Yes 
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="fsi_plugin_enabled" value="0"  <?php print (get_option('fsi_plugin_enabled') == 0) ? 'checked' : ''; ?> /> No
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="fsi_snippet_code"><?php _e('Enter your code in the text area below'); ?></label></th>
                        <td>
                            <textarea class="form-control" style="width: 100%; height: 300px;" name="fsi_snippet_code"><?php print esc_attr(get_option('fsi_snippet_code')); ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="fsi_identity">Enable FS Identity?</label></th>
                        <td>
                            <label class="radio-inline">
                                <input type="radio" name="fsi_identity" value="1" <?php print (get_option('fsi_identity') == 1) ? 'checked' : ''; ?> /> Yes 
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="fsi_identity" value="0"  <?php print (get_option('fsi_identity') == 0) ? 'checked' : ''; ?> /> No
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

$FSforWPFullStoryIntegrationPlugin = new FSforWPFullStoryIntegrationPlugin();
