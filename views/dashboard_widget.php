<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$CI = &get_instance();
$CI->load->model('vapi_integration/vapi_integration_model');

// Get statistics and recent calls
$stats = $CI->vapi_integration_model->get_call_statistics();
$recent_calls = $CI->vapi_integration_model->get_recent_calls(5);

// Calculate success and failure rates
$success_count = $stats['completed'];
$failure_count = $stats['error'];
$other_count = $stats['requested'] + $stats['ended'];
$total = $stats['total'];
?>
<div class="widget" id="widget-<?php echo create_widget_id(); ?>" data-name="<?php echo e(_l('vapi_call_statistics')); ?>">
    <div class="row">
        <div class="col-md-12">
            <div class="panel_s">
                <div class="panel-body padding-10">
                    <div class="widget-dragger"></div>

                    <p class="tw-font-semibold tw-flex tw-items-center tw-mb-0 tw-space-x-1.5 rtl:tw-space-x-reverse tw-p-1.5">
                        <i class="fa fa-phone tw-text-neutral-500 tw-w-6 tw-h-6"></i>
                        <span class="tw-text-neutral-700">
                            <?php echo _l('vapi_call_statistics'); ?>
                        </span>
                    </p>

                    <hr class="-tw-mx-3 tw-mt-3 tw-mb-6">

                    <?php if ($total > 0) { ?>
                    <!-- Statistics Summary -->
                    <div class="row text-center" style="margin-bottom: 20px;">
                        <div class="col-md-4">
                            <div class="tw-p-2">
                                <div class="tw-text-2xl tw-font-bold" style="color: #28a745;">
                                    <?php echo $success_count; ?>
                                </div>
                                <div class="tw-text-xs tw-text-neutral-600">
                                    <?php echo _l('vapi_completed'); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="tw-p-2">
                                <div class="tw-text-2xl tw-font-bold" style="color: #dc3545;">
                                    <?php echo $failure_count; ?>
                                </div>
                                <div class="tw-text-xs tw-text-neutral-600">
                                    <?php echo _l('vapi_failed'); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="tw-p-2">
                                <div class="tw-text-2xl tw-font-bold" style="color: #6c757d;">
                                    <?php echo $total; ?>
                                </div>
                                <div class="tw-text-xs tw-text-neutral-600">
                                    <?php echo _l('total'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } else { ?>
                    <div class="text-center tw-py-8">
                        <i class="fa fa-phone tw-text-4xl tw-text-neutral-300 tw-mb-3"></i>
                        <p class="tw-text-neutral-500"><?php echo _l('vapi_no_calls_yet'); ?></p>
                    </div>
                    <?php } ?>

                    <!-- Recent Calls -->
                    <?php if (!empty($recent_calls)) { ?>
                    <hr class="-tw-mx-3 tw-mt-3 tw-mb-3">
                    <h5 class="tw-font-semibold tw-mb-3"><?php echo _l('vapi_recent_calls'); ?></h5>
                    <div class="table-responsive">
                        <table class="table table-condensed">
                            <thead>
                                <tr>
                                    <th><?php echo _l('id'); ?></th>
                                    <th><?php echo _l('lead'); ?></th>
                                    <th><?php echo _l('status'); ?></th>
                                    <th><?php echo _l('date_created'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_calls as $call) { ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo admin_url('vapi_integration/view_call/' . $call->id); ?>" target="_blank">
                                            #<?php echo $call->id; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($call->lead_id) { ?>
                                            <a href="<?php echo admin_url('leads/index/' . $call->lead_id); ?>" target="_blank">
                                                <?php echo html_escape($call->lead_name ?? 'Lead #' . $call->lead_id); ?>
                                            </a>
                                        <?php } else { ?>
                                            <span class="text-muted">N/A</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <span class="label label-<?php 
                                            echo $call->status === 'completed' ? 'success' : 
                                                ($call->status === 'error' ? 'danger' : 'info'); 
                                        ?>">
                                            <?php echo html_escape(ucfirst($call->status ?? 'unknown')); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo _dt($call->created_at); ?></small>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center tw-mt-3">
                        <a href="<?php echo admin_url('vapi_integration/logs'); ?>" class="btn btn-default btn-xs">
                            <?php echo _l('vapi_view_all_calls'); ?> <i class="fa fa-arrow-right"></i>
                        </a>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

