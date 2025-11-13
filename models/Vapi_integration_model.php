<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Vapi_integration_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get lead by ID
     */
    public function get_lead($id)
    {
        $this->db->where('id', intval($id));
        return $this->db->get(db_prefix() . 'leads')->row();
    }

   /**
 * Initiate call via Vapi.ai API
 */
public function initiate_call($lead)
{
    $api_key = trim(get_option('vapi_api_key'));
    $assistant_id = trim(get_option('vapi_assistant_id'));
    $phone_number_id = trim(get_option('vapi_phone_number_id'));
    $webhook_token = get_option('vapi_webhook_token');

    if (empty($api_key)) {
        return ['success' => false, 'message' => 'Vapi API key not configured'];
    }

    // Vapi.ai API endpoint
    $endpoint = 'https://api.vapi.ai/call';
    
    // Prepare payload
    $payload = [];
    
    if (!empty($assistant_id)) {
        $payload['assistantId'] = $assistant_id;
    }
    
    if (!empty($phone_number_id)) {
        $payload['phoneNumberId'] = $phone_number_id;
    }

    $payload['customer'] = [
        'number' => $lead->phonenumber
    ];
    
    $payload['metadata'] = [
        'lead_id' => (int)$lead->id
    ];
    
    // Remove serverUrl from payload - configure webhook in Vapi.ai dashboard instead
    // The webhook URL should be set in your Vapi.ai account settings, not in the call payload
    
    $json_payload = json_encode($payload);

    // Make API request
    $ch = curl_init($endpoint);
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
    ];
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Extract external call ID from response
    $external_id = null;
    $response_data = json_decode($response, true);
    if (is_array($response_data)) {
        $external_id = $response_data['id'] ?? $response_data['callId'] ?? ($response_data['data']['id'] ?? null);
    }

    // Log the call attempt
    $status = ($http_code >= 200 && $http_code < 300) ? 'requested' : 'error';
    $this->log_call($lead->id, $external_id, $status, $json_payload, $response);

    if ($curl_error) {
        return ['success' => false, 'message' => 'cURL error: ' . $curl_error];
    }
    
    if ($http_code >= 200 && $http_code < 300) {
        return [
            'success' => true, 
            'external_id' => $external_id,
            'data' => $response, // Include full response for debugging
            'message' => 'Call initiated successfully'
        ];
    }
    
    return [
        'success' => false, 
        'data' => $response, // Include response even on error for debugging
        'message' => 'HTTP ' . $http_code . ': ' . substr($response, 0, 200)
    ];
}

    /**
     * Log call to database
     */
    public function log_call($lead_id, $external_id = null, $status = 'requested', $request_payload = null, $response_payload = null)
    {
        $data = [
            'lead_id' => $lead_id,
            'external_id' => $external_id,
            'status' => $status,
            'request_payload' => $request_payload,
            'response_payload' => $response_payload,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        
        $this->db->insert(db_prefix() . 'vapi_calls', $data);
        return $this->db->insert_id();
    }

    /**
     * Get call by external ID
     */
    public function get_call_by_external_id($external_id)
    {
        $this->db->where('external_id', $external_id);
        return $this->db->get(db_prefix() . 'vapi_calls')->row();
    }

   /**
 * Update or create call record from webhook
 */
public function update_call_from_webhook($external_id, $lead_id, $payload, $status = null, $additional_data = [])
{
    log_activity('Vapi Integration: update_call_from_webhook called. External ID: ' . $external_id . ', Status: ' . $status);
    
    $existing = null;
    if (!empty($external_id)) {
        $existing = $this->get_call_by_external_id($external_id);
        if ($existing) {
            log_activity('Vapi Integration: Found existing call record ID: ' . $existing->id);
        } else {
            log_activity('Vapi Integration: No existing call record found for external ID: ' . $external_id);
        }
    }
    
    $now = date('Y-m-d H:i:s');

    if ($existing) {
        $update = [
            'response_payload' => is_string($payload) ? $payload : json_encode($payload),
            'updated_at' => $now,
        ];
        
        if ($status !== null) {
            $update['status'] = $status;
        }
        
        foreach ($additional_data as $key => $value) {
            if ($value !== null) {
                $update[$key] = $value;
            }
        }
        
        log_activity('Vapi Integration: Updating call record. Data: ' . json_encode($update));
        
        $this->db->where('id', $existing->id);
        $result = $this->db->update(db_prefix() . 'vapi_calls', $update);
        
        if ($result) {
            log_activity('Vapi Integration: Call record updated successfully. ID: ' . $existing->id . ', Status: ' . $status);
        } else {
            log_activity('Vapi Integration: Failed to update call record. DB Error: ' . $this->db->error()['message']);
        }
        
        return $existing->id;
    }

    $insert = [
        'lead_id' => $lead_id,
        'external_id' => $external_id,
        'status' => $status ?: 'received',
        'request_payload' => null,
        'response_payload' => is_string($payload) ? $payload : json_encode($payload),
        'created_at' => $now,
        'updated_at' => $now,
    ];
    
    foreach ($additional_data as $key => $value) {
        if ($value !== null) {
            $insert[$key] = $value;
        }
    }
    
    log_activity('Vapi Integration: Inserting new call record. Data: ' . json_encode($insert));
    
    $result = $this->db->insert(db_prefix() . 'vapi_calls', $insert);
    
    if ($result) {
        $insert_id = $this->db->insert_id();
        log_activity('Vapi Integration: New call record created. ID: ' . $insert_id);
        return $insert_id;
    } else {
        log_activity('Vapi Integration: Failed to insert call record. DB Error: ' . $this->db->error()['message']);
        return false;
    }
}

    /**
     * Log webhook event
     */
    public function log_webhook_event($vapi_call_id, $lead_id, $external_id, $event_type, $payload)
    {
        $data = [
            'vapi_call_id' => $vapi_call_id ?: null,
            'lead_id' => $lead_id ?: null,
            'external_id' => $external_id ?: null,
            'event_type' => $event_type ?: null,
            'event_payload' => is_string($payload) ? $payload : json_encode($payload),
            'created_at' => date('Y-m-d H:i:s'),
        ];
        
        $this->db->insert(db_prefix() . 'vapi_call_events', $data);
        return $this->db->insert_id();
    }

/**
 * Process webhook event from Vapi.ai
 */
public function process_webhook_event($message, $raw_payload = null)
{
    log_activity('Vapi Integration: process_webhook_event called');
    error_log('Vapi Integration: process_webhook_event called');
    
    // Extract call information - Vapi.ai structure
    $external_id = null;
    $lead_id = null;
    
    // Get call data from message
    $call = $message['call'] ?? null;
    
    if ($call && is_array($call)) {
        $external_id = $call['id'] ?? null;
        log_activity('Vapi Integration: External ID extracted: ' . $external_id);
        
        // Get lead_id from metadata
        if (isset($call['metadata']['lead_id'])) {
            $lead_id = intval($call['metadata']['lead_id']);
            log_activity('Vapi Integration: Lead ID from metadata: ' . $lead_id);
        }
        
        // Fallback: try to find lead by phone number
        if ($lead_id === null && isset($call['customer']['number'])) {
            $phone = $call['customer']['number'];
            log_activity('Vapi Integration: Searching lead by phone: ' . $phone);
            $lead = $this->find_lead_by_phone($phone);
            if ($lead) {
                $lead_id = $lead->id;
                log_activity('Vapi Integration: Found lead by phone: ' . $lead_id);
            } else {
                log_activity('Vapi Integration: No lead found for phone: ' . $phone);
            }
        }
    }
    
    // Also check message level for customer number
    if ($lead_id === null && isset($message['customer']['number'])) {
        $phone = $message['customer']['number'];
        log_activity('Vapi Integration: Searching lead by phone (message level): ' . $phone);
        $lead = $this->find_lead_by_phone($phone);
        if ($lead) {
            $lead_id = $lead->id;
            log_activity('Vapi Integration: Found lead by phone (message level): ' . $lead_id);
        }
    }

    // Get event type and status
    $event_type = $message['type'] ?? 'unknown';
    $status = $message['status'] ?? ($call['status'] ?? null);
    
    log_activity('Vapi Integration: Event type: ' . $event_type . ', Status: ' . $status);
    
    // Extract additional data
    $additional_data = [];
    
    // Check for artifact (end-of-call-report)
    if (isset($call['artifact']) && is_array($call['artifact'])) {
        $artifact = $call['artifact'];
        
        if (isset($artifact['transcript'])) {
            $additional_data['transcript'] = $artifact['transcript'];
            log_activity('Vapi Integration: Found transcript in artifact');
        }
        
        if (isset($artifact['recording'])) {
            if (is_array($artifact['recording'])) {
                $additional_data['recording_url'] = $artifact['recording']['url'] ?? null;
            } else {
                $additional_data['recording_url'] = $artifact['recording'];
            }
            log_activity('Vapi Integration: Found recording in artifact: ' . ($additional_data['recording_url'] ?? 'null'));
        }
    }
    
    // Also check call level for transcript and recording
    if (isset($call['transcript'])) {
        $additional_data['transcript'] = $call['transcript'];
        log_activity('Vapi Integration: Found transcript in call');
    }
    
    if (isset($call['recordingUrl'])) {
        $additional_data['recording_url'] = $call['recordingUrl'];
        log_activity('Vapi Integration: Found recordingUrl: ' . $call['recordingUrl']);
    }
    
    // Get duration if available
    if (isset($call['duration'])) {
        $additional_data['duration_seconds'] = intval($call['duration']);
    }
    
    // Get ended reason
    if (isset($message['endedReason'])) {
        $additional_data['ended_reason'] = $message['endedReason'];
        log_activity('Vapi Integration: Ended reason: ' . $message['endedReason']);
    } elseif (isset($call['endedReason'])) {
        $additional_data['ended_reason'] = $call['endedReason'];
        log_activity('Vapi Integration: Ended reason (call level): ' . $call['endedReason']);
    }
    
    // Update status based on endedReason
    if ($status === 'ended' && !empty($additional_data['ended_reason'])) {
        // Check if it's an error
        if (strpos($additional_data['ended_reason'], 'error') !== false) {
            $status = 'error';
            log_activity('Vapi Integration: Status changed to error due to endedReason');
        } else {
            $status = 'completed';
            log_activity('Vapi Integration: Status changed to completed');
        }
    }

    log_activity('Vapi Integration: About to update call. External ID: ' . $external_id . ', Lead ID: ' . $lead_id . ', Status: ' . $status);
    
    // Update or create call record
    $vapi_call_id = $this->update_call_from_webhook(
        $external_id, 
        $lead_id, 
        $raw_payload ?? $message, 
        $status, 
        $additional_data
    );
    
    log_activity('Vapi Integration: Call record updated/created. ID: ' . $vapi_call_id);

    // Log the event
    $event_id = $this->log_webhook_event($vapi_call_id, $lead_id, $external_id, $event_type, $raw_payload ?? $message);
    log_activity('Vapi Integration: Event logged. Event ID: ' . $event_id);

    // Add note to lead if call completed, ended, or error
    if ($lead_id && ($event_type === 'end-of-call-report' || $event_type === 'status-update' || $status === 'completed' || $status === 'error')) {
        $note_id = $this->add_lead_note_from_call($lead_id, $event_type, $additional_data);
        log_activity('Vapi Integration: Note added to lead. Note ID: ' . $note_id);
    }
    
    return true;
}

    /**
     * Find lead by phone number
     */
    public function find_lead_by_phone($phone)
    {
        if (empty($phone)) {
            return null;
        }
        
        // Normalize phone number
        $normalized = preg_replace('/[^\d+]/', '', $phone);
        
        // Try exact match
        $this->db->where('phonenumber', $normalized);
        $query = $this->db->get(db_prefix() . 'leads');
        if ($query->num_rows() > 0) {
            return $query->row();
        }

        // Try partial match (last 7 digits)
        $digits = preg_replace('/\D/', '', $normalized);
        if (strlen($digits) >= 7) {
            $suffix = substr($digits, -7);
            $this->db->like('phonenumber', $suffix, 'before');
            $this->db->limit(1);
            $query = $this->db->get(db_prefix() . 'leads');
            if ($query->num_rows() > 0) {
                return $query->row();
            }
        }
        
        return null;
    }

    /**
     * Add note to lead from call event
     */
    public function add_lead_note_from_call($lead_id, $event_type, $data)
    {
        $note_content = "Vapi.ai Call Event: " . ucfirst(str_replace('-', ' ', $event_type)) . "\n\n";
        
        if (!empty($data['duration_seconds'])) {
            $note_content .= "Duration: " . $data['duration_seconds'] . " seconds\n";
        }
        
        if (!empty($data['ended_reason'])) {
            $note_content .= "Ended Reason: " . $data['ended_reason'] . "\n";
        }
        
        if (!empty($data['transcript'])) {
            $note_content .= "\nTranscript:\n" . $data['transcript'] . "\n";
        }
        
        if (!empty($data['recording_url'])) {
            $note_content .= "\nRecording: " . $data['recording_url'] . "\n";
        }
        
        $note_content .= "\n(Full details available in Vapi Integration call logs)";

        $note = [
            'description' => $note_content,
            'dateadded' => date('Y-m-d H:i:s'),
            'addedfrom' => get_staff_user_id() ?: 0,
            'relation' => $lead_id,
            'relation_type' => 'lead',
        ];
        
        $this->db->insert(db_prefix() . 'notes', $note);
        return $this->db->insert_id();
    }

    /**
     * Get all calls
     */
    public function get_all_calls($limit = 100)
    {
        $this->db->select(db_prefix() . 'vapi_calls.*, ' . db_prefix() . 'leads.name as lead_name');
        $this->db->from(db_prefix() . 'vapi_calls');
        $this->db->join(db_prefix() . 'leads', db_prefix() . 'leads.id = ' . db_prefix() . 'vapi_calls.lead_id', 'left');
        $this->db->order_by(db_prefix() . 'vapi_calls.created_at', 'DESC');
        $this->db->order_by(db_prefix() . 'vapi_calls.id', 'DESC'); // Secondary sort by ID for consistency
        $this->db->limit($limit);
        return $this->db->get()->result();
    }
    
    /**
 * Get call count for a lead
 */
public function get_lead_call_count($lead_id)
{
    $this->db->where('lead_id', intval($lead_id));
    return $this->db->count_all_results(db_prefix() . 'vapi_calls');
}

/**
 * Get all calls for a specific lead
 */
public function get_lead_calls($lead_id, $limit = 100)
{
    $this->db->where('lead_id', intval($lead_id));
    $this->db->order_by('created_at', 'DESC');
    $this->db->limit($limit);
    return $this->db->get(db_prefix() . 'vapi_calls')->result();
}

/**
 * Get call by ID
 */
public function get_call($id)
{
    $this->db->where('id', intval($id));
    return $this->db->get(db_prefix() . 'vapi_calls')->row();
}

/**
 * Get call statistics for dashboard widget
 * Returns counts for success, failure, and other statuses
 */
public function get_call_statistics()
{
    $stats = [
        'completed' => 0,
        'error' => 0,
        'requested' => 0,
        'ended' => 0,
        'total' => 0
    ];
    
    // Get counts by status
    $this->db->select('status, COUNT(*) as count');
    $this->db->from(db_prefix() . 'vapi_calls');
    $this->db->group_by('status');
    $results = $this->db->get()->result();
    
    foreach ($results as $result) {
        $status = strtolower($result->status ?? 'unknown');
        if (isset($stats[$status])) {
            $stats[$status] = intval($result->count);
        }
        $stats['total'] += intval($result->count);
    }
    
    return $stats;
}

/**
 * Get recent calls for dashboard widget
 */
public function get_recent_calls($limit = 10)
{
    $this->db->select(db_prefix() . 'vapi_calls.*, ' . db_prefix() . 'leads.name as lead_name');
    $this->db->from(db_prefix() . 'vapi_calls');
    $this->db->join(db_prefix() . 'leads', db_prefix() . 'leads.id = ' . db_prefix() . 'vapi_calls.lead_id', 'left');
    $this->db->order_by(db_prefix() . 'vapi_calls.created_at', 'DESC');
    $this->db->order_by(db_prefix() . 'vapi_calls.id', 'DESC');
    $this->db->limit($limit);
    return $this->db->get()->result();
}

// ==================== CAMPAIGN METHODS ====================

/**
 * Get all campaigns
 */
public function get_campaigns($filters = [])
{
    $this->db->select(db_prefix() . 'vapi_campaigns.*, ' . db_prefix() . 'staff.firstname, ' . db_prefix() . 'staff.lastname');
    $this->db->from(db_prefix() . 'vapi_campaigns');
    $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'vapi_campaigns.created_by', 'left');
    
    if (isset($filters['status'])) {
        $this->db->where(db_prefix() . 'vapi_campaigns.status', $filters['status']);
    }
    
    if (isset($filters['search'])) {
        $this->db->like(db_prefix() . 'vapi_campaigns.name', $filters['search']);
    }
    
    $this->db->order_by(db_prefix() . 'vapi_campaigns.created_at', 'DESC');
    
    return $this->db->get()->result();
}

