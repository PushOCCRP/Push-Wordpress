<?php

class PushMobileAppHelpers {
  public static function post_types() {
    $args = array(
      'public'   => true,
      '_builtin' => false
    );

    $output = 'names'; // names or objects, note names is the default
    $operator = 'and'; // 'and' or 'or'

    $post_types = get_post_types( $args, $output, $operator );

    return $post_types;

  }

  // Returns all categories that are public, not built in, and has at least one post.
  public static function categories() {
    $args = array(
      'public'   => true,
      '_builtin' => false
    );

    $output = 'names'; // names or objects, note names is the default
    $operator = 'and'; // 'and' or 'or'

    $categories = get_categories( $args, $output, $operator );

    return $categories;
  }

}
?>