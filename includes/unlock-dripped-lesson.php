<?php

/**
 * Unlocking Dripped Lessons by Redeeming BadgeOS Points
 *
 * @author   WooNinjas
 * @version  1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class BOSLD_Unlock_Drip_Lessons
 */
class BOSLD_Unlock_Drip_Lessons {

    public $unlock_drip_lesson;

    /**
     * Unlock_Drip_Lessons constructor.
     */
    public function __construct () {

        $default_values = get_option( 'wblg_options' );
        $this->unlock_drip_lesson = isset(  $default_values['unlock_lessons'] ) ? $default_values['unlock_lessons'] : 'no';

        $this->init();
    }

    /**
     * Initialize all hooks
     */
    public function init() {
        add_action( 'add_meta_boxes', [ $this, 'create_meta_boxes' ], 10, 2 );
        add_action( 'save_post', [ $this, 'save_meta_box_val' ] );
        add_action( 'save_post', [ $this, 'save_meta_box_point_types_val' ] );
        add_filter( 'learndash_template', [ $this, 'bosld_template_path' ], 10, 5 );
        add_action( 'admin_post_bos_unlock_dripped_lesson', [ $this, 'bosld_unlock_drip_lesson' ] );
        add_filter( 'ld_lesson_access_from', [ $this, 'bosdld_unlock_drip_content_filter' ], 10, 3 );
        add_action( 'wp_footer', [ $this, 'alert_error' ] , 11);
    }

    /**
     * Get LearnDash template within the plugin and pass data to be used in template
     *
     * @param string $template_filename - Template File Name
     * @param string $name - Template Name
     * @param array $args - Data for Template
     * @param bool $echo - echo or return
     * @param bool $return_file_path - return just file path if true
     * @return string $name
     */
    public function bosld_template_path( $template_filename, $name, $args, $echo = false, $return_file_path = false ) {

        if ( $this->unlock_drip_lesson == 'yes' && $name == 'learndash_course_lesson_not_available' ) {

            $ld_active_theme = get_option('learndash_settings_courses_themes');

            if( $ld_active_theme['active_theme'] == 'legacy' ) {
                $template_filename = WBLG_TEMPLATES_DIR . '/dripped_lesson_template.php';
            } else {
                $template_filename = WBLG_TEMPLATES_DIR . '/dripped_lesson_template_new.php';
            }

        }

        return $template_filename;
    }

    /**
     * Adds MetaBoxes for BadgeOS LD Gateway
     *
     * @param $post_args
     * @return bool
     */
    public function create_meta_boxes ( $post_type, $post ) {

        add_meta_box(
            'bos-ld-lessons-undrip-meta-box',
            __( 'Undrip Lesson Points', WBLG_LANG ),
            [ $this, 'render_meta_box' ],
            'sfwd-lessons',
            'side',
            'default',
            [ 'post_type' => 'sfwd-lessons' ]
        );

        add_meta_box(
            'bos-ld-lessons-exclude-point-types-meta-box',
            __( 'Exclude BadgeOS Point Types', WBLG_LANG ),
            [ $this, 'render_meta_box_point_types' ],
            'sfwd-lessons',
            'side',
            'default',
            [ 'post_type' => 'sfwd-lessons' ]
        );
    }

    /**
     * Render meta_box for BadgeOS exclude point types field
     *
     * @param $post
     * @param $callback_args
     */
    public function render_meta_box_point_types( $post, $callback_args ) {

        $excluded_point_types      = get_post_meta( $post->ID, '_bosld_excluded_lesson_point_types', true );
        if(!is_array($excluded_point_types)) $excluded_point_types = array();
        ?>
        <p>
            <label for="bos-ld-buy-course-points"><strong><?php echo __( 'Select BadgeOS point types to exclude', WBLG_LANG ); ?></strong></label> <br /> <br />
            <select name="badgeos_ldgw_excluded_point_types[]" id="badgeos_ldgw_excluded_point_types" multiple style="width: 80%;">
                <option value="0" <?php selected(empty($excluded_point_types) || in_array( 0, $excluded_point_types)); ?>><?php _e('None', WBLG_LANG); ?></option>
                <?php foreach (badgeos_get_point_types() as $credit_type): ?>
                    <option value="<?php echo $credit_type->ID; ?>" <?php selected(in_array( $credit_type->ID, $excluded_point_types)); ?>><?php echo $credit_type->post_title; ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p class="description" style="font-style:italic;">
            <?php _e( 'Exclude BadgeOS point types not to be used to unlock lessons."', WBLG_LANG ); ?>
        </p>
        <?php wp_nonce_field( 'bos_ld_excluded_point_types_meta_box', 'bos_ld_excluded_point_types_meta_box_nonce' );
    }

