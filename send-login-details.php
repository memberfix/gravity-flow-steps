/**
 * Gravity Flow Step: Send Login Details.
 *
 * Company: MemberFix
 * URL: https://memberfix.rocks
 * Author: Denys Melnychuk
 * Date: 31.05.2024
 * Version: 1.1
 */



// Wait until Gravity Flow is ready before declaring the step class.
add_action( 'gravityflow_loaded', function() {

// Define a custom step class
    class Gravity_Flow_Step_Send_Login_Email extends Gravity_Flow_Step {
        
        // Unique identifier for this step type
        public $_step_type = 'send-login-details';
        
        /**
         * Returns the label for the step type.
         *
         * @return string
         */
        public function get_label() {
            return 'Send Login Details';
        }


		        // Define settings fields
				public function get_settings() {
					return array(
						'title'  => 'Memberpress Transaction Details',
						'fields' => array(
							array(
								'name'       => 'email_field',         // Unique id for the field
								'class'      => 'merge-tag-support',   // Adds merge tag option to the field
								'required'   => true,                  // Is required
								'label'      => 'Email field',         // Label of the field
								'type'       => 'text',
							),
							array(
							  'name'       => 'entry_timeline_note',
							  'class'      => 'merge-tag-support',
							  'required'   => true,
							  'label'      => 'Entries timeline note',
							  'type'       => 'text',
						  ),
						),
					);
				}




/**
* Process the step.
*/
        public function process() {

			$entry = $this->get_entry();

// Get settings values
            $email_field = $this->get_setting('email_field');
            $entry_timeline_note = $this->get_setting('entry_timeline_note');

// Replace merge tags in the settings values

$email_field = GFCommon::replace_variables($email_field, $this->get_form(), $entry, false, false, false, 'text');
$entry_timeline_note = GFCommon::replace_variables($entry_timeline_note, $this->get_form(), $entry, false, false, false, 'text');


$user = get_user_by('email', $email_field);
$user_id_value = $user->ID;


            
// Get the user's email address and login details
		
$user_info = get_userdata( $user_id_value );
$user_email = $user_info->user_email;
$user_login = $user_info->user_login;

// Generate a password reset key
$key = get_password_reset_key( $user_info );

// Construct the password reset link
$reset_link = '' . esc_url_raw( add_query_arg( array(
						'action' => 'rp',
						'key' => $key,
						'login' => rawurlencode( $user_login ),
					), wp_login_url() ) ) . '';

// Email content here
$subject = 'Your Login Details';

$message .= "
					
Hello  $user_login, 	            \n
Here are your login details: 			\n

					";
			
// Prepare email headers
$headers = array(
									'From:',
									
                  'Reply-To:',
								);

// Send the email
wp_mail( $user_email, $subject, $message, $headers );


			
// Add a note to the entry's timeline
gravity_flow()->add_timeline_note( 
$entry_id, 'Login credentials email was sent.' );
			
// Return true to indicate the step is complete
            return true;
        }
    }

// Register the custom step class
    Gravity_Flow_Steps::register( new Gravity_Flow_Step_Send_Login_Email() );
});

