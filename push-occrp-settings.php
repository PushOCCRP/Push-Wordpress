<?php


require 'push-occrp-helpers.php';

class PushMobileAppSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );                    
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            'Push Mobile App', 
            'manage_options', 
            'push-mobile-app-admin', 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'disabled_categories' );
        ?>
        <div class="wrap">
            <h1>Push Mobile App</h1>
            <?php
                // WPML: if the "all languages" choice is currently chosen, don't put any settings in.
                // This will be added at some point, but right now it's a lot of extra work
                if ( function_exists('icl_object_id') && apply_filters( 'wpml_current_language', NULL ) == 'all') {
                    ?>
                    Push mobile app settings not available if 'All Languages' is chosen. Please change to a single language on the menu at the top of the escreen to edit the settings.
                    <?php
                    return;
                } else {
            ?>
                    <form method="post" action="options.php">
                    <?php
                        // This prints out all hidden setting fields
                        settings_fields( 'push_app_mobile_categories_option_group' );
                        do_settings_sections( 'push-mobile-app-admin' );
                        submit_button();
                    ?>
                    </form>
            <?php
                } 
            ?>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {       
         
        register_setting(
            'push_app_mobile_categories_option_group', // Option group
            'push_app_option_name', // Option name
            array( $this, 'validate_sorting' ) // Sanitize
        );

        register_setting(
            'push_app_mobile_categories_option_group', // Option group
            'push_app_disabled_categories', // Option name
            array( $this, 'validate_categories' ) // Sanitize
        );

        register_setting(
            'push_app_mobile_categories_option_group', // Option group
            'push_app_disabled_post_types', // Option name
            array( $this, 'validate_post_types' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'Available Post Types and Categories', // Title
            array( $this, 'print_section_info' ), // Callback
            'push-mobile-app-admin' // Page
        );  

        add_settings_field(
            'categories', 
            'Categories To Disable', 
            array( $this, 'categories_callback' ), 
            'push-mobile-app-admin', 
            'setting_section_id'
        );      
   
        if(count(PushMobileAppHelpers::post_types()) > 0){
            add_settings_field(
                'post_types', 
                'Post Types', 
                array( $this, 'post_types_callback' ), 
                'push-mobile-app-admin', 
                'setting_section_id'
            );     

            add_settings_field(
                'sort_stories_by',
                'Sort Stories By',
                array( $this, 'sort_stories_by_callback' ), 
                'push-mobile-app-admin', 
                'setting_section_id'
            );     
        }
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['id_number'] ) )
            $new_input['id_number'] = absint( $input['id_number'] );

        if( isset( $input['title'] ) )
            $new_input['title'] = sanitize_text_field( $input['title'] );

        return $new_input;
    }

    /**
     * Ensure categories are showing up right???
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function validate_categories( $input )
    {
        // We make a copy of the input so that we can sanitize everything.
        $input = array_map(function($value){
            sanitize_text_field($value);
        }, $input);

        $disabled_categories = get_option('push_app_disabled_categories');

        if ( function_exists('icl_object_id')){
            $current_lang = apply_filters( 'wpml_current_language', NULL );
        
             // Validation should run against on all the inputs
            if(is_array($disabled_categories) === false){
                $disabled_categories = array();            
            }
        
            $disabled_categories[$current_lang] = $input;
        }
        
        return $disabled_categories;
    }

    /**
     * Ensure post types are showing up right???
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function validate_post_types( $input )
    {
        // We make a copy of the input so that we can sanitize everything.
        $input = array_map(function($value){
            sanitize_text_field($value);
        }, $input);

        $disabled_post_types = get_option('push_app_disabled_post_types');
        
        if ( function_exists('icl_object_id')){
            $current_lang = apply_filters( 'wpml_current_language', NULL );
            
            // Validation should run against on all the inputs
            if(is_array($disabled_post_types) === false){
                $disabled_post_types = array();            
            }
            
            $disabled_post_types[$current_lang] = $input;
            return $disabled_post_types;
        }

        // Validation should run against on all the inputs
        return $disabled_post_types;
    }

    /**
     * Validate post sorting, whether categories or post_types
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function validate_sorting( $input )
    {
        $input = sanitize_text_field($input);
        $sorting_type = get_option('sorting_type');

        // Validation
        $valid_options = ['categories', 'post_types'];
        if(!array_search($input, $valid_options)){
            return $sorting_type;
        }

        if ( function_exists('icl_object_id')){        
            $current_lang = apply_filters( 'wpml_current_language', NULL );
            
            if(is_array($sorting_type) === false){
                $sorting_type = array();            
            }
            
            $sorting_type[$current_lang] = $input;
        }

        // Validation should run against on all the inputs
        return $sorting_type;
    }


    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        if ( function_exists('icl_object_id') ) {
            $languages = $languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=id&order=desc' );
            $current_lang = apply_filters( 'wpml_current_language', NULL );
        
            if(!empty($languages) && $current_lang != null && array_key_exists($current_lang, $languages)){
                $language = $languages[$current_lang];
                $translated_name = apply_filters( 'wpml_translated_language_name', NULL, $language['code'], 'en' );
                print "Settings for " . $language['native_name'] . " (" . $translated_name . ")";
            }
        }
    }

    /** 
     * Get the categories option array and display check boxes for each
     */
    public function categories_callback()
    {
        printf('Please check all categories to <strong>not</strong> include in responses.<br><br>');

        $categories = PushMobileAppHelpers::categories();
        $disabled_categories = get_option('push_app_disabled_categories');

        // WPML Languages
        if( function_exists('icl_object_id') ) {
            $current_lang = apply_filters( 'wpml_current_language', NULL );

            if(!is_array($disabled_categories)){
                $disabled_categories = array();
            }

            if(!array_key_exists($current_lang, $disabled_categories)){
                $disabled_categories[$current_lang] = array();
            }

            $disabled_categories = $disabled_categories[$current_lang];
        }

        foreach($categories as $category){
            $checked = "";
            
            if(isset($disabled_categories) && $disabled_categories != false && array_key_exists($category->term_id, $disabled_categories)){
                $checked = "checked";
            }
            printf('<label>%s</label>&nbsp;&nbsp;', $category->name);
            printf(
                '<input type="checkbox" id="title" name="push_app_disabled_categories[%s]" %s>',
                     $category->term_id,
                     $checked
            );
            printf('<br><br>');
        }
    }

    /** 
     * Get the post_types option array and display check boxes for each
     */
    public function post_types_callback()
    {
        printf('Please check all post types to <strong>not</strong> include in responses.<br><br>');
        $post_types = PushMobileAppHelpers::post_types();

        $disabled_post_types = get_option('push_app_disabled_post_types');

        // WPML Languages
        if( function_exists('icl_object_id') ) {
            $current_lang = apply_filters( 'wpml_current_language', NULL );

            if(!is_array($disabled_post_types)){
                $disabled_post_types = array();
            }

            if(!array_key_exists($current_lang, $disabled_post_types)){
                $disabled_post_types[$current_lang] = array();
            }

            $disabled_post_types = $disabled_post_types[$current_lang];
        }

        foreach($post_types as $post_type){
            $checked = "";
            if(isset($disabled_post_types) && $disabled_post_types != false && array_key_exists($post_type, $disabled_post_types)){
                $checked = "checked";
            }

            printf('<label>%s</label>&nbsp;&nbsp;', $post_type);
            printf(
                '<input type="checkbox" id="title" name="push_app_disabled_post_types[%s]" %s>',
                     $post_type,
                     $checked
            );
            printf('<br><br>');
        }
    }

    public function sort_stories_by_callback()
    {
        $option = get_option('push_app_option_name');
        if(function_exists('icl_object_id')) {
            $current_lang = apply_filters( 'wpml_current_language', NULL );

            if(!is_array($option)){
                $option = array();
            }

            if(!array_key_exists($current_lang, $option)){
                $option[$current_lang] = array();
            }
            
            $option = $option[$current_lang];
        }
        
        if(count($option) == 0){
            $option = '';
        }

        printf('<select name="push_app_option_name">');
        if($option == 'categories'){
            printf('<option id="title" name="push_app_option_name" value="categories" selected>Categories</option>');
        } else {
            printf('<option id="title" name="push_app_option_name" value="categories">Categories</option>');

        }

        if($option == "post_types"){
            printf('<option id="title" name="push_app_option_name" value="post_types" selected>Post Types</option>');
        } else {
            printf('<option id="title" name="push_app_option_name" value="post_types">Post Types</option>');
        }
        printf('</select>');
    }

}

if( is_admin() )
    $settings_page = new PushMobileAppSettingsPage();
