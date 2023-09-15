<?php
/**
 * Plugin Name: WPChill Car Management
 * Plugin URI: https://wpchill.com/
 * Description: A custom plugin to manage cars with a custom post type and Gutenberg block.
 * Version: 1.0
 * Author: Abdul Rauf
 * Author URI: https://x.com/realniendoo
 * License: GPL2
 * Text Domain: wpchill-carmanagement
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Hook into the 'init' action
add_action('init', 'wpchill_initialize_plugin');

function wpchill_initialize_plugin() {
    // Registering the 'Car' custom post type
    register_post_type('wpchill_car', array(
        'labels' => array(
            'name' => __('Cars', 'wpchill-carmanagement'),
            'singular_name' => __('Car', 'wpchill-carmanagement'),
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'menu_icon' => 'dashicons-car',
    ));

    // Registering the 'Manufacturer' taxonomy
    register_taxonomy('wpchill_manufacturer', 'wpchill_car', array(
        'labels' => array(
            'name' => __('Manufacturers', 'wpchill-carmanagement'),
            'singular_name' => __('Manufacturer', 'wpchill-carmanagement'),
        ),
        'public' => true,
        'hierarchical' => true,
    ));

    // Hook to add custom meta boxes
    add_action('add_meta_boxes', 'wpchill_add_car_meta_boxes');

    function wpchill_add_car_meta_boxes() {
        add_meta_box(
            'wpchill_car_details',
            __('Car Details', 'wpchill-carmanagement'),
            'wpchill_display_car_meta_boxes',
            'wpchill_car',
            'normal',
            'high'
        );
    }

    function wpchill_display_car_meta_boxes($post) {
        // Nonce field for security
        wp_nonce_field(basename(__FILE__), 'wpchill_car_nonce');

        // Get current values if they exist
        $model = get_post_meta($post->ID, '_wpchill_car_model', true);
        $fuel_type = get_post_meta($post->ID, '_wpchill_car_fuel_type', true);
        $price = get_post_meta($post->ID, '_wpchill_car_price', true);
        $color = get_post_meta($post->ID, '_wpchill_car_color', true);

        // Display the fields
        echo '<label for="wpchill_car_model">' . __('Model', 'wpchill-carmanagement') . '</label>';
        echo '<input type="text" name="wpchill_car_model" value="' . esc_attr($model) . '"><br>';

        echo '<label for="wpchill_car_fuel_type">' . __('Fuel Type', 'wpchill-carmanagement') . '</label>';
        echo '<input type="text" name="wpchill_car_fuel_type" value="' . esc_attr($fuel_type) . '"><br>';

        echo '<label for="wpchill_car_price">' . __('Price', 'wpchill-carmanagement') . '</label>';
        echo '<input type="text" name="wpchill_car_price" value="' . esc_attr($price) . '"><br>';

        echo '<label for="wpchill_car_color">' . __('Color', 'wpchill-carmanagement') . '</label>';
        echo '<input type="text" name="wpchill_car_color" value="' . esc_attr($color) . '"><br>';
    }

    add_action('save_post', 'wpchill_save_car_meta_boxes');

    function wpchill_save_car_meta_boxes($post_id) {
        // Verify nonce
        if (!isset($_POST['wpchill_car_nonce']) || !wp_verify_nonce($_POST['wpchill_car_nonce'], basename(__FILE__))) {
            return;
        }

        // Save each custom field
        update_post_meta($post_id, '_wpchill_car_model', sanitize_text_field($_POST['wpchill_car_model']));
        update_post_meta($post_id, '_wpchill_car_fuel_type', sanitize_text_field($_POST['wpchill_car_fuel_type']));
        update_post_meta($post_id, '_wpchill_car_price', sanitize_text_field($_POST['wpchill_car_price']));
        update_post_meta($post_id, '_wpchill_car_color', sanitize_text_field($_POST['wpchill_car_color']));
    }

    add_shortcode('wpchill_car_list', 'wpchill_display_car_list');

    function wpchill_display_car_list($atts) {
        // Extract shortcode attributes
        $atts = shortcode_atts(array(
            'show_filter' => '0',
            'manufacturer' => '',
            'model' => '',
            'fuel_type' => '',
            'color' => '',
        ), $atts, 'wpchill_car_list');

        // Start output buffering
        ob_start();

        // Display content based on attributes
        // Check if the filter form should be displayed
        if ($atts['show_filter'] === '1') {
            // Display the filter form
            echo '<form method="POST" action="">';
            echo '<label>' . __('Manufacturer', 'wpchill-carmanagement') . ': </label>';
            wp_dropdown_categories(array(
                'taxonomy' => 'wpchill_manufacturer',
                'name' => 'manufacturer',
                'selected' => $atts['manufacturer'],
                'show_option_all' => __('All', 'wpchill-carmanagement'),
                'value_field' => 'slug'  
            ));
            echo '<br>';

            echo '<label>' . __('Model', 'wpchill-carmanagement') . ': </label>';
            echo '<input type="text" name="model" value="' . esc_attr($atts['model']) . '"><br>';

            echo '<label>' . __('Fuel Type', 'wpchill-carmanagement') . ': </label>';
            echo '<input type="text" name="fuel_type" value="' . esc_attr($atts['fuel_type']) . '"><br>';

            echo '<label>' . __('Color', 'wpchill-carmanagement') . ': </label>';
            echo '<input type="text" name="color" value="' . esc_attr($atts['color']) . '"><br>';

            echo '<input type="submit" value="' . __('Filter', 'wpchill-carmanagement') . '">';
            echo '</form>';
        }

        // Display the results container
        echo '<div id="car-results"></div>';

        // Return the buffered content
        return ob_get_clean();
    }

    function wpchill_filter_cars() {
        // Get the attributes from the AJAX request
        $manufacturer = $_POST['manufacturer'];
        $model = $_POST['model'];
        $fuel_type = $_POST['fuel_type'];
        $color = $_POST['color'];

        // Set up the WP_Query arguments based on the attributes
        $args = array(
            'post_type' => 'wpchill_car',
            'tax_query' => array(),
            'meta_query' => array(),
        );

        if (!empty($manufacturer) && $manufacturer !== 'all') {
            $args['tax_query'][] = array(
                'taxonomy' => 'wpchill_manufacturer',
                'field'    => 'slug',
                'terms'    => $manufacturer,
            );
        }

        if (!empty($model)) {
            $args['meta_query'][] = array(
                'key' => '_wpchill_car_model',
                'value' => $model,
                'compare' => '='
            );
        }

        if (!empty($fuel_type)) {
            $args['meta_query'][] = array(
                'key' => '_wpchill_car_fuel_type',
                'value' => $fuel_type,
                'compare' => '='
            );
        }

        if (!empty($color)) {
            $args['meta_query'][] = array(
                'key' => '_wpchill_car_color',
                'value' => $color,
                'compare' => '='
            );
        }

        $query = new WP_Query($args);

        // Loop through the cars and display them
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                echo '<div class="car-card">';
                if (has_post_thumbnail()) {
                    echo '<div class="car-image">';
                    the_post_thumbnail('medium');
                    echo '</div>';
                }
                echo '<h3>';
                the_title();
                echo '</h3>';
                echo '<p><strong>' . __('Model', 'wpchill-carmanagement') . ':</strong> ' . get_post_meta(get_the_ID(), '_wpchill_car_model', true) . '</p>';
                echo '<p><strong>' . __('Fuel Type', 'wpchill-carmanagement') . ':</strong> ' . get_post_meta(get_the_ID(), '_wpchill_car_fuel_type', true) . '</p>';
                echo '<p><strong>' . __('Price', 'wpchill-carmanagement') . ':</strong> ' . get_post_meta(get_the_ID(), '_wpchill_car_price', true) . '</p>';
                echo '<p><strong>' . __('Color', 'wpchill-carmanagement') . ':</strong> ' . get_post_meta(get_the_ID(), '_wpchill_car_color', true) . '</p>';
                echo '</div>';
            }
            wp_reset_postdata();
        } else {
            echo '<p>' . __('No cars found.', 'wpchill-carmanagement') . '</p>';
        }

        // End the AJAX function and return the results
        wp_die();
    }
    add_action('wp_ajax_filter_cars', 'wpchill_filter_cars');
    add_action('wp_ajax_nopriv_filter_cars', 'wpchill_filter_cars');

    function wpchill_enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('wpchill-ajax', plugin_dir_url(__FILE__) . 'ajax.js', array('jquery'), '1.0', true);

        // Enqueue the style.css
        wp_enqueue_style('wpchill-styles', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0', 'all');

        // Pass ajax_url to ajax.js
        wp_localize_script('wpchill-ajax', 'frontendajax', array('ajaxurl' => admin_url('admin-ajax.php')));

        // Dynamic CSS for the form and results card
   $options = get_option('wpchill_car_styling_options', array());

$form_background_color = isset($options['form_background_color']) ? $options['form_background_color'] : '#FFFFFF'; 
$form_text_color = isset($options['form_text_color']) ? $options['form_text_color'] : '#000000'; 
$form_border_color = isset($options['form_border_color']) ? $options['form_border_color'] : '#CCCCCC'; 
$form_button_color = isset($options['form_button_color']) ? $options['form_button_color'] : '#FF0000'; 
$form_button_text_color = isset($options['form_button_text_color']) ? $options['form_button_text_color'] : '#FFFFFF';
$results_card_background_color = isset($options['results_card_background_color']) ? $options['results_card_background_color'] : '#FFFFFF';
$results_card_text_color = isset($options['results_card_text_color']) ? $options['results_card_text_color'] : '#000000'; 
$results_card_border_color = isset($options['results_card_border_color']) ? $options['results_card_border_color'] : '#CCCCCC'; 

$custom_css = "
    .car-card img {
        max-width: 100%;
        height: auto;
        display: block;
    }
    form {
        background-color: $form_background_color;
        color: $form_text_color;
        border-color: $form_border_color;
    }
    input[type='submit'] {
        background-color: $form_button_color;
        color: $form_button_text_color;
    }
    .car-card {
        background-color: $results_card_background_color;
        color: $results_card_text_color;
        border-color: $results_card_border_color;
    }
";
wp_add_inline_style('wpchill-styles', $custom_css);

    }
    add_action('wp_enqueue_scripts', 'wpchill_enqueue_scripts');

    // Add settings menu
    add_action('admin_menu', 'wpchill_car_styling_menu');

    function wpchill_car_styling_menu() {
        add_options_page(
            __('Car Styling Options', 'wpchill-carmanagement'),
            __('Car Styling', 'wpchill-carmanagement'),
            'manage_options',
            'wpchill-car-styling',
            'wpchill_car_styling_options_page'
        );
    }

    function wpchill_car_styling_options_page() {
        ?>
        <div class="wrap">
            <h2><?php _e('Car Styling Options', 'wpchill-carmanagement'); ?></h2>
            <form action="options.php" method="post">
                <?php
                settings_fields('wpchill_car_styling_options');
                do_settings_sections('wpchill-car-styling');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    // Register settings
    add_action('admin_init', 'wpchill_car_styling_settings_init');

    function wpchill_car_styling_settings_init() {
        register_setting('wpchill_car_styling_options', 'wpchill_car_styling_options', 'wpchill_car_styling_options_validate');

        add_settings_section('wpchill_car_styling_main', __('Main Settings', 'wpchill-carmanagement'), 'wpchill_car_styling_section_text', 'wpchill-car-styling');

        add_settings_field('wpchill_form_background_color', __('Form Background Color', 'wpchill-carmanagement'), 'wpchill_form_background_color_callback', 'wpchill-car-styling', 'wpchill_car_styling_main');
        add_settings_field('wpchill_form_text_color', __('Form Text Color', 'wpchill-carmanagement'), 'wpchill_form_text_color_callback', 'wpchill-car-styling', 'wpchill_car_styling_main');
        add_settings_field('wpchill_form_border_color', __('Form Border Color', 'wpchill-carmanagement'), 'wpchill_form_border_color_callback', 'wpchill-car-styling', 'wpchill_car_styling_main');
        add_settings_field('wpchill_form_button_color', __('Form Button Color', 'wpchill-carmanagement'), 'wpchill_form_button_color_callback', 'wpchill-car-styling', 'wpchill_car_styling_main');
        add_settings_field('wpchill_form_button_text_color', __('Form Button Text Color', 'wpchill-carmanagement'), 'wpchill_form_button_text_color_callback', 'wpchill-car-styling', 'wpchill_car_styling_main');
        add_settings_field('wpchill_results_card_background_color', __('Results Card Background Color', 'wpchill-carmanagement'), 'wpchill_results_card_background_color_callback', 'wpchill-car-styling', 'wpchill_car_styling_main');
        add_settings_field('wpchill_results_card_text_color', __('Results Card Text Color', 'wpchill-carmanagement'), 'wpchill_results_card_text_color_callback', 'wpchill-car-styling', 'wpchill_car_styling_main');
        add_settings_field('wpchill_results_card_border_color', __('Results Card Border Color', 'wpchill-carmanagement'), 'wpchill_results_card_border_color_callback', 'wpchill-car-styling', 'wpchill_car_styling_main');
    }


   
    function wpchill_car_styling_section_text() {
        echo '<p>' . __('Please set the styling options for the car form and results card.', 'wpchill-carmanagement') . '</p>';
        echo '<p>' . __('To display the list of cars on any page or post, use the following shortcode:', 'wpchill-carmanagement') . '</p>';
        echo '<code>' . '[wpchill_car_list show_filter="1"]' . '</code>';
        echo '<p>' . __('The "show_filter" attribute can be set to "1" to display the filter form, or "0" to hide it.', 'wpchill-carmanagement') . '</p>';
    }

    function wpchill_form_background_color_callback() {
        $options = get_option('wpchill_car_styling_options');
        echo '<input id="wpchill_form_background_color" name="wpchill_car_styling_options[form_background_color]" type="color" value="' . esc_attr($options['form_background_color'] ?? '') . '" />';
    }

    function wpchill_form_text_color_callback() {
        $options = get_option('wpchill_car_styling_options');
        echo '<input id="wpchill_form_text_color" name="wpchill_car_styling_options[form_text_color]" type="color" value="' . esc_attr($options['form_text_color'] ?? '') . '" />';
    }

    function wpchill_form_border_color_callback() {
        $options = get_option('wpchill_car_styling_options');
        echo '<input id="wpchill_form_border_color" name="wpchill_car_styling_options[form_border_color]" type="color" value="' . esc_attr($options['form_border_color'] ?? '') . '" />';
    }

    function wpchill_form_button_color_callback() {
        $options = get_option('wpchill_car_styling_options');
        echo '<input id="wpchill_form_button_color" name="wpchill_car_styling_options[form_button_color]" type="color" value="' . esc_attr($options['form_button_color'] ?? '') . '" />';
    }

    function wpchill_form_button_text_color_callback() {
        $options = get_option('wpchill_car_styling_options');
        echo '<input id="wpchill_form_button_text_color" name="wpchill_car_styling_options[form_button_text_color]" type="color" value="' . esc_attr($options['form_button_text_color'] ?? '') . '" />';
    }

    function wpchill_results_card_background_color_callback() {
        $options = get_option('wpchill_car_styling_options');
        echo '<input id="wpchill_results_card_background_color" name="wpchill_car_styling_options[results_card_background_color]" type="color" value="' . esc_attr($options['results_card_background_color'] ?? '') . '" />';
    }

    function wpchill_results_card_text_color_callback() {
        $options = get_option('wpchill_car_styling_options');
        echo '<input id="wpchill_results_card_text_color" name="wpchill_car_styling_options[results_card_text_color]" type="color" value="' . esc_attr($options['results_card_text_color'] ?? '') . '" />';
    }

    function wpchill_results_card_border_color_callback() {
        $options = get_option('wpchill_car_styling_options');
        echo '<input id="wpchill_results_card_border_color" name="wpchill_car_styling_options[results_card_border_color]" type="color" value="' . esc_attr($options['results_card_border_color'] ?? '') . '" />';
    }

    function wpchill_car_styling_options_validate($input) {
        $options = get_option('wpchill_car_styling_options');
        $options['form_background_color'] = sanitize_text_field($input['form_background_color']);
        $options['form_text_color'] = sanitize_text_field($input['form_text_color']);
        $options['form_border_color'] = sanitize_text_field($input['form_border_color']);
        $options['form_button_color'] = sanitize_text_field($input['form_button_color']);
        $options['form_button_text_color'] = sanitize_text_field($input['form_button_text_color']);
        $options['results_card_background_color'] = sanitize_text_field($input['results_card_background_color']);
        $options['results_card_text_color'] = sanitize_text_field($input['results_card_text_color']);
        $options['results_card_border_color'] = sanitize_text_field($input['results_card_border_color']);
        return $options;
    }
}

// Register the block's assets for the editor
function wpchill_enqueue_block_editor_assets() {
    wp_enqueue_script(
        'wpchill-carmanagement-block',
        plugins_url('blocks/build/index.js', __FILE__),
        array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-components'),
        filemtime(plugin_dir_path(__FILE__) . 'blocks/build/index.js')
    );
}
add_action('enqueue_block_editor_assets', 'wpchill_enqueue_block_editor_assets');


// Render the block on the frontend
function wpchill_render_car_filter_block($attributes) {
    // Extract attributes
    $show_filter = isset($attributes['showFilter']) && $attributes['showFilter'] ? '1' : '0';
    $filterTitle = isset($attributes['filterTitle']) ? $attributes['filterTitle'] : 'Car Filter';
    $buttonLabel = isset($attributes['buttonLabel']) ? $attributes['buttonLabel'] : 'Filter';
    $filterStyle = isset($attributes['filterStyle']) ? $attributes['filterStyle'] : 'default';

    // Modify the shortcode or rendering function to consider these attributes
    // For this example, I'm just using the shortcode
    return wpchill_display_car_list(array(
        'show_filter' => $show_filter,
        'filter_title' => $filterTitle,
        'button_label' => $buttonLabel,
        'filter_style' => $filterStyle
    ));
}

// Register the block
function wpchill_register_car_filter_block() {
    register_block_type('wpchill-carmanagement/car-filter', array(
        'render_callback' => 'wpchill_render_car_filter_block',
        'attributes' => array(
            'showFilter' => array(
                'type' => 'boolean',
                'default' => true
            ),
            'filterTitle' => array(
                'type' => 'string',
                'default' => 'Car Filter'
            ),
            'buttonLabel' => array(
                'type' => 'string',
                'default' => 'Filter'
            ),
            'filterStyle' => array(
                'type' => 'string',
                'default' => 'default'
            )
        )
    ));
}
add_action('init', 'wpchill_register_car_filter_block');


function wpchill_register_manufacturer_taxonomy() {
    $labels = array(
        'name' => _x('Manufacturers', 'taxonomy general name'),
        'singular_name' => _x('Manufacturer', 'taxonomy singular name'),
        'search_items' => __('Search Manufacturers'),
        'all_items' => __('All Manufacturers'),
        'parent_item' => __('Parent Manufacturer'),
        'parent_item_colon' => __('Parent Manufacturer:'),
        'edit_item' => __('Edit Manufacturer'),
        'update_item' => __('Update Manufacturer'),
        'add_new_item' => __('Add New Manufacturer'),
        'new_item_name' => __('New Manufacturer Name'),
        'menu_name' => __('Manufacturers'),
    );

    register_taxonomy('wpchill_manufacturer', array('wpchill_car'), array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'manufacturer'),
    ));
}
add_action('init', 'wpchill_register_manufacturer_taxonomy');


// Generate sample data on plugin activation
// This function will run when the plugin is activated

function wpchill_generate_sample_cars() {
    // Ensure our taxonomy is registered before we use it
    wpchill_register_manufacturer_taxonomy();

    // Check if there are any cars already
    $existing_cars = get_posts(array(
        'post_type' => 'wpchill_car',
        'post_status' => 'publish',
        'numberposts' => 1
    ));

    if (!empty($existing_cars)) {
        return; // Exit the function if there are already cars
    }

    // Sample manufacturers
    $manufacturers = array('Toyota', 'Honda', 'Ford', 'Chevrolet', 'BMW', 'Audi', 'Mercedes-Benz', 'Volkswagen', 'Nissan', 'Hyundai');

    // Check if manufacturers already exist, if not, create them
    foreach ($manufacturers as $manufacturer) {
        if (!term_exists($manufacturer, 'wpchill_manufacturer')) {
            wp_insert_term($manufacturer, 'wpchill_manufacturer');
        }
    }

    // Sample data for cars
    $sample_cars = array(
        array('title' => 'Toyota Corolla', 'manufacturer' => 'Toyota', 'model' => 'Corolla', 'fuel_type' => 'Petrol', 'price' => '20000', 'color' => 'Red'),
        array('title' => 'Honda Civic', 'manufacturer' => 'Honda', 'model' => 'Civic', 'fuel_type' => 'Diesel', 'price' => '22000', 'color' => 'Blue'),
        array('title' => 'Ford Mustang', 'manufacturer' => 'Ford', 'model' => 'Mustang', 'fuel_type' => 'Petrol', 'price' => '30000', 'color' => 'Yellow'),
        array('title' => 'Chevrolet Camaro', 'manufacturer' => 'Chevrolet', 'model' => 'Camaro', 'fuel_type' => 'Petrol', 'price' => '29000', 'color' => 'Black'),
        array('title' => 'BMW M3', 'manufacturer' => 'BMW', 'model' => 'M3', 'fuel_type' => 'Diesel', 'price' => '50000', 'color' => 'White'),
        array('title' => 'Audi A4', 'manufacturer' => 'Audi', 'model' => 'A4', 'fuel_type' => 'Diesel', 'price' => '40000', 'color' => 'Silver'),
        array('title' => 'Mercedes-Benz C-Class', 'manufacturer' => 'Mercedes-Benz', 'model' => 'C-Class', 'fuel_type' => 'Petrol', 'price' => '45000', 'color' => 'Grey'),
        array('title' => 'Volkswagen Golf', 'manufacturer' => 'Volkswagen', 'model' => 'Golf', 'fuel_type' => 'Diesel', 'price' => '21000', 'color' => 'Green'),
        array('title' => 'Nissan Altima', 'manufacturer' => 'Nissan', 'model' => 'Altima', 'fuel_type' => 'Petrol', 'price' => '23000', 'color' => 'Blue'),
        array('title' => 'Hyundai Elantra', 'manufacturer' => 'Hyundai', 'model' => 'Elantra', 'fuel_type' => 'Petrol', 'price' => '19000', 'color' => 'Red')
    );

    foreach($sample_cars as $car) {
        // Create a new car post
        $car_id = wp_insert_post(array(
            'post_title' => $car['title'],
            'post_type' => 'wpchill_car',
            'post_status' => 'publish'
        ));

        // Set the manufacturer taxonomy
        wp_set_object_terms($car_id, $car['manufacturer'], 'wpchill_manufacturer');

        // Add custom meta data for the car
        update_post_meta($car_id, '_wpchill_car_model', $car['model']);
        update_post_meta($car_id, '_wpchill_car_fuel_type', $car['fuel_type']);
        update_post_meta($car_id, '_wpchill_car_price', $car['price']);
        update_post_meta($car_id, '_wpchill_car_color', $car['color']);
    }
}

// Register the activation hook
register_activation_hook(__FILE__, 'wpchill_generate_sample_cars');
