<?php

namespace AuRise\Plugin\Blocks;

defined('ABSPATH') || exit; // Exit if accessed directly.

use AuRise\Plugin\Blocks\Settings;
use AuRise\Plugin\Blocks\Utilities;

/**
 * Class AuRise Block Base
 *
 * @package AuRise\Plugin\Blocks
 */
abstract class Block extends Shortcode
{
    /**
     * Name of Block
     *
     * @var string $name
     */
    protected $name;

    /**
     * Namespace of Block
     *
     * @var string
     */
    private $namespace = null;

    /**
     * Constructor
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct()
    {
        if (is_null($this->namespace)) {
            $this->namespace = 'aurise/' . str_replace('_', '-', $this->tag);
        }
        $this->is_block = true;
        $block_js = Settings::$vars['path'] . 'assets/scripts/block-' . $this->tag . '.js';
        if (!file_exists($block_js)) {
            $this->create_block_js($block_js, $this->atts);
        } else {
            $ts1 = @filemtime(__FILE__);
            $ts2 = @filemtime(Settings::$vars['path'] . 'includes/blocks/' . $this->tag . '.php');
            $ts3 = Settings::get($this->tag . '_block_js', 'internal');
            if (!$ts1 || !$ts2 || !is_array($ts3) || count($ts3) !== 2 || $ts1 !== $ts3[0] || $ts2 !== $ts3[1]) {
                $this->create_block_js($block_js, $this->atts);
            }
        }
        // Call the parent constructor to initialise shortcode
        //parent::__construct();
        $this->init_shortcode(); // Now that this construct is being called in do_action('init'), add shortcode directly too from Shortcode class

        //add_action('init', array($this, 'init_block'), 11);
        $this->init_block(); // Now that this construct is being called in do_action('init'), call it directly

        if (is_admin()) {
            add_action('enqueue_block_editor_assets', array($this, 'init_block_editor'));
            add_action('admin_enqueue_scripts', array($this, 'load_editor_assets'));
            add_filter('block_categories_all', array($this, 'add_category'));
        }
    }

    /**
     * Add Block Category
     *
     * @param array $block_categories A sequential array of associative arrays with `slug` and `title` keys for each block category.
     *
     * @return array Filtered block categories.
     */
    public function add_category($block_categories)
    {
        if (in_array('aurise', array_column($block_categories, 'slug'))) {
            return $block_categories;
        }

        // Add my category to the beginning
        array_unshift($block_categories, array(
            'slug'  => 'aurise',
            'title' => __('AuRise Creative', 'aurise'),
            //'icon'  => 'superhero-alt', //https://developer.wordpress.org/resource/dashicons/#superhero-alt
        ));
        // $block_categories[] = array(
        //     'slug'  => 'aurise',
        //     'title' => __('AuRise Creative', 'aurise'),
        //     //'icon'  => 'superhero-alt', //https://developer.wordpress.org/resource/dashicons/#superhero-alt
        // );
        //au_debug_log($block_categories, 'Filtering Block Categories');
        return $block_categories;
    }

    /**
     * Initialise
     *
     * Hooked action on `init`, this function initializes the shortcode(s) and image sizes for this platform.
     *
     * @since 1.0.0
     *
     * @hook `init`
     *
     * @return void
     */
    public function init_block()
    {
        if (is_admin()) {
            //Allow language translations in JS
            wp_set_script_translations($this->handle, 'aurise-blocks');
        }

        // Register the block
        register_block_type($this->namespace, array(
            'api_version' => 2,
            'editor_script' => $this->handle,
            'render_callback' => array($this, 'render_block')
        ));
    }

    /**
     * Initialise Block Editor in WP Admin
     *
     * @hook `enqueue_block_editor_assets`
     *
     * @return void
     */
    public function init_block_editor()
    {
        wp_enqueue_script(
            $this->handle, //Handle
            Settings::$vars['url'] . 'assets/scripts/block-' . $this->tag .  '.js', // Full URL of the script, or path of the script relative to the WordPress root directory
            array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-editor'), // Dependencies
            @filemtime(Settings::$vars['path'] . 'assets/scripts/block-' . $this->tag .  '.js'), //version
            array('in_footer' => true) //Add to footer
        );
        do_action($this->handle . '_init_block');
    }

