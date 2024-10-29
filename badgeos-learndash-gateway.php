<?php
/**
 * Plugin Name: BadgeOS LearnDash Gateway
 * Description: The BadgeOS LearnDash Gateway addon lets your user redeem their remaining BadgeOS points. These points can be used to make purchases on your LearnDash site. You can now make your e-learning platform even more engaging.
 * Author: BadgeOS
 * Version: 1.0
 * Author URI: https://badgeos.org
 */

class BadgeOS_LearnDash_Gateway {

	const VERSION = '1.0';

	public static $plugin_options;

	/**
	 * Get everything running.
	 */
	function __construct() {

		// Define plugin constants
		$this->basename       = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url  = plugins_url( dirname( $this->basename ) );

		self::$plugin_options = get_option( 'wblg_options' );

		// Load translations
		//load_plugin_textdomain( 'badgeos-ld-gateway', false, dirname( $this->basename ) . '/languages' );

		// Run our activation and deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// If BadgeOS and LearnDash are unavailable, deactivate our plugin
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );

		$this->setup_constants();

		add_action( 'plugin_action_links_' . $this->basename, [ $this, "wblg_action_links" ] );

		// Include our other plugin files
		add_action( 'init', array( $this, 'includes' ) );

		add_action( 'init', array( $this, 'hooks' ) );



	} /* __construct() */


	/**
	 * Setup Constants
	 */
	private function setup_constants() {

		/**
		 * Directory
		 */
		define( 'WBLG_DIR', plugin_dir_path( __FILE__ ) );
		define( 'WBLG_LANG', 'badgeos-ld-gateway' );
		define( 'WBLG_DIR_FILE', WBLG_DIR . basename( __FILE__ ) );
		define( 'WBLG_INCLUDES_DIR', trailingslashit( WBLG_DIR . 'includes' ) );
		define( 'WBLG_BASE_DIR', plugin_basename( __FILE__ ) );
		define( 'WBLG_TEMPLATES_DIR', trailingslashit ( WBLG_DIR . 'templates' ) );

		/**
		 * URLS
		 */
		define( 'WBLG_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );
		define( 'WBLG_ASSETS_URL', trailingslashit( WBLG_URL . 'assets' ) );
	}

	/**
	 *
	 */
	public function wblg_action_links( $links ) {
		$setting = array('<a href="' . esc_url( admin_url( '/admin.php?page=badgeos_learndash_gateway_settings' ) ) . '">' . __( 'Settings', WBLG_LANG ) . '</a>');
		return array_merge( $links, $setting );
	}


	/**
	 * Include our plugin dependencies
	 *
	 * @since 1.0.0
	 */
	public function includes() {

		// If BadgeOS is available...
		if ( $this->meets_requirements() ) {

			if ( file_exists( WBLG_INCLUDES_DIR . 'admin.php' ) ) {
				require_once( WBLG_INCLUDES_DIR . 'admin.php' );
			}

			if ( file_exists( WBLG_INCLUDES_DIR . 'course-buy.php' ) ) {
				require_once( WBLG_INCLUDES_DIR . 'course-buy.php' );
			}

			if ( file_exists( WBLG_INCLUDES_DIR . 'unlock-dripped-lesson.php' ) ) {
				require_once( WBLG_INCLUDES_DIR . 'unlock-dripped-lesson.php' );
			}

			if ( file_exists( WBLG_INCLUDES_DIR . 'unlock-course.php' ) ) {
				require_once( WBLG_INCLUDES_DIR . 'unlock-course.php' );
			}

            if( file_exists( WBLG_INCLUDES_DIR . 'helper-functions.php' ) ) {
                require_once ( WBLG_INCLUDES_DIR . 'helper-functions.php' );
            }
		}

	} /* includes() */



	public function hooks() {

		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'frontend_enqueue_scripts' ], 11 );
	}

	/**
	 * Activation hook for the plugin.
	 *
	 * @since 1.0.0
	 */
	public function activate() {

		// If BadgeOS is available, run our activation functions
		if ( $this->meets_requirements() ) {

			// Do some activation things

		}

	} /* activate() */

	/**
	 * Deactivation hook for the plugin.
	 *
	 * Note: this plugin may auto-deactivate due
	 * to $this->maybe_disable_plugin()
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {

		// Do some deactivation things.

	} /* deactivate() */


	/**
	 * Enqueue scripts on admin
	 *
	 * @param string $hook
	 * @since 1.0
	 */
	public function admin_enqueue_scripts( $hook ) {
		$screen = get_current_screen();

		/**
		 * plugin's admin script
		 */
		wp_enqueue_style( 'bosldgw-admin-style', WBLG_ASSETS_URL . 'css/admin-style.css', null, time(), null );

		wp_register_script( 'bosldgw-admin-script', WBLG_ASSETS_URL . 'js/admin-script.js', [ 'jquery' ], time(), true );
		wp_enqueue_script( 'bosldgw-admin-script' );
	}


	/**
	 * Enqueue scripts on frontend
	 *
	 * @since 1.0
	 */
	public function frontend_enqueue_scripts() {

		wp_register_script( "bosldgw-gw-sweetalert", WBLG_ASSETS_URL . 'js/sweet-alert.min.js', array( 'jquery' ), '', true );
		wp_register_script( 'bosldgw-gw-front-script', WBLG_ASSETS_URL . 'js/front-script.js', [ 'jquery', 'bosldgw-gw-sweetalert'], time(), true );
        wp_register_script( "bosldgw-gw-sweetalert2", WBLG_ASSETS_URL . 'js/sweetalert2.min.js', array( 'jquery' ), '', true );

		global $post;
        $required_points = '';
        $user_id = get_current_user_id();
        $default_values = self::$plugin_options;

        if( is_singular( 'sfwd-courses' ) || is_singular( 'sfwd-lessons' ) ) {
            wp_enqueue_style( 'bosldgw-front-style', WBLG_ASSETS_URL . 'css/front-style.css' );
            wp_enqueue_script( 'bosldgw-gw-sweetalert2' );
            wp_add_inline_script( 'bosldgw-gw-sweetalert2', 'var swal2 = swal;' );
            wp_enqueue_script( 'bosldgw-gw-sweetalert' );
            wp_enqueue_script( 'bosldgw-gw-front-script' );

            wp_enqueue_style( 'bosldgw-sweetalert2-style', WBLG_ASSETS_URL . 'css/sweetalert2.min.css' );
        }

        $unlock_courses         = isset( $default_values['unlock_courses'] ) ? $default_values['unlock_courses'] : '';
        $use_remaining_points   = isset( $default_values['use_remaining_points'] ) ? $default_values['use_remaining_points'] : '';
        $buy_courses           	= isset( $default_values['buy_courses'] ) ? $default_values['buy_courses'] : '';

        if( is_singular( 'sfwd-courses' ) ) {

            $course_meta            = get_post_meta( $post->ID, '_sfwd-courses', true );
            $buy_course_points      = get_post_meta( $post->ID, '_bosld_buy_course_credits', true );

            $user_course_points     = learndash_get_user_course_points( $user_id );

            $course_access_points           = @$course_meta['sfwd-courses_course_points_access'];
            $course_access_points_enabled   = @$course_meta['sfwd-courses_course_points_enabled'];

            if( $use_remaining_points == 'yes') {
                $point_to_remove    = floatval( $course_access_points ) - floatval( $user_course_points );
            } else {
                $point_to_remove    = floatval( $course_access_points );
            }

            if( $buy_courses == 'yes' && @$course_meta['sfwd-courses_course_price_type'] == 'paynow' ) {
                $required_points = $buy_course_points;
            }

            if( $course_access_points_enabled == "on" && $user_course_points != $course_access_points && 'yes' == $unlock_courses ) {
                $required_points = $point_to_remove;
            }
        }

		if( is_singular( 'sfwd-lessons' ) ) {
            $required_points     =   get_post_meta( $post->ID, '_bos_ld_unlock_lesson_points', true );
        }

        $excluded_point_types = boslgw_get_excluded_point_types($post);
        $credit_types = badgeos_get_point_types();
        $select_options = array();
        if ( is_array( $credit_types ) && ! empty( $credit_types ) ) {
            foreach ($credit_types as $credit_type) {
                if( !in_array($credit_type->ID, $excluded_point_types) ) {
                    $earned_credits = absint( badgeos_get_points_by_type($credit_type->ID, $user_id) );
                    //if ($earned_credits > 0) {
                        $credit_type_title = get_post_meta($credit_type->ID, '_point_plural_name', true);
                        $credit_type_title = !empty($credit_type_title) ? $credit_type_title : $credit_type->post_title;
                        $select_options[$credit_type->ID] = __(sprintf('%s (%s)', $credit_type_title, $earned_credits), WBLG_LANG);
                    //}
                }
            }
        }

		wp_localize_script( 'bosldgw-gw-front-script', 'bosldgw_objects',
            array(
                'ajaxurl'                   => admin_url( 'admin-ajax.php' ),
                'bosld_required_points'  	=> $required_points,
                'bosld_required_points_msg' => __( 'You do not have enough credits of ', WBLG_LANG ),
                'confirm_points'            => array(
                    'title'                 => __( 'Are you sure?', WBLG_LANG ),
                    'text'                  => __( "{ACTUAL_POINTS} points out of total {TOTAL_POINT} points will be deducted from your BadgeOS Points!", WBLG_LANG ),
                    'btn_text'              => __( 'Okay', WBLG_LANG ),
                    'btn_cancel_text'       => __( 'Cancel', WBLG_LANG )
                ),
                'bos_points'            => array(
                    'title'                 => __( 'Use BadgeOS Points', WBLG_LANG ),
                    'text'                  => __( "{ACTUAL_POINTS} points will be deducted from your BadgeOS Points!", WBLG_LANG ),
                    'btn_text'              => __( 'Okay', WBLG_LANG ),
                    'btn_cancel_text'       => __( 'Cancel', WBLG_LANG ),
                    'select_placeholder'    => __( 'Select Points Type?', WBLG_LANG ),
                    'select_empty_error'    => __( 'Please select point type.', WBLG_LANG ),
                    'select_options'        => $select_options
                ),
                'not_enough_points'         => array(
                    'title'                 => __( 'Sorry', WBLG_LANG ),
                    'text'                  => __( 'You do not have enough amount of points of type', WBLG_LANG ),
                    'btn_text'              => __( 'Okay', WBLG_LANG ),
                ),
            )
        );
	}

	/**
	 * Check if BadgeOS is available
	 *
	 * @since  1.0.0
	 * @return bool True if BadgeOS is available, false otherwise
	 */
	public static function meets_requirements() {

		if ( class_exists('BadgeOS') && !class_exists('SFWD_LMS') )
			return false;
		if ( !class_exists('BadgeOS') && class_exists('SFWD_LMS') )
			return false;
		if ( !class_exists('BadgeOS') && !class_exists('SFWD_LMS') )
			return false;
		if ( class_exists('BadgeOS') && class_exists('SFWD_LMS') )
			return true;
		else
			return false;

	} /* meets_requirements() */

	/**
	 * Potentially output a custom error message and deactivate
	 * this plugin, if we don't meet requriements.
	 *
	 * This fires on admin_notices.
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_plugin() {

		if ( ! $this->meets_requirements() ) {

		    $badgeos_activated = class_exists( 'BadgeOS' );
            $learndash_activated = class_exists( 'SFWD_LMS' );

            if ( !$badgeos_activated || !$learndash_activated ) {

                unset($_GET['activate']);
                $message = __('<div id="message" class="error"><p><strong>BadgeOS LearnDash Gateway</strong> requires both <a href="%s" target="_blank">%s</a> and <a href="%s" target="_blank">%s</a> add-ons to be activated.</p></div>', 'badgeos-learndash');
                echo sprintf($message,
                    'https://badgeos.org/',
                    'BadgeOS',
                    'https://www.learndash.com/',
                    'LearnDash'
                );
            }

			// Deactivate our plugin
			deactivate_plugins( $this->basename );
		}

	} /* maybe_disable_plugin() */

} /* BadgeOS_LearnDash_Gateway */


if( !function_exists("dd") ) {

	/**
	 * @since 1.0
	 * @return $data prints $data wrapped within pre tag
	 */
	function dd( $data, $exit_data = true) {
	  	echo '<pre>'.print_r($data, true).'</pre>';
	  	if($exit_data == false)
	    	echo '';
	  	else
	    	exit;
	}
}

/**
 * @return WBLG|bool
 */
function WBLG() {
    $GLOBALS['badgeos_ld_gateway'] = new BadgeOS_LearnDash_Gateway();
}
add_action( 'plugins_loaded', 'WBLG' );

function debug_log($var, $print=true) {
    ob_start();
    if( $print ) {
        if( is_object($var) || is_array($var) ) {
            echo print_r($var, true);
        } else {
            echo $var;
        }
    } else {
        var_dump($var);
    }
    error_log(ob_get_clean());
}
