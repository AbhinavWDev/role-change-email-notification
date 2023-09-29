<?php
/*
Plugin Name: Dynamic Role Change Email Notification
Description: The "Dynamic Role Change Email Notification" plugin allows you to send email notifications when a user's role is changed on your WordPress website. You can also customize the email templates with dynamic content.
Version: 1.0
Author: Abhinav Saxena
Author URI: https://abhiwebworks.in/
*/

// Add an admin menu for your plugin
function add_email_settings_menu() {
    add_menu_page(
        'Email Settings',
        'Email Settings',
        'manage_options',
        'email-settings',
        'email_settings_page'
    );
}
add_action('admin_menu', 'add_email_settings_menu');



// Create the email settings page
function email_settings_page() {
    // Handle form submission to save settings
    if (isset($_POST['save_email_settings'])) {
        // Sanitize and save settings
        update_option('email_template_subject', sanitize_text_field($_POST['email_template_subject']));
        update_option('email_template_message', wp_kses_post($_POST['email_template_message']));
        update_option('email_template_button_style', sanitize_text_field($_POST['email_template_button_style']));
        update_option('email_template_button_text', sanitize_text_field($_POST['email_template_button_text']));

        echo '<div class="updated"><p>Email settings saved.</p></div>';
    }

    // Retrieve current settings
    $email_template_subject = get_option('email_template_subject', 'Your Role Has Been Changed');
    $email_template_message = get_option('email_template_message', 'Your Role On Our Website Has Been Changed To <b>{new_role}</b>');
    $email_template_button_style = get_option('email_template_button_style', 'background-color: #178d9b; font-weight: 600; font-size: 12px; border-radius: 5px; color: #ffffff; display: inline-block; padding: 10px 20px; text-decoration: none;');
    $email_template_button_text = get_option('email_template_button_text', 'Reset Your Password');

    // Display the settings form
    ?>
    <div class="wrap">
        <h2>Email Settings</h2>
        <form method="post" action="">
            <label for="email_template_subject">Email Subject:</label><br>
            <input style="width: 50%;" type="text" id="email_template_subject" name="email_template_subject" value="<?php echo esc_attr($email_template_subject); ?>"><br><br>

            <label for="email_template_message">Email Message:</label><br>
            <?php
                $editor_settings = array(
                    'textarea_name' => 'email_template_message',
                    'textarea_rows' => 10,
                    'teeny' => true, // Use the minimal editor
                );
                wp_editor(wp_kses_post($email_template_message), 'email_template_message', $editor_settings);
            ?>
            <br><br>

            <label for="email_template_button_text">Button Text:</label><br>
            <input type="text" id="email_template_button_text" name="email_template_button_text" value="<?php echo esc_attr($email_template_button_text); ?>"><br><br>

            <input type="submit" name="save_email_settings" class="button-primary" value="Save Settings">
        </form>
    </div>
    <?php
}

// Hook into the user role change action
function send_email_on_role_change($user_id, $new_role, $old_roles) {
    // Check if the user role has changed
    if ($new_role !== $old_roles[0]) {
        // Get the user's email
        $user = get_user_by('id', $user_id);
        $user_email = $user->user_email;

        // Retrieve email template settings
        $email_subject = get_option('email_template_subject', 'Your Role Has Been Changed');
        $email_message = get_option('email_template_message', 'Your Role On Our Website Has Been Changed To <b>{new_role}</b>');
        $email_button_style = get_option('email_template_button_style', 'background-color: #178d9b; font-weight: 600; font-size: 12px; border-radius: 5px; color: #ffffff; display: inline-block; padding: 10px 20px; text-decoration: none;');
        $email_button_text = get_option('email_template_button_text', 'Reset Your Password'); // New field

        // Replace placeholders in the email message
        $email_message = str_replace('{new_role}', $new_role, $email_message);

        // Compose the HTML email message with the dynamic button text
        $reset_message = "
            <html>
            <head>
                <title>$email_subject</title>
            </head>
            <body>
                <p>$email_message</p>
                <p>To reset your password, please click on the following button:</p>
                <p><a href=\"$reset_link\" style=\"$email_button_style\">$email_button_text</a></p>
            </body>
            </html>
        ";

        // Set headers to specify HTML content
        $headers = array('Content-Type: text/html; charset=UTF-8');
        

        // Send the combined email with HTML formatting
        wp_mail($user_email, $email_subject, $reset_message, $headers);
    }
}
add_action('set_user_role', 'send_email_on_role_change', 10, 3);
