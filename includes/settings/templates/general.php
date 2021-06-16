<?php
/**
* General Options
*/

if ( ! defined( 'ABSPATH' ) ) exit;


$llmsat_options    = get_option( 'llmsat_options', array() );

$delete_attendance = !empty( $llmsat_options['llmsat_delete_attendance']) ? $llmsat_options['llmsat_delete_attendance'] : 'no';
?>
<div id="llmsat-general-options">
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
		<input type="hidden" name="action" value="llmsat_admin_settings">
		<?php wp_nonce_field( 'llmsat_admin_settings_action', 'llmsat_admin_settings_field' ); ?>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
						<label for="llmsat_delete_attendance">
							<?php _e( 'Delete Attendance On Uninstall  ', 'llms-attendance' ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" name="llmsat_delete_attendance" id="llmsat_delete_attendance"<?php if( $delete_attendance == 'on' ) { ?>checked="checked"<?php } ?> />
						<p class="description"><?php _e( 'If enabled it will delete all courses & users attendance data', 'llms-attendance'); ?></p>
					</td>
				</tr>
				<?php do_action( 'lifterlms_attendance_settings', $llmsat_options ); ?>
			</tbody>
		</table>
		<p>
			<?php
			submit_button( __( 'Save Settings', 'llms-attendance' ), 'primary', 'llmsat_settings_submit' );
			?>
		</p>
	</form>
</div>