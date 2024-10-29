<?php
function boslgw_get_excluded_point_types($post_id) {

    $post = get_post($post_id);

    $wblg_options = get_option( "wblg_options", array() );

    $excluded_point_types = !empty( $wblg_options['excluded_point_types']) ? $wblg_options['excluded_point_types'] : array();

    if($post) {

        if ($post->post_type == 'sfwd-courses') {
            $excluded_point_types_post = get_post_meta($post->ID, '_bosld_excluded_course_point_types', true);

            if (!empty($excluded_point_types_post)) {
                $excluded_point_types = $excluded_point_types_post;
            }
        } else if ($post->post_type == 'sfwd-lessons') {
            $excluded_point_types_post = get_post_meta($post->ID, '_bosld_excluded_lesson_point_types', true);

            if (!empty($excluded_point_types_post)) {
                $excluded_point_types = $excluded_point_types_post;
            }
        }
    }

    return $excluded_point_types;
}
