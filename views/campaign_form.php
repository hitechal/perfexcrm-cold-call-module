<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo $title; ?></h4>
                        <hr class="hr-panel-heading" />
                        
                        <?php echo form_open(admin_url('vapi_integration/save_campaign')); ?>
                        
                        <?php if ($campaign) { ?>
                            <input type="hidden" name="id" value="<?php echo $campaign->id; ?>">
                        <?php } ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name"><?php echo _l('vapi_campaign_name'); ?> <span class="text-danger">*</span></label>
                                    <input type="text" id="name" name="name" class="form-control" 
                                           value="<?php echo $campaign ? html_escape($campaign->name) : ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status"><?php echo _l('vapi_campaign_status'); ?></label>
                                    <select id="status" name="status" class="selectpicker" data-width="100%">
                                        <option value="draft" <?php echo ($campaign && $campaign->status == 'draft') ? 'selected' : ''; ?>><?php echo _l('vapi_campaign_status_draft'); ?></option>
                                        <option value="scheduled" <?php echo ($campaign && $campaign->status == 'scheduled') ? 'selected' : ''; ?>><?php echo _l('vapi_campaign_status_scheduled'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description"><?php echo _l('vapi_campaign_description'); ?></label>
                            <textarea id="description" name="description" class="form-control" rows="3"><?php echo $campaign ? html_escape($campaign->description) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="scheduled_at"><?php echo _l('vapi_campaign_scheduled_at'); ?></label>
                            <input type="text" id="scheduled_at" name="scheduled_at" class="form-control datetimepicker" 
                                   value="<?php echo $campaign && $campaign->scheduled_at ? _d($campaign->scheduled_at) : ''; ?>">
                        </div>
                        
                        <hr>
                        <h5><?php echo _l('vapi_campaign_lead_filters'); ?></h5>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="filter_status"><?php echo _l('vapi_filter_by_status'); ?></label>
                                    <select id="filter_status" name="filter_status" class="selectpicker" data-width="100%">
                                        <option value=""><?php echo _l('all'); ?></option>
                                        <?php foreach ($lead_statuses as $status) { ?>
                                            <option value="<?php echo $status['id']; ?>" 
                                                <?php echo ($campaign && isset($campaign->lead_filter['status']) && $campaign->lead_filter['status'] == $status['id']) ? 'selected' : ''; ?>>
                                                <?php echo html_escape($status['name']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="filter_source"><?php echo _l('vapi_filter_by_source'); ?></label>
                                    <select id="filter_source" name="filter_source" class="selectpicker" data-width="100%">
                                        <option value=""><?php echo _l('all'); ?></option>
                                        <?php foreach ($lead_sources as $source) { ?>
                                            <option value="<?php echo $source['id']; ?>" 
                                                <?php echo ($campaign && isset($campaign->lead_filter['source']) && $campaign->lead_filter['source'] == $source['id']) ? 'selected' : ''; ?>>
                                                <?php echo html_escape($source['name']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="filter_assigned"><?php echo _l('vapi_filter_by_assigned'); ?></label>
                                    <select id="filter_assigned" name="filter_assigned" class="selectpicker" data-width="100%">
                                        <option value=""><?php echo _l('all'); ?></option>
                                        <?php foreach ($staff_members as $staff) { ?>
                                            <option value="<?php echo $staff['staffid']; ?>" 
                                                <?php echo ($campaign && isset($campaign->lead_filter['assigned']) && $campaign->lead_filter['assigned'] == $staff['staffid']) ? 'selected' : ''; ?>>
                                                <?php echo html_escape($staff['firstname'] . ' ' . $staff['lastname']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="filter_date_from"><?php echo _l('vapi_filter_date_from'); ?></label>
                                    <input type="text" id="filter_date_from" name="filter_date_from" class="form-control datepicker" 
                                           value="<?php echo $campaign && isset($campaign->lead_filter['date_added_from']) ? $campaign->lead_filter['date_added_from'] : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="filter_date_to"><?php echo _l('vapi_filter_date_to'); ?></label>
                                    <input type="text" id="filter_date_to" name="filter_date_to" class="form-control datepicker" 
                                           value="<?php echo $campaign && isset($campaign->lead_filter['date_added_to']) ? $campaign->lead_filter['date_added_to'] : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="filter_has_phone" value="1" 
                                                   <?php echo ($campaign && isset($campaign->lead_filter['has_phone']) && $campaign->lead_filter['has_phone']) ? 'checked' : ''; ?>>
                                            <?php echo _l('vapi_filter_has_phone'); ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="add_leads_now" value="1" checked>
                                    <?php echo _l('vapi_add_leads_now'); ?>
                                </label>
                            </div>
                        </div>
                        
                        <div class="btn-bottom-toolbar text-right">
                            <a href="<?php echo admin_url('vapi_integration/campaigns'); ?>" class="btn btn-default"><?php echo _l('cancel'); ?></a>
                            <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
                        </div>
                        
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>

