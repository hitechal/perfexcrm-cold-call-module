# Changelog

All notable changes to the Automated Calls module will be documented in this file.

## [1.0.0] - 2025-01-13

### Added
- Initial release of Automated Calls module
- Single call initiation from lead profiles
- Comprehensive call logging and tracking
- Call details view with transcript and recording
- Dashboard widget for call statistics
- Campaign management system
- Bulk calling campaigns with lead filtering
- Real-time campaign progress tracking
- Webhook integration for call status updates
- Lead modal integration with "Vapi Calls" tab
- Call transcript display in chat-style format
- Call recording playback and download
- Activity logging for all call events
- Advanced lead filtering for campaigns
- Campaign scheduling functionality
- Campaign statistics and reporting
- Webhook security token authentication
- Standalone webhook endpoint for reliability
- CSRF protection for all forms
- Multi-language support structure

### Features
- **Call Management**
  - Initiate calls directly from lead profiles
  - View call history per lead
  - Detailed call information view
  - Call status tracking (requested, completed, error)
  - Call transcript viewing
  - Call recording playback and download

- **Campaign Management**
  - Create and manage bulk calling campaigns
  - Advanced lead filtering (status, source, assigned, phone, date)
  - Campaign scheduling
  - Real-time progress tracking
  - Campaign statistics (success rate, completion rate)
  - Individual lead status tracking within campaigns

- **Dashboard Integration**
  - Call statistics widget
  - Recent calls display
  - Quick access to call logs

- **Webhook Integration**
  - Secure webhook endpoint
  - Token-based authentication
  - Real-time status updates
  - Transcript and recording updates
  - Comprehensive event logging

### Security
- All API credentials stored securely in database
- Webhook token authentication
- CSRF protection enabled
- Input sanitization and validation
- Parameterized database queries

### Database
- `tblvapi_calls` - Call records
- `tblvapi_call_events` - Webhook event logs
- `tblvapi_campaigns` - Campaign data
- `tblvapi_campaign_leads` - Campaign lead associations

### Technical Details
- Compatible with Perfex CRM 3.4.0+
- PHP 7.4+ required
- MySQL 5.7+ required
- Shared hosting compatible
- No SSH/terminal access required

---

## Future Enhancements (Planned)

- [ ] Call scheduling for individual leads
- [ ] Call templates and presets
- [ ] Advanced reporting and analytics
- [ ] Export call logs to CSV/Excel
- [ ] Integration with other Perfex CRM modules
- [ ] Multi-language translations
- [ ] Call retry functionality
- [ ] Custom call scripts per campaign
- [ ] Call performance metrics
- [ ] Integration with calendar for scheduling

---

**Note**: This changelog follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) principles.

