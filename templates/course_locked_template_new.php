<?php
/**
 * Displays the Course Points Access message
 *
 * Available Variables:
 * current_post : (WP_Post Object) Current Post object being display. Equal to global $post in most cases.
 * content_type : (string) Will contain the singlar lowercase common label 'course', 'lesson', 'topic', 'quiz'
 * course_access_points : (integer) Points required to access this course.
 * user_course_points : (integer) the user's current total course points.
 * course_settings : (array) Settings specific to current course
 *
 * @since 3.0
 *
 * @package LearnDash\Course
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$user_id            = ( is_user_logged_in() ) ? get_current_user_id() : false;
$bosld_gateway      = get_option( 'wblg_options' );

$bosldgw_unlock_course = array(
    'course_points' =>  $course_access_points,
    'user_ld_points'=>  $user_course_points,
    'user_id'       =>  $user_id,
    'course_id'     =>  $current_post->ID
);
$bosldgw_unlock_course =  json_encode( serialize($bosldgw_unlock_course) );

$creds_remaining_migration = isset($bosld_gateway['use_remaining_points']) ? $bosld_gateway['use_remaining_points'] : "";

if( 'yes' == $creds_remaining_migration ) {
    $point_to_remove = floatval( $course_access_points ) - floatval( $user_course_points );
} else {
    $point_to_remove = floatval( $course_access_points );
}

$user_points = badgeos_get_users_points($user_id);
?>

<div class="learndash-wrapper">
    <?php

    $message = '<p>' . sprintf( esc_html_x(
            'To take this %s you need at least %.01f total points. You currently have %.01f points.',
            'placeholders: (1) will be Course. (2) course_access_points. (3) user_course_points ',
            'learndash'
        ),
            $content_type,
            $course_access_points,
            $user_course_points
        ) . '</p>';

    learndash_get_template_part( 'modules/alert.php', array(
        'type'      =>  'warning',
        'icon'      =>  'alert',
        'message'   =>  $message
    ), true );

    ?>

    <div class="migrate_points_form">
        <h3>
            <?php _e( 'Do you want to redeem your BadgeOS points to unlock this course?', WBLG_LANG ); ?>
        </h3>
        <form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="POST" class="wmca-redeem-points">

            <input type="hidden" name="bosldgw_unlock_course_vals" value='<?php echo $bosldgw_unlock_course; ?>'>

            <input type="hidden" id="bosldgw_point_type_id" name="bosldgw_point_type_id" value="0">
            <input type="hidden" id="bosldgw_course_points" name="bosldgw_course_points" value="<?php echo $point_to_remove; ?>">

            <input
                    type="hidden"
                    name="point_types"
                    id="user-points"
                    class="choose-point-type <?php echo empty($user_points) ? "no-points" : ""; ?>"
                    value="<?php echo $user_points; ?>"
            >
            <div class="migrate_points_button">
                <input type="submit" data-removable-points="<?php echo $point_to_remove; ?>" class="btn btn-primary wmca-redeem-points-submit" value="<?php _e( "Unlock Course", WBLG_LANG ); ?>">
            </div>

            <?php wp_nonce_field( 'bosld_unlock_course_action', 'bosld_unlock_course_field' ); ?>
            <input type="hidden" name="action" value="bosld_unlock_course">
        </form>
    </div>
</div>
