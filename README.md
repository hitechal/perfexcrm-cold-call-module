# Automated Calls Module for Perfex CRM

A comprehensive Perfex CRM module that integrates Vapi.ai automated calling functionality, allowing you to initiate automated calls directly from lead profiles, manage bulk calling campaigns, and track call performance.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Campaign Management](#campaign-management)
- [Webhook Setup](#webhook-setup)
- [Database Structure](#database-structure)
- [Troubleshooting](#troubleshooting)
- [Support](#support)

## Features

### Core Functionality
- **Single Call Initiation**: Initiate automated calls directly from lead profiles
- **Call Logging**: Comprehensive call history with status tracking
- **Call Details View**: Detailed view of each call including transcript, recording, and metadata
- **Dashboard Widget**: Real-time call statistics on the admin dashboard
- **Lead Integration**: Seamless integration with Perfex CRM leads

### Campaign Management
- **Bulk Calling Campaigns**: Create and manage bulk calling campaigns
- **Lead Filtering**: Advanced lead filtering by status, source, assigned staff, phone number, and date
- **Campaign Scheduling**: Schedule campaigns for future execution
- **Real-time Progress Tracking**: Monitor campaign progress with live statistics
- **Campaign Statistics**: Success rates, completion rates, and detailed metrics

### Call Features
- **Call Transcripts**: View conversation transcripts in a chat-style interface
- **Call Recordings**: Listen to and download call recordings
- **Status Tracking**: Track call status (requested, completed, error, etc.)
- **Error Handling**: Detailed error information for failed calls
- **Activity Logging**: Automatic notes added to leads after calls

## Requirements

- **Perfex CRM**: Version 3.4.0 or higher
- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher
- **Vapi.ai Account**: Active Vapi.ai account with API access
- **cURL**: PHP cURL extension enabled
- **Shared Hosting Compatible**: Works on shared hosting (no SSH/terminal required)

## Installation

### Method 1: File Manager / FTP Installation

1. **Upload Module Files**
   - Upload the entire `vapi_integration` folder to your Perfex CRM installation:
     ```
     /modules/vapi_integration/
     ```

2. **Database Setup**
   - Access your phpMyAdmin
   - Select your Perfex CRM database
   - Import the `install.sql` file or run the SQL commands manually
   - Alternatively, the module will create tables automatically on activation

3. **Activate Module**
   - Log in to your Perfex CRM admin panel
   - Navigate to **Setup > Modules**
   - Find "Automated Calls" in the modules list
   - Click **Activate**

### Method 2: Manual Database Setup

If automatic installation fails, run the SQL commands from `install.sql` manually:

```sql
-- Create vapi_calls table
CREATE TABLE IF NOT EXISTS `tblvapi_calls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) DEFAULT NULL,
  `external_id` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'requested',
  `request_payload` longtext,
  `response_payload` longtext,
  `transcript` longtext,
  `recording_url` varchar(500) DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT NULL,
  `ended_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`),
  KEY `external_id` (`external_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create vapi_call_events table
CREATE TABLE IF NOT EXISTS `tblvapi_call_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vapi_call_id` int(11) DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `external_id` varchar(255) DEFAULT NULL,
  `event_type` varchar(100) DEFAULT NULL,
  `event_payload` longtext,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vapi_call_id` (`vapi_call_id`),
  KEY `lead_id` (`lead_id`),
  KEY `external_id` (`external_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create vapi_campaigns table
CREATE TABLE IF NOT EXISTS `tblvapi_campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `status` varchar(50) DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `total_leads` int(11) DEFAULT 0,
  `calls_initiated` int(11) DEFAULT 0,
  `calls_completed` int(11) DEFAULT 0,
  `calls_failed` int(11) DEFAULT 0,
  `calls_pending` int(11) DEFAULT 0,
  `lead_filter` longtext,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`),
  KEY `scheduled_at` (`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create vapi_campaign_leads table
CREATE TABLE IF NOT EXISTS `tblvapi_campaign_leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `call_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `scheduled_at` datetime DEFAULT NULL,
  `called_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campaign_id` (`campaign_id`),
  KEY `lead_id` (`lead_id`),
  KEY `call_id` (`call_id`),
  KEY `status` (`status`),
  UNIQUE KEY `campaign_lead_unique` (`campaign_id`, `lead_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Configuration

### Step 1: Access Settings

1. Navigate to **Automated Calls > Settings** in the admin sidebar
2. The module menu is located second in the sidebar (right after Dashboard)

### Step 2: Configure Vapi.ai Credentials

1. **Vapi API Key** (Required)
   - Log in to your [Vapi.ai Dashboard](https://dashboard.vapi.ai)
   - Navigate to API settings
   - Copy your API key
   - Paste it into the "Vapi API Key" field

2. **Assistant ID** (Optional)
   - Your Vapi.ai Assistant ID
   - If not provided, you must configure it in your Vapi.ai account settings

3. **Phone Number ID** (Optional)
   - Your Vapi.ai Phone Number ID
   - If not provided, you must configure it in your Vapi.ai account settings

4. **Webhook Token** (Auto-generated)
   - A security token is automatically generated
   - This token secures your webhook endpoint
   - Keep this token secure

### Step 3: Configure Webhook in Vapi.ai

1. Copy the **Webhook URL** from the settings page
2. Log in to your [Vapi.ai Dashboard](https://dashboard.vapi.ai)
3. Navigate to **Settings > Webhooks**
4. Add a new webhook with the copied URL
5. Ensure the webhook is enabled for the following events:
   - `status-update`
   - `end-of-call-report`
   - `transcript`
   - `recording`

## Usage

### Initiating a Single Call

1. Navigate to **Leads** in your Perfex CRM
2. Open a lead profile
3. Click on the **"Vapi Calls"** tab (second tab in the lead modal)
4. Click the **"Initiate Call"** button
5. Confirm the action
6. The call will be initiated and you'll see the status update in real-time

### Viewing Call Logs

#### All Calls
1. Navigate to **Automated Calls > Call Logs**
2. View all calls with filtering and sorting options
3. Click on any call ID to view detailed information

#### Lead-Specific Calls
1. Open a lead profile
2. Click on the **"Vapi Calls"** tab
3. View all calls for that specific lead
4. Click **"View Log"** to see detailed call information

### Viewing Call Details

1. Navigate to **Automated Calls > Call Logs**
2. Click on any call ID
3. View comprehensive call information including:
   - Call status and duration
   - Transcript (chat-style display)
   - Recording (playable and downloadable)
   - Error details (if any)
   - Raw response data

## Campaign Management

### Creating a Campaign

1. Navigate to **Automated Calls > Campaigns**
2. Click **"Create Campaign"**
3. Fill in the campaign details:
   - **Campaign Name**: A descriptive name for your campaign
   - **Description**: Optional description
   - **Status**: Draft, Scheduled, Running, Completed, or Paused
   - **Scheduled At**: Optional future date/time for scheduling
4. Configure **Lead Filters**:
   - Filter by Lead Status
   - Filter by Lead Source
   - Filter by Assigned Staff
   - Only leads with phone numbers
   - Date range (Date Added From/To)
5. Click **"Add leads to campaign now"** to add filtered leads
6. Click **"Save Campaign"**

### Starting a Campaign

1. Navigate to **Automated Calls > Campaigns**
2. Click on a campaign to view details
3. Click **"Start Campaign"**
4. Confirm the action
5. The system will:
   - Update campaign status to "Running"
   - Initiate calls to all pending leads
   - Update campaign statistics in real-time
   - Log all activities

### Viewing Campaign Progress

1. Navigate to **Automated Calls > Campaigns**
2. Click on a campaign
3. View real-time statistics:
   - Total Leads
   - Calls Initiated
   - Calls Completed
   - Calls Failed
   - Calls Pending
   - Success Rate
   - Completion Rate
4. View campaign leads with their individual statuses
5. Statistics update automatically via AJAX

### Campaign Statuses

- **Draft**: Campaign created but not started
- **Scheduled**: Campaign scheduled for future execution
- **Running**: Campaign is currently executing
- **Completed**: All leads have been processed
- **Paused**: Campaign execution paused

### Lead Statuses in Campaign

- **Pending**: Lead not yet called
- **Initiated**: Call has been initiated
- **Completed**: Call completed successfully
- **Failed**: Call failed or lead has no phone number

## Webhook Setup

The module uses a standalone webhook endpoint to receive callbacks from Vapi.ai. This ensures reliable processing even if the main Perfex CRM application is under heavy load.

### Webhook Endpoint

The webhook URL is automatically generated and displayed in the settings page:
```
https://yourdomain.com/modules/vapi_integration/webhook.php?token=YOUR_TOKEN
```

### Webhook Security

- The webhook is protected by a security token
- The token is stored in the database and must match the token in the URL
- Invalid tokens result in a 403 Forbidden response

### Webhook Processing

The webhook handles the following events:
- **status-update**: Updates call status in real-time
- **end-of-call-report**: Final call report with transcript and recording
- **transcript**: Live transcript updates
- **recording**: Recording URL updates

### Webhook Logging

Webhook activity is logged to:
```
/application/logs/webhook_debug_YYYY-MM-DD.txt
```

This log file helps troubleshoot webhook issues.

## Database Structure

### Tables

#### `tblvapi_calls`
Stores all call records with status, transcript, recording, and metadata.

#### `tblvapi_call_events`
Logs all webhook events for debugging and audit purposes.

#### `tblvapi_campaigns`
Stores campaign information and statistics.

#### `tblvapi_campaign_leads`
Links leads to campaigns and tracks individual lead call status.

### Options

The module stores configuration in the `tbloptions` table:
- `vapi_api_key`: Vapi.ai API key
- `vapi_assistant_id`: Vapi.ai Assistant ID
- `vapi_phone_number_id`: Vapi.ai Phone Number ID
- `vapi_webhook_token`: Webhook security token

## Troubleshooting

### Calls Not Being Initiated

1. **Check API Configuration**
   - Verify API key is correct in Settings
   - Ensure Assistant ID and Phone Number ID are configured (either in module or Vapi.ai dashboard)

2. **Check Lead Phone Numbers**
   - Ensure leads have valid phone numbers
   - Phone numbers should be in E.164 format (e.g., +1234567890)

3. **Check Activity Logs**
   - Navigate to **Utilities > Activity Log**
   - Look for error messages related to campaign or call initiation

### Webhook Not Receiving Updates

1. **Verify Webhook URL**
   - Copy the webhook URL from Settings
   - Ensure it's correctly configured in Vapi.ai dashboard
   - Test the webhook URL manually (should return JSON error about missing token)

2. **Check Webhook Token**
   - Ensure the token in the URL matches the token in Settings
   - Regenerate token if needed (clear the field and save)

3. **Check Webhook Logs**
   - Check `/application/logs/webhook_debug_YYYY-MM-DD.txt`
   - Look for error messages or connection issues

4. **Check File Permissions**
   - Ensure `webhook.php` is readable and executable
   - Check that log directory is writable

### Call Status Not Updating

1. **Check Webhook Configuration**
   - Verify webhook is properly configured in Vapi.ai
   - Ensure webhook events are enabled

2. **Check Database Connection**
   - Verify database credentials in `application/config/database.php`
   - Test database connectivity

3. **Check Webhook Logs**
   - Review webhook log files for errors
   - Look for database errors or connection issues

### Campaign Not Starting

1. **Check for Pending Leads**
   - Ensure campaign has leads added
   - Verify leads have phone numbers
   - Check lead status is "pending"

2. **Check Activity Logs**
   - Review activity logs for error messages
   - Look for API errors or database issues

3. **Check Campaign Status**
   - Ensure campaign status allows starting
   - Verify campaign hasn't already been completed

### 500 Internal Server Error

1. **Check PHP Error Logs**
   - Review server error logs
   - Look for PHP fatal errors or warnings

2. **Check File Permissions**
   - Ensure all module files are readable
   - Check that log directories are writable

3. **Check Database Structure**
   - Verify all tables are created correctly
   - Run `install.sql` manually if needed

4. **Check PHP Version**
   - Ensure PHP 7.4 or higher is installed
   - Verify required PHP extensions are enabled

## Support

### Module Information

- **Module Name**: Automated Calls
- **Version**: 1.0.0
- **Compatibility**: Perfex CRM 3.4.0+
- **Author**: Perfex Module Developer

### Getting Help

1. **Check Activity Logs**: Review activity logs for error messages
2. **Check Webhook Logs**: Review webhook debug logs for API issues
3. **Review Documentation**: Refer to this README for common issues
4. **Vapi.ai Documentation**: Consult [Vapi.ai Documentation](https://docs.vapi.ai) for API-specific issues

### Common Issues

#### Module Not Appearing in Menu
- Ensure module is activated in **Setup > Modules**
- Clear browser cache
- Check file permissions

#### Settings Not Saving
- Verify database connection
- Check file permissions on module directory
- Review PHP error logs

#### Calls Failing
- Verify API key is correct
- Check lead phone numbers are valid
- Review Vapi.ai account status
- Check API rate limits

## File Structure

```
modules/vapi_integration/
├── config/
│   └── csrf_exclude_uris.php      # CSRF exclusion for webhook
├── controllers/
│   └── Vapi_integration.php       # Main controller
├── language/
│   └── english/
│       └── vapi_integration_lang.php  # Language strings
├── models/
│   └── Vapi_integration_model.php # Database and API operations
├── views/
│   ├── campaigns.php              # Campaign list view
│   ├── campaign_form.php          # Campaign create/edit form
│   ├── dashboard_widget.php       # Dashboard statistics widget
│   ├── lead_calls_tab.php         # Lead modal tab content
│   ├── logs.php                   # Call logs list
│   ├── settings.php               # Module settings
│   ├── view_call.php              # Single call details
│   └── view_campaign.php          # Campaign details
├── install.php                    # Installation script
├── install.sql                    # Database schema
├── vapi_integration.php           # Module initialization
├── webhook.php                    # Standalone webhook endpoint
└── README.md                      # This file
```

## Security Notes

- All API credentials are stored securely in the database
- Webhook endpoint is protected by token authentication
- CSRF protection is enabled for all forms
- All user inputs are sanitized and validated
- Database queries use parameterized statements to prevent SQL injection

## License

This module is proprietary software. Unauthorized distribution or modification is prohibited.

---

**Note**: This module requires an active Vapi.ai account and API access. Ensure you have proper API credentials before installation.

