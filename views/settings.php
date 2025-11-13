<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo _l('vapi_settings'); ?></h4>
                        <hr class="hr-panel-heading" />
                        
                        <?php echo form_open(admin_url('vapi_integration/save_settings')); ?>
                        
                        <div class="form-group">
                            <label for="api_key" class="control-label">
                                <?php echo _l('vapi_api_key'); ?> <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   name="api_key" 
                                   id="api_key" 
                                   class="form-control" 
                                   value="<?php echo html_escape($api_key ?? ''); ?>" 
                                   required>
                            <small class="form-text text-muted">
                                Get your API key from <a href="https://dashboard.vapi.ai" target="_blank">Vapi.ai Dashboard</a>
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="assistant_id" class="control-label">
                                <?php echo _l('vapi_assistant_id'); ?>
                            </label>
                            <input type="text" 
                                   name="assistant_id" 
                                   id="assistant_id" 
                                   class="form-control" 
                                   value="<?php echo html_escape($assistant_id ?? ''); ?>">
                            <small class="form-text text-muted">Your Vapi.ai Assistant ID (optional)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone_number_id" class="control-label">
                                <?php echo _l('vapi_phone_number_id'); ?>
                            </label>
                            <input type="text" 
                                   name="phone_number_id" 
                                   id="phone_number_id" 
                                   class="form-control" 
                                   value="<?php echo html_escape($phone_number_id ?? ''); ?>">
                            <small class="form-text text-muted">Your Vapi.ai Phone Number ID (optional)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="webhook_token" class="control-label">
                                <?php echo _l('vapi_webhook_token'); ?>
                            </label>
                            <input type="text" 
                                   name="webhook_token" 
                                   id="webhook_token" 
                                   class="form-control" 
                                   value="<?php echo html_escape($webhook_token ?? ''); ?>" 
                                   readonly>
                            <small class="form-text text-muted">Security token for webhook endpoint</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> <?php echo _l('save'); ?>
                        </button>
                        
                        <?php echo form_close(); ?>
                        
                        <hr class="hr-panel-heading" />
                        
                        <h5><?php echo _l('vapi_webhook_url'); ?></h5>
                        <p><?php echo _l('vapi_webhook_instructions'); ?></p>
                        <div class="form-group">
                            <label class="control-label">Webhook URL:</label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control" 
                                       value="<?php echo html_escape($webhook_url ?? ''); ?>" 
                                       readonly 
                                       id="webhook-url-input">
                                <div class="input-group-append">
                                    <button class="btn btn-default" type="button" onclick="copyWebhookUrl()">
                                        <i class="fa fa-copy"></i> <?php echo _l('copy'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <a href="<?php echo admin_url('vapi_integration/logs'); ?>" class="btn btn-info">
                                <i class="fa fa-list"></i> <?php echo _l('vapi_view_call_logs'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function copyWebhookUrl() {
    var input = document.getElementById('webhook-url-input');
    input.select();
    document.execCommand('copy');
    alert('<?php echo _l('copied_to_clipboard'); ?>');
}
</script>
<?php init_tail(); ?>