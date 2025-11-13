<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Hook to inject the Call button into the lead modal
hooks()->add_action('lead_modal_profile_bottom', 'vapi_integration_inject_call_button');

function vapi_integration_inject_call_button($lead_id)
{
    if (!is_admin()) {
        return;
    }
    
    $CI = &get_instance();
    $CI->load->view('vapi_integration/lead_call_button', ['lead_id' => $lead_id]);
}