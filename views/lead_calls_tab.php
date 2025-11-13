<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
// Load calls directly in PHP
$CI = &get_instance();
$CI->load->model('vapi_integration/vapi_integration_model');
$calls = $CI->vapi_integration_model->get_lead_calls($lead->id);
?>
<div role="tabpanel" class="tab-pane" id="tab_vapi_calls">
    <div class="row">
        <div class="col-md-12">
            <div class="panel_s">
                <div class="panel-body">
                    <h4 class="no-margin"><?php echo _l('vapi_calls'); ?></h4>
                    <hr class="hr-panel-heading" />
                    
                    <!-- Call Button -->
                    <div class="form-group">
                        <button id="vapi-call-btn-<?php echo $lead->id; ?>" 
                                class="btn btn-success" 
                                type="button">
                            <i class="fa fa-phone"></i> <?php echo _l('vapi_initiate_call'); ?>
                        </button>
                    </div>
                    
                    <!-- Call Logs Table -->
                    <div class="table-responsive">
                            <table class="table dt-table table-vapi-calls-lead" id="vapi-calls-table-<?php echo $lead->id; ?>" data-order-col="4" data-order-type="desc">
                                    <thead>
                                        <tr>
                                            <th><?php echo _l('id'); ?></th>
                                            <th><?php echo _l('vapi_external_id'); ?></th>
                                            <th><?php echo _l('status'); ?></th>
                                            <th><?php echo _l('vapi_duration'); ?></th>
                                            <th><?php echo _l('date_created'); ?></th>
                                            <th><?php echo _l('vapi_listen_call'); ?></th>
                                            <th><?php echo _l('options'); ?></th>
                                        </tr>
                                    </thead>
                            <tbody>
                                <?php if (!empty($calls)) { ?>
                                    <?php foreach ($calls as $call) { ?>
                                    <tr>
                                        <td><?php echo $call->id; ?></td>
                                        <td><?php echo html_escape($call->external_id ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="label label-<?php 
                                                echo $call->status === 'completed' ? 'success' : 
                                                    ($call->status === 'error' ? 'danger' : 'info'); 
                                            ?>">
                                                <?php echo html_escape($call->status ?? 'unknown'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $call->duration_seconds ? $call->duration_seconds . 's' : 'N/A'; ?></td>
                                        <td data-order="<?php echo strtotime($call->created_at); ?>"><?php echo _dt($call->created_at); ?></td>
                                        <td>
                                            <?php if (!empty($call->recording_url)) { ?>
                                                <button type="button" 
                                                        class="btn btn-primary btn-xs" 
                                                        onclick="playVapiCallRecording('<?php echo html_escape($call->recording_url); ?>', <?php echo $call->id; ?>); return false;">
                                                    <i class="fa fa-play"></i> <?php echo _l('vapi_listen'); ?>
                                                </button>
                                                <a href="<?php echo html_escape($call->recording_url); ?>" 
                                                   download 
                                                   class="btn btn-success btn-xs">
                                                    <i class="fa fa-download"></i> <?php echo _l('download'); ?>
                                                </a>
                                            <?php } else { ?>
                                                <span class="text-muted"><?php echo _l('vapi_no_recording'); ?></span>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo admin_url('vapi_integration/view_call/' . $call->id); ?>" 
                                               target="_blank"
                                               class="btn btn-default btn-xs" 
                                               title="<?php echo _l('vapi_view_call_details'); ?>">
                                                <i class="fa fa-eye"></i> <?php echo _l('view'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="7" class="text-center"><?php echo _l('no_data_found'); ?></td>
                                            </tr>
                                        <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Audio Player Modal -->
<div class="modal fade" id="vapi-recording-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?php echo _l('vapi_listen_call'); ?> (Call ID: <span id="recording-call-id"></span>)</h4>
            </div>
            <div class="modal-body text-center">
                <audio id="vapi-audio-player" controls style="width: 100%;">
                    <source id="vapi-audio-source" src="" type="audio/mpeg">
                    <?php echo _l('vapi_browser_not_support_audio'); ?>
                </audio>
            </div>
            <div class="modal-footer">
                <a href="#" id="vapi-download-link" class="btn btn-success" download>
                    <i class="fa fa-download"></i> <?php echo _l('download'); ?>
                </a>
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var leadId = <?php echo $lead->id; ?>;
    var btnId = 'vapi-call-btn-' + leadId;
    var tableId = 'vapi-calls-table-' + leadId;
    
    // Initialize button click handler
    function initCallButton() {
        var $btn = $('#' + btnId);
        if ($btn.length === 0) {
            setTimeout(initCallButton, 100);
            return;
        }
        
        $btn.off('click').on('click', function() {
            if (!confirm('<?php echo _l('vapi_confirm_call'); ?>')) {
                return false;
            }
            
            var $button = $(this);
            var original = $button.html();
            $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <?php echo _l('vapi_calling'); ?>...');
            
            var callUrl = '<?php echo admin_url('vapi_integration/call_lead/'); ?>' + leadId;
            
            $.get(callUrl)
                .done(function(response) {
                    var json = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (json && json.success === false) {
                        alert_float('danger', '<?php echo _l('vapi_call_failed'); ?>: ' + (json.message || '<?php echo _l('unknown_error'); ?>'));
                    } else {
                        alert_float('success', '<?php echo _l('vapi_call_initiated'); ?>');
                        // Reload the lead modal to refresh call logs
                        setTimeout(function() {
                            init_lead_modal_data(leadId);
                        }, 1000);
                    }
                })
                .fail(function(xhr) {
                    var errorMsg = '<?php echo _l('error'); ?>: ';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg += xhr.responseJSON.message;
                    } else {
                        errorMsg += 'HTTP ' + xhr.status;
                    }
                    alert_float('danger', errorMsg);
                })
                .always(function() {
                    $button.prop('disabled', false).html(original);
                });
            
            return false;
        });
    }
    
    // Initialize DataTable for sorting/filtering
    function initDataTable() {
        var $table = $('#' + tableId);
        if ($table.length === 0) {
            setTimeout(initDataTable, 100);
            return;
        }
        
        // Only initialize if not already initialized
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#' + tableId)) {
            return;
        }
        
        // Use Perfex's inline DataTable function
        if (typeof initDataTableInline !== 'undefined') {
            initDataTableInline($table);
        } else if ($.fn.DataTable) {
            $table.DataTable({
                order: [[4, 'desc']], // Sort by date_created column (index 4)
                pageLength: 10,
                columnDefs: [
                    { orderable: false, targets: [5, 6] } // Disable sorting on Listen and Options columns
                ]
            });
        }
    }
    
    // Initialize when tab is shown
    $('#tab_vapi_calls').on('shown.bs.tab', function() {
        initCallButton();
        initDataTable();
    });
    
    // Also try to initialize immediately
    if (typeof jQuery !== 'undefined') {
        $(document).ready(function() {
            setTimeout(function() {
                initCallButton();
                // Only init table if tab is active
                if ($('#tab_vapi_calls').hasClass('active')) {
                    initDataTable();
                }
            }, 500);
        });
    }
})();

// Play call recording in modal
function playVapiCallRecording(recordingUrl, callId) {
    var $modal = $('#vapi-recording-modal');
    var $audio = $('#vapi-audio-player');
    var $source = $('#vapi-audio-source');
    var $downloadLink = $('#vapi-download-link');
    
    // Set recording URL
    $source.attr('src', recordingUrl);
    $audio[0].load();
    
    // Set download link
    $downloadLink.attr('href', recordingUrl);
    
    // Set call ID
    $('#recording-call-id').text(callId);
    
    // Show modal
    $modal.modal('show');
    
    // Play when modal is shown
    $modal.on('shown.bs.modal', function() {
        $audio[0].play().catch(function(error) {
            console.error('Error playing audio:', error);
            alert_float('warning', '<?php echo _l('vapi_error_playing_recording'); ?>');
        });
    });
    
    // Pause when modal is hidden
    $modal.on('hidden.bs.modal', function() {
        $audio[0].pause();
        $audio[0].currentTime = 0;
    });
}

// Move Vapi Calls tab to second position (after Lead Profile)
(function() {
    function moveVapiTabToSecond() {
        // Move the tab navigation item
        var $vapiTab = $('a[href="#tab_vapi_calls"]').closest('li');
        if ($vapiTab.length > 0) {
            var $tabsList = $vapiTab.closest('ul.nav-tabs');
            if ($tabsList.length > 0) {
                // Find the first tab (Lead Profile)
                var $firstTab = $tabsList.find('li').first();
                
                // Only move if not already in the correct position
                if ($vapiTab.index() !== 1) {
                    // Remove from current position
                    $vapiTab.detach();
                    // Insert after first tab (making it second)
                    $firstTab.after($vapiTab);
                }
            }
        }
        
        // Move the tab content pane
        var $vapiTabPane = $('#tab_vapi_calls');
        if ($vapiTabPane.length > 0) {
            var $tabContent = $vapiTabPane.closest('.tab-content');
            if ($tabContent.length > 0) {
                // Find the first tab pane (Lead Profile)
                var $firstTabPane = $tabContent.find('.tab-pane').first();
                
                // Only move if not already in the correct position
                if ($vapiTabPane.index() !== 1) {
                    // Remove from current position
                    $vapiTabPane.detach();
                    // Insert after first tab pane (making it second)
                    $firstTabPane.after($vapiTabPane);
                }
            }
        }
    }
    
    // Try immediately
    moveVapiTabToSecond();
    
    // Also try after a short delay (in case DOM isn't ready)
    setTimeout(moveVapiTabToSecond, 100);
    
    // Also try when lead modal is shown
    $(document).on('shown.bs.modal', '#lead', function() {
        setTimeout(moveVapiTabToSecond, 50);
    });
    
    // Also try when tabs are clicked (in case tabs are dynamically loaded)
    $(document).on('click', 'a[data-toggle="tab"]', function() {
        setTimeout(moveVapiTabToSecond, 10);
    });
})();
</script>