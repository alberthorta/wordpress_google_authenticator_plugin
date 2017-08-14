<?php
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//      http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.

/**
	Plugin Name: Google Authentication Plugin
	Description: Activate Google Authentication for admin pages on selected users.
	Version: 1.0
	Author: Albert Horta Llobet, Andre DeMarre
	Author URI: https://github.com/alberthorta/wordpress_google_authenticator_plugin
**/

require_once(dirname(__FILE__)."/classes/GoogleAuthenticator.php");

function my_show_extra_profile_fields($user) {
?>

    <h3>Google Authentication</h3>
    <?php
        $auth_enable = get_user_meta($user->ID, 'google_auth_enable');
        if(is_array($auth_enable) && isset($auth_enable[0]) && $auth_enable[0]==1) {
            $auth_enable=1;
        } else {
            $auth_enable=0;
        }
        $g = new GoogleAuthenticator();
        $secret = get_user_meta($user->ID, 'google_auth_secret');
        if(is_array($secret) && isset($secret[0]) && $secret[0]!="" && $auth_enable==1) {
            $secret = $secret[0];
        } else {
            $secret = $g->generateSecret();
        }
    ?>
    <script language="javascript">
        jQuery().ready(function() {
            jQuery("#admin_google_auth_enable").change(function() {
               jQuery("#admin_google_auth_code").stop();
               if(jQuery(this).is(":checked")) {
                   jQuery("#admin_google_auth_code").fadeIn();
               } else {
                   jQuery("#admin_google_auth_code").fadeOut();
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
        <tr>
            <td>&nbsp;</td>
            <td style="vertical-align: top;">
                <?php
                    $qr = base64_encode(file_get_contents($g->getURL($user->user_login,str_replace(["http://","https://"],"",get_site_url()),$secret,get_bloginfo())));
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
add_action('show_user_profile', 'my_show_extra_profile_fields');
add_action('edit_user_profile', 'my_show_extra_profile_fields');

function my_save_extra_profile_fields($user_id) {
    if ( !current_user_can( 'edit_user', $user_id ) )
        return false;

    update_user_meta( $user_id, 'google_auth_enable', $_POST['admin_google_auth_enable'] );
    if($_POST['admin_google_auth_enable'] == 1) {
        update_user_meta($user_id, 'google_auth_secret', $_POST['admin_google_auth_secret']);
    }
};
add_action('personal_options_update', 'my_save_extra_profile_fields');
add_action('edit_user_profile_update', 'my_save_extra_profile_fields');

function login_form_new_fields(){
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
add_action('login_form', 'login_form_new_fields');

function authenticate_filter($user, $username, $password){
    $google_auth_code_field_name = $_POST['google_auth_code_field_name'];

    $user_login = get_user_by( 'login', $username );
    $user_email = get_user_by( 'email', $username );
    $user = $user_login?$user_login:$user_email;

    if($user) {
        $auth_enable = get_user_meta($user->ID, 'google_auth_enable');
        if(is_array($auth_enable) && isset($auth_enable[0]) && $auth_enable[0]==1) {
            $auth_enable=1;
        } else {
            $auth_enable=0;
        }
        $g = new GoogleAuthenticator();
        $secret = get_user_meta($user->ID, 'google_auth_secret');
        if(is_array($secret) && isset($secret[0]) && $secret[0]!="" && $auth_enable==1) {
            $secret = $secret[0];
        }
        if($auth_enable && $g->checkCode($secret, $google_auth_code_field_name)) {
            remove_action('authenticate', 'wp_authenticate_username_password', 20);
            remove_action('authenticate', 'wp_authenticate_email_password', 20);
            return $user;
        }
    }

    return null;
}
add_filter( 'authenticate', 'authenticate_filter', 10, 3 );

