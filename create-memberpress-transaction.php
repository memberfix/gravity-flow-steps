/**
 * Gravity Flow Custom Step: Create MemberPress Transaction.
 *
 * Description: Custom step in Gravity Flow to create a new MemberPress transaction.
 *
 * Company: MemberFix 
 * URL: https://memberfix.rocks
 * Author: Denys Melnychuk
 * Date: 27.05.2025
 * Version: 1.3
 */

add_action('gravityflow_loaded', function() {

    class Gravity_Flow_Step_Add_Mepr_Txn extends Gravity_Flow_Step {

        public $_step_type = 'add_mepr_txn_step';

        public function get_label() {
            return 'Create MemberPress Transaction';
        }

        public function get_icon_url() {
            return '<i class="fa fa-indent"></i>';
        }

        public function get_settings() {
            return array(
                'title'  => 'MemberPress Transaction Details',
                'fields' => array(
                    array(
                        'name'     => 'user_id',
                        'class'    => 'merge-tag-support',
                        'required' => true,
                        'label'    => 'User ID',
                        'type'     => 'text',
                    ),
                    array(
                        'name'     => 'membership_id',
                        'class'    => 'merge-tag-support',
                        'required' => true,
                        'label'    => 'Membership ID',
                        'type'     => 'text',
                    ),
                    array(
                        'name'     => 'membership_term',
                        'class'    => 'merge-tag-support',
                        'required' => true,
                        'label'    => 'Membership Term (in years)',
                        'type'     => 'text',
                    ),
                    array(
                        'name'     => 'entry_timeline_note',
                        'class'    => 'merge-tag-support',
                        'required' => true,
                        'label'    => 'Entry Timeline Note',
                        'type'     => 'text',
                    ),
                    array(
                        'name'     => 'start_date',
                        'class'    => 'merge-tag-support',
                        'required' => true,
                        'label'    => 'Start Date',
                        'type'     => 'text',
                    ),
                ),
            );
        }

        public function process() {
            $entry = $this->get_entry();

            $user_id           = GFCommon::replace_variables($this->get_setting('user_id'), $this->get_form(), $entry, false, false, false, 'text');
            $membership_id     = GFCommon::replace_variables($this->get_setting('membership_id'), $this->get_form(), $entry, false, false, false, 'text');
            $membership_term   = GFCommon::replace_variables($this->get_setting('membership_term'), $this->get_form(), $entry, false, false, false, 'text');
            $entry_timeline_note = GFCommon::replace_variables($this->get_setting('entry_timeline_note'), $this->get_form(), $entry, false, false, false, 'text');
            $start_date        = GFCommon::replace_variables($this->get_setting('start_date'), $this->get_form(), $entry, false, false, false, 'text');


            // Parse start date
            $start_date_object = new DateTime($start_date);
            if (!$start_date_object) {
                error_log('Invalid start date: ' . $start_date);
                return false;
            }
            $start_date_formatted = $start_date_object->format('Y-m-d H:i:s');

            // Calculate expiration date based on membership term
            $expiration_date_object = clone $start_date_object;
            $expiration_date_object->modify("+{$membership_term} years");
            $expiration_date_formatted = $expiration_date_object->format('Y-m-d H:i:s');

            // Create the transaction
            $txn = new MeprTransaction();
            $txn->amount     = 0.00;
            $txn->total      = 0.00;
            $txn->user_id    = $user_id;
            $txn->product_id = $membership_id;
            $txn->status     = MeprTransaction::$complete_str;
            $txn->txn_type   = MeprTransaction::$payment_str;
            $txn->gateway    = 'manual';
            $txn->created_at = $start_date_formatted;
            $txn->expires_at = $expiration_date_formatted;
            $txn->store();

            // Record event
            $event = MeprEvent::record('transaction-completed', $txn);
            do_action('mepr-event-transaction-completed', $event);
            //error_log('Triggered mepr-event-transaction-completed');

            // Add note to entry timeline
            gravity_flow()->add_timeline_note($entry['id'], $entry_timeline_note);

            return true;
        }
    }

    Gravity_Flow_Steps::register(new Gravity_Flow_Step_Add_Mepr_Txn());
});
