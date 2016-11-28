<?php
/**
 * Plugin Name: Push Occrp
 * Plugin URI:  https://github.com/pushoccrrp
 * Description: An export plugin for the Push mobile app ecosystem.
 * Version:	1.5.1
 * Author:	Christopher Guess
 * Author URI:  https://www.tryandguess.com/
 * License:
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require 'push-occrp-settings.php';
//require 'push-occrp-helpers.php';

add_filter( 'rewrite_rules_array','my_insert_rewrite_rules' );
add_filter( 'query_vars','my_insert_query_vars' );
add_action( 'wp_loaded','my_flush_rules' );
add_action( 'template_redirect', 'push_endpoint_data');

// flush_rules() if our rules are not yet included
function my_flush_rules(){
    $rules = get_option( 'rewrite_rules' );

    if ( ! isset( $rules['push-occrp/(.*?)/(.+?)'] ) ) {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }
}

// Adding a new rule
function my_insert_rewrite_rules( $rules )
{
    $newrules = array();
    $newrules['push-occrp/([0-9a-zA-Z]+)/([0-9a-zA-Z]+)/'] = 'index.php?push=$matches[1]&myargument=$matches[2]';
    return $newrules + $rules;
}

// Adding the id var so that WP recognizes it
function my_insert_query_vars( $vars ) {
    array_push($vars, 'occrp_push_type', 'q', 'article_id', 'language', 'post_types', 'categorized');
    return $vars;
}

function push_endpoint_data() {
	$type = get_query_var("occrp_push_type");

	if(!$type) {
		return;
	}

	$categorized = false;

	// Set up the variables properly
    if($type == 'search' && !get_query_var('q')) {
        json_error(1001,  "No query submitted for search." );
    } else {
        $query = get_query_var('q');
    }

    if($type == 'article' && !get_query_var('article_id')) {
        json_error(1002,  "No id for article." );
    } else {
        $article_id = get_query_var('article_id');
    }

    if($type == 'articles' && get_query_var('post_types')){
    	$post_types = explode(',', get_query_var('post_types'));
    	if(get_query_var('categorized') == 'true'){
    		$categorized = true;
    	}
    } else {
    	$post_types = null;
    }

    $args = array();

	if($type != 'articles' || !$categorized){

		switch ($type) {
			case 'articles':
				$args = arguments_for_articles($post_types);
				break;
			case 'article':
				$args = arguments_for_article($article_id);
				break;
			case 'search':
				$args = arguments_for_search($query);
				break;
			case 'post_types':
				wp_send_json(categories());
				return;
				break;
			default:
				wp_send_json(error($type + " not acceptable type"));
				return;
			}

		$response = articles_response($args);
		wp_send_json( $response );
	} else {
		// Should never not be articles, but just in case
		if($type != 'articles' || !$categorized){
			wp_send_json(error("Categorized can only be used for fetching articles."));
			return;
		}

		$response = default_response();
		$response['results'] = array();
		$response['categories'] = $post_types;

        $option = get_option('push_app_option_name');

		// WPML
		if ( function_exists('icl_object_id') ) {
			$current_lang = apply_filters( 'wpml_current_language', NULL );
			if(array_key_exists($current_lang, $option)){
				$option = $option[$current_lang];			
			}
		}

		if($option == 'categories'){
			foreach($post_types as $category){
				
				if(is_category_excluded($category) == true){
					$response['results'][$category] = array();
				} else {
					$category_object = PushMobileAppHelpers::category_for_name($category);
					if($category_object == null){
						$error_array = array();
						$error_array['error'] = 'unknown category "'.$category.'" in params';
						wp_send_json($error_array);
						return;
					}

					$args = arguments_for_articles(PushMobileAppHelpers::post_types(), $category_object);
					$post_items = articles_for_args($args);
					
					$response['results'][$category] = $post_items['results'];
				}
			}
		} elseif($option == 'post_types'){

			foreach($post_types as $post_type){
				if(is_post_type_excluded($post_type) == true){
					$response['results'][$post_type] = array();
				} else {
					$args = arguments_for_articles($post_type);
					$post_items = articles_for_args($args);

					$response['results'][$post_type] = $post_items['results'];
				}
			}

		}


		wp_send_json($response);
	}
}

// This retreives a list of categories, depending on the settings set.
function categories() {
	$return_type = get_option('push_app_option_name');
	
	// Defaults to 'categories'
	if((is_string($return_type) && strlen($return_type) == 0) || !isset($return_type)){
		$return_type = 'categories';
	}

	// WPML
	if ( function_exists('icl_object_id') ) {
		$current_lang = apply_filters( 'wpml_current_language', NULL );
		
		if(is_array($return_type) && array_key_exists($current_lang, $return_type)){
			$return_type = $return_type[$current_lang];			
		}
	}

	if($return_type == 'categories'){
		$categories = PushMobileAppHelpers::categories();

		$cleaned_categories = array_filter($categories, function ($category) {
			$disabled_categories = get_option('push_app_disabled_categories', "");

			// WPML
			if ( function_exists('icl_object_id') ) {
				$current_lang = apply_filters( 'wpml_current_language', NULL );
				if(is_array($disabled_categories) && array_key_exists($current_lang, $disabled_categories)){
					$disabled_categories = $disabled_categories[$current_lang];			
				}

				if(!isset($disabled_categories)){
					$disabled_categories = array();
				}
			}

			if(is_string($disabled_categories)){
				return true;
			}

			$filter = !array_key_exists($category->term_id, $disabled_categories);
			return $filter;
		});

		$return_array = array();
		

		foreach($cleaned_categories as $category){
			array_push($return_array, $category->name);
		}
	} elseif('post_types'){
		$post_types = PushMobileAppHelpers::post_types();

		$cleaned_post_types = array_filter($post_types, function ($post_type) {
			$disabled_post_types = get_option('push_app_disabled_post_types', "");
			// WPML
			if ( function_exists('icl_object_id') ) {
				$current_lang = apply_filters( 'wpml_current_language', NULL );
				if(is_array($disabled_post_types) && array_key_exists($current_lang, $disabled_post_types)){
					$disabled_post_types = $disabled_post_types[$current_lang];			
				}
			}

			if(is_string($disabled_post_types)){
				return true;
			}

			$filter = !array_key_exists($post_type, $disabled_post_types);
			return $filter;
		});

		$return_array = array();
		foreach($cleaned_post_types as $cleaned_post_type){
			array_push($return_array, $cleaned_post_type);
		}
		
	}
	
	return($return_array);
}

// Generic getting format, set up the args first then this creates the entire final response
function articles_response($args) {
    $response = default_response();

	$post_items = articles_for_args($args);

	$response['total_items'] = $post_items['total_items'];
	$response['total_pages'] = $post_items['total_pages'];
    $response['page'] = 1;

    $response['results'] = $post_items['results'];

	return $response;
}

function default_response() {
	$response = array();

    $response['results'] = array();
    $response['start_date'] = null;
    $response['end_date'] = null;
    $response['total_items'] = 0;
    $response['total_pages'] = 0;
    $response['page'] = 0;

	return $response;
}

function error($message = 'Generic error.') {
	$error_response = array();
	$error_response['error'] = $message;
	return $error_response;
}

function articles_for_args($args) {
    $post_query = new WP_Query( $args );
    if ( $post_query->have_posts() ) {
    	$response = array();
        $response['total_items'] = $post_query->found_posts;
        $response['total_pages'] = $post_query->max_num_pages + 1;
        $response['page'] = 1;

        $response['results'] = array();

		while ( $post_query->have_posts() ) {
			$post_query->the_post();
			$post_data = array();

			$post_data["headline"] = get_the_title();
			$post_data["description"] = get_the_excerpt();
			$post_data["body"] = get_the_content();
			$post_data["author"] = get_the_author();
			$post_data["publish_date"] = get_the_date("Ymd");
			$post_data["id"] = get_the_id();
            $post_data["language"] = "en-GB";
            $post_data["image_urls"] = array();
	    	$post_data["images"] = array();

			if (has_post_thumbnail( get_the_id() ) ){
				$feat_image = wp_get_attachment_url( get_post_thumbnail_id(get_the_id()) );
				$post_data["images"][] = array("url" => $feat_image, "caption" => "", "width" => "", "height" => "", "byline" => "");
			}


            $post_data["captions"] = array();
            $post_data["url"] = get_site_url() . "/?p=" . get_the_id();


            array_push($response['results'], $post_data);
		}
		wp_reset_postdata();

		return $response;
	}

	return null;
}

function arguments_for_articles($post_types = 'post', $categories = null, $number_of_articles = 10) {

	// Make sure you don't return any post_types that are restricted
	if(is_array($post_types)){
	    $disabled_post_types = get_option('push_app_disabled_post_types');

		// WPML
		if ( function_exists('icl_object_id') ) {
			$current_lang = apply_filters( 'wpml_current_language', NULL );
			if( is_array($disabled_post_types) && array_key_exists($current_lang, $disabled_post_types)){
				$disabled_post_types = $disabled_post_types[$current_lang];			
			}
		}
		
		if(!is_string($disabled_post_types)){
			$post_types = array_diff($post_types, array_keys($disabled_post_types));
		}
	}

	if($categories == null){
		$categories = categories_to_exclude();
	} else {
		if(!is_array($categories)){
			$temp_categories = array();
			array_push($temp_categories, $categories);
			$categories = $temp_categories;
		}
		$categories = array_map(function($category){
			return $category->term_id;
		}, $categories);
	}


    return array(
        'post_type'      => $post_types,
        'cat' 			 => $categories,
        'posts_per_page' => 10,
    );
}

function arguments_for_article($article_id) {
    return array(
        'post_type' => 'any',
        'p'         => $article_id,
        'cat' 		=> categories_to_exclude(),
    );
}

function arguments_for_search($query, $number_of_articles = 10) {
    return array(
        's' 			 => $query,
        'posts_per_page' => $number_of_articles,
        'cat' 			 => categories_to_exclude(),
    );
}

function json_error($code = '', $message = '') {
    $error = array(
        'type'       => 'error',
        'error_code' => $code,
        'message'    => $message,
        );
    wp_send_json( $error );
}

function categories_to_exclude(){	
	$disabled_categories = get_option('push_app_disabled_categories', "");
    
	// WPML
	if ( function_exists('icl_object_id') ) {
	    $current_lang = apply_filters( 'wpml_current_language', NULL );
		if( is_array($disabled_categories) && array_key_exists($current_lang, $disabled_categories)){
			$disabled_categories = $disabled_categories[$current_lang];			
		}
	}

	if(is_string($disabled_categories)){
		return '';
	}

	$return_string = join(',', array_map(function($disabled_category){
		return '-' . $disabled_category;
	}, array_keys($disabled_categories)));

	return $return_string;
}

function is_category_excluded($category_name) {
	$disabled_categories = get_option('push_app_disabled_categories', "");

	// WPML
	if ( function_exists('icl_object_id') ) {
	    $current_lang = apply_filters( 'wpml_current_language', NULL );
		if( is_array($disabled_categories) && array_key_exists($current_lang, $disabled_categories)){
			$disabled_categories = $disabled_categories[$current_lang];			
		}
	}

	if(is_string($disabled_categories)){
		return false;
	}

	foreach(array_keys($disabled_categories) as $disabled_category_id){
		$disabled_category = get_the_category_by_ID($disabled_category_id);
		if($disabled_category == $category_name){ 
			return true;
		}
	}

	return false;
}

function is_post_type_excluded($post_type) {
	$disabled_post_types = get_option('push_app_disabled_post_types', '');

	// WPML
	if ( function_exists('icl_object_id') ) {
	    $current_lang = apply_filters( 'wpml_current_language', NULL );
		if( is_array($disabled_post_types) && array_key_exists($current_lang, $disabled_post_types)){
			$disabled_post_types = $disabled_post_types[$current_lang];			
		}
	}

	if(is_string($disabled_post_types)){
		return false;
	}

	foreach(array_keys($disabled_post_types) as $disabled_post_type){
		if($disabled_post_type == $post_type){ 
			return true;
		}
	}

	return false;
}







