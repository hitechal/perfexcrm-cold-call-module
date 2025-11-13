<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Vapi_integration extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('vapi_integration/vapi_integration_model');
    }

    /**
     * Settings page
     */
    public function index()
    {
        if (!is_admin()) {
            access_denied('Automated Calls');
        }

        $data['title'] = _l('vapi_integration');
        $data['api_key'] = get_option('vapi_api_key');
        $data['assistant_id'] = get_option('vapi_assistant_id');
        $data['phone_number_id'] = get_option('vapi_phone_number_id');
        
        // Generate webhook token if not exists
        $webhook_token = get_option('vapi_webhook_token');
        if (empty($webhook_token)) {
            $webhook_token = bin2hex(random_bytes(32));
            add_option('vapi_webhook_token', $webhook_token);
        }
        $data['webhook_token'] = $webhook_token;
        // Use standalone webhook file
        $data['webhook_url'] = site_url('modules/vapi_integration/webhook.php?token=' . $webhook_token);

        $this->load->view('vapi_integration/settings', $data);
    }

    /**
     * Save settings
     */
    public function save_settings()
    {
        if (!is_admin()) {
            access_denied('Automated Calls');
        }

        if ($this->input->post()) {
            update_option('vapi_api_key', $this->input->post('api_key'));
            update_option('vapi_assistant_id', $this->input->post('assistant_id'));
            update_option('vapi_phone_number_id', $this->input->post('phone_number_id'));
            
            $webhook_token = $this->input->post('webhook_token');
            if (empty($webhook_token)) {
                $webhook_token = bin2hex(random_bytes(32));
            }
            update_option('vapi_webhook_token', $webhook_token);

            set_alert('success', _l('updated_successfully', _l('settings')));
        }

        redirect(admin_url('vapi_integration'));
    }

    /**
     * Initiate call to lead
     */
    public function call_lead($lead_id)
    {
        if (!is_admin()) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Permission denied']));
            return;
        }

        $lead_id = intval($lead_id);
        $lead = $this->vapi_integration_model->get_lead($lead_id);

        if (!$lead) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Lead not found']));
            return;
        }

        if (empty($lead->phonenumber)) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Lead has no phone number']));
            return;
        }

        $result = $this->vapi_integration_model->initiate_call($lead);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }

    /**
     * Call logs page
     */
    public function logs()
    {
        if (!is_admin()) {
            access_denied('Automated Calls');
        }

        $data['title'] = _l('vapi_call_logs');
        $data['calls'] = $this->vapi_integration_model->get_all_calls(100);
        
        $this->load->view('vapi_integration/logs', $data);
    }
    
    /**
     * View call details
     */
    public function view_call($id)
    {
        if (!is_admin()) {
            access_denied('Automated Calls');
        }

        $call_id = intval($id);
        $call = $this->vapi_integration_model->get_call($call_id);
        
        if (!$call) {
            show_404();
        }
        
        // Get lead information if available
        $lead = null;
        if ($call->lead_id) {
            $lead = $this->vapi_integration_model->get_lead($call->lead_id);
        }
        
        // Parse response payload for additional details
        $response_data = null;
        if (!empty($call->response_payload)) {
            $response_data = json_decode($call->response_payload, true);
        }
        
        $data['title'] = _l('vapi_call_details') . ' #' . $call_id;
        $data['call'] = $call;
        $data['lead'] = $lead;
        $data['response_data'] = $response_data;
        
        $this->load->view('vapi_integration/view_call', $data);
    }
    
 /**
 * Get call logs for a specific lead (AJAX)
 */
public function get_lead_calls($lead_id)
{
    if (!is_admin()) {
        access_denied('Automated Calls');
    }

    $lead_id = intval($lead_id);
    $this->load->model('vapi_integration/vapi_integration_model');
    
    // Get calls for this lead
    $calls = $this->vapi_integration_model->get_lead_calls($lead_id);
    
    // Format for response
    $data = [];
    foreach ($calls as $call) {
        $data[] = [
            'id' => $call->id,
            'external_id' => $call->external_id,
            'status' => $call->status,
            'duration_seconds' => $call->duration_seconds,
            'created_at' => _dt($call->created_at),
            'transcript' => $call->transcript,
            'recording_url' => $call->recording_url,
        ];
    }
    
    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode(['data' => $data]));
}

// ==================== CAMPAIGN METHODS ====================

/**
 * Campaigns list page
 */