    /**
     * Display Block on Frontend
     *
     * @since 1.0.0
     *
     * @param array $atts Optional. An associative array of block attributes
     * @param string $content Optional. The block content
     *
     * @return string HTML output of shortcode.
     */
    public function render_block($atts = array(), $content = '')
    {
        do_action($this->handle . '_block');
        return sprintf(
            '[%1$s %2$s]%3$s[/%1$s]',
            $this->handle,
            Utilities::format_atts((array)apply_filters($this->handle . '_block', $atts)),
            (string)apply_filters($this->handle . '_block_content', $content)
        );
    }

    public function load_editor_assets($hook)
    {
        if (!wp_style_is(self::$layout_css, 'enqueued') && !wp_style_is(self::$layout_css, 'registered')) {
            wp_register_style(
                self::$layout_css,
                Settings::$vars['url'] . 'assets/styles/pseudo-bootstrap.css',
                array(), //Dependencies
                @filemtime(Settings::$vars['path'] . 'assets/styles/pseudo-bootstrap.css'), //Version
            );
        }

        //Load only on the new/edit pages
        if ($hook == 'post-new.php' || $hook == 'post.php') {

            // Editor (Block) Stylesheet
            $handle = Settings::$vars['slug'];
            if (!wp_style_is($handle, 'enqueued')) {
                wp_enqueue_style(
                    $handle, // Handle
                    Settings::$vars['url'] . 'assets/styles/admin-editor.css', // URL of source file
                    array(self::$layout_css), // Dependencies
                    @filemtime(Settings::$vars['path'] . 'assets/styles/admin-editor.css'), // Version
                );
            }
        }
    }

