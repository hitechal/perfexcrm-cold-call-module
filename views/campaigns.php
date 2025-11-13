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
                                <h4 class="no-margin"><?php echo _l('vapi_campaigns'); ?></h4>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="<?php echo admin_url('vapi_integration/campaign'); ?>" class="btn btn-info">
                                    <i class="fa fa-plus"></i> <?php echo _l('vapi_create_campaign'); ?>
                                </a>
                            </div>
                        </div>
                        <hr class="hr-panel-heading" />
                        
                        <!-- Filters -->
                        <div class="row mbot15">
                            <div class="col-md-12">
                                <form method="get" action="<?php echo admin_url('vapi_integration/campaigns'); ?>">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <select name="status" class="selectpicker" data-width="100%">
                                                <option value=""><?php echo _l('all_statuses'); ?></option>
                                                <option value="draft" <?php echo (isset($_GET['status']) && $_GET['status'] == 'draft') ? 'selected' : ''; ?>><?php echo _l('vapi_campaign_status_draft'); ?></option>
                                                <option value="scheduled" <?php echo (isset($_GET['status']) && $_GET['status'] == 'scheduled') ? 'selected' : ''; ?>><?php echo _l('vapi_campaign_status_scheduled'); ?></option>
                                                <option value="running" <?php echo (isset($_GET['status']) && $_GET['status'] == 'running') ? 'selected' : ''; ?>><?php echo _l('vapi_campaign_status_running'); ?></option>
                                                <option value="completed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'completed') ? 'selected' : ''; ?>><?php echo _l('vapi_campaign_status_completed'); ?></option>
                                                <option value="paused" <?php echo (isset($_GET['status']) && $_GET['status'] == 'paused') ? 'selected' : ''; ?>><?php echo _l('vapi_campaign_status_paused'); ?></option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" name="search" class="form-control" placeholder="<?php echo _l('search'); ?>" value="<?php echo isset($_GET['search']) ? html_escape($_GET['search']) : ''; ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" class="btn btn-info"><?php echo _l('filter'); ?></button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Campaigns Table -->
                        <div class="table-responsive">
                            <table class="table dt-table">
                                <thead>
                                    <tr>
                                        <th><?php echo _l('id'); ?></th>
                                        <th><?php echo _l('vapi_campaign_name'); ?></th>
                                        <th><?php echo _l('vapi_campaign_status'); ?></th>
                                        <th><?php echo _l('vapi_total_leads'); ?></th>
                                        <th><?php echo _l('vapi_calls_completed'); ?></th>
                                        <th><?php echo _l('vapi_calls_failed'); ?></th>
                                        <th><?php echo _l('vapi_success_rate'); ?></th>
                                        <th><?php echo _l('date_created'); ?></th>
                                        <th><?php echo _l('options'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($campaigns)) { ?>
                                        <?php foreach ($campaigns as $campaign) { ?>
                                        <tr>
                                            <td><?php echo $campaign->id; ?></td>
                                            <td>
                                                <a href="<?php echo admin_url('vapi_integration/view_campaign/' . $campaign->id); ?>">
                                                    <?php echo html_escape($campaign->name); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="label label-<?php 
                                                    echo $campaign->status === 'completed' ? 'success' : 
                                                        ($campaign->status === 'running' ? 'info' : 
                                                        ($campaign->status === 'failed' ? 'danger' : 'default')); 
                                                ?>">
                                                    <?php echo html_escape(ucfirst($campaign->status)); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $campaign->total_leads; ?></td>
                                            <td><?php echo $campaign->calls_completed; ?></td>
                                            <td><?php echo $campaign->calls_failed; ?></td>
                                            <td>
                                                <?php 
                                                $rate = $campaign->calls_initiated > 0 ? round(($campaign->calls_completed / $campaign->calls_initiated) * 100, 1) : 0;
                                                echo $rate . '%';
                                                ?>
                                            </td>
                                            <td><?php echo _dt($campaign->created_at); ?></td>
                                            <td>
                                                <a href="<?php echo admin_url('vapi_integration/view_campaign/' . $campaign->id); ?>" 
                                                   class="btn btn-default btn-icon" 
                                                   title="<?php echo _l('view'); ?>">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="<?php echo admin_url('vapi_integration/campaign/' . $campaign->id); ?>" 
                                                   class="btn btn-default btn-icon" 
                                                   title="<?php echo _l('edit'); ?>">
                                                    <i class="fa fa-pencil"></i>
                                                </a>
                                                <a href="<?php echo admin_url('vapi_integration/delete_campaign/' . $campaign->id); ?>" 
                                                   class="btn btn-danger btn-icon" 
                                                   title="<?php echo _l('delete'); ?>"
                                                   onclick="return confirm('<?php echo _l('confirm_action_prompt'); ?>');">
                                                    <i class="fa fa-remove"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="9" class="text-center"><?php echo _l('no_data_found'); ?></td>
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
<?php init_tail(); ?>

