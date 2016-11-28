<?php

class PushMobileAppHelpers {
  public static function post_types() {
    $args = array(
      'public'   => true,
      '_builtin' => true
    );

    $output = 'names'; // names or objects, note names is the default
    $operator = 'and'; // 'and' or 'or'

    $post_types = get_post_types( $args, $output, $operator );
    
    $args['_builtin'] = false;
    $post_types = array_merge($post_types, get_post_types($args, $output, $operator));

    return $post_types;

  }

  // Returns all categories that are public, not built in, and has at least one post.
  public static function categories($output = 'names') {
    $args = array(
      'public'   => true,
      '_builtin' => false
    );

//    $output = 'names'; // names or objects, note names is the default
    $operator = 'and'; // 'and' or 'or'

    $categories = get_categories( $args, $output, $operator );

    return $categories;
  }

  public static function category_for_name($category_name){

    $categories = PushMobileAppHelpers::categories('objects');

    foreach($categories as $category){
      if($category->name == $category_name){
        return $category;
      }
    }


    return null;
  }

}
?>