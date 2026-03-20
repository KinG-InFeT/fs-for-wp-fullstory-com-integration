<?php
/**
 * Plugin Name: FS for WP - FullStory.com Integration
 * Plugin URI: https://wordpress-plugins.luongovincenzo.it/plugin/fs-for-wp-fullstory-com-integration
 * Description: FS for WP - FullStory.com Integration is a wordpress plugin makes it simple to add the FullStory code snippet to your website.
 * Version: 2.0.0
 * Author: Vincenzo Luongo
 * Author URI: https://www.luongovincenzo.it/
 * License: GPLv2 or later
 * Text Domain: fs-for-wp-fullstory-com-integration
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */
if (!defined('ABSPATH')) {
    exit;
}

class FSforWPFullStoryIntegrationPlugin {

    private const VERSION = '2.0.0';
    private const OPTION_SNIPPET = 'fsi_snippet_code';
    private const OPTION_IDENTITY = 'fsi_identity';
    private const OPTION_ENABLED = 'fsi_plugin_enabled';

    public function __construct() {
        add_action('wp_head', [$this, 'render_snippet']);
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_plugin_actions']);

        add_filter('fsi_additional_data', [$this, 'add_woocommerce_data']);
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
        array_unshift($links, '<a href="' . $url . '">' . __('Settings', 'fs-for-wp-fullstory-com-integration') . '</a>');
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

    public function sanitize_snippet_code(string $value): string {
        $value = trim($value);
        if (empty($value)) {
            add_settings_error(
                self::OPTION_SNIPPET,
                'fsi_snippet_code_empty',
                __('You must insert at least one snippet code.', 'fs-for-wp-fullstory-com-integration'),
                'error'
            );
        }
        return wp_kses($value, [
            'script' => [
                'type' => [],
                'src' => [],
                'async' => [],
                'defer' => [],
                'crossorigin' => [],
            ],
        ]);
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

    public function render_snippet(): void {
        if (!get_option(self::OPTION_ENABLED)) {
            return;
        }

        $snippet = get_option(self::OPTION_SNIPPET);
        if (empty($snippet)) {
            return;
        }

        echo PHP_EOL . '<!-- FS for WP - FullStory.com Integration v' . esc_html(self::VERSION) . ' [START] -->' . PHP_EOL;
        echo wp_kses($snippet, [
            'script' => [
                'type' => [],
                'src' => [],
                'async' => [],
                'defer' => [],
                'crossorigin' => [],
            ],
        ]) . PHP_EOL;

        if (get_option(self::OPTION_IDENTITY) && is_user_logged_in()) {
            $this->render_identity_script();
        }

        echo '<!-- FS for WP - FullStory.com Integration [END] -->' . PHP_EOL . PHP_EOL;
    }

    private function render_identity_script(): void {
        $current_user = wp_get_current_user();

        $userData = [
            'displayName' => $current_user->display_name,
            'email' => $current_user->user_email,
            'roles' => implode(', ', $current_user->roles),
        ];

        $additionalData = apply_filters('fsi_additional_data', []);
        foreach ($additionalData as $key => $value) {
            $userData[sanitize_key($key)] = $value;
        }

        $jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
        $jsonData = wp_json_encode($userData, $jsonFlags);
        $uid = esc_js($current_user->ID . '-' . $current_user->user_email);

        echo '<!-- FS.identify [START] -->' . PHP_EOL;
        echo '<script type="text/javascript">' . PHP_EOL;
        echo 'document.addEventListener("DOMContentLoaded", function() {' . PHP_EOL;
        echo '  if (typeof FS === "function") {' . PHP_EOL;
        echo '    FS("setIdentity", { uid: "' . $uid . '", properties: ' . $jsonData . ' });' . PHP_EOL;
        echo '  } else if (typeof FS !== "undefined" && typeof FS.identify === "function") {' . PHP_EOL;
        echo '    FS.identify("' . $uid . '", ' . $jsonData . ');' . PHP_EOL;
        echo '  }' . PHP_EOL;
        echo '});' . PHP_EOL;
        echo '</script>' . PHP_EOL;
        echo '<!-- FS.identify [END] -->' . PHP_EOL;
    }

    public function enqueue_admin_styles(string $hook): void {
        if ($hook !== 'settings_page_fullstory-settings') {
            return;
        }
        wp_add_inline_style('wp-admin', $this->get_admin_css());
    }

    private function get_admin_css(): string {
        return '
            .fsi-settings-wrap {
                max-width: 800px;
            }
            .fsi-settings-wrap .fsi-header {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-left: 4px solid #2271b1;
                padding: 16px 20px;
                margin: 20px 0;
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .fsi-settings-wrap .fsi-header h1 {
                margin: 0;
                padding: 0;
                font-size: 1.4em;
                line-height: 1.3;
            }
            .fsi-settings-wrap .fsi-header p {
                margin: 4px 0 0;
                color: #646970;
            }
            .fsi-settings-wrap .fsi-card {
                background: #fff;
                border: 1px solid #c3c4c7;
                padding: 20px 24px;
                margin-bottom: 16px;
            }
            .fsi-settings-wrap .fsi-card h2 {
                margin-top: 0;
                padding-top: 0;
                font-size: 1.1em;
                border-bottom: 1px solid #f0f0f1;
                padding-bottom: 10px;
            }
            .fsi-settings-wrap .fsi-field {
                margin-bottom: 16px;
            }
            .fsi-settings-wrap .fsi-field label {
                display: block;
                font-weight: 600;
                margin-bottom: 6px;
            }
            .fsi-settings-wrap .fsi-field .description {
                color: #646970;
                font-style: italic;
                margin-top: 4px;
                font-size: 13px;
            }
            .fsi-settings-wrap .fsi-snippet-textarea {
                width: 100%;
                height: 250px;
                font-family: Consolas, Monaco, monospace;
                font-size: 13px;
                line-height: 1.5;
                padding: 10px;
                border: 1px solid #8c8f94;
                background: #f6f7f7;
                resize: vertical;
            }
            .fsi-settings-wrap .fsi-snippet-textarea:focus {
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
                outline: none;
                background: #fff;
            }
            .fsi-settings-wrap .fsi-toggle-group {
                display: flex;
                gap: 16px;
            }
            .fsi-settings-wrap .fsi-toggle-group label {
                font-weight: normal;
                display: inline-flex;
                align-items: center;
                gap: 4px;
                cursor: pointer;
            }
            .fsi-settings-wrap .fsi-footer {
                color: #646970;
                font-size: 12px;
                margin-top: 16px;
            }
            .fsi-settings-wrap .fsi-footer a {
                text-decoration: none;
            }
        ';
    }

    public function settings_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap fsi-settings-wrap">

            <div class="fsi-header">
                <div>
                    <h1><?php esc_html_e('FS for WP - FullStory.com Integration', 'fs-for-wp-fullstory-com-integration'); ?></h1>
                    <p><?php
                        printf(
                            /* translators: %s: link to FullStory panel */
                            esc_html__('Get your code snippet from %s', 'fs-for-wp-fullstory-com-integration'),
                            '<a href="https://app.fullstory.com" target="_blank" rel="noopener noreferrer">FullStory Panel</a>'
                        );
                    ?></p>
                </div>
            </div>

            <?php settings_errors(); ?>

            <form method="POST" action="<?php echo esc_url(admin_url('options.php')); ?>">
                <?php settings_fields('fullstory-settings-group'); ?>

                <div class="fsi-card">
                    <h2><?php esc_html_e('General Settings', 'fs-for-wp-fullstory-com-integration'); ?></h2>

                    <div class="fsi-field">
                        <label><?php esc_html_e('Enable Integration', 'fs-for-wp-fullstory-com-integration'); ?></label>
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
                    </div>

                    <div class="fsi-field">
                        <label for="fsi_snippet_code"><?php esc_html_e('FullStory Code Snippet', 'fs-for-wp-fullstory-com-integration'); ?></label>
                        <textarea id="fsi_snippet_code" class="fsi-snippet-textarea" name="fsi_snippet_code"><?php echo esc_textarea(get_option(self::OPTION_SNIPPET)); ?></textarea>
                        <p class="description"><?php esc_html_e('Paste the FullStory JavaScript snippet code from your FullStory dashboard.', 'fs-for-wp-fullstory-com-integration'); ?></p>
                    </div>
                </div>

                <div class="fsi-card">
                    <h2><?php esc_html_e('User Identity', 'fs-for-wp-fullstory-com-integration'); ?></h2>

                    <div class="fsi-field">
                        <label><?php esc_html_e('Enable FS.identify', 'fs-for-wp-fullstory-com-integration'); ?></label>
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
                        <p class="description"><?php esc_html_e('When enabled, logged-in users will be identified in FullStory with their display name, email, and roles.', 'fs-for-wp-fullstory-com-integration'); ?></p>
                    </div>
                </div>

                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'fs-for-wp-fullstory-com-integration'); ?>">
                </p>
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