    /**
     * Create Block JavaScript File
     *
     * @param string $filepath Filepath.
     * @param array $settings Block settings.
     *
     * @return void
     */
    private function create_block_js($filepath, $settings)
    {
        $settings = shortcode_atts(
            (array)apply_filters('aurise_block_settings', array(
                'apiVersion' => 2,
                'title' => $this->name,
                'icon' => '',
                'description' => '',
                'category' => 'aurise', // [ text | media | design | widgets | theme | embed ]
                'keywords' => array(),
                'fields' => array()
            ), $this->tag),
            $settings,
            'aurise_block_settings'
        );

        $minify = !defined('WP_DEBUG') || !WP_DEBUG;
        // $minify = true;
        $eol = $minify ? '' : PHP_EOL;

        // Start with self initialising script
        $content = sprintf(
            'console.log("%2$s");%1$s((blocks,element,blockEditor,data)=>{%1$sconst { __ }=window.wp.i18n;%1$sconst {registerBlockType}=blocks;%1$sconst el=element.createElement;%1$sconst useBlockProps=blockEditor.useBlockProps;%1$sconst InnerBlocks=blockEditor.InnerBlocks;%1$sconst useInnerBlocksProps=blockEditor.useInnerBlocksProps;%1$sconst useSelect=data.useSelect;%1$s',
            $eol,
            esc_html(sprintf(
                __('%s block editor script loaded.', 'aurise-blocks'),
                $this->name
            ))
        );

        $keywords = array_unique(array_filter(array_merge(array(
            'aurise',
            'orise',
            'arise',
            'tessa'
        ), (array)$settings['keywords'])));

        // Block registration
        $content .= sprintf(
            'registerBlockType(%1$s"%2$s",%1$s{%1$sapiVersion: %3$s,%1$stitle:"%4$s",%1$sicon:"%5$s",%1$sdescription:"%6$s",%1$scategory:"%7$s",%1$skeywords:%8$s,',
            $eol,
            esc_attr($this->namespace), // 1
            esc_attr($settings['apiVersion']),
            esc_html($settings['title']),
            esc_attr($settings['icon']),
            esc_attr($settings['description']),
            esc_attr($settings['category']),
            json_encode(array_values($keywords))
        );

        $attributes = array();
        $examples = array();
        $update = array();
        $editor = array();

        if (is_array($settings['fields'])) {
            $attribute_config = array(
                'id' => '',
                'variable_type' => 'string',
                'default_value' => '',
                'column' => 'col-md-12',
                'label' => '',
                'input_type' => 'text'
            );
            foreach ($settings['fields'] as $field) {
                if (!is_array($field)) continue;

                $field = array_merge($attribute_config, $field);

                $id = trim(sanitize_key($field['id']));
                $input_type = trim(sanitize_key($field['input_type']));

                if (!$id) continue;

                // Attribute Configuration
                $attributes[$id] = array(
                    'type' => trim(sanitize_key($field['variable_type'])),
                    'default' => $field['default_value']
                );

                // Example Configuration
                if (array_key_exists('example_value', $field)) {
                    $examples[$id] = sanitize_text_field($field['example_value']);
                }

                // Attribute update function
                $update[] = sprintf(
                    'function update_%s(event){%s}',
                    $id,
                    $input_type == 'checkbox' ?
                        sprintf('if(event.target.checked){props.setAttributes({%1$s:"on"})}else{props.setAttributes({%1$s:""})}', $id) :
                        sprintf('props.setAttributes({%s:event.target.value})', $id)
                );

                // if ($input_type == 'select') {

                //     $update[] = sprintf('function choices_%s(event){}', '');
                // }

                $input_atts = Utilities::array_has_key('input_atts', $field);
                if (is_array($input_atts) && count($input_atts)) {
                    $input_atts = ',' . Utilities::format_atts($input_atts, '', ',', ':');
                } else {
                    $input_atts = '';
                }

                // Attribute Editor
                if ($input_type == 'checkbox' || $input_type == 'radio') {
                    $editor[] = $eol . sprintf(
                        'el(
                           "label",{className:"au-input-field col-xs-12 %3$s"},
                            el("input", {
                                type: "%2$s",
                                value: "%1$s",
                                checked: props.attributes.%1$s ? "checked" : "",
                                onChange: update_%1$s%5$s
                            }),
                            el("span",{className: "au-input-label"},"%4$s"),
                         )',
                        esc_attr($id), // 1
                        esc_attr($input_type), // 2
                        esc_attr(trim(sanitize_html_class($field['column']))), // 3
                        esc_attr(htmlentities($field['label'], ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8', false)), // 4
                        $input_atts // 5
                    ) . ($minify ? '' : ' // Close form element' . PHP_EOL);
                } elseif ($input_type == 'textarea') {
                    $editor[] = $eol . sprintf(
                        'el(
                            "label",{className:"au-input-field col-xs-12 %3$s"},
                            el("span",{className: "au-input-label"},"%4$s"),
                            el("%2$s", {
                                value: props.attributes.%1$s,
                                onChange: update_%1$s%5$s
                            })
                         )',
                        esc_attr($id),
                        esc_attr($input_type),
                        esc_attr(trim(sanitize_html_class($field['column']))),
                        esc_attr(htmlentities($field['label'], ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8', false)), // 4
                        $input_atts // 5
                    ) . ($minify ? '' : ' // Close form element' . PHP_EOL);
                } elseif ($input_type == 'select') {
                    $choices = Utilities::array_has_key('choices', $field, array());
                    $formatted_choices = array();
                    foreach ($choices as $key => $value) {
                        $formatted_choices[] = sprintf(
                            'el("option",{value:"%s"},"%s")',
                            esc_attr($key),
                            esc_attr($value)
                        );
                    }
                    $editor[] = $eol . sprintf(
                        'el(
                            "label",{className:"au-input-field col-xs-12 %3$s"},
                            el("span",{className: "au-input-label"},"%4$s"),
                            el(
                                "%2$s",
                                {
                                    value: props.attributes.%1$s,
                                    onChange: update_%1$s%5$s
                                },
                                %6$s
                            )
)',
                        esc_attr($id),
                        esc_attr($input_type),
                        esc_attr(trim(sanitize_html_class($field['column']))),
                        esc_attr(htmlentities($field['label'], ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8', false)), // 4
                        $input_atts, // 5
                        $eol . implode(',' . $eol, $formatted_choices) . $eol
                    ) . ($minify ? '' : ' // Close form element' . PHP_EOL);
                } else {
                    $editor[] = $eol . sprintf(
                        'el(
                            "label",{className:"au-input-field col-xs-12 %3$s"},
                            el("span",{className: "au-input-label"},"%4$s"),
                            el("input", {
                                type: "%2$s",
                                value: props.attributes.%1$s,
                                onChange: update_%1$s%5$s
                            })
                       )',
                        esc_attr($id),
                        esc_attr($input_type),
                        esc_attr(trim(sanitize_html_class($field['column']))),
                        esc_attr(htmlentities($field['label'], ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8', false)), // 4
                        $input_atts // 5
                    ) . ($minify ? '' : ' // Close form element' . PHP_EOL);
                }
            }
        }

        // Block Settings
        if (!$minify) {
            $content .= PHP_EOL . PHP_EOL . '// Block Attributes' . PHP_EOL;
        }
        $content .= sprintf('attributes:%s,', json_encode($attributes, $minify ? 0 : JSON_PRETTY_PRINT));

        // Example Configuration
        if (count($examples)) {
            if (!$minify) {
                $content .= PHP_EOL . PHP_EOL . '// Examples Configuration' . PHP_EOL;
            }
            $content .= sprintf('example:{attributes:%s},', json_encode($examples, $minify ? 0 : JSON_PRETTY_PRINT));
        }

        // Edit Update functions
        if (!$minify) {
            $content .= PHP_EOL . PHP_EOL . '// Edit Update Functions' . PHP_EOL;
        }
        $content .= 'edit:function(props,setAttributes,className){' . $eol  . implode($eol, $update);

        // Editor Output
        if (!$minify) {
            $content .= PHP_EOL . PHP_EOL . '// START Block Editor' . PHP_EOL;
        }
        $content .= sprintf(
            'let blockProps=useBlockProps(),%1$sinnerBlockProps=useInnerBlocksProps(blockProps),%1$soutput=el("div",%1$sblockProps,%1$sel("div",%1$s{className:"au-block-editor %2$s"},%1$sel("h3",null,"%3$s"),%1$s',
            $eol,
            esc_attr($this->handle),
            esc_html($settings['title'])
        );
        $content .= 'el("div",{ className: "au-row" },'; // Open row
        if (!$minify) {
            $content .= PHP_EOL . PHP_EOL . '// START Block Editor Fields' . PHP_EOL;
        }
        $content .= implode(',' . $eol, $editor);
        if (!$minify) {
            $content .= PHP_EOL . '// END Block Editor Fields' . PHP_EOL . PHP_EOL;
        }
        $content .= ')' . ($minify ? '' : '// Close row element' . PHP_EOL); // Close row
        $content .= ')' . ($minify ? '' : '// Close wrapping element' . PHP_EOL); // Close row
        $content .= ');' . ($minify ? '' : '// Close outer element element and variables' . PHP_EOL); // Close row
        $content .= 'return output;},'; // Close editor output
        if (!$minify) {
            $content .= PHP_EOL . '// END Block Editor' . PHP_EOL . PHP_EOL;
        }


        // Block Save
        if (!$minify) {
            $content .= PHP_EOL . PHP_EOL . '// Save Block' . PHP_EOL;
        }
        $content .= 'save:function(props){' . $eol . 'return InnerBlocks.Content;' . $eol . '}';

        // Close Script
        if (!$minify) {
            $content .= PHP_EOL . PHP_EOL;
        }
        $content .= sprintf('}' . $eol . ');' . $eol . '})%1$s(window.wp.blocks,window.wp.element,window.wp.blockEditor,window.wp.data);', $eol);

        // Write block to file
        if (file_put_contents($filepath, $content) !== false) {
            Settings::set($this->tag . '_block_js', array(
                @filemtime(__FILE__),
                @filemtime(Settings::$vars['path'] . 'includes/blocks/' . $this->tag . '.php')
            ));
        }
    }
}
