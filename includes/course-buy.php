<?php

/**
 * Buy Courses by Redeeming BadgeOS Points
 *
 * @author   WooNinjas
 * @category Frontend
 * @version  1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Class Buy_Courses
 * @package WMCA
 */
class BOSLDGW_Purchase_Course {

	public $bosldgw_buy_course;

	public $course;

	public function __construct() {

        $default_values = get_option( 'wblg_options' );
		$this->bosldgw_buy_course = isset(  $default_values['buy_courses'] ) ? $default_values['buy_courses'] : 'no';
        $this->init();
	}


	public function init() {

        // course meta box for points to buy
        add_action( 'add_meta_boxes', [ $this, 'create_meta_boxes' ], 10, 2 );
        add_action( 'save_post', [ $this, 'save_meta_box_val' ] );
        add_action( 'save_post', [ $this, 'save_meta_box_point_types_val' ] );

        if( 'yes' == $this->bosldgw_buy_course ) {
            add_filter( 'learndash_payment_button', [ $this, 'payment_button' ], 10, 2 );
            add_action( 'wp_loaded', [ $this, 'bosld_process_checkout' ] );
            add_action( 'wp_footer', [ $this, 'alert_error' ] );
        }
	}

    /**
     * Adds MetaBoxes for BadgeOS LD Gateway for Multiple point types
     *
     * @param $post_args
     * @return bool
     */
    public function create_meta_boxes ( $post_type, $post ) {
        add_meta_box(
            'bosldgw-course-buy-meta-box',
            'BadgeOS Buy Course Points',
            [ $this, 'render_meta_box' ],
            'sfwd-courses',
            'side',
            'default'
        );

        add_meta_box(
            'bosldgw-excluded-point-types-meta-box',
            'Exclude BadgeOS Point Types',
            [ $this, 'render_meta_box_point_types' ],
            'sfwd-courses',
            'side',
            'default'
        );
    }

    public function render_meta_box( $post, $callback_args ) {

        $buy_course_credit      = get_post_meta( $post->ID, '_bosld_buy_course_credits', true );
        ?>
        <p>
            <label for="bos-ld-buy-course-points"><strong><?php echo __( 'BadgeOS points to buy this course', WBLG_LANG ); ?></strong></label> <br /> <br />
            <input type="number" min="1" name="bosld_buy_course_points" id="bos-ld-buy-course-points" value="<?php echo $buy_course_credit; ?>" />
        </p>
        <p class="description" style="font-style:italic;">
            <?php _e( 'Enter BadgeOS points to buy this course. Applicable when "Course Price Type" is set to "Buy Now"', WBLG_LANG ); ?>
        </p>
        <?php wp_nonce_field( 'bos_ld_buy_course_meta_box', 'bos_ld_buy_course_meta_box_nonce' );
    }

