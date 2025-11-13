# Quick Installation Guide

## Prerequisites

- Perfex CRM 3.4.0 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Active Vapi.ai account
- File Manager or FTP access
- phpMyAdmin access

## Installation Steps

### 1. Upload Module Files

Using File Manager or FTP:
- Upload the `vapi_integration` folder to: `/modules/vapi_integration/`
- Ensure all files and folders are uploaded correctly

### 2. Database Setup

**Option A: Automatic (Recommended)**
- The module will create tables automatically when activated
- No manual SQL required

**Option B: Manual**
- Open phpMyAdmin
- Select your Perfex CRM database
- Import `install.sql` file
- Or copy/paste SQL commands from `install.sql`

### 3. Activate Module

1. Log in to Perfex CRM admin panel
2. Go to **Setup > Modules**
3. Find "Automated Calls" module
4. Click **Activate**

### 4. Configure Settings

1. Navigate to **Automated Calls > Settings**
2. Enter your Vapi.ai API Key (required)
3. Enter Assistant ID (optional, if not set in Vapi.ai)
4. Enter Phone Number ID (optional, if not set in Vapi.ai)
5. Copy the Webhook URL
6. Click **Save**

### 5. Configure Vapi.ai Webhook

1. Log in to [Vapi.ai Dashboard](https://dashboard.vapi.ai)
2. Go to **Settings > Webhooks**
3. Add new webhook with the URL from step 4
4. Enable webhook events:
   - `status-update`
   - `end-of-call-report`
   - `transcript`
   - `recording`
5. Save webhook configuration

### 6. Test Installation

1. Go to **Leads** in Perfex CRM
2. Open any lead with a phone number
3. Click **"Vapi Calls"** tab
4. Click **"Initiate Call"**
5. Verify call is initiated and status updates

## Verification Checklist

- [ ] Module appears in sidebar menu (second position)
- [ ] Settings page loads correctly
- [ ] Webhook URL is displayed
- [ ] Can initiate a test call
- [ ] Call status updates via webhook
- [ ] Dashboard widget shows statistics

## Troubleshooting

If installation fails:

1. **Check file permissions**: All files should be readable (644)
2. **Check database**: Verify tables were created
3. **Check PHP errors**: Review error logs
4. **Check module activation**: Ensure module shows as "Active"
5. **Clear cache**: Clear browser and Perfex cache

For detailed troubleshooting, see README.md

## Next Steps

After installation:
1. Review the full documentation in `README.md`
2. Configure your first campaign
3. Test with a single lead call
4. Set up bulk campaigns

---

**Need Help?** Refer to the main README.md for detailed documentation and troubleshooting.

