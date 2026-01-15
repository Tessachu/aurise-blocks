<?php

namespace AuRise\Plugin\Blocks;

defined('ABSPATH') || exit; // Exit if accessed directly.

use AuRise\Plugin\Blocks\Settings;
use AuRise\Plugin\Blocks\Utilities;

/**
 * Class AuRise Copyright Shortcode and Block
 *
 * @package AuRise\Plugin\Blocks
 */
class Copyright extends Block
{

    private $menus = null;

    /**
     * The single instance of the class
     *
     * @var Copyright
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
     * @return Copyright instance.
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

        $this->name = __('Copyright', 'aurise-blocks'); // Name of block
        $this->tag = 'copyright'; // Tag of block
        $this->handle = sanitize_key(self::$prefix . $this->tag);
        $this->atts = array(
            // Block Settings
            'icon' => 'businesswoman', // https://developer.wordpress.org/resource/dashicons/#businesswoman
            'description' => __('Display website copyright information.', 'aurise-blocks'),
            'keywords' => array('copyright', 'author', 'year', 'site'),
            'fields' => apply_filters($this->handle . '_atts', array(
                array(
                    'id' => 'menu_id',
                    'column' => 'col-md-6',
                    'label' => __('Menu ID (int)', 'aurise-blocks'),
                    'input_type' => 'select',
                    'choices' => $this->menus
                ),
                array(
                    'id' => 'public_login',
                    'column' => 'col-md-6',
                    'label' => __('Include User Account Links in Menu', 'aurise-blocks'),
                    'input_type' => 'checkbox',
                    'example_value' => 'on'
                )
            ))
        );

        // Call the parent constructor to initialise everything
        parent::__construct();

        // Frontend Assets
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        add_action($this->handle . '_frontend_assets', array($this, 'load_assets'));

        // Frontend HTML
        add_filter($this->handle . '_render_frontend', array($this, 'render_frontend'), 10, 3);
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
            Settings::$vars['url'] . "assets/styles/copyright{$min}.css", // Source
            array(), // Dependencies
            @filemtime(Settings::$vars['path'] . "assets/styles/copyright{$min}.css") // Version
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
        if (!wp_style_is($this->handle, 'enqueued')) {
            wp_enqueue_style($this->handle);
        }
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
        $atts = shortcode_atts(array(
            'menu_id' => '',
            'public_login' => ''
        ), array_change_key_case($atts, CASE_LOWER), $this->handle);

        // Get business information and author credit
        // if (function_exists('au_get_business')) {
        //     $b = au_get_business();
        //     $site_name = $b['name'];
        //     $site_url = $b['url'];
        //     $copyright_year = $b['copyright_year'];
        //     // if ($b['name'] == 'AuRise Creative') {
        //     //     $before_credit_link = '';
        //     //     $credit_link_label = 'Website created with <span title="love"><i class="fa-solid fa-heart"></i></span> and <span title="coffee"><i class="fa-solid fa-mug-hot"></i></span> by moi';
        //     //     $after_credit_link = '!';
        //     // } else {
        //     //     $before_credit_link = __('Website by', 'aurise-blocks') . '&nbsp;';
        //     //     $credit_link_label = __('AuRise Creative', 'aurise-blocks');
        //     //     $after_credit_link = '.';
        //     // }
        // } else {
        //     $site_name = get_bloginfo();
        //     $site_url = home_url();
        //     $copyright_year = date('Y');
        // }
        $site_url = apply_filters($this->handle . '_siteurl', home_url());
        $site_credit = apply_filters($this->handle . '_credit', sprintf(
            __('Website by %s.', 'aurise-blocks'),
            sprintf(
                '<a class="theme-author-link" href="%s" target="_blank" rel="noopener">%s</a>',
                esc_url(apply_filters($this->handle . '_credit_url', sanitize_url(sprintf(
                    apply_filters($this->handle . '_credit_site', 'https://aurisecreative.com/') . '?utm_source=%s&utm_medium=website&utm_campaign=site-credit-referral&utm_content=%s',
                    urlencode(strtolower(wp_parse_url($site_url, PHP_URL_HOST))),
                    urlencode(get_the_permalink())
                )))),
                esc_html(apply_filters($this->handle . '_credit_name', __('AuRise Creative', 'aurise-blocks')))
            )
        ));

        // Get menu, if applicable
        $menu_id = apply_filters($this->handle . '_menu_id', intval(sanitize_text_field($atts['menu_id'])));
        $menu_html = '';
        $menu_args = apply_filters($this->handle . '_menu_args', array(
            'menu' => $menu_id,
            //'container' => 0, // Remove container
            'container_aria_label' => apply_filters($this->handle . '_menu_label', 'Footer'),
            'menu_class' => 'au-menu au-footer-menu au-copyright-menu',
            'depth' => 1,
        ));
        if ($menu_id) {
            $menu_html .= do_shortcode(sprintf(
                '[au_menu %s]',
                Utilities::format_atts($menu_args)
            ));
        }

        // Add public login/logout links, if applicable
        if (boolval(trim(sanitize_text_field($atts['public_login'])))) {
            if (is_user_logged_in()) {
                // Log out link
                $link = sanitize_url(apply_filters($this->handle . '_logout_link', home_url('wp-login.php?action=logout')));
                $label = apply_filters($this->handle . '_logout_text', __('Logout', 'aurise-blocks'));
            } else {
                $link = sanitize_url(apply_filters($this->handle . '_login_link', home_url('/wp-login.php?redirect_to=' . urlencode(home_url('wp-admin/')))));
                $label = apply_filters($this->handle . '_login_text', __('Login', 'aurise-blocks'));
            }
            $link = apply_filters($this->handle . '_account_link', sprintf(
                '<li class="menu-item menu-item-type-custom au-menu-item"><a href="%s" rel="%s" target="%s">%s</a></li>',
                esc_url($link),
                esc_attr(apply_filters($this->handle . '_account_rel', 'nofollow', $link, $label)),
                esc_attr(apply_filters($this->handle . '_account_target', '_self', $link, $label)),
                esc_html($label)
            ));
            if ($menu_html) {
                $end = '</ul></nav>';
                $p = strrpos($menu_html, $end);
                if ($p !== false) {
                    // Insert before the closing tags
                    $menu_html = substr_replace($menu_html, $link, $p, strlen($end));
                } else {
                    // Append to the end
                    $menu_html .= $link;
                }
            } else {
                // Create the entire menu markup
                $menu_html = sprintf(
                    '<nav class="au-menu-container"><ul class="%s">%s</ul></nav>',
                    esc_attr($menu_args['menu_class']),
                    $link
                );
            }
        }
        $menu_html = apply_filters($this->handle . '_menu_html', $menu_html, $menu_id, $menu_args);

        // Render the output HTML
        return sprintf(
            '<div class="au-copyright">%s<p>&copy; %s <a href="%s">%s</a>. %s</p>%s<p class="site-credit">%s</p>%s</div>',
            wp_kses_post(apply_filters($this->handle . '_before', '', $atts)),
            esc_html(apply_filters($this->handle . '_year', date('Y'), $atts)),
            esc_url($site_url),
            esc_html(apply_filters($this->handle . '_sitename', get_bloginfo('name', 'display'), $atts)),
            esc_html(apply_filters($this->handle . '_rights', __('All rights reserved.', 'aurise-blocks'), $atts)),
            $menu_html ? wp_kses_post($menu_html) : '', // Optional menu
            wp_kses($site_credit, array(
                'span' => array('class' => true, 'title' => true),
                'a' => array('class' => true, 'href' => true, 'target' => array('_blank'), 'rel' => true, 'title' => true),
                'i' => array('class' => true, 'aria-hidden' => true),
            ), array('https')),
            wp_kses_post(apply_filters($this->handle . '_after', '', $atts)),
        );
    }
}
Copyright::instance();
