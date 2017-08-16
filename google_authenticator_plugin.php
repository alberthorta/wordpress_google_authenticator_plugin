<?php
/*
    Google Authentication Plugin is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 2 of the License, or
    any later version.

    Google Authentication Plugin is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Google Authentication Plugin. If not, see https://github.com/alberthorta/wordpress_google_authenticator_plugin.
*/

/**
	Plugin Name: Google Authentication Plugin
    Plugin URI: https://github.com/alberthorta/wordpress_google_authenticator_plugin
    Description: Activate Google Authentication for admin pages on selected users.
	Version: 20170813
    License:     GPL2
    License URI: https://www.gnu.org/licenses/gpl-2.0.html
	Author: Albert Horta Llobet, Andre DeMarre
	Author URI: https://github.com/alberthorta
**/

require_once(dirname(__FILE__)."/classes/GoogleAuthenticator.php");

function google_authenticator_is_auth_enabled($user) {
    $auth_enable = get_user_meta($user->ID, 'google_auth_enable');
    if(is_array($auth_enable) && isset($auth_enable[0]) && $auth_enable[0]==1) {
        $auth_enable=1;
    } else {
        $auth_enable=0;
    }
    return $auth_enable;
}

function google_authenticator_is_auth_double($user) {
    $auth_double = get_user_meta($user->ID, 'google_auth_double');
    if(is_array($auth_double) && isset($auth_double[0]) && $auth_double[0]==1) {
        $auth_double=1;
    } else {
        $auth_double=0;
    }
    return $auth_double;
}

function google_authenticator_get_secret($user) {
    $g = new GoogleAuthenticator();
    $secret = get_user_meta($user->ID, 'google_auth_secret');
    if(is_array($secret) && isset($secret[0]) && $secret[0]!="" && google_authenticator_is_auth_enabled($user)==1) {
        $secret = $secret[0];
    } else {
        $secret = $g->generateSecret();
    }
    return $secret;
}

function google_authenticator_plugin_deactivation()
{
    delete_metadata('user', -1, 'google_auth_enable', '', true);
    delete_metadata('user', -1, 'google_auth_secret', '', true);
    delete_metadata('user', -1, 'google_auth_double', '', true);
}
register_deactivation_hook( __FILE__, 'google_authenticator_plugin_deactivation' );

function google_authenticator_plugin_show_extra_profile_fields($user) {
?>

    <h3>Google Authentication</h3>
    <?php
        $auth_enable = google_authenticator_is_auth_enabled($user);
        $auth_double = google_authenticator_is_auth_double($user);
        $secret = google_authenticator_get_secret($user);
    ?>
    <script language="javascript">
        jQuery().ready(function() {
            jQuery("#admin_google_auth_enable").change(function() {
               jQuery("#admin_google_auth_code").stop();
                jQuery("#double_admin_check").stop();
               if(jQuery(this).is(":checked")) {
                   jQuery("#admin_google_auth_code").fadeIn();
                   jQuery("#double_admin_check").fadeIn();
               } else {
                   jQuery("#admin_google_auth_code").fadeOut();
                   jQuery("#double_admin_check").fadeOut();
               }
            });
        });
    </script>
    <table class="form-table">
        <tr>
            <th><label for="admin_bar_front">Enable</label></th>

            <td style="vertical-align: top;">
                <label for="admin_bar_front">
                    <input
                        name="admin_google_auth_enable"
                        id="admin_google_auth_enable"
                        value="1"
                        <?php if($auth_enable==1) echo('checked="checked"'); ?>
                        type="checkbox"
                        onclick=""
                    >
                    Enable Google Authentication for this user.
                </label>
                <input type="hidden" name="admin_google_auth_secret" id="admin_google_auth_secret" value="<?php echo($secret); ?>">
            </td>
        </tr>
        <tr id="double_admin_check" style='<?php echo(($auth_enable==1?"":"display: none;")); ?>'>
            <th><label for="admin_bar_front_double">Regular Password</label></th>

            <td style="vertical-align: top;">
                <label for="admin_bar_front_double">
                    <input
                            name="admin_google_auth_double"
                            id="admin_google_auth_double"
                            value="1"
                        <?php if($auth_double==1) echo('checked="checked"'); ?>
                            type="checkbox"
                            onclick=""
                    >
                    Password field must be also valid (Double Authentication).
                </label>
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td style="vertical-align: top;">
                <?php
                    $qr = base64_encode(file_get_contents((new GoogleAuthenticator())->getURL($user->user_login,str_replace(["http://","https://"],"",get_site_url()),$secret,get_bloginfo())));
                ?>
                <div id='admin_google_auth_code' style='width:200px; <?php echo(($auth_enable==1?"":"display: none;")); ?>'>
                    <span style='display:block; text-align:center; font-size:12px; font-weight: bold;'>
                        <span style='color:#3369E8'>G</span><span style='color:#D50F25'>o</span><span style='color:#EEB211'>o</span><span style='color:#3369E8'>g</span><span style='color:#009925'>l</span><span style='color:#D50F25'>e</span>
                        Authentication Code
                    </span>
                    <img src='data:image/png;base64,<?php echo($qr); ?>'/>
                    <span style='display:block; text-align:center; font-size:10px;'>
                        Code: <?php echo($secret); ?>
                    </span>
                </div>
            </td>
        </tr>
    </table>
<?php
};
add_action('show_user_profile', 'google_authenticator_plugin_show_extra_profile_fields');
add_action('edit_user_profile', 'google_authenticator_plugin_show_extra_profile_fields');

