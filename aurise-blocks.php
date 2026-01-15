<?php

/**
 * Plugin Name: Blocks by AuRise Creative
 * Description: Helpful content blocks!
 * Version: 1.0.0
 * Author: AuRise Creative
 * Author URI: https://aurisecreative.com/
 * Update URI: https://aurisecreative.com/automatic-plugin-updates/
 * License: GPL v3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 5.8
 * Requires PHP: 5.6.20
 * Text Domain: aurise-blocks
 * Domain Path: /languages/
 *
 * @package AuRise\Plugin\Blocks
 * @copyright Copyright (c) 2024 Tessa Watkins, AuRise Creative <tessa@aurisecreative.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or higher
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

defined('ABSPATH') || exit; // Exit if accessed directly
defined('AURISE_BLOCKS_DIR') || define('AURISE_BLOCKS_DIR', __DIR__); // Define root directory
defined('AURISE_BLOCKS_FILE') || define('AURISE_BLOCKS_FILE', __FILE__);  // Define root file
defined('AURISE_BLOCKS_VERSION') || define('AURISE_BLOCKS_VERSION', '1.0.0'); // Define plugin version

/**
 * Initialise plugin once everything is loaded
 */
add_action('plugins_loaded', function () {
    require_once AURISE_BLOCKS_DIR . '/includes/class-utilities.php'; // Load the utilities class
    require_once AURISE_BLOCKS_DIR . '/includes/class-settings.php'; // Load the settings class
    require_once AURISE_BLOCKS_DIR . '/includes/class-shortcode.php'; // Load the abstract shortcode class
    require_once AURISE_BLOCKS_DIR . '/includes/class-block.php'; // Load the abstract block class
    require_once AURISE_BLOCKS_DIR . '/includes/class-main.php'; // Load the main plugin class

    /**
     * The global instance of the Main plugin class
     *
     * @var AuRise\Plugin\Blocks\Main
     *
     * @since 1.0.0
     */
    $au_init_plugin = str_replace('-', '_', sanitize_key(dirname(plugin_basename(AURISE_CONSTANT_FILE)))); // E.g. `plugin_folder`
    global ${$au_init_plugin}; // I.e. `$plugin_folder`
    ${$au_init_plugin} = AuRise\Plugin\PluginNamespace\Main::instance(); // Run once to init
});