/**
 * Get campaign by ID
 */
public function get_campaign($id)
{
    $this->db->where('id', intval($id));
    return $this->db->get(db_prefix() . 'vapi_campaigns')->row();
}

/**
 * Create new campaign
 */
public function create_campaign($data)
{
    $data['created_by'] = get_staff_user_id();
    $data['created_at'] = date('Y-m-d H:i:s');
    $data['updated_at'] = date('Y-m-d H:i:s');
    
    if (isset($data['lead_filter']) && is_array($data['lead_filter'])) {
        $data['lead_filter'] = json_encode($data['lead_filter']);
    }
    
    $this->db->insert(db_prefix() . 'vapi_campaigns', $data);
    return $this->db->insert_id();
}

/**
 * Update campaign
 */
public function update_campaign($id, $data)
{
    $data['updated_at'] = date('Y-m-d H:i:s');
    
    if (isset($data['lead_filter']) && is_array($data['lead_filter'])) {
        $data['lead_filter'] = json_encode($data['lead_filter']);
    }
    
    $this->db->where('id', intval($id));
    $this->db->update(db_prefix() . 'vapi_campaigns', $data);
    return $this->db->affected_rows() > 0;
}

/**
 * Delete campaign
 */
public function delete_campaign($id)
{
    // Delete campaign leads first
    $this->db->where('campaign_id', intval($id));
    $this->db->delete(db_prefix() . 'vapi_campaign_leads');
    
    // Delete campaign
    $this->db->where('id', intval($id));
    $this->db->delete(db_prefix() . 'vapi_campaigns');
    return $this->db->affected_rows() > 0;
}

