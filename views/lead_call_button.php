<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (is_admin() && isset($lead_id) && !empty($lead_id)) { ?>
<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <button id="vapi-call-btn-<?php echo $lead_id; ?>" 
                    class="btn btn-success" 
                    type="button"
                    onclick="vapiInitiateCall(<?php echo $lead_id; ?>)">
                <i class="fa fa-phone"></i> <?php echo _l('vapi_initiate_call'); ?>
            </button>
        </div>
    </div>
</div>

<script>
function vapiInitiateCall(leadId) {
    if (!confirm('<?php echo _l('vapi_confirm_call'); ?>')) {
        return false;
    }
    
    var $btn = $('#vapi-call-btn-' + leadId);
    var original = $btn.html();
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <?php echo _l('vapi_calling'); ?>...');
    
    var callUrl = '<?php echo admin_url('vapi_integration/call_lead/'); ?>' + leadId;
    
    $.get(callUrl)
        .done(function(response) {
            var json = typeof response === 'string' ? JSON.parse(response) : response;
            
            if (json && json.success === false) {
                alert_float('danger', '<?php echo _l('vapi_call_failed'); ?>: ' + (json.message || '<?php echo _l('unknown_error'); ?>'));
            } else {
                alert_float('success', '<?php echo _l('vapi_call_initiated'); ?>');
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
            $btn.prop('disabled', false).html(original);
        });
    
    return false;
}
</script>
<?php } ?>