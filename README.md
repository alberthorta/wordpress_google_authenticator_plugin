# WordPress Google Authenticator Plugin

## Allows WordPress users to activate Google Authentication on their WordPress login.

## How it work?

WordPress Google Authenticator provides a simple and lightweight plugin to add the Google Double Authentication support on the WordPress user login on certain users.

## How to install it?

Just copy the plugin to your plugin folder and activate it. Then you have to go to users > All Users > {select any user} and activate the Google Authentication there. You have to do it on every user and each of them will have their own secret.

You have to install Google Authenticator App on your phone (ios/android/blackberry) and you can scan the QR code it's shown on the admin page with the App. From that moment on, you can use the code returned by the App instead of the regular password (NOTE: You still can use the old password in order to log into your account).

## Credits

This library is using [Christian Stocker's (GoogleAuthenticator.php library)](https://github.com/chregu/GoogleAuthenticator.php), all credit about this library must go to him.
