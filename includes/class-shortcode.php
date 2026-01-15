<?php

namespace AuRise\Plugin\Blocks;

defined('ABSPATH') || exit; // Exit if accessed directly.

use AuRise\Plugin\Blocks\Utilities;

/**
 * Shortcode Base Class
 *
 * @package AuRise\Plugin\Blocks
 */
abstract class Shortcode
{
    protected static $prefix = 'au_';

    protected static $layout_css = 'au-pseudo-bootstrap';

    /**
     * Is Block
     *
     * True if this class is extended by the Block class. False otherwise.
     *
     * @var bool $is_block
     */
    protected $is_block = false;

    /**
     * Shortcode Tag
     *
     * The shortcode tag without a prefix.
     *
     * @var string $tag
     */
    protected $tag;

    /**
     * Prefixed Shortcode Tag & Asset Handles
     *
     * The shortcode tag used with the static `$prefix`.
     *
     * @var string $handle
     */
    protected $handle;

    /**
     * Absolute path to the shortcode file or directory.
     *
     * @var string $path
     */
    protected $path;

    /**
     * Absolute url to the shortcode file or directory.
     *
     * @var string $url
     */
    protected $url;

    /**
     * Shortcode Attributes
     *
     * Shortcode attributes or block settings.
     *
     * @var array $atts
     */
    protected $atts;

    /**
     * Constructor
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct()
    {
        add_action('init', array($this, 'init_shortcode'));
    }

    /**
     * Initialise
     *
     * Hooked action on `init`, this function initializes the shortcode.
     *
     * @since 1.0.0
     *
     * @hook `init`
     *
     * @return void
     */
    public function init_shortcode()
    {
        add_shortcode($this->handle, array($this, 'render_shortcode'));
    }

    /**
     * Render Shortcode
     *
     * @since 1.0.0
     *
     * @param array $atts Optional. An associative array of shortcode attributes
     * @param string $content Optional. The content between the opening and closing shortcode tags
     * @param string $tag Optional. The shortcode tag.
     *
     * @return string HTML of shortcode
     */
    public function render_shortcode($atts = array(), $content = '', $tag = '')
    {
        do_action($this->handle . '_frontend_assets');
        $atts = array_change_key_case($atts, CASE_LOWER);
        $values = array();
        if (is_array($this->atts) && count($this->atts)) {
            // Field attributes were defined, merge them with the passed values
            if ($this->is_block) {
                // Blocks use this for their block settings
                $fields = $this->atts['fields'];
            } else {
                // Simple shortcodes use this just for their attributes
                $fields = $this->atts;
            }
            foreach ($fields as $field) {
                $field_id = strtolower($field['id']);
                //$default_values[$field_id] = Utilities::array_has_key('default_value', $field);
                $values[$field_id] = Utilities::array_has_key(
                    $field_id,
                    $atts,
                    Utilities::array_has_key('default_value', $field)
                );
            }
        } else {
            // No fields were created, so just set it to the attributes list
            $values = $atts;
        }
        return (string)apply_filters($this->handle . '_render_frontend', '', $values, $content);
    }
}