public function campaigns()
{
    if (!is_admin()) {
        access_denied('Automated Calls');
    }
    
    $data['title'] = _l('vapi_campaigns');
    $this->load->model('vapi_integration/vapi_integration_model');
    
    $filters = [];
    if ($this->input->get('status')) {
        $filters['status'] = $this->input->get('status');
    }
    if ($this->input->get('search')) {
        $filters['search'] = $this->input->get('search');
    }
    
    $data['campaigns'] = $this->vapi_integration_model->get_campaigns($filters);
    $this->load->view('vapi_integration/campaigns', $data);
}

/**
 * Create or edit campaign
 */
public function campaign($id = null)
{
    if (!is_admin()) {
        access_denied('Automated Calls');
    }
    
    $this->load->model('vapi_integration/vapi_integration_model');
    $this->load->model('leads_model');
    $this->load->model('staff_model');
    
    if ($id) {
        $data['campaign'] = $this->vapi_integration_model->get_campaign($id);
        if (!$data['campaign']) {
            show_404();
        }
        $data['title'] = _l('vapi_edit_campaign');
    } else {
        $data['campaign'] = null;
        $data['title'] = _l('vapi_create_campaign');
    }
    
    // Get lead sources and statuses for filters
    $data['lead_statuses'] = $this->leads_model->get_status();
    $data['lead_sources'] = $this->leads_model->get_source();
    $data['staff_members'] = $this->staff_model->get('', ['active' => 1]);
    
    if ($data['campaign'] && !empty($data['campaign']->lead_filter)) {
        $data['campaign']->lead_filter = json_decode($data['campaign']->lead_filter, true);
    }
    
    $this->load->view('vapi_integration/campaign_form', $data);
}

/**
 * Save campaign
 */
public function save_campaign()
{
    if (!is_admin()) {
        access_denied('Automated Calls');
    }
    
    $this->load->model('vapi_integration/vapi_integration_model');
    
    $id = $this->input->post('id');
    $data = [
        'name' => $this->input->post('name'),
        'description' => $this->input->post('description'),
        'scheduled_at' => $this->input->post('scheduled_at') ? date('Y-m-d H:i:s', strtotime($this->input->post('scheduled_at'))) : null,
        'status' => $this->input->post('status') ?: 'draft',
        'lead_filter' => [
            'status' => $this->input->post('filter_status'),
            'source' => $this->input->post('filter_source'),
            'assigned' => $this->input->post('filter_assigned'),
            'has_phone' => $this->input->post('filter_has_phone') ? true : false,
            'date_added_from' => $this->input->post('filter_date_from'),
            'date_added_to' => $this->input->post('filter_date_to'),
        ]
    ];
    
    if ($id) {
        $result = $this->vapi_integration_model->update_campaign($id, $data);
        $message = $result ? _l('vapi_campaign_updated') : _l('vapi_campaign_update_failed');
    } else {
        $id = $this->vapi_integration_model->create_campaign($data);
        $message = $id ? _l('vapi_campaign_created') : _l('vapi_campaign_create_failed');
    }
    
    if ($id && $this->input->post('add_leads_now') == '1') {
        // Get filtered leads and add to campaign
        $leads = $this->vapi_integration_model->get_filtered_leads($data['lead_filter']);
        if (!empty($leads)) {
            $lead_ids = array_column($leads, 'id');
            $this->vapi_integration_model->add_leads_to_campaign($id, $lead_ids);
        }
    }
    
    set_alert('success', $message);
    redirect(admin_url('vapi_integration/campaigns'));
}

/**
 * View campaign details
 */
public function view_campaign($id)
{
    if (!is_admin()) {
        access_denied('Automated Calls');
    }
    
    $this->load->model('vapi_integration/vapi_integration_model');
    
    $campaign = $this->vapi_integration_model->get_campaign($id);
    if (!$campaign) {
        show_404();
    }
    
    $data['title'] = $campaign->name;
    $data['campaign'] = $campaign;
    $data['statistics'] = $this->vapi_integration_model->get_campaign_statistics($id);
    
    // Get campaign leads
    $status_filter = $this->input->get('status');
    $filters = [];
    if ($status_filter) {
        $filters['status'] = $status_filter;
    }
    $data['campaign_leads'] = $this->vapi_integration_model->get_campaign_leads($id, $filters);
    
    $this->load->view('vapi_integration/view_campaign', $data);
}

/**
 * Delete campaign
 */
public function delete_campaign($id)
{
    if (!is_admin()) {
        access_denied('Automated Calls');
    }
    
    $this->load->model('vapi_integration/vapi_integration_model');
    
    if ($this->vapi_integration_model->delete_campaign($id)) {
        set_alert('success', _l('vapi_campaign_deleted'));
    } else {
        set_alert('danger', _l('vapi_campaign_delete_failed'));
    }
    
    redirect(admin_url('vapi_integration/campaigns'));
}