    /**
     * Save BadgeOS excluded point types
     *
     * @param $post_id
     * @return mixed
     */
    public function save_meta_box_point_types_val( $post_id ) {

        /*
         * We need to verify this came from the our screen and with proper authorization,
         * because save_post can be triggered at other times.
         */

        // Check if our nonce is set.
        if ( ! isset( $_POST['bos_ld_excluded_point_types_meta_box_nonce'] ) ) {
            return $post_id;
        }

        $nonce = $_POST['bos_ld_excluded_point_types_meta_box_nonce'];

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce, 'bos_ld_excluded_point_types_meta_box' ) ) {
            return $post_id;
        }

        /*
         * If this is an autosave, our form has not been submitted,
         * so we don't want to do anything.
         */
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        // Check the user's permissions.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return $post_id;
        }

        /* OK, it's safe for us to save the data now. */

        update_post_meta( $post_id, '_bosld_excluded_lesson_point_types', sanitize_text_field($_POST['badgeos_ldgw_excluded_point_types']) );
    }

    /**
    * Metabox callback
    *
    */
    public function render_meta_box( $post, $callback_args ) {
        $points = get_post_meta( $post->ID, '_bos_ld_unlock_lesson_points', true );
        ?>
        <p>
            <label for="wmca-unlock-points"><strong><?php echo __( 'Points required to unlock this lesson', WBLG_LANG ); ?></strong></label> <br /> <br />
            <input type="number" min="0" name="bos_ld_unlock_lesson_points" id="wmca-unlock-points" value="<?php echo $points; ?>" />
        </p>
        <p class="description" style="font-style:italic;">
            <?php
            echo sprintf(__("BadgeOS points to unlock this lesson. Applicable when Lesson visible X days after OR Lesson Visible on Specific Date is set. AND <a href='%s' target='_blank'>unlock drip</a> lessons option is enabled.", WBLG_LANG), esc_url( admin_url( "admin.php?page=badgeos_learndash_gateway_settings" ) ));
            ?>
        </p>

        <?php wp_nonce_field( 'bos_ld_unlock_lesson_meta_box', 'bos_ld_unlock_lesson_meta_box_nonce' );
    }


    /**
     * Save the BadgeOS LD Gateway meta when the post is saved.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public function save_meta_box_val( $post_id ) {

        /*
         * We need to verify this came from the our screen and with proper authorization,
         * because save_post can be triggered at other times.
         */

        // Check if our nonce is set.
        if ( ! isset( $_POST['bos_ld_unlock_lesson_meta_box_nonce'] ) ) {
            return $post_id;
        }

        $nonce = $_POST['bos_ld_unlock_lesson_meta_box_nonce'];

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce, 'bos_ld_unlock_lesson_meta_box' ) ) {
            return $post_id;
        }

        /*
         * If this is an autosave, our form has not been submitted,
         * so we don't want to do anything.
         */
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        // Check the user's permissions.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return $post_id;
        }

        /* OK, it's safe for us to save the data now. */

        if( isset( $_POST['bos_ld_unlock_lesson_points'] ) ) {
            update_post_meta( $post_id, '_bos_ld_unlock_lesson_points', sanitize_text_field($_POST['bos_ld_unlock_lesson_points']) );
        }

    }

    /**
     * Migrate points
     * From BadgeOS to unlock dripped lessons and update user meta
     */
    public function bosld_unlock_drip_lesson() {

        if ( !isset( $_POST['bos_unlock_drip_lesson'] ) || !wp_verify_nonce( $_POST['bos_unlock_drip_lesson'], 'bos_unlock_drip_lesson_action' ) ) {
            wp_die( __( 'Something is wrong!, your nonce did not verify. Please go back and try again.', WBLG_LANG ), __( 'Something is wrong!', WBLG_LANG ), [ '403', true ] );
        }

        extract($_POST);
        $base_array = array();

        /*$credit_type_arr    = explode('|', $credit_types);
        $credit_id          = $credit_type_arr[0];
        $credit_number      = $credit_type_arr[1];*/

        $bos_unlock_drip_content_vals = stripslashes( $bos_unlock_drip_content_vals );
        $bosld_contents = unserialize( json_decode( $bos_unlock_drip_content_vals ) );

        $excluded_point_types = boslgw_get_excluded_point_types($bosld_contents['lesson_id']);
        $user_points = 0;
        if(!in_array($bosldgw_point_type_id, $excluded_point_types)) {
            $user_points = badgeos_get_points_by_type($bosldgw_point_type_id, get_current_user_id());
        }


        $redeem_points = array(
            'user_id'       =>  $bosld_contents['user_id'],
            'point_remove'  =>  $bosld_contents['lesson_unlock_points'],
            'lesson_id'     =>  $bosld_contents['lesson_id'],
            'course_id'     =>  $bosld_contents['course_id'],
            'total_points'  =>  $user_points
        );

        $redeem_points = apply_filters( 'bosld_pre_user_unlock_drip_lesson', $redeem_points );

        // get user's existing unlocked lessons
        $user_unlocked_lessons = maybe_unserialize( get_user_meta( $redeem_points['user_id'], '_bosld_unlocked_lessons', true ) );

        $unlocked_lessons = array_merge( $base_array, $user_unlocked_lessons );

        if( in_array( $redeem_points['lesson_id'], $unlocked_lessons ) ) {
            wp_safe_redirect( add_query_arg( 'status', 'exists', $_POST['_wp_http_referer'] ), 302 );
            exit;
        }

        if($user_points >= $redeem_points["point_remove"]) {
            $unlocked_lessons[] = $redeem_points['lesson_id'];

            //$credits_removed = badgeos_update_users_points( $redeem_points['user_id'], -$redeem_points["point_remove"] );
            $this->deduct_points($redeem_points['user_id'], $bosldgw_point_type_id, $redeem_points["point_remove"]);

            //if( $credits_removed != 0 ) {
            // updating user's meta with updated unlocked lessons array
            $user_updated = update_user_meta($redeem_points['user_id'], '_bosld_unlocked_lessons', $unlocked_lessons);
            /*} else {
                wp_safe_redirect( add_query_arg( 'status', 'error', $_POST['_wp_http_referer'] ), 302 );
                exit;
            }*/

            do_action('bosld_after_user_unlock_drip_lesson', $redeem_points['user_id'], $redeem_points['lesson_id'], $redeem_points['credit_remove'], $redeem_points['credit_id']);
        } else {
            $this->show_notification( 'less_points_lesson' );
        }

        wp_safe_redirect( $_POST['_wp_http_referer'], 302 );
        exit;
    }


    function alert_error() {

        if ( ( isset( $_GET['bos-ld-gw-error'] ) ) && ( !empty( $_GET['bos-ld-gw-error'] ) ) ) {

            if($_GET['bos-ld-gw-error'] == 'less_points_lesson') {
                $message = esc_html__( 'The Point Type you have chosen, has not enough points to unlock this lesson', WBLG_LANG );
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
     * @param string $return - Timestamp of dipped lesson
     * @param int $lesson_id - Lesson ID
     * @param int $user_id   - User ID
     * @return string $return
     */
    public function bosdld_unlock_drip_content_filter( $return, $lesson_id, $user_id ) {

        $unlocked_lessons = get_user_meta( $user_id, '_bosld_unlocked_lessons', true );

        if( $this->unlock_drip_lesson == 'yes' && ( is_array( $unlocked_lessons ) && in_array( $lesson_id, $unlocked_lessons ) ) ) {
            $return = '';
            return $return;
        } else {
            return $return;
        }
    }

    private function deduct_points($user_id, $credit_type_id, $points) {
        if( (int) $points > 0 ) {
            badgeos_revoke_credit( $credit_type_id, $user_id, 'Deduct', $points, 'user_credit_used', 0, '', '' );
            badgeos_recalc_total_points($user_id);
        }
    }
}

return new BOSLD_Unlock_Drip_Lessons();
