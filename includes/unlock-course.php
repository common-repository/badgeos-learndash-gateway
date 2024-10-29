<?php

/**
 * Unlocking Courses by Redeeming BadgeOS points
 *
 * @author   WooNinjas
 * @version  1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class BadgeOS_LD_Unlock_Course
 */
class BadgeOS_LD_Unlock_Course {

    public $options;

    /**
     * BadgeOS_Unlock_Courses constructor.
     */
    public function __construct () {
        add_filter( 'learndash_template', [ $this, 'bosld_template_path' ], 10, 4 );
        add_action( 'admin_post_bosld_unlock_course', [ $this, 'bosld_migrate_user_points' ] );
        add_action( 'wp_footer', [ $this, 'alert_error' ] , 11);

        $this->options = get_option( 'wblg_options' );
    }

    function alert_error() {

        if ( ( isset( $_GET['bos-ld-gw-error'] ) ) && ( !empty( $_GET['bos-ld-gw-error'] ) ) ) {

            $message = '';

            if($_GET['bos-ld-gw-error'] == 'less_points') {
                $message = __( 'The Point Type you have chosen, has not enough points to unlock this course', WBLG_LANG );
            }
            ?>

            <script type="text/javascript">
                jQuery( document ).ready( function() {
                    if ( jQuery( '.migrate_points_form' ).length ) {
                        jQuery( '<p class="learndash-error"><?php echo $message; ?></p>' ).insertBefore( '.migrate_points_form' );
                    }
                });
            </script>
        <?php }
    }

    /**
     * @param string $error_code
     */
    function show_notification( $error_code = '' ) {

        $redirect_url = add_query_arg( 'bos-ld-gw-error', $error_code, $_POST['_wp_http_referer'] );
        wp_safe_redirect( $redirect_url, 302 );
        exit;
    }

    /**
     * Get LearnDash template within the plugin and pass data to be used in template
     *
     * @param string $name - Template Name
     * @param array $args - Data for Template
     * @param bool $echo - echo or return
     * @param bool $return_file_path - return just file path if true
     * @return string
     */
    public function bosld_template_path( $name, $args, $echo = false, $return_file_path = false ) {

        $default_values = $this->options;
        $creds_points_migration = isset( $default_values['unlock_courses'] ) ? $default_values['unlock_courses'] : '';
        $ld_active_theme = get_option('learndash_settings_courses_themes');

        if ( $creds_points_migration == 'yes' ) {

            if( $args == 'learndash_course_points_access_message' ) {

                if( $ld_active_theme['active_theme'] == 'legacy' ) {
                    return WBLG_TEMPLATES_DIR . '/course_locked_template.php';
                } else {
                    return WBLG_TEMPLATES_DIR . '/course_locked_template_new.php';
                }
            }

        }

        return $name;
    }

    /**
     * Migrate points/credits
     * From BadgeOS to Learndash
     */
    public function bosld_migrate_user_points() {

        if ( !isset( $_POST['bosld_unlock_course_field'] ) || !wp_verify_nonce( $_POST['bosld_unlock_course_field'], 'bosld_unlock_course_action' ) ) {
            wp_die( __( 'Sorry, something is wrong. Please go back and try again.', BOSLDGW_LANG ) );
        }

        extract($_POST);

        $redeem_points = array();

        $default_values = $this->options;
        $creds_remaining_migration = isset( $default_values['use_remaining_points'] ) ? $default_values['use_remaining_points'] : '';

        //$user_points = $point_types;

        $bosldgw_unlock_course_vals = stripslashes( $bosldgw_unlock_course_vals );
        $bosldgw_unlock_course_vals = unserialize( json_decode( $bosldgw_unlock_course_vals ) );

        $course_points      = $bosldgw_unlock_course_vals['course_points'];
        $user_ld_points     = $bosldgw_unlock_course_vals['user_ld_points'];
        $user_id            = $bosldgw_unlock_course_vals['user_id'];
        $course_id          = $bosldgw_unlock_course_vals['course_id'];



        $excluded_point_types = boslgw_get_excluded_point_types($course_id);
        $user_points = 0;
        if(!in_array($bosldgw_point_type_id, $excluded_point_types)) {
            $user_points = badgeos_get_points_by_type($bosldgw_point_type_id, $user_id);
        }

        if( 'yes' == $creds_remaining_migration ) {

            $point_to_remove    = floatval($course_points) - floatval($user_ld_points);
            $point_to_add       = floatval($point_to_remove) + floatval($user_ld_points);

            if( $user_points < $point_to_remove ) {
                /*wp_safe_redirect( $_POST['_wp_http_referer'], 302 );
                exit;*/
                $this->show_notification( 'less_points' );
            }

        } else {

            if( $user_points < $course_points ) {
                /*wp_safe_redirect( $_POST['_wp_http_referer'], 302 );
                exit;*/
                $this->show_notification( 'less_points' );
            }

            $point_to_remove    = floatval($course_points);
            $point_to_add       = floatval($course_points) + floatval($user_ld_points);
        }

        $redeem_points = array(
            'user_id'       =>  $user_id,
            'course_id'     =>  $course_id,
            'points_remove' =>  $point_to_remove,
            'points_add'    =>  $point_to_add,
        );

        $redeem_points = apply_filters( 'bosld_unlock_course_pre', $redeem_points );
        //dd($_POST);
        // update user cours points
        update_user_meta( $redeem_points['user_id'], 'course_points', floatval( $redeem_points['points_add'] ) );

        //$credits_removed = badgeos_update_users_points( $redeem_points['user_id'], -$redeem_points["points_remove"] );

        $this->deduct_points($user_id,$bosldgw_point_type_id, $redeem_points['points_remove']);

        do_action( 'bosld_unlock_course_after', $user_id, $course_id, $point_to_remove, $point_to_add );

        wp_safe_redirect( $_POST['_wp_http_referer'], 302 );
        exit;
    }

    private function deduct_points($user_id, $credit_type_id, $points) {
        if( (int) $points > 0 ) {
            badgeos_revoke_credit( $credit_type_id, $user_id, 'Deduct', $points, 'user_credit_used', 0, '', '' );
            badgeos_recalc_total_points($user_id);
        }
    }
}

return new BadgeOS_LD_Unlock_Course();

//badgeos_update_users_points( 2, -50 );