    /**
     * Render meta_box for BadgeOS exclude point types field
     *
     * @param $post
     * @param $callback_args
     */
    public function render_meta_box_point_types( $post, $callback_args ) {

        $excluded_point_types      = get_post_meta( $post->ID, '_bosld_excluded_course_point_types', true );
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
            <?php _e( 'Exclude BadgeOS point types not to be used for buying or unlock courses/lessons."', WBLG_LANG ); ?>
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

        update_post_meta( $post_id, '_bosld_excluded_course_point_types', sanitize_text_field($_POST['badgeos_ldgw_excluded_point_types']) );
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
        if ( ! isset( $_POST['bos_ld_buy_course_meta_box_nonce'] ) ) {
            return $post_id;
        }

        $nonce = $_POST['bos_ld_buy_course_meta_box_nonce'];

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce, 'bos_ld_buy_course_meta_box' ) ) {
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

        update_post_meta( $post_id, '_bosld_buy_course_credits', sanitize_text_field($_POST['bosld_buy_course_points']) );
    }

    /**
     * Output modified payment button
     * @param  string $default_button Learndash default payment button
     * @param  array  $params         Button parameters
     * @return string                 Modified button
     */
    public function payment_button( $default_button, $params = null ) {

        // Also ensure the price it not zero
        if ( ( !isset( $params['price'] ) ) || ( empty( $params['price'] ) ) ) {
            return $default_button;
        }

        $this->default_button = $default_button;

        if ( isset( $params['post'] ) ) {
            $this->course = $params['post'];
        }
        //dd($this->course);
        $this->bos_ld_button = $this->bos_ld_buy_button();
        if ( !empty( $this->bos_ld_button ) )
            return $default_button . $this->bos_ld_buy_button();
        else
            return $default_button;

    }

    /**
     * BadgeOS LD Gateway payment button
     * @return string Payment button
     */
    public function bos_ld_buy_button() {

        if ( empty( $this->course ) ) return;

        if ( !is_user_logged_in() ) return;

        $user_id = get_current_user_id();

        $meta               = get_post_meta( $this->course->ID, '_sfwd-courses', true );
        $course_name        = $this->course->post_title;
        $course_id          = $this->course->ID;
        $course_plan_id     = 'bos-ld-gw-course-' . $this->course->ID;
        $course_price_type  = @$meta['sfwd-courses_course_price_type'];
        $course_price       = get_post_meta( $this->course->ID, '_bosld_buy_course_credits', true );

        if( empty( $course_price ) || 'paynow' != $course_price_type ) {
            return;
        }

        $user_points = badgeos_get_users_points($user_id);

        if ( $this->is_paypal_active() ) {
            $bos_ld_button_text  = __( 'Use BadgeOS Points', WBLG_LANG );
        } else {
            if ( class_exists('LearnDash_Custom_Label') ) {
                $bos_ld_button_text  = \LearnDash_Custom_Label::get_label( 'button_take_this_course' );
            } else {
                $bos_ld_button_text  = __( 'Take This Course', WBLG_LANG );
            }
        }

        $bos_ld_button_text = apply_filters( 'bosldgw_purchase_button_text', $bos_ld_button_text );

        ob_start();
        ?>
        <div class="learndash_checkout_button badgeos-learndash-button">
            <form name="" action="" method="post" id="bos-ld-checkout-<?php echo $course_id; ?>" class="bos-ld-checkout">
                <input type="hidden" name="action" value="bosldgw" />
                <input type="hidden" name="course_name" value="<?php echo $course_name; ?>" />
                <input type="hidden" name="course_price" value="<?php echo $course_price; ?>" class="bos-ld-course-price">
                <input type="hidden" name="user_points" class="bos-ld-user-points" value='<?php echo $user_points; ?>'>
                <input type="hidden" name="bos_ld_plan_id" value="<?php echo $course_plan_id; ?>" />
                <?php wp_nonce_field( 'bosldgw_buy_course_action', 'bosldgw_buy_course' ); ?>
                <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                <input type="hidden" id="bosldgw_point_type_id" name="bosldgw_point_type_id" value="0">
                <input type="hidden" id="bosldgw_course_points" name="bosldgw_course_points" value="<?php echo $course_price; ?>">

                <input type="submit" value="<?php echo $bos_ld_button_text; ?>" class="bos-ld-checkout-button btn-join button">

            </form>
        </div>
        <?php

        $output	= ob_get_clean();

        return $output;
    }

    /**
     * Check if PayPal is used or not.
     * @return boolean True if active, false otherwise.
     */
    public function is_paypal_active() {
        if ( version_compare( LEARNDASH_VERSION, '2.4.0', '<' ) ) {
            $ld_options   = learndash_get_option( 'sfwd-courses' );
            $paypal_email = isset( $ld_options['paypal_email'] ) ? $ld_options['paypal_email'] : '';
        } else {
            $paypal_email = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_PayPal', 'paypal_email' );
        }

        if ( ! empty( $paypal_email ) ) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Process BadgeOS LD Gateway checkout
     */
    public function bosld_process_checkout() {

        if ( isset( $_POST['action'] ) && $_POST['action'] == 'bosldgw' ) {

            if ( ! $this->is_transaction_legit( $_POST ) ) {
                wp_die( __( 'Sorry but there is something wrong. Please go back and try again.', WBLG_LANG ), 'Something is wrong!', [ 'response' => 403, 'back_link' => TRUE ] );
            }

            $transaction_status = array();
            $transaction_status['bos_ld_gw_message_type']  = '';
            $transaction_status['bos_ld_gw_message']       = '';

            extract($_POST);

            if ( 0 != $user_id ) {
                $user = get_userdata( $user_id );
                $user_email = ( '' != $user->user_email ) ? $user->user_email : '';
            }

            $user_points = badgeos_get_points_by_type(sanitize_text_field($bosldgw_point_type_id), $user_id);

            $credit_value = $user_points;

            if( $credit_value < $course_price) {
                $transaction_status['bos_ld_gw_message_type'] = 'error';
                $transaction_status['bos_ld_gw_message'] = __( 'The Point Type you have chosen, has not enough points to buy this course', WBLG_LANG );
                $this->show_notification( $transaction_status );
            }

            //dd($_POST);
            //$points_subtracted = badgeos_update_users_points( $user_id, -$course_price );

            $this->deduct_points($user_id, $bosldgw_point_type_id, $course_price);

            /*if( $points_subtracted == 0 ) {
                $transaction_status['bos_ld_gw_message_type'] = 'error';
                $transaction_status['bos_ld_gw_message'] = __( 'Some error has occurred. Please try again.', WBLG_LANG );
                $this->show_notification( $transaction_status );
            }*/
            // Associate course with user
            $bos_ld_gw_success = $this->associate_course( $course_id, $user_id );

            if( sfwd_lms_has_access( $course_id, $user_id ) ) {

                $transaction_status['bos_ld_gw_message_type'] = 'success';
                $transaction_status['bos_ld_gw_message'] = __( 'The transaction was successful. You now have access the course.', WBLG_LANG );

            }

            $transaction = $_POST;

            // Log transaction
            $this->record_transaction( $transaction, $course_id, $user_id, $user_email );

            // show success notification
            $this->show_notification( $transaction_status );
        }
    }

    /**
     * Check if BadgeOS LD Gateway transaction is legit
     * @param  array  $post     Transaction form submit $_POST
     * @return boolean          True if legit, false otherwise
     */
    public function is_transaction_legit( $post ) {

        if ( isset( $_POST['bosldgw_buy_course'] ) && wp_verify_nonce( $_POST['bosldgw_buy_course'], 'bosldgw_buy_course_action' ) ) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Associate course with user
     * @param  int $course_id Post ID of a course
     * @param  int $user_id   ID of a user
     */
    public function associate_course( $course_id, $user_id ) {
        return ld_update_course_access( $user_id, $course_id );
    }


    /**
     * Record transaction in database
     * @param  array  $transaction  Transaction data passed through $_POST
     * @param  int    $course_id    Post ID of a course
     * @param  int    $user_id      ID of a user
     * @param  string $user_email   Email of the user
     */
    public function record_transaction( $transaction, $course_id, $user_id, $user_email ) {

        $credit_type_arr    = explode('|', $transaction['selected_point_type']);
        $credit_id          = $credit_type_arr[0];
        $credit_value       = $credit_type_arr[1];

        unset( $transaction['bos_ld_gw_buy_course'] );
        unset( $transaction['point_types'] );
        unset( $transaction['selected_point_type'] );
        unset( $transaction['action'] );
        unset( $transaction['bosldgw_buy_course'] );
        unset( $transaction['bos_ld_plan_id'] );
        unset( $transaction['user_points'] );

        $transaction['user_id']   = $user_id;
        $transaction['course_id'] = $course_id;

        $course_title = sanitize_text_field($_POST['course_name']);

        $post_id = wp_insert_post( array( 'post_title' => "Course {$course_title} Purchased By {$user_email} via BadgeOS Points", 'post_type' => 'sfwd-transactions', 'post_status' => 'publish', 'post_author' => $user_id ) );

        $transaction['currency']    = 'BadgeOS Points';
        $transaction['user_email']  = $user_email;

        foreach ( $transaction as $key => $value ) {
            update_post_meta( $post_id, $key, $value );
        }

        do_action( "bos_ld_gw_user_purchase_after", $post_id, $user_id );
    }

    /**
     * Show notification callback
     */
    function show_notification( $transaction_status = array()) {

        $unique_id = wp_generate_password( 10, false, false );
        $transient_id = 'ld_'. $unique_id;

        set_transient( $transient_id, $transaction_status, HOUR_IN_SECONDS );

        $redirect_url = add_query_arg( 'bos-ld-gw-trans-id', $unique_id );
        wp_redirect( $redirect_url );
        exit();
    }


    /**
     * Output BadgeOS LD Gateway error alert
     */
    public function alert_error() {
        if ( !is_singular( 'sfwd-courses' ) ) return;

        if ( ( isset( $_GET['bos-ld-gw-trans-id'] ) ) && ( !empty( sanitize_text_field($_GET['bos-ld-gw-trans-id']) ) ) ) {

            $transient_id = 'ld_'. sanitize_text_field($_GET['bos-ld-gw-trans-id']);

            $transaction_status = get_transient( $transient_id );

            delete_transient( $transient_id );

            if ( !empty( $transaction_status ) ) {

                if ( ( isset( $transaction_status['bos_ld_gw_message'] ) ) && ( !empty( $transaction_status['bos_ld_gw_message'] ) ) && ( isset( $transaction_status['bos_ld_gw_message_type'] ) ) ) {

                    if ( $transaction_status['bos_ld_gw_message_type'] == 'error' ) {
                        ?>
                        <script type="text/javascript">
                            jQuery( document ).ready( function() {
                                if ( jQuery( '.user_has_no_access' ).length ) {
                                    jQuery( '<p class="learndash-error"><?php echo htmlentities( $transaction_status['bos_ld_gw_message'], ENT_QUOTES ); ?></p>' ).insertBefore( '.user_has_no_access' );
                                }
                            });
                        </script>
                        <?php
                    } else if ( $transaction_status['bos_ld_gw_message_type'] == 'success' ) {
                        ?>
                        <script type="text/javascript">
                            jQuery( document ).ready( function() {
                                if ( jQuery( '.user_has_access' ).length ) {
                                    jQuery( '<p class="learndash-success"><?php echo htmlentities( $transaction_status['bos_ld_gw_message'], ENT_QUOTES ); ?></p>' ).insertBefore( '.user_has_access' );
                                }
                            });
                        </script>
                        <?php
                    }
                }
            }
        }
    }

    private function deduct_points($user_id, $credit_type_id, $points) {
        if( (int) $points > 0 ) {
            badgeos_revoke_credit( $credit_type_id, $user_id, 'Deduct', $points, 'user_credit_used', 0, '', '' );
            badgeos_recalc_total_points($user_id);
        }
    }
}

return new BOSLDGW_Purchase_Course();
