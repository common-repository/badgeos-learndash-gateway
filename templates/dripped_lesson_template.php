<?php
/**
 * Displays the Course Lesson Not Available	message
 *
 * Available Variables:
 * user_id : (integer) The user_id whose points to show
 * course_id : (integer) The ID of the couse shown
 * lesson_id: (integer) The Of of the lesson not available
 * ld_lesson_access_from_int : (integer) timestamp when lesson will become available
 * ld_lesson_access_from_date : (string) Formatted human readable date/time of ld_lesson_access_from_int
 * context : (string) The context will be set based on where this message is shown. course, lesson, loop, etc.
 *
 * @since 2.4
 *
 * @package LearnDash\Course
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


$lesson_unlock_points       = !empty( $lesson_unlocked = get_post_meta( $lesson_id, '_bos_ld_unlock_lesson_points', true ) ) ? $lesson_unlocked : "";

$bosld_unlock_drip_content  = array(
    'lesson_unlock_points'          =>  $lesson_unlock_points,
    'lesson_id'                     =>  $lesson_id,
    'course_id'                     =>  $course_id,
    'user_id'                       =>  $user_id,
    'ld_lesson_access_from_int'     =>  @$lesson_access_from_int,
    'ld_lesson_access_from_date'    =>  @$lesson_access_from_date,
    'context'                       =>  $context
);

$bosld_unlock_drip_content =  json_encode( serialize( $bosld_unlock_drip_content ) );


$user_id            = ( is_user_logged_in() ) ? get_current_user_id() : false;
$bosld_gateway      = get_option( 'wblg_options' );
$user_points        = badgeos_get_users_points( $user_id );

// First generate the message
$message = sprintf( __( '<span class="ld-display-label">Available on:</span> <span class="ld-display-date">%s</span>', 'learndash' ), learndash_adjust_date_time_display( $lesson_access_from_int ) );
$wrap_start = '<small class="notavailable_message">';
$wrap_end = '</small>';

// The figure out how to display it
if ( $context == 'lesson' ) {
    // On the lesson single we display additional information.
    $message .= '<br><br><a href="'. get_permalink( $course_id) . '">'. sprintf( _x( 'Return to %s Overview', 'Return to Course Overview Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ) . '</a>';

    $wrap_start = '<div class="notavailable_message">';
    $wrap_end = '</div>';

}
echo $wrap_start . apply_filters( 'learndash_lesson_available_from_text', $message, get_post( $lesson_id ), $lesson_access_from_int ) . $wrap_end;
?>

<?php

$user_id = get_current_user_id();
$credit_types = badgeos_get_point_types();
$user_points = 0;
if ( is_array( $credit_types ) && ! empty( $credit_types ) ) {
    $excluded_point_types = boslgw_get_excluded_point_types($lesson_id);
    foreach ($credit_types as $credit_type) {
        if(!in_array($credit_type->ID, $excluded_point_types)) {

            $earned_credits = badgeos_get_points_by_type($credit_type->ID, $user_id);
            if ($earned_credits >= $lesson_unlock_points) {
                $user_points = $earned_credits;
            }
        }
    }
}

if ( $context == 'lesson' ) {
    if( !empty($user_points) && !empty( $lesson_unlock_points ) ):
        ?>
        <div class="migrate_points_form">
            <br>
            <h4>
                <?php _e( "Do you want to unlock this lesson using your BadgeOS points?", WBLG_LANG ); ?>
            </h4>
            <form action="<?php echo admin_url( "admin-post.php" ) ?>" method="POST" class="wmca-redeem-points">
                <input type="hidden" name="bos_unlock_drip_content_vals" value='<?php echo $bosld_unlock_drip_content; ?>'>
                <input type="hidden" id="bosldgw_point_type_id" name="bosldgw_point_type_id" value="0">
                <input type="hidden" class="bos-ld-user-points" name="bosldgw_user_points" value="<?php echo $user_points; ?>">
                <input type="hidden" id="bosldgw_course_points" name="lesson_points_required" value="<?php echo $lesson_unlock_points; ?>">
                <input
                        type="hidden"
                        name="point_types"
                        id="user-points"
                        class="choose-point-type <?php echo empty($user_points) ? "no-points" : ""; ?>"
                        value="<?php echo $user_points; ?>"
                >

                <div class="migrate_points_button">
                    <input type="submit" data-removable-points="<?php echo $lesson_unlock_points; ?>" class="btn btn-primary wmca-redeem-points-submit" value="<?php _e( 'Unlock Lesson', WBLG_LANG ); ?>">
                </div>

                <?php wp_nonce_field( 'bos_unlock_drip_lesson_action', 'bos_unlock_drip_lesson' ); ?>
                <input type="hidden" name="action" value="bos_unlock_dripped_lesson">
            </form>
        </div>
    <?php else: ?>
        <div class="migrate_points_form">
            <br>
            <h4>
                <?php _e( "Sorry! The lesson is locked and will be available on {$lesson_access_from_date}.", WBLG_LANG ); ?>
            </h4>
        </div>
    <?php endif; ?>

<?php } ?>
