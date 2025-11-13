<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo _l('vapi_call_logs'); ?></h4>
                        <hr class="hr-panel-heading" />
                        
                        <div class="table-responsive">
                            <table class="table dt-table table-vapi-calls" data-order-col="5" data-order-type="desc">
                                <thead>
                                    <tr>
                                        <th><?php echo _l('id'); ?></th>
                                        <th><?php echo _l('lead'); ?></th>
                                        <th><?php echo _l('vapi_external_id'); ?></th>
                                        <th><?php echo _l('status'); ?></th>
                                        <th><?php echo _l('vapi_duration'); ?></th>
                                        <th><?php echo _l('date_created'); ?></th>
                                        <th><?php echo _l('options'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($calls)) { ?>
                                        <?php foreach ($calls as $call) { ?>
                                        <tr>
                                            <td data-order="<?php echo $call->id; ?>"><?php echo $call->id; ?></td>
                                            <td>
                                                <?php if ($call->lead_id) { ?>
                                                    <a href="<?php echo admin_url('leads/index/' . $call->lead_id); ?>">
                                                        <?php echo html_escape($call->lead_name ?? 'Lead #' . $call->lead_id); ?>
                                                    </a>
                                                <?php } else { ?>
                                                    N/A
                                                <?php } ?>
                                            </td>
                                            <td><?php echo html_escape($call->external_id ?? 'N/A'); ?></td>
                                            <td data-order="<?php echo html_escape($call->status ?? 'unknown'); ?>">
                                                <span class="label label-<?php 
                                                    echo $call->status === 'completed' ? 'success' : 
                                                        ($call->status === 'error' ? 'danger' : 'info'); 
                                                ?>">
                                                    <?php echo html_escape(ucfirst($call->status ?? 'unknown')); ?>
                                                </span>
                                            </td>
                                            <td data-order="<?php echo $call->duration_seconds ?? 0; ?>">
                                                <?php echo $call->duration_seconds ? $call->duration_seconds . 's' : 'N/A'; ?>
                                            </td>
                                            <td data-order="<?php echo strtotime($call->created_at); ?>">
                                                <?php echo _dt($call->created_at); ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo admin_url('vapi_integration/view_call/' . $call->id); ?>" 
                                                   class="btn btn-default btn-icon" 
                                                   title="<?php echo _l('vapi_view_call_details'); ?>">
                                                    <i class="fa fa-eye"></i>
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
                        
                        <a href="<?php echo admin_url('vapi_integration'); ?>" class="btn btn-default">
                            <i class="fa fa-arrow-left"></i> <?php echo _l('back'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>