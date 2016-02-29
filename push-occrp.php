<?php
/**
 * Plugin Name: Push Occrp
 * Plugin URI:  https://vccw.cc/
 * Description: An export plugin for the Push-OCCRP mobile app ecosystem.
 * Version:	 0.1
 * Author:	  Christopher Guess
 * Author URI:  https://www.tryandguess.com/
 * License:	 
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

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
function my_insert_query_vars( $vars )
{
    array_push($vars, 'type', 'q');
    return $vars;
}

function push_endpoint_data() {
	$type = get_query_var("type");
	
	if(!$type) {
		return;
	}

    if($type == 'search' && !get_query_var('q')) {
        json_error(1001,  "No query submitted for search." );
    } else {
        $query = get_query_var('q');
    }
     
    $args = array();

    switch ($type) {
        case 'articles':
            $args = arguments_for_articles();
            break;
        case 'search':
            $args = arguments_for_search($query);
            break;
        }
 
    $post_query = new WP_Query( $args );
 
    $response = array();
    $response['results'] = array();
    $response['start_date'] = null;
    $response['end_date'] = null;
    $response['total_items'] = 0;
    $response['total_pages'] = 0;
    $response['page'] = 0;

    if ( $post_query->have_posts() ) {
        $response['total_items'] = $post_query->found_posts;
        $response['total_pages'] = $post_query->max_num_pages + 1;
        $response['page'] = 1;
        

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
            $post_data["captions"] = array(); 
            
            $response['total_items']++;
            
            $response['results'][] = $post_data;
		}
		wp_reset_postdata();
	}
    
    wp_send_json( $response );
}

function arguments_for_articles($number_of_articles = 10) {
    return array(
        'post_type'      => 'post',
        'posts_per_page' => 10,
    );
}

function arguments_for_search($query, $number_of_articles = 10) {
    return array(
        's' => $query,
        'posts_per_page' => $number_of_articles,
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














