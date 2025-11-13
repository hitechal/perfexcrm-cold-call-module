<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="no-margin"><?php echo _l('vapi_call_details'); ?> #<?php echo $call->id; ?></h4>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="<?php echo admin_url('vapi_integration/logs'); ?>" class="btn btn-default">
                                    <i class="fa fa-arrow-left"></i> <?php echo _l('back'); ?>
                                </a>
                            </div>
                        </div>
                        <hr class="hr-panel-heading" />
                        
                        <!-- Call Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <h5><?php echo _l('vapi_call_information'); ?></h5>
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr>
                                            <td><strong><?php echo _l('id'); ?>:</strong></td>
                                            <td><?php echo $call->id; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo _l('vapi_external_id'); ?>:</strong></td>
                                            <td><?php echo html_escape($call->external_id ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo _l('status'); ?>:</strong></td>
                                            <td>
                                                <span class="label label-<?php 
                                                    echo $call->status === 'completed' ? 'success' : 
                                                        ($call->status === 'error' ? 'danger' : 
                                                        ($call->status === 'requested' ? 'info' : 'warning')); 
                                                ?>">
                                                    <?php echo html_escape(ucfirst($call->status ?? 'unknown')); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php if ($call->ended_reason) { ?>
                                        <tr>
                                            <td><strong><?php echo _l('vapi_ended_reason'); ?>:</strong></td>
                                            <td>
                                                <span class="text-<?php echo $call->status === 'error' ? 'danger' : 'muted'; ?>">
                                                    <?php echo html_escape($call->ended_reason); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                        <tr>
                                            <td><strong><?php echo _l('vapi_duration'); ?>:</strong></td>
                                            <td><?php echo $call->duration_seconds ? $call->duration_seconds . ' ' . _l('seconds') : 'N/A'; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo _l('date_created'); ?>:</strong></td>
                                            <td><?php echo _dt($call->created_at); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo _l('date_updated'); ?>:</strong></td>
                                            <td><?php echo _dt($call->updated_at); ?></td>
                                        </tr>
                                        <?php if ($lead) { ?>
                                        <tr>
                                            <td><strong><?php echo _l('lead'); ?>:</strong></td>
                                            <td>
                                                <a href="<?php echo admin_url('leads/index/' . $lead->id); ?>">
                                                    <?php echo html_escape($lead->name ?? 'Lead #' . $lead->id); ?>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="col-md-6">
                                <h5><?php echo _l('vapi_call_recording'); ?></h5>
                                <?php if (!empty($call->recording_url)) { ?>
                                    <div class="panel panel-default">
                                        <div class="panel-body text-center">
                                            <audio controls style="width: 100%; margin-bottom: 15px;">
                                                <source src="<?php echo html_escape($call->recording_url); ?>" type="audio/mpeg">
                                                <?php echo _l('vapi_browser_not_support_audio'); ?>
                                            </audio>
                                            <div>
                                                <a href="<?php echo html_escape($call->recording_url); ?>" 
                                                   download 
                                                   class="btn btn-success">
                                                    <i class="fa fa-download"></i> <?php echo _l('download'); ?> <?php echo _l('recording'); ?>
                                                </a>
                                                <a href="<?php echo html_escape($call->recording_url); ?>" 
                                                   target="_blank" 
                                                   class="btn btn-info">
                                                    <i class="fa fa-external-link"></i> <?php echo _l('open_in_new_tab'); ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php } else { ?>
                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle"></i> <?php echo _l('vapi_no_recording'); ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        
                        <!-- Transcript -->
                        <?php if (!empty($call->transcript)) { 
                            // Parse transcript into messages
                            $transcript_lines = explode("\n", $call->transcript);
                            $messages = [];
                            $current_speaker = null;
                            $current_message = '';
                            
                            foreach ($transcript_lines as $line) {
                                $line = trim($line);
                                if (empty($line)) {
                                    if (!empty($current_message) && $current_speaker) {
                                        $messages[] = [
                                            'speaker' => $current_speaker,
                                            'text' => trim($current_message)
                                        ];
                                        $current_message = '';
                                    }
                                    continue;
                                }
                                
                                // Check if line starts with a speaker label
                                if (preg_match('/^(Agent|Customer|Caller|System|AI|User|Bot|Assistant):\s*(.*)$/i', $line, $matches)) {
                                    // Save previous message if exists
                                    if (!empty($current_message) && $current_speaker) {
                                        $messages[] = [
                                            'speaker' => $current_speaker,
                                            'text' => trim($current_message)
                                        ];
                                    }
                                    // Map various labels to standard names
                                    $speaker_label = strtolower($matches[1]);
                                    if (in_array($speaker_label, ['ai', 'bot', 'assistant'])) {
                                        $current_speaker = 'Agent';
                                    } elseif (in_array($speaker_label, ['user', 'caller'])) {
                                        $current_speaker = 'Customer';
                                    } else {
                                        $current_speaker = ucfirst($speaker_label);
                                    }
                                    $current_message = $matches[2];
                                } else {
                                    // Continuation of current message
                                    if ($current_speaker) {
                                        $current_message .= ' ' . $line;
                                    } else {
                                        // No speaker identified, treat as system message
                                        if (empty($current_message)) {
                                            $current_speaker = 'System';
                                        }
                                        $current_message .= $line;
                                    }
                                }
                            }
                            
                            // Add last message
                            if (!empty($current_message) && $current_speaker) {
                                $messages[] = [
                                    'speaker' => $current_speaker,
                                    'text' => trim($current_message)
                                ];
                            }
                            
                            // If no structured messages found, display as plain text
                            if (empty($messages)) {
                                $messages[] = [
                                    'speaker' => 'System',
                                    'text' => $call->transcript
                                ];
                            }
                        ?>
                        <div class="row">
                            <div class="col-md-12">
                                <h5><?php echo _l('vapi_transcript'); ?></h5>
                                <div class="panel panel-default" style="margin-bottom: 0;">
                                    <div class="panel-body" style="padding: 0;">
                                        <div class="chat-transcript" style="max-height: 600px; overflow-y: auto; padding: 20px; background: #f8f9fa;">
                                            <?php foreach ($messages as $index => $message) { 
                                                $is_agent = (strtolower($message['speaker']) === 'agent');
                                                $is_customer = (strtolower($message['speaker']) === 'customer' || strtolower($message['speaker']) === 'caller');
                                                $is_system = (strtolower($message['speaker']) === 'system');
                                            ?>
                                            <div class="chat-message-wrapper" style="margin-bottom: 20px; display: flex; <?php echo $is_agent ? 'justify-content: flex-start;' : ($is_customer ? 'justify-content: flex-end;' : 'justify-content: center;'); ?>">
                                                <div class="chat-message" style="max-width: 70%; <?php 
                                                    if ($is_agent) {
                                                        echo 'background: #007bff; color: white; border-radius: 18px 18px 18px 4px;';
                                                    } elseif ($is_customer) {
                                                        echo 'background: #e9ecef; color: #333; border-radius: 18px 18px 4px 18px;';
                                                    } else {
                                                        echo 'background: #fff3cd; color: #856404; border-radius: 8px; border: 1px solid #ffc107;';
                                                    }
                                                ?> padding: 12px 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: relative;">
                                                    <?php if (!$is_system) { ?>
                                                    <div class="chat-speaker" style="font-size: 11px; font-weight: 600; margin-bottom: 4px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        <?php echo html_escape($message['speaker']); ?>
                                                    </div>
                                                    <?php } ?>
                                                    <div class="chat-text" style="line-height: 1.5; word-wrap: break-word;">
                                                        <?php echo nl2br(html_escape($message['text'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <style>
                            .chat-transcript::-webkit-scrollbar {
                                width: 8px;
                            }
                            .chat-transcript::-webkit-scrollbar-track {
                                background: #f1f1f1;
                                border-radius: 4px;
                            }
                            .chat-transcript::-webkit-scrollbar-thumb {
                                background: #888;
                                border-radius: 4px;
                            }
                            .chat-transcript::-webkit-scrollbar-thumb:hover {
                                background: #555;
                            }
                            .chat-message {
                                animation: fadeIn 0.3s ease-in;
                            }
                            @keyframes fadeIn {
                                from { opacity: 0; transform: translateY(10px); }
                                to { opacity: 1; transform: translateY(0); }
                            }
                        </style>
                        <?php } else { ?>
                        <div class="row">
                            <div class="col-md-12">
                                <h5><?php echo _l('vapi_transcript'); ?></h5>
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> <?php echo _l('vapi_no_transcript'); ?>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                        
                        <!-- Error Details (if error status) -->
                        <?php if ($call->status === 'error' && !empty($call->ended_reason)) { ?>
                        <div class="row">
                            <div class="col-md-12">
                                <h5><?php echo _l('vapi_error_details'); ?></h5>
                                <div class="alert alert-danger">
                                    <strong><?php echo _l('error'); ?>:</strong> <?php echo html_escape($call->ended_reason); ?>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                        
                        <!-- Raw Response Data (Collapsible) -->
                        <?php if ($response_data) { ?>
                        <div class="row">
                            <div class="col-md-12">
                                <h5>
                                    <a href="#raw-data" data-toggle="collapse" aria-expanded="false" aria-controls="raw-data">
                                        <?php echo _l('vapi_raw_response_data'); ?> <i class="fa fa-chevron-down"></i>
                                    </a>
                                </h5>
                                <div class="collapse" id="raw-data">
                                    <div class="panel panel-default">
                                        <div class="panel-body">
                                            <pre style="max-height: 500px; overflow-y: auto;"><?php echo htmlspecialchars(json_encode($response_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?></pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>

