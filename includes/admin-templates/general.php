<?php
/**
* General Options
*/

if ( ! defined( "ABSPATH" ) ) {
    exit;
}


$wblg_options			= get_option( "wblg_options", array() );

$buy_courses 			= !empty( $wblg_options['buy_courses']) ? $wblg_options['buy_courses'] : 'no';
$unlock_courses 		= !empty( $wblg_options['unlock_courses']) ? $wblg_options['unlock_courses'] : 'no';
$use_remaining_points 	= !empty( $wblg_options['use_remaining_points']) ? $wblg_options['use_remaining_points'] : 'no';
$unlock_lessons 		= !empty( $wblg_options['unlock_lessons']) ? $wblg_options['unlock_lessons'] : 'no';
$exclude_credit_type	= !empty( $wblg_options['exclude_credit_type']) ? $wblg_options['exclude_credit_type'] : '';
$excluded_point_types	= !empty( $wblg_options['excluded_point_types']) ? $wblg_options['excluded_point_types'] : array();

$credit_types = badgeos_get_point_types();
$select_options = array();

if ( is_array( $credit_types ) && ! empty( $credit_types ) ) {
    foreach ($credit_types as $credit_type) {
        $earned_credits = badgeos_get_points_by_type($credit_type->ID);
        if( $earned_credits > 0 ) {
            $credit_type_title  = get_post_meta($credit_type->ID, '_point_plural_name', true);
            $credit_type_title = !empty($credit_type_title) ? $credit_type_title : $credit_type->post_title;
            $select_options[$credit_type->ID] = __( sprintf('%s (%s)', $credit_type_title, $earned_credits), WBLG_LANG );
        }
    }
}
?>


<div id="wblg-general-options">
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
		<input type="hidden" name="action" value="wblg_admin_settings">
		<?php wp_nonce_field( 'wblg_admin_settings_action', 'wblg_admin_settings_field' ); ?>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
						<label for="bosldgw_unlock_courses">
							<?php _e( 'Exclude point types:', WBLG_LANG ); ?>
						</label>
					</th>
					<td>
						<select name="badgeos_ldgw_settings[excluded_point_types][]" id="bosldgw_excluded_point_types" multiple style="width: 15%">
                            <option value="0" <?php selected(empty($excluded_point_types) || in_array( 0, $excluded_point_types)); ?>><?php _e('None', WBLG_LANG); ?></option>
                        <?php foreach ($credit_types as $credit_type): ?>
                            <option value="<?php echo $credit_type->ID; ?>" <?php selected(in_array( $credit_type->ID, $excluded_point_types)); ?>><?php echo $credit_type->post_title; ?></option>
                        <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e( 'Exclude point types which can not be used to redeem points.', WBLG_LANG ); ?></p>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="bosldgw_unlock_courses">
							<?php _e( 'Allow Unlocking courses:', WBLG_LANG ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" class="checkbox" name="badgeos_ldgw_settings[unlock_courses]" id="bosldgw_unlock_courses" value="yes" <?php checked( $unlock_courses, "yes" ); ?>>
						<p class="description"><?php _e( 'Allow unlocking courses using BadgeOS Points.', WBLG_LANG ); ?></p>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="bosldgw_use_remaining_points">
							<?php _e( 'Redeem only remaining points/credits:', WBLG_LANG ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" class="checkbox" name="badgeos_ldgw_settings[use_remaining_points]" id="bosldgw_use_remaining_points" value="yes" <?php checked( $use_remaining_points, "yes" ); ?>>
						<p class="description"><?php _e( 'If checked, only remaining points will be redeemed from BadgeOS Points. If unchecked, actual course points will be redeemed.', WBLG_LANG ); ?></p>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="bosldgw_unlock_lessons">
							<?php _e( 'Allow Unlock Lessons:', WBLG_LANG ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" class="checkbox" name="badgeos_ldgw_settings[unlock_lessons]" id="bosldgw_unlock_lessons" value="yes" <?php checked( $unlock_lessons, "yes" ); ?>>
						<p class="description"><?php _e( 'If checked, students will be able to unlock dripped lessons by spending BadgeOS Points.', WBLG_LANG ); ?></p>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="bosldgw_buy_courses">
							<?php _e( 'Buy courses using BadgeOS Points:', WBLG_LANG ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" class="checkbox" name="badgeos_ldgw_settings[buy_courses]" id="bosldgw_buy_courses" value="yes" <?php checked( $buy_courses, "yes" ); ?>>
						<p class="description"><?php _e( 'Allow students to buy courses using BadgeOS Points.', WBLG_LANG ); ?></p>
					</td>
				</tr>

				<!-- <tr valign="top">
					<th scope="row" colspan="2">
						<h4 id="badgeos-ld-gateway-settings" style="margin-bottom: 0;"><?php _e("BadgeOS LearnDash Gateway License Configuration", WBLG_LANG); ?></h4>
					</th>
				</tr> -->
				<!-- <tr valign="top">
					<th scope="row">
						<label for="bosldgw_license_key">
							<?php _e( 'License Key:', WBLG_LANG ); ?>
						</label>
					</th>
					<td>
						<input type="text" class="regular-text" name="badgeos_ldgw_settings[license_key]" id="bosldgw_license_key" value="<?php echo $license_key; ?>" placeholder="<?php _e("Enter license key provided with plugin", WBLG_LANG) ?>">
				
						<span class="badgeos-license-status inactive">License Status: <strong>Inactive</strong></span>
				
						<p class="description"><?php _e( 'Please enter the license key for this product to get automatic updates. You were emailed the license key when you purchased this item.', WBLG_LANG ); ?></p>
					</td>
				</tr> -->
				<?php do_action( "badgeos_learndash_gateway_settings", $wblg_options ); ?>
			</tbody>
		</table>
		<p>
			<?php
			submit_button( __("Save Settings", WBLG_LANG), "primary", "wblg_settings_submit" );
			?>
		</p>
	</form>
</div>