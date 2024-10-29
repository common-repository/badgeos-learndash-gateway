<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BadgeOS_LD_Gateway_Admin_Settings
 */
class BadgeOS_LD_Gateway_Admin_Settings {

    public $page_tab;

    public function __construct() {

        $this->page_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';

        add_filter( 'admin_footer_text', [ $this, 'remove_footer_admin' ] );
        add_action( 'admin_menu', [ $this, 'wblg_admin_settings_page'] );
        add_action( 'admin_post_wblg_admin_settings', [ $this, 'wblg_admin_settings_save' ] );
        add_action( 'admin_notices', [ $this, 'wblg_admin_notices'] );
	}

    /**
     *  Save plugin options
     */
    public function wblg_admin_settings_save() {
        if( isset($_POST['wblg_settings_submit']) ) {
            update_option( 'wblg_options', $_POST['badgeos_ldgw_settings'], array() );
            wp_safe_redirect( add_query_arg( 'settings-updated', 'true', $_POST['_wp_http_referer'] ) );
            exit;
        }
    }

    /**
     * Display Notices
     */
    public function wblg_admin_notices() {

        $screen = get_current_screen();
        if( $screen->base != 'badgeos_page_badgeos_learndash_gateway_settings' ) {
            return;
        }

        if( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == 'true' ) {
            $class = 'notice notice-success is-dismissible';
            $message = __( 'Settings Saved', WBLG_LANG );
            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
        }
    }

    /**
     * Create admin settings page
     */
    public function wblg_admin_settings_page() {
        add_submenu_page(
            'badgeos_badgeos',
            __( 'BadgeOS LearnDash Gateway', WBLG_LANG),
            __( 'BadgeOS LearnDash Gateway', WBLG_LANG),
            'manage_options',
            'badgeos_learndash_gateway_settings',
            [ $this, 'wblg_settings_callback_func' ]
        );
    }

    /**
     * Callback function for Setting Page
     */
    public function wblg_settings_callback_func() {
        ?>
        <div class="wrap">
            <div class="icon-options-general icon32"></div>
            <h1><?php echo __( 'BadgeOS LearnDash Gateway Settings', WBLG_LANG ); ?></h1>

            <div class="nav-tab-wrapper">
                <?php
                $bosldgw_settings_sections = $this->bosldgw_get_setting_sections();
                foreach( $bosldgw_settings_sections as $key => $bosldgw_settings_section ) {
                    ?>
                    <a href="?page=badgeos_learndash_gateway_settings&tab=<?php echo $key; ?>"
                       class="nav-tab <?php echo $this->page_tab == $key ? 'nav-tab-active' : ''; ?>">
                        <span class="dashicons <?php echo $bosldgw_settings_section['icon']; ?>"></span>
                        <?php _e( $bosldgw_settings_section['title'], WBLG_LANG ); ?>
                    </a>
                    <?php
                }
                ?>
            </div>

            <?php
            foreach( $bosldgw_settings_sections as $key => $bosldgw_settings_section ) {
                if( $this->page_tab == $key ) {
                    include( 'admin-templates/' . $key . '.php' );
                }
            }
            ?>
        </div>
        <?php
    }

    /**
     * WBLG Settings Sections
     *
     * @return mixed|void
     */
    public function bosldgw_get_setting_sections() {

        $wblg_settings_sections = array(
            'general' => array(
                'title' => __( 'General Options', WBLG_LANG ),
                'icon' => 'dashicons-admin-generic',
            )
        );

        return apply_filters( 'wblg_settings_sections', $wblg_settings_sections );
    }

    /**
     * Add footer branding
     *
     * @param $footer_text
     * @return mixed
     */
    function remove_footer_admin ( $footer_text ) {
        if( isset( $_GET['page'] ) && ( $_GET['page'] == 'badgeos_learndash_gateway_settings' ) ) {
            _e('Fueled by <a href="http://www.wordpress.org" target="_blank">WordPress</a> | developed and designed by <a href="https://wooninjas.com" target="_blank">The WooNinjas</a></p>', WBLG_LANG );
        } else {
            return $footer_text;
        }
    }
}

$GLOBALS['badgeos_learndash_gateway_options'] = new BadgeOS_LD_Gateway_Admin_Settings();