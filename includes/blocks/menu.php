<?php

namespace AuRise\Plugin\Blocks;

defined('ABSPATH') || exit; // Exit if accessed directly.

use AuRise\Plugin\Blocks\Settings;
use AuRise\Plugin\Blocks\Utilities;

/**
 * Class AuRise Menu Shortcode and Block
 *
 * @package AuRise\Plugin\Blocks
 */
class Menu extends Block
{

    private $menus = null;

    /**
     * The single instance of the class
     *
     * @var Menu
     *
     * @since 1.0.0
     */
    protected static $_instance = null;

    /**
     * Main Instance
     *
     * Ensures only one instance of is loaded or can be loaded.
     *
     * @since 1.0.0
     *
     * @static
     *
     * @return Menu instance.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        if (is_null($this->menus)) {
            $this->menus = array();
            if (is_array($menus = wp_get_nav_menus()) && count($menus)) {
                foreach ($menus as $menu) {
                    if ($menu instanceof \WP_Term) {
                        $this->menus[$menu->term_id] = $menu->name;
                    }
                }
            }
        }

        $this->name = __('Menu', 'aurise-blocks'); // Name of block
        $this->tag = 'menu'; // Tag of block
        $this->handle = sanitize_key(self::$prefix . $this->tag);
        $this->atts = array(
            // Block Settings
            'icon' => 'dashicons-menu-alt3', // https://developer.wordpress.org/resource/dashicons/#dashicons-menu-alt3
            'description' => __('Display website menu information.', 'aurise-blocks'),
            'keywords' => array('menu', 'navigation', 'link', 'url'),
            'fields' => array(
                array(
                    'id' => 'menu',
                    'column' => 'col-md-6',
                    'label' => __('Menu ID', 'aurise-blocks'),
                    'input_type' => 'select',
                    'choices' => $this->menus
                ),
                array(
                    'id' => 'menu_class',
                    'column' => 'col-md-12',
                    'label' => __('Additional classes to add to the menu wrapper.', 'aurise-blocks'),
                    'example_value' => 'au-menu-footer'
                ),
                array(
                    'id' => 'depth',
                    'column' => 'col-md-6',
                    'label' => __('Maximum menu depth', 'aurise-blocks'),
                    'default_value' => '0',
                    'example_value' => '2',
                    'input_type' => 'number',
                    'input_atts' => array(
                        'min' => 0,
                        'step' => 1
                    )
                )
            )
        );

        // Frontend HTML
        add_filter($this->handle . '_render_frontend', array($this, 'render_frontend'), 10, 3);
        add_filter('shortcode_atts_' . $this->handle, array($this, 'default_atts'), 10, 3);

        // Call the parent constructor to initialise everything
        parent::__construct();
    }

    /**
     * Register Frontend Assets
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_assets()
    {
        $min = '';
        wp_register_style(
            $this->handle,
            Settings::$vars['url'] . "assets/styles/menu{$min}.css", // Source
            array(), // Dependencies
            @filemtime(Settings::$vars['path'] . "assets/styles/menu{$min}.css") // Version
        );
    }

    /**
     * Load Frontend Assets
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function load_assets()
    {
        if (!wp_style_is($this->handle)) {
            wp_enqueue_style($this->handle);
        }
    }

    /**
     * Filters shortcode attributes.
     *
     * If the third parameter of the shortcode_atts() function is present then this filter is available.
     * The third parameter, $shortcode, is the name of the shortcode.
     *
     * @since 3.6.0
     * @since 4.4.0 Added the `$shortcode` parameter.
     *
     * @param array  $out The output array of shortcode attributes.
     * @param array  $pairs The supported attributes and their defaults.
     * @param array  $atts The user defined shortcode attributes.
     */

    public function default_atts($filtered, $defaults, $input)
    {
        // Default values to use in `wp_nav_menu()`
        $menu_defaults = array(
            'menu' => '', // int|string|WP_Term Desired menu. Accepts a menu ID, slug, name, or object.
            'container' => 'nav', // Replace div with nav element (set to falsey to remove completely)
            'container_class' => 'au-menu-container', // Class in nav element
            'container_id' => '', // Same as default
            'container_aria_label' => '',
            'menu_class' => 'au-menu', // CSS class(es) to use for the ul element which forms the menu, overwrite default of `menu`
            'menu_id' => '',
            'echo' => false, // Return the menu instead of echoing it
            'fallback_cb' => false, // If the menu doesn't exist, a callback function will fire. Default is 'wp_page_menu'. Set to false for no fallback.
            'before' => '', // Text before the link markup.
            'after' => '', // Text after the link markup.
            'link_before' => '', // Text before the link text.
            'link_after' => '', // Text after the link text.
            'items_wrap' => '<ul data-id="%1$s" class="%2$s">%3$s</ul>', // Similar to default, just move id attribute to data-id attribute in case nav appears multiple times on page
            'item_spacing' => 'preserve', // Same as default
            'depth' => 0, // int How many levels of the hierarchy are to be included. 0 means all.
            'walker' => '', // No special walker
            'theme_location' => '' // No special theme location
        );
        // Loop through the menu default attributes and add them
        foreach ($menu_defaults as $key => $default_value) {
            if (array_key_exists($key, $filtered)) {
                // Combine classes with the defaults instead of overwriting them
                if (strpos($key, '_class') !== false) {
                    $default_classes = explode(' ', $default_value);
                    $input_classes = explode(' ', $input[$key]);
                    $filtered[$key] = implode(' ', array_unique(array_filter(array_merge($default_classes, $input_classes))));
                }
            } elseif (array_key_exists($key, $input)) {
                // Still add me despite the block configuration not having a default value for me for the function default needs me
                $filtered[$key] = $input[$key];
            } else {
                // Simply use the function default
                $filtered[$key] = $default_value;
            }
        }
        return $filtered;
    }