function google_authenticator_plugin_save_extra_profile_fields($user_id) {
    if ( !current_user_can( 'edit_user', $user_id ) )
        return false;

    update_user_meta( $user_id, 'google_auth_enable', $_POST['admin_google_auth_enable'] );
    if($_POST['admin_google_auth_enable'] == 1) {
        update_user_meta($user_id, 'google_auth_secret', $_POST['admin_google_auth_secret']);
        update_user_meta($user_id, 'google_auth_double', $_POST['admin_google_auth_double']);
    }
};
add_action('personal_options_update', 'google_authenticator_plugin_save_extra_profile_fields');
add_action('edit_user_profile_update', 'google_authenticator_plugin_save_extra_profile_fields');

function google_authenticator_plugin_login_form_new_fields(){
    ?>
    <div style="text-align: center; margin-top: 10px; margin-bottom: 15px;"><p>.. or use ..</p></div>
    <p>
        <label for="google_auth_code_field">
            <span style='color:#3369E8'>G</span><span style='color:#D50F25'>o</span><span style='color:#EEB211'>o</span><span style='color:#3369E8'>g</span><span style='color:#009925'>l</span><span style='color:#D50F25'>e</span>
            Authentication Code<br>
            <input type="text" size="6" value="" class="input" id="google_auth_code_field" name="google_auth_code_field_name"></label>
    </p>
    <?php
}
add_action('login_form', 'google_authenticator_plugin_login_form_new_fields');

function google_authenticator_plugin_authenticate_filter($user, $username, $password){
    $google_auth_code_field_name = $_POST['google_auth_code_field_name'];

    $user_login = get_user_by( 'login', $username );
    $user_email = get_user_by( 'email', $username );
    $user = $user_login?$user_login:$user_email;

    if($user) {
        $auth_enable = google_authenticator_is_auth_enabled($user);
        $auth_double = google_authenticator_is_auth_double($user);
        $secret = google_authenticator_get_secret($user);
        $g = new GoogleAuthenticator();
        if($auth_enable) {
            remove_action('authenticate', 'wp_authenticate_username_password', 20);
            remove_action('authenticate', 'wp_authenticate_email_password', 20);
            if(!$g->checkCode($secret, $google_auth_code_field_name)) {
                return null;
            }
            if($auth_double==1) {
                $auth_username_password = wp_authenticate_username_password(null, $username, $password);
                $auth_email_password = wp_authenticate_email_password(null, $username, $password);
                if($auth_username_password!=null) {
                    return $auth_username_password;
                } else if($auth_email_password!=null) {
                    return $auth_email_password;
                }
                return null;
            }
            return $user;
        }
    }

    return null;
}
add_filter( 'authenticate', 'google_authenticator_plugin_authenticate_filter', 10, 3 );

