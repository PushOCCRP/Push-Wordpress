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
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'push_app_mobile_categories_option_group' );
                do_settings_sections( 'push-mobile-app-admin' );
                submit_button();
            ?>
            </form>
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
            'push_app_option_name' // Option name
             // Sanitize
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
            'My Custom Settings', // Title
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
        var_dump($input);
        // Validation should run against on all the inputs
        return $input;
    }

    /**
     * Ensure post types are showing up right???
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function validate_post_types( $input )
    {
        // Validation should run against on all the inputs
        return $input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
//        print 'Enter your settings below:';
    }

    /** 
     * Get the categories option array and display check boxes for each
     */
    public function categories_callback()
    {
        printf('Please check all categories to <strong>not</strong> include in responses.<br><br>');

        $categories = PushMobileAppHelpers::categories();
        $disabled_categories = get_option('push_app_disabled_categories');

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