    /**
     * Filter Frontend Rendering
     *
     * @since 1.0.0
     *
     * @param string $html Optional. The current value of the frontend HTML to render.
     * @param array $atts Optional. An associative array of shortcode attributes
     * @param string $content Optional. The content between the opening and closing shortcode tags
     * @param string $tag Optional. The shortcode tag.
     *
     * @return string HTML of shortcode
     */
    public function render_frontend($html = '', $atts = array(), $content = '')
    {
        $menu = trim(sanitize_text_field(Utilities::array_has_key('menu', $atts)));
        if (!$menu) {
            return '<!-- No menu ID was specified!! -->';
        }
        return self::get($atts, false, Utilities::refresh_cache(true));
    }

    /**
     * Get Menu
     *
     * Displays a menu. Looks for cache, falls back to transient, then falls
     * back to the lookup code. All cache and transients expire in 12 hours.
     * Documentation: https://developer.wordpress.org/reference/functions/wp_nav_menu/
     *
     * @param array $params Menu parameters
     * @param bool $echo Optional. If true, will echo the content. Default is true.
     * @param bool $force_new Optional. If true, will use queries instead of cache and transients. Default is false.
     *
     * @return string HTML content of the menu.
     */
    public static function get($params = array(), $echo = true, $skip_cache = false)
    {
        $menu_id = trim(sanitize_text_field(Utilities::array_has_key('menu', $params)));
        if (!$menu_id) {
            return '';
        }
        $key = 'user-' . (function_exists('is_user_logged_in') && is_user_logged_in() ? 'user' : 'anon') . '_' . http_build_query($params);
        $type = 'both';
        $group = 'menu';
        $hours = 24; // Expire in a day
        $menu = $skip_cache ? '' : Utilities::get_cache($key, $type, $group);
        if (!$menu) {
            // $_params = array_merge(array(
            //     'menu' => '', // int|string|WP_Term Desired menu. Accepts a menu ID, slug, name, or object.
            //     'container' => false, // Remove nav container
            //     //  'container_class' => '',
            //     //  'container_id' => '',
            //     //  'container_aria_label' => '',
            //     'menu_class' => 'au-menu', // CSS class(es) to use for the ul element which forms the menu.
            //     //  'menu_id' => '',
            //     'echo' => false, // Return the menu instead of echoing it
            //     'fallback_cb' => false, // If the menu doesn't exist, a callback function will fire. Default is 'wp_page_menu'. Set to false for no fallback.
            //     //   'before' => '', // Text before the link markup.
            //     //   'after' => '', // Text after the link markup.
            //     //   'link_before' => '', // Text before the link text.
            //     //   'link_after' => '', // Text after the link text.
            //     'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>', // Default
            //     //   'item_spacing' => 'preserve',
            //     'depth' => 0, // int How many levels of the hierarchy are to be included. 0 means all.
            //     //   'walker' => '',
            //     //  'theme_location' => ''
            // ), $params);

            // if (is_string($params['menu']) && is_numeric($params['menu'])) {
            //     $params['menu'] = intval($params['menu']);
            // }

            // if (array_key_exists('depth', $params) && is_string($params['depth'])) {
            //     $params['depth'] = intval($params['depth']);
            // }
            // if (array_key_exists('items_wrap', $params)) {
            //     //$params['items_wrap'] = htmlentities($params['items_wrap'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
            //     $params['items_wrap'] =
            // }
            foreach ($params as $param => $value) {
                if (is_string($value) && !empty(trim($value))) {
                    switch ($param) {
                        case 'menu': // Ensure that the menu is an integer when it needs to be, else errors occur
                        case 'depth': // Ensure the depth is an integer, else errors occur
                            if (is_numeric($value)) {
                                $params[$param] = intval($value);
                            }
                            break;
                        case 'before':
                        case 'after':
                        case 'link_before':
                        case 'link_after':
                        case 'items_wrap':
                            // HTML passed in this param is encoded, so decode it
                            $params[$param] = html_entity_decode($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                            break;
                        default:
                            break;
                    }
                }
            }
            $menu = Utilities::set_cache($key, wp_nav_menu($params), $hours, $type, $group, '', true);
        }
        if ($echo) {
            echo ($menu);
        }
        return $menu;
    }
}
Menu::instance();
