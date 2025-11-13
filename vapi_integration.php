<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Automated Calls
Description: Integrates Vapi.ai single-call functionality into Perfex CRM leads. Initiate automated calls directly from lead profiles and receive call transcripts/recordings via webhooks.
Version: 1.0.0
Requires at least: 3.4.0
Author: Mandi Blaceri
*/

define('VAPI_INTEGRATION_MODULE_NAME', 'vapi_integration');

/**
 * Register module activation hook
 */
register_activation_hook(VAPI_INTEGRATION_MODULE_NAME, 'vapi_integration_activation_hook');

function vapi_integration_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
 * Register module deactivation hook
 */
register_deactivation_hook(VAPI_INTEGRATION_MODULE_NAME, 'vapi_integration_deactivation_hook');

function vapi_integration_deactivation_hook()
{
    // Optional: Add cleanup logic if needed on deactivation
}

/**
 * Register module uninstall hook
 */
register_uninstall_hook(VAPI_INTEGRATION_MODULE_NAME, 'vapi_integration_uninstall_hook');

function vapi_integration_uninstall_hook()
{
    // Optional: Remove database tables on uninstall
}

/**
 * Register language files
 */
register_language_files(VAPI_INTEGRATION_MODULE_NAME, [VAPI_INTEGRATION_MODULE_NAME]);

/**
 * Add admin menu items
 */
hooks()->add_action('admin_init', 'vapi_integration_admin_menu');

function vapi_integration_admin_menu()
{
    $CI = &get_instance();
    
    if (!is_admin() || !isset($CI->app_menu)) {
        return;
    }

    $CI->app_menu->add_sidebar_menu_item('vapi_integration', [
        'name'     => _l('vapi_integration'),
        'href'     => admin_url('vapi_integration'),
        'position' => 2,
        'icon'     => 'fa fa-phone',
    ]);

    $CI->app_menu->add_sidebar_children_item('vapi_integration', [
        'slug'     => 'vapi_integration_campaigns',
        'name'     => _l('vapi_campaigns'),
        'href'     => admin_url('vapi_integration/campaigns'),
        'position' => 1,
    ]);

    $CI->app_menu->add_sidebar_children_item('vapi_integration', [
        'slug'     => 'vapi_integration_logs',
        'name'     => _l('vapi_call_logs'),
        'href'     => admin_url('vapi_integration/logs'),
        'position' => 2,
    ]);

    $CI->app_menu->add_sidebar_children_item('vapi_integration', [
        'slug'     => 'vapi_integration_settings',
        'name'     => _l('vapi_settings'),
        'href'     => admin_url('vapi_integration'),
        'position' => 3,
    ]);
}

/**
 * Add Vapi Calls tab to lead modal
 */
hooks()->add_action('after_lead_lead_tabs', 'vapi_integration_add_lead_tab');

function vapi_integration_add_lead_tab($lead)
{
    if (!is_admin() || empty($lead) || empty($lead->id)) {
        return;
    }
    
    // Get call count for badge
    $CI = &get_instance();
    $CI->load->model('vapi_integration/vapi_integration_model');
    $call_count = $CI->vapi_integration_model->get_lead_call_count($lead->id);
    
    echo '<li role="presentation">';
    echo '<a href="#tab_vapi_calls" aria-controls="tab_vapi_calls" role="tab" data-toggle="tab">';
    echo '<i class="fa fa-phone menu-icon"></i> ';
    echo _l('vapi_calls');
    if ($call_count > 0) {
        echo ' <span class="badge">' . $call_count . '</span>';
    }
    echo '</a>';
    echo '</li>';
}

/**
 * Add Vapi Calls tab content to lead modal
 */
hooks()->add_action('after_lead_tabs_content', 'vapi_integration_add_lead_tab_content');

function vapi_integration_add_lead_tab_content($lead)
{
    if (!is_admin() || empty($lead) || empty($lead->id)) {
        return;
    }
    
    $CI = &get_instance();
    $CI->load->view('vapi_integration/lead_calls_tab', ['lead' => $lead]);
}

/**
 * Add dashboard widget for Vapi call statistics
 */
hooks()->add_filter('get_dashboard_widgets', 'vapi_integration_add_dashboard_widget');

function vapi_integration_add_dashboard_widget($widgets)
{
    $widgets[] = [
        'path'      => 'vapi_integration/dashboard_widget',
        'container' => 'right-4',
    ];

    return $widgets;
}