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
                                <h4 class="no-margin"><?php echo html_escape($campaign->name); ?></h4>
                                <?php if ($campaign->description) { ?>
                                    <p class="text-muted"><?php echo html_escape($campaign->description); ?></p>
                                <?php } ?>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="<?php echo admin_url('vapi_integration/campaigns'); ?>" class="btn btn-default">
                                    <i class="fa fa-arrow-left"></i> <?php echo _l('back'); ?>
                                </a>
                                <a href="<?php echo admin_url('vapi_integration/campaign/' . $campaign->id); ?>" class="btn btn-info">
                                    <i class="fa fa-pencil"></i> <?php echo _l('edit'); ?>
                                </a>
                                <?php if ($campaign->status == 'draft' || $campaign->status == 'scheduled') { ?>
                                    <a href="<?php echo admin_url('vapi_integration/start_campaign/' . $campaign->id); ?>" 
                                       class="btn btn-success"
                                       onclick="return confirm('<?php echo _l('vapi_confirm_start_campaign'); ?>');">
                                        <i class="fa fa-play"></i> <?php echo _l('vapi_start_campaign'); ?>
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                        <hr class="hr-panel-heading" />
                        
                        <!-- Campaign Statistics -->
                        <div class="row" id="campaign-stats">
                            <div class="col-md-3">
                                <div class="panel panel-default">
                                    <div class="panel-body text-center">
                                        <h3 class="no-margin" id="stat-total"><?php echo $statistics['total_leads']; ?></h3>
                                        <p class="text-muted"><?php echo _l('vapi_total_leads'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="panel panel-default">
                                    <div class="panel-body text-center">
                                        <h3 class="no-margin" id="stat-initiated" style="color: #337ab7;"><?php echo $statistics['calls_initiated']; ?></h3>
                                        <p class="text-muted"><?php echo _l('vapi_calls_initiated'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="panel panel-default">
                                    <div class="panel-body text-center">
                                        <h3 class="no-margin" id="stat-completed" style="color: #28a745;"><?php echo $statistics['calls_completed']; ?></h3>
                                        <p class="text-muted"><?php echo _l('vapi_completed'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="panel panel-default">
                                    <div class="panel-body text-center">
                                        <h3 class="no-margin" id="stat-failed" style="color: #dc3545;"><?php echo $statistics['calls_failed']; ?></h3>
                                        <p class="text-muted"><?php echo _l('vapi_failed'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    <div class="panel-body text-center">
                                        <h3 class="no-margin" id="stat-success-rate"><?php echo $statistics['success_rate']; ?>%</h3>
                                        <p class="text-muted"><?php echo _l('vapi_success_rate'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    <div class="panel-body text-center">
                                        <h3 class="no-margin" id="stat-completion-rate"><?php echo $statistics['completion_rate']; ?>%</h3>
                                        <p class="text-muted"><?php echo _l('vapi_completion_rate'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Campaign Leads Table -->
                        <hr>
                        <h5><?php echo _l('vapi_campaign_leads'); ?></h5>
                        
                        <!-- Status Filter -->
                        <div class="row mbot15">
                            <div class="col-md-12">
                                <a href="<?php echo admin_url('vapi_integration/view_campaign/' . $campaign->id); ?>" 
                                   class="btn btn-sm <?php echo !isset($_GET['status']) ? 'btn-info' : 'btn-default'; ?>">
                                    <?php echo _l('all'); ?>
                                </a>
                                <a href="<?php echo admin_url('vapi_integration/view_campaign/' . $campaign->id . '?status=pending'); ?>" 
                                   class="btn btn-sm <?php echo (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'btn-info' : 'btn-default'; ?>">
                                    <?php echo _l('pending'); ?>
                                </a>
                                <a href="<?php echo admin_url('vapi_integration/view_campaign/' . $campaign->id . '?status=initiated'); ?>" 
                                   class="btn btn-sm <?php echo (isset($_GET['status']) && $_GET['status'] == 'initiated') ? 'btn-info' : 'btn-default'; ?>">
                                    <?php echo _l('initiated'); ?>
                                </a>
                                <a href="<?php echo admin_url('vapi_integration/view_campaign/' . $campaign->id . '?status=completed'); ?>" 
                                   class="btn btn-sm <?php echo (isset($_GET['status']) && $_GET['status'] == 'completed') ? 'btn-info' : 'btn-default'; ?>">
                                    <?php echo _l('vapi_completed'); ?>
                                </a>
                                <a href="<?php echo admin_url('vapi_integration/view_campaign/' . $campaign->id . '?status=failed'); ?>" 
                                   class="btn btn-sm <?php echo (isset($_GET['status']) && $_GET['status'] == 'failed') ? 'btn-info' : 'btn-default'; ?>">
                                    <?php echo _l('vapi_failed'); ?>
                                </a>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table dt-table">
                                <thead>
                                    <tr>
                                        <th><?php echo _l('lead'); ?></th>
                                        <th><?php echo _l('email'); ?></th>
                                        <th><?php echo _l('phonenumber'); ?></th>
                                        <th><?php echo _l('status'); ?></th>
                                        <th><?php echo _l('date_created'); ?></th>
                                        <th><?php echo _l('options'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($campaign_leads)) { ?>
                                        <?php foreach ($campaign_leads as $campaign_lead) { ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo admin_url('leads/index/' . $campaign_lead->lead_id); ?>" target="_blank">
                                                    <?php echo html_escape($campaign_lead->lead_name ?? 'Lead #' . $campaign_lead->lead_id); ?>
                                                </a>
                                            </td>
                                            <td><?php echo html_escape($campaign_lead->email ?? 'N/A'); ?></td>
                                            <td><?php echo html_escape($campaign_lead->phonenumber ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="label label-<?php 
                                                    echo $campaign_lead->status === 'completed' ? 'success' : 
                                                        ($campaign_lead->status === 'failed' ? 'danger' : 
                                                        ($campaign_lead->status === 'initiated' ? 'info' : 'default')); 
                                                ?>">
                                                    <?php echo html_escape(ucfirst($campaign_lead->status)); ?>
                                                </span>
                                            </td>
                                            <td><?php echo _dt($campaign_lead->created_at); ?></td>
                                            <td>
                                                <?php if ($campaign_lead->call_id) { ?>
                                                    <a href="<?php echo admin_url('vapi_integration/view_call/' . $campaign_lead->call_id); ?>" 
                                                       class="btn btn-default btn-icon" 
                                                       title="<?php echo _l('vapi_view_call_details'); ?>" 
                                                       target="_blank">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="6" class="text-center"><?php echo _l('no_data_found'); ?></td>
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
</div>

<?php if ($campaign->status == 'running') { ?>
<script>
// Real-time progress tracking for running campaigns
(function() {
    var campaignId = <?php echo $campaign->id; ?>;
    var updateInterval = setInterval(function() {
        $.get(admin_url + 'vapi_integration/get_campaign_progress/' + campaignId, function(response) {
            if (response && response.statistics) {
                var stats = response.statistics;
                $('#stat-total').text(stats.total_leads);
                $('#stat-initiated').text(stats.calls_initiated);
                $('#stat-completed').text(stats.calls_completed);
                $('#stat-failed').text(stats.calls_failed);
                $('#stat-success-rate').text(stats.success_rate + '%');
                $('#stat-completion-rate').text(stats.completion_rate + '%');
                
                // Stop updating if campaign is completed
                if (response.campaign && response.campaign.status === 'completed') {
                    clearInterval(updateInterval);
                }
            }
        }).fail(function() {
            clearInterval(updateInterval);
        });
    }, 5000); // Update every 5 seconds
    
    // Stop updating when page is hidden
    $(window).on('beforeunload', function() {
        clearInterval(updateInterval);
    });
})();
</script>
<?php } ?>

<?php init_tail(); ?>

