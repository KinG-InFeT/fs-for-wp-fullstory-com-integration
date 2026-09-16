<?php
/**
 * Plugin Name: FS for WP - FullStory.com Integration
 * Plugin URI: https://wordpress-plugins.luongovincenzo.it/plugin/fs-for-wp-fullstory-com-integration
 * Description: Add the FullStory code snippet to your website and identify logged-in users.
 * Version: 3.0.0
 * Author: Vincenzo Luongo
 * Author URI: https://www.luongovincenzo.it/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fs-for-wp-fullstory-com-integration
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */
if (!defined('ABSPATH')) {
    exit;
}

class FSforWPFullStoryIntegrationPlugin {

    private const VERSION = '3.0.0';
    private const OPTION_SNIPPET = 'fsi_snippet_code';
    private const OPTION_IDENTITY = 'fsi_identity';
    private const OPTION_ENABLED = 'fsi_plugin_enabled';
    private const SCRIPT_HANDLE = 'fs-for-wp-fullstory';

    public function __construct() {
        add_action('init', [$this, 'load_textdomain']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_snippet']);
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_plugin_actions']);

        add_filter('fsi_additional_data', [$this, 'add_woocommerce_data']);
    }

    // Bundled translations; language packs from WordPress.org, when present, still take priority
    public function load_textdomain(): void {
        load_plugin_textdomain('fs-for-wp-fullstory-com-integration', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function add_woocommerce_data(array $dataFields): array {
        if (class_exists('WooCommerce')) {
            $userID = get_current_user_id();
            $dataFields['totalWooOrders'] = wc_get_customer_order_count($userID);
            $dataFields['totalWooAmountSpent'] = (float) wc_get_customer_total_spent($userID);
        }
        return $dataFields;
    }

    public function add_plugin_actions(array $links): array {
        $url = esc_url(admin_url('options-general.php?page=fullstory-settings'));
        array_unshift($links, '<a href="' . $url . '">' . esc_html__('Settings', 'fs-for-wp-fullstory-com-integration') . '</a>');
        return $links;
    }

    public function register_settings(): void {
        register_setting('fullstory-settings-group', self::OPTION_SNIPPET, [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitize_snippet_code'],
            'default' => '',
        ]);
        register_setting('fullstory-settings-group', self::OPTION_IDENTITY, [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 0,
        ]);
        register_setting('fullstory-settings-group', self::OPTION_ENABLED, [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 0,
        ]);
    }

    /**
     * The snippet is JavaScript that runs on every page, so it is stored as pasted: HTML filters such as
     * wp_kses() corrupt it. Only users allowed to publish unfiltered HTML may change it, which on multisite
     * excludes site administrators.
     */
    public function sanitize_snippet_code($value): string {
        $current = $this->get_snippet();
        $value = is_string($value) ? str_replace("\r\n", "\n", trim($value)) : $current;

        if ($value !== $current && !current_user_can('unfiltered_html')) {
            add_settings_error(
                self::OPTION_SNIPPET,
                'fsi_snippet_code_not_allowed',
                __('You are not allowed to change the code snippet.', 'fs-for-wp-fullstory-com-integration'),
                'error'
            );
            return $current;
        }

        if ($value === '') {
            add_settings_error(
                self::OPTION_SNIPPET,
                'fsi_snippet_code_empty',
                __('You must insert at least one snippet code.', 'fs-for-wp-fullstory-com-integration'),
                'error'
            );
        }

        return $value;
    }

    public function add_menu(): void {
        add_options_page(
            __('FullStory Settings', 'fs-for-wp-fullstory-com-integration'),
            __('FullStory Settings', 'fs-for-wp-fullstory-com-integration'),
            'manage_options',
            'fullstory-settings',
            [$this, 'settings_page']
        );
    }

    private function get_snippet(): string {
        // Browsers post textarea line breaks as CRLF
        $snippet = str_replace("\r\n", "\n", trim((string) get_option(self::OPTION_SNIPPET, '')));

        // Version 2.0.0 saved the snippet through wp_kses(), which turned every "&&" into "&amp;&amp;"
        if (strpos($snippet, '&amp;&amp;') !== false) {
            $snippet = html_entity_decode($snippet, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $snippet;
    }

    // AMP pages only allow AMP components and FullStory has none: its script would make them invalid
    private function is_amp_request(): bool {
        return (function_exists('ampforwp_is_amp_endpoint') && ampforwp_is_amp_endpoint())
            || (function_exists('amp_is_request') && amp_is_request());
    }

    public function enqueue_snippet(): void {
        if (!get_option(self::OPTION_ENABLED) || $this->is_amp_request()) {
            return;
        }

        // FullStory provides the snippet wrapped in <script> tags: keep only the JavaScript inside them
        $snippet = $this->get_snippet();
        if (preg_match_all('#<script\b[^>]*>(.*?)</script>#is', $snippet, $matches)) {
            $snippet = implode("\n", $matches[1]);
        }

        $snippet = trim($snippet);
        if ($snippet === '') {
            return;
        }

        wp_register_script(self::SCRIPT_HANDLE, false, [], self::VERSION, false);
        wp_enqueue_script(self::SCRIPT_HANDLE);
        wp_add_inline_script(self::SCRIPT_HANDLE, $snippet);

        if (get_option(self::OPTION_IDENTITY) && is_user_logged_in()) {
            wp_add_inline_script(self::SCRIPT_HANDLE, $this->get_identity_script());
        }
    }

    private function get_identity_script(): string {
        $current_user = wp_get_current_user();

        $userData = [
            'displayName' => $current_user->display_name,
            'email' => $current_user->user_email,
            'roles' => implode(', ', $current_user->roles),
        ];

        foreach ((array) apply_filters('fsi_additional_data', []) as $key => $value) {
            $userData[$key] = $value;
        }

        // Snippet 2.x exposes the v2 API (FS("setIdentity")); older snippets only know FS.identify()
        return sprintf(
            '(function (fs, uid, properties) {
    if (typeof fs !== "function") {
        return;
    }
    if (parseInt(fs._v, 10) >= 2) {
        fs("setIdentity", { uid: uid, properties: properties });
    } else if (typeof fs.identify === "function") {
        fs.identify(uid, properties);
    }
})(window[window._fs_namespace || "FS"], %s, %s);',
            wp_json_encode($current_user->ID . '-' . $current_user->user_email, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE),
            wp_json_encode($userData, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)
        );
    }

    public function enqueue_admin_styles(string $hook): void {
        if ($hook !== 'settings_page_fullstory-settings') {
            return;
        }
        wp_enqueue_style('fs-for-wp-fullstory-admin', plugins_url('css/admin.css', __FILE__), [], self::VERSION);
    }

    public function settings_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $can_edit_snippet = current_user_can('unfiltered_html');
        ?>
        <div class="wrap fsi-settings-wrap">

            <div class="fsi-header">
                <div>
                    <h1><?php esc_html_e('FS for WP - FullStory.com Integration', 'fs-for-wp-fullstory-com-integration'); ?></h1>
                    <p><?php
                        printf(
                            /* translators: %s: link to the FullStory panel */
                            esc_html__('Get your code snippet from %s', 'fs-for-wp-fullstory-com-integration'),
                            '<a href="https://app.fullstory.com" target="_blank" rel="noopener noreferrer">' . esc_html__('FullStory Panel', 'fs-for-wp-fullstory-com-integration') . '</a>'
                        );
                    ?></p>
                </div>
            </div>

            <form method="POST" action="<?php echo esc_url(admin_url('options.php')); ?>">
                <?php settings_fields('fullstory-settings-group'); ?>

                <div class="fsi-card">
                    <h2><?php esc_html_e('General Settings', 'fs-for-wp-fullstory-com-integration'); ?></h2>

                    <fieldset class="fsi-field">
                        <legend><?php esc_html_e('Enable Integration', 'fs-for-wp-fullstory-com-integration'); ?></legend>
                        <div class="fsi-toggle-group">
                            <label>
                                <input type="radio" name="fsi_plugin_enabled" value="1" <?php checked(get_option(self::OPTION_ENABLED), 1); ?> />
                                <?php esc_html_e('Yes', 'fs-for-wp-fullstory-com-integration'); ?>
                            </label>
                            <label>
                                <input type="radio" name="fsi_plugin_enabled" value="0" <?php checked(get_option(self::OPTION_ENABLED), 0); ?> />
                                <?php esc_html_e('No', 'fs-for-wp-fullstory-com-integration'); ?>
                            </label>
                        </div>
                        <p class="description"><?php esc_html_e('Enable or disable the FullStory tracking code on your website.', 'fs-for-wp-fullstory-com-integration'); ?></p>
                    </fieldset>

                    <div class="fsi-field">
                        <label for="fsi_snippet_code"><?php esc_html_e('FullStory Code Snippet', 'fs-for-wp-fullstory-com-integration'); ?></label>
                        <textarea id="fsi_snippet_code" class="fsi-snippet-textarea" name="fsi_snippet_code" <?php echo $can_edit_snippet ? '' : 'readonly'; ?>><?php echo esc_textarea($this->get_snippet()); ?></textarea>
                        <p class="description">
                            <?php
                            if ($can_edit_snippet) {
                                esc_html_e('Paste the FullStory JavaScript snippet code from your FullStory dashboard.', 'fs-for-wp-fullstory-com-integration');
                            } else {
                                esc_html_e('Only users allowed to publish unfiltered HTML can change the code snippet.', 'fs-for-wp-fullstory-com-integration');
                            }
                            ?>
                        </p>
                    </div>
                </div>

                <div class="fsi-card">
                    <h2><?php esc_html_e('User Identity', 'fs-for-wp-fullstory-com-integration'); ?></h2>

                    <fieldset class="fsi-field">
                        <legend><?php esc_html_e('Enable FS.identify', 'fs-for-wp-fullstory-com-integration'); ?></legend>
                        <div class="fsi-toggle-group">
                            <label>
                                <input type="radio" name="fsi_identity" value="1" <?php checked(get_option(self::OPTION_IDENTITY), 1); ?> />
                                <?php esc_html_e('Yes', 'fs-for-wp-fullstory-com-integration'); ?>
                            </label>
                            <label>
                                <input type="radio" name="fsi_identity" value="0" <?php checked(get_option(self::OPTION_IDENTITY), 0); ?> />
                                <?php esc_html_e('No', 'fs-for-wp-fullstory-com-integration'); ?>
                            </label>
                        </div>
                        <p class="description"><?php esc_html_e('When enabled, logged-in users are identified in FullStory with their display name, email and roles, plus order count and total spent when WooCommerce is active.', 'fs-for-wp-fullstory-com-integration'); ?></p>
                    </fieldset>
                </div>

                <?php submit_button(__('Save Settings', 'fs-for-wp-fullstory-com-integration')); ?>
            </form>

            <p class="fsi-footer">
                FS for WP - FullStory.com Integration v<?php echo esc_html(self::VERSION); ?> |
                <a href="https://wordpress-plugins.luongovincenzo.it/plugin/fs-for-wp-fullstory-com-integration" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Plugin Homepage', 'fs-for-wp-fullstory-com-integration'); ?></a>
            </p>

        </div>
        <?php
    }
}

new FSforWPFullStoryIntegrationPlugin();