/**
 * Start campaign (initiate calls)
 */
public function start_campaign($id)
{
    if (!is_admin()) {
        access_denied('Automated Calls');
    }
    
    $this->load->model('vapi_integration/vapi_integration_model');
    
    $campaign = $this->vapi_integration_model->get_campaign($id);
    if (!$campaign) {
        show_404();
    }
    
    // Update campaign status
    $this->vapi_integration_model->update_campaign($id, [
        'status' => 'running',
        'started_at' => date('Y-m-d H:i:s')
    ]);
    
    // Get pending leads
    $pending_leads = $this->vapi_integration_model->get_campaign_leads($id, ['status' => 'pending']);
    
    if (empty($pending_leads)) {
        set_alert('warning', 'No pending leads found in this campaign.');
        redirect(admin_url('vapi_integration/view_campaign/' . $id));
        return;
    }
    
    $initiated = 0;
    $failed = 0;
    
    foreach ($pending_leads as $campaign_lead) {
        try {
            $lead = $this->vapi_integration_model->get_lead($campaign_lead->lead_id);
            if ($lead && !empty($lead->phonenumber)) {
                // Initiate call
                $result = $this->vapi_integration_model->initiate_call($lead);
                
                if (isset($result['success']) && $result['success']) {
                    // Get the call record that was just created
                    $call_record = null;
                    if (!empty($result['external_id'])) {
                        $call_record = $this->vapi_integration_model->get_call_by_external_id($result['external_id']);
                    }
                    
                    // If not found by external_id, get the most recent call for this lead
                    if (!$call_record) {
                        $recent_calls = $this->vapi_integration_model->get_lead_calls($campaign_lead->lead_id, 1);
                        if (!empty($recent_calls)) {
                            $call_record = $recent_calls[0];
                        }
                    }
                    
                    $call_id = $call_record ? $call_record->id : null;
                    
                    // Update campaign lead status
                    $this->vapi_integration_model->update_campaign_lead_status($id, $campaign_lead->lead_id, 'initiated', $call_id);
                    $initiated++;
                    
                    $call_id_display = $call_id ? $call_id : 'N/A';
                    log_activity('Campaign ' . $id . ': Call initiated for lead ' . $campaign_lead->lead_id . ' (Call ID: ' . $call_id_display . ')');
                } else {
                    $this->vapi_integration_model->update_campaign_lead_status($id, $campaign_lead->lead_id, 'failed');
                    $failed++;
                    $error_msg = isset($result['message']) ? $result['message'] : 'Unknown error';
                    log_activity('Campaign ' . $id . ': Failed to initiate call for lead ' . $campaign_lead->lead_id . ' - ' . $error_msg);
                }
            } else {
                $this->vapi_integration_model->update_campaign_lead_status($id, $campaign_lead->lead_id, 'failed');
                $failed++;
                log_activity('Campaign ' . $id . ': Lead ' . $campaign_lead->lead_id . ' has no phone number');
            }
        } catch (Exception $e) {
            $failed++;
            log_activity('Campaign ' . $id . ': Error processing lead ' . $campaign_lead->lead_id . ' - ' . $e->getMessage());
            $this->vapi_integration_model->update_campaign_lead_status($id, $campaign_lead->lead_id, 'failed');
        }
    }
    
    // Update campaign statistics
    $this->vapi_integration_model->update_campaign_stats($id);
    
    set_alert('success', sprintf(_l('vapi_campaign_started'), $initiated));
    redirect(admin_url('vapi_integration/view_campaign/' . $id));
}

/**
 * Get campaign progress (AJAX)
 */
public function get_campaign_progress($id)
{
    if (!is_admin()) {
        access_denied('Automated Calls');
    }
    
    $this->load->model('vapi_integration/vapi_integration_model');
    
    $campaign = $this->vapi_integration_model->get_campaign($id);
    $statistics = $this->vapi_integration_model->get_campaign_statistics($id);
    
    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode([
            'campaign' => $campaign,
            'statistics' => $statistics
        ]));
}

/**
 * Add leads to campaign (AJAX)
 */
public function add_campaign_leads($id)
{
    if (!is_admin()) {
        access_denied('Automated Calls');
    }
    
    $this->load->model('vapi_integration/vapi_integration_model');
    
    $lead_ids = $this->input->post('lead_ids');
    if (empty($lead_ids) || !is_array($lead_ids)) {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => false, 'message' => _l('vapi_no_leads_selected')]));
        return;
    }
    
    $added = $this->vapi_integration_model->add_leads_to_campaign($id, $lead_ids);
    
    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode(['success' => true, 'added' => $added]));
}
}