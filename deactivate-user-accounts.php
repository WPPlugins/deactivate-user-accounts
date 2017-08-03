<?php
/*
Plugin Name: Deactivate User Accounts
Plugin URI: http://blogzpot.com/
Description: Gives you the ability to temporarily disable user accounts without having to delete them.
Version: 1.0
Author: Kevin Ardy
Author URI: http://blogzpot.com/
License: GPL2
*/

/*
* On login attempt check if user account is active
*/

function wpduact_check_login($user, $username, $password) {
	if(is_wp_error($user))
		return $user;

    $meta = get_user_meta($user->ID, 'wpduact_status', true);
    if ($meta == 'inactive') {
		return new WP_Error('wpduact_inactive', 'Your account has been temporarily deactivated.');
    }
    else {
        return $user;
    }
}
add_filter('authenticate', 'wpduact_check_login', 100, 3);

/*
* Insert new items to the bulk actions dropdown on users.php
*/

function wpduact_bulk_admin_footer() {
	if(!current_user_can('activate_plugins'))
		return;
?>
		<script type="text/javascript">
			jQuery(document).ready(function($) { 
				$('<option>').val('wpduact_deactivate_account').text('Deactivate').appendTo("select[name='action']"); 
				$('<option>').val('wpduact_activate_account').text('Activate').appendTo("select[name='action']"); 
			});
		</script>
<?php
}
add_action('admin_footer-users.php', 'wpduact_bulk_admin_footer');

/*
* Perform bulk actions on form submit
*/
 
function wpduact_users_bulk_action() {
	if(!current_user_can('activate_plugins'))
		return;

	$wp_list_table = _get_list_table('WP_Users_List_Table');
	$action = $wp_list_table->current_action();
	switch($action) {
		case 'wpduact_deactivate_account':
			$user_ids = $_GET['users'];
			$deactivated = 0;
			foreach( $user_ids as $user_id ) {
				if(get_current_user_id() != $user_id){
					update_user_meta($user_id, 'wpduact_status', 'inactive');
					$deactivated++;
				}
			}
			$sendback = add_query_arg( array('deactivated' => $deactivated ), $sendback );
		break;
		case 'wpduact_activate_account':
			$user_ids = $_GET['users'];
			$activated = 0;
			foreach( $user_ids as $user_id ) {
				update_user_meta($user_id, 'wpduact_status', 'active');
				$activated++;
			}
			$sendback = add_query_arg( array('activated' => $activated ), $sendback );
		break;
		default: return;
	}
	wp_redirect($sendback);
	exit();
}
add_action('load-users.php', 'wpduact_users_bulk_action');

/*
* Display admin notice on activation and deactivation of accounts
*/

function custom_bulk_admin_notices() {
	global $pagenow;
	if($pagenow == 'users.php'){
		if(isset($_REQUEST['deactivated']) && (int) $_REQUEST['deactivated']) {
			$message = sprintf( _n( 'User account deactivated.', '%s user accounts deactivated.', $_REQUEST['deactivated'] ), number_format_i18n( $_REQUEST['deactivated'] ) );
			echo "<div class=\"updated\"><p>$message</p></div>";
		}
		elseif(isset($_REQUEST['activated']) && (int) $_REQUEST['activated']){
			$message = sprintf( _n( 'User account activated.', '%s user accounts activated.', $_REQUEST['activated'] ), number_format_i18n( $_REQUEST['activated'] ) );
			echo "<div class=\"updated\"><p>$message</p></div>";
		}
	}
}
add_action('admin_notices', 'custom_bulk_admin_notices');

/*
* Display status of each account in the WordPress users table
*/

function wpduact_add_user_id_column($columns) {
    $columns['wpduact_status'] = 'Account Status';
    return $columns;
}
add_filter('manage_users_columns', 'wpduact_add_user_id_column');
 
function wpduact_show_user_id_column_content($value, $column_name, $user_id) {
    $account_status = get_user_meta( $user_id, 'wpduact_status', true );
	if ( 'wpduact_status' == $column_name )
		return (!$account_status)?'Active':ucfirst($account_status);
    return $value;
}
add_action('manage_users_custom_column',  'wpduact_show_user_id_column_content', 10, 3);

?>