/**
 * Get campaign leads
 */
public function get_campaign_leads($campaign_id, $filters = [])
{
    $this->db->select(db_prefix() . 'vapi_campaign_leads.*, ' . db_prefix() . 'leads.name as lead_name, ' . db_prefix() . 'leads.email, ' . db_prefix() . 'leads.phonenumber');
    $this->db->from(db_prefix() . 'vapi_campaign_leads');
    $this->db->join(db_prefix() . 'leads', db_prefix() . 'leads.id = ' . db_prefix() . 'vapi_campaign_leads.lead_id', 'left');
    $this->db->where('campaign_id', intval($campaign_id));
    
    if (isset($filters['status'])) {
        $this->db->where(db_prefix() . 'vapi_campaign_leads.status', $filters['status']);
    }
    
    $this->db->order_by(db_prefix() . 'vapi_campaign_leads.created_at', 'ASC');
    
    return $this->db->get()->result();
}

/**
 * Add leads to campaign
 */
public function add_leads_to_campaign($campaign_id, $lead_ids)
{
    $inserted = 0;
    foreach ($lead_ids as $lead_id) {
        // Check if already exists
        $this->db->where('campaign_id', intval($campaign_id));
        $this->db->where('lead_id', intval($lead_id));
        $exists = $this->db->get(db_prefix() . 'vapi_campaign_leads')->row();
        
        if (!$exists) {
            $this->db->insert(db_prefix() . 'vapi_campaign_leads', [
                'campaign_id' => intval($campaign_id),
                'lead_id' => intval($lead_id),
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $inserted++;
        }
    }
    
    // Update total_leads count
    $this->update_campaign_stats($campaign_id);
    
    return $inserted;
}

/**
 * Get leads based on filter criteria
 */
public function get_filtered_leads($filters)
{
    $this->db->select('id, name, email, phonenumber');
    $this->db->from(db_prefix() . 'leads');
    
    if (isset($filters['status']) && !empty($filters['status'])) {
        $this->db->where('status', $filters['status']);
    }
    
    if (isset($filters['source']) && !empty($filters['source'])) {
        $this->db->where('source', $filters['source']);
    }
    
    if (isset($filters['assigned']) && !empty($filters['assigned'])) {
        $this->db->where('assigned', $filters['assigned']);
    }
    
    if (isset($filters['has_phone']) && $filters['has_phone']) {
        $this->db->where('phonenumber !=', '');
        $this->db->where('phonenumber IS NOT NULL');
    }
    
    if (isset($filters['date_added_from']) && !empty($filters['date_added_from'])) {
        $this->db->where('dateadded >=', $filters['date_added_from']);
    }
    
    if (isset($filters['date_added_to']) && !empty($filters['date_added_to'])) {
        $this->db->where('dateadded <=', $filters['date_added_to']);
    }
    
    return $this->db->get()->result();
}

/**
 * Update campaign statistics
 */
public function update_campaign_stats($campaign_id)
{
    // Get counts
    $this->db->where('campaign_id', intval($campaign_id));
    $total = $this->db->count_all_results(db_prefix() . 'vapi_campaign_leads');
    
    $this->db->where('campaign_id', intval($campaign_id));
    $this->db->where('status', 'completed');
    $completed = $this->db->count_all_results(db_prefix() . 'vapi_campaign_leads');
    
    $this->db->where('campaign_id', intval($campaign_id));
    $this->db->where('status', 'failed');
    $failed = $this->db->count_all_results(db_prefix() . 'vapi_campaign_leads');
    
    $this->db->where('campaign_id', intval($campaign_id));
    $this->db->where('status', 'initiated');
    $initiated = $this->db->count_all_results(db_prefix() . 'vapi_campaign_leads');
    
    $this->db->where('campaign_id', intval($campaign_id));
    $this->db->where('status', 'pending');
    $pending = $this->db->count_all_results(db_prefix() . 'vapi_campaign_leads');
    
    // Update campaign
    $this->db->where('id', intval($campaign_id));
    $this->db->update(db_prefix() . 'vapi_campaigns', [
        'total_leads' => $total,
        'calls_completed' => $completed,
        'calls_failed' => $failed,
        'calls_initiated' => $initiated,
        'calls_pending' => $pending,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Update campaign lead status
 */
public function update_campaign_lead_status($campaign_id, $lead_id, $status, $call_id = null)
{
    $data = [
        'status' => $status
    ];
    
    if ($status === 'initiated' || $status === 'completed') {
        $data['called_at'] = date('Y-m-d H:i:s');
    }
    
    if ($call_id) {
        $data['call_id'] = intval($call_id);
    }
    
    $this->db->where('campaign_id', intval($campaign_id));
    $this->db->where('lead_id', intval($lead_id));
    $this->db->update(db_prefix() . 'vapi_campaign_leads', $data);
    
    // Update campaign stats
    $this->update_campaign_stats($campaign_id);
    
    return $this->db->affected_rows() > 0;
}

/**
 * Get campaign statistics
 */
public function get_campaign_statistics($campaign_id)
{
    $campaign = $this->get_campaign($campaign_id);
    if (!$campaign) {
        return null;
    }
    
    $stats = [
        'total_leads' => $campaign->total_leads,
        'calls_initiated' => $campaign->calls_initiated,
        'calls_completed' => $campaign->calls_completed,
        'calls_failed' => $campaign->calls_failed,
        'calls_pending' => $campaign->calls_pending,
        'success_rate' => $campaign->calls_initiated > 0 ? round(($campaign->calls_completed / $campaign->calls_initiated) * 100, 2) : 0,
        'completion_rate' => $campaign->total_leads > 0 ? round((($campaign->calls_completed + $campaign->calls_failed) / $campaign->total_leads) * 100, 2) : 0
    ];
    
    return $stats;
}

/**
 * Get next pending lead for campaign
 */
public function get_next_pending_lead($campaign_id)
{
    $this->db->where('campaign_id', intval($campaign_id));
    $this->db->where('status', 'pending');
    $this->db->order_by('created_at', 'ASC');
    $this->db->limit(1);
    return $this->db->get(db_prefix() . 'vapi_campaign_leads')->row();
}
}