<?php
// Standalone webhook endpoint - bypasses full Perfex loading
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to webhook caller
ini_set('log_errors', 1);

// Get paths
$base_path = dirname(dirname(dirname(__FILE__)));
$system_path = $base_path . '/system/';
$application_folder = $base_path . '/application/';

define('APPPATH', $application_folder . '/');
define('FCPATH', $base_path . '/');
define('BASEPATH', $system_path);
define('ENVIRONMENT', 'production'); // Set environment

// Log file for debugging
$log_file = $application_folder . 'logs/webhook_debug_' . date('Y-m-d') . '.txt';

function webhook_log($message) {
    global $log_file;
    $log_entry = date('Y-m-d H:i:s') . " - " . $message . "\n";
    // Use error_log as backup if file_put_contents fails
    @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    error_log("Vapi Webhook: " . $message);
}

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        webhook_log("FATAL ERROR: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
    }
});

webhook_log("=== WEBHOOK STARTED ===");
webhook_log("Base path: " . $base_path);
webhook_log("APPPATH: " . APPPATH);

// Load database config
webhook_log("Checking for database config file...");
$db_config_file = APPPATH . 'config/database.php';
webhook_log("Database config path: " . $db_config_file);
webhook_log("File exists: " . (file_exists($db_config_file) ? 'YES' : 'NO'));

if (!file_exists($db_config_file)) {
    webhook_log("ERROR: database.php not found at: " . $db_config_file);
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Configuration error']);
    exit;
}

webhook_log("Attempting to load database config file...");

// Set up error handler
$old_error_handler = set_error_handler(function($errno, $errstr, $errfile, $errline) {
    global $log_file;
    $log_entry = date('Y-m-d H:i:s') . " - PHP ERROR: [$errno] $errstr in $errfile on line $errline\n";
    @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    error_log("Vapi Webhook PHP ERROR: [$errno] $errstr");
    return false; // Let PHP handle it normally
});

// Enhanced shutdown function
register_shutdown_function(function() {
    global $log_file;
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE, E_RECOVERABLE_ERROR])) {
        $log_entry = date('Y-m-d H:i:s') . " - SHUTDOWN ERROR: [" . $error['type'] . "] " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'] . "\n";
        @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        error_log("Vapi Webhook SHUTDOWN ERROR: " . $error['message']);
    }
});

try {
    webhook_log("Loading app-config.php first...");
    
    // database.php includes app-config.php, so load it first
    $app_config_file = APPPATH . 'config/app-config.php';
    if (file_exists($app_config_file)) {
        webhook_log("app-config.php exists, loading...");
        ob_start();
        require_once($app_config_file);
        $app_config_output = ob_get_clean();
        if (!empty($app_config_output)) {
            webhook_log("WARNING: Output from app-config.php: " . substr($app_config_output, 0, 200));
        }
        webhook_log("app-config.php loaded");
    } else {
        webhook_log("WARNING: app-config.php not found at: " . $app_config_file);
    }
    
    // Define db_prefix function if not exists (needed by database.php)
    if (!function_exists('db_prefix')) {
        function db_prefix() {
            return defined('APP_DB_PREFIX') ? APP_DB_PREFIX : 'tbl';
        }
        webhook_log("db_prefix() function defined");
    }
    
    webhook_log("Starting require_once database.php with output buffering...");
    ob_start();
    require_once($db_config_file);
    $output = ob_get_clean();
    webhook_log("require_once finished, output length: " . strlen($output));
    
    if (!empty($output)) {
        webhook_log("WARNING: Output captured during require: " . substr($output, 0, 200));
    }
    
    webhook_log("require_once completed");
    
    if (!isset($db)) {
        webhook_log("ERROR: \$db variable not set after loading config");
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database configuration not loaded']);
        exit;
    }
    
    if (!isset($db['default'])) {
        webhook_log("ERROR: \$db['default'] not set after loading config");
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database default configuration not found']);
        exit;
    }
    
    webhook_log("Database config loaded successfully");
    webhook_log("\$db array keys: " . implode(', ', array_keys($db)));
    
} catch (ParseError $e) {
    ob_end_clean();
    webhook_log("PARSE ERROR loading database config: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Configuration parse error']);
    exit;
} catch (Exception $e) {
    ob_end_clean();
    webhook_log("EXCEPTION loading database config: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Configuration error']);
    exit;
} catch (Error $e) {
    ob_end_clean();
    webhook_log("FATAL ERROR loading database config: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Configuration fatal error']);
    exit;
} catch (Throwable $e) {
    ob_end_clean();
    webhook_log("THROWABLE loading database config: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Configuration error']);
    exit;
} finally {
    if ($old_error_handler) {
        set_error_handler($old_error_handler);
    }
}

// Connect to database directly
webhook_log("Getting database config...");
if (!isset($db) || !isset($db['default'])) {
    webhook_log("ERROR: Database config array not found");
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database configuration not found']);
    exit;
}

$db_config = $db['default'];
webhook_log("Database host: " . ($db_config['hostname'] ?? 'NOT SET'));
webhook_log("Database name: " . ($db_config['database'] ?? 'NOT SET'));
webhook_log("Database user: " . ($db_config['username'] ?? 'NOT SET'));

try {
    $mysqli = new mysqli($db_config['hostname'], $db_config['username'], $db_config['password'], $db_config['database']);
    
    if ($mysqli->connect_error) {
        webhook_log("ERROR: Database connection failed - " . $mysqli->connect_error);
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
    
    webhook_log("Database connected successfully");
} catch (Exception $e) {
    webhook_log("EXCEPTION connecting to database: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection error']);
    exit;
} catch (Error $e) {
    webhook_log("FATAL ERROR connecting to database: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection error']);
    exit;
}

// Set charset
$mysqli->set_charset('utf8mb4');

// Get token from URL
$token = isset($_GET['token']) ? $_GET['token'] : '';

// Verify token from database
$table_prefix = $db_config['dbprefix'];
$sql = "SELECT value FROM " . $table_prefix . "options WHERE name = 'vapi_webhook_token'";
webhook_log("Executing SQL: " . $sql);

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    webhook_log("ERROR: Prepare failed - " . $mysqli->error);
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database query failed: ' . $mysqli->error]);
    $mysqli->close();
    exit;
}

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$expected_token = $row ? $row['value'] : '';

webhook_log("Token check - Expected: " . substr($expected_token, 0, 10) . "... Got: " . substr($token, 0, 10) . "...");

if (empty($expected_token) || $token !== $expected_token) {
    webhook_log("ERROR: Invalid token");
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid token']);
    $mysqli->close();
    exit;
}

webhook_log("Token validated successfully");

// Read payload
$payload = file_get_contents('php://input');
webhook_log("Payload received, length: " . strlen($payload));

$data = json_decode($payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    webhook_log("ERROR: JSON decode failed - " . json_last_error_msg());
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
    $mysqli->close();
    exit;
}

webhook_log("JSON decoded successfully");

// Extract message
$message = isset($data['message']) ? $data['message'] : $data;
$call = isset($message['call']) ? $message['call'] : [];
$artifact = isset($message['artifact']) ? $message['artifact'] : [];
$external_id = isset($call['id']) ? $call['id'] : null;
$lead_id = isset($call['metadata']['lead_id']) ? intval($call['metadata']['lead_id']) : null;
// Prioritize message.status over call.status (message.status is more current)
$status = isset($message['status']) ? $message['status'] : (isset($call['status']) ? $call['status'] : null);
$event_type = isset($message['type']) ? $message['type'] : 'unknown';

webhook_log("=== EXTRACTION ===");
webhook_log("external_id: " . ($external_id ?: 'NULL'));
webhook_log("lead_id: " . ($lead_id ?: 'NULL'));
webhook_log("message.status: " . (isset($message['status']) ? $message['status'] : 'NULL'));
webhook_log("call.status: " . (isset($call['status']) ? $call['status'] : 'NULL'));
if (isset($message['status'])) {
    webhook_log("Using message.status (prioritized over call.status)");
} elseif (isset($call['status'])) {
    webhook_log("Using call.status (message.status not available)");
}
webhook_log("Final status before mapping: " . ($status ?: 'NULL'));
webhook_log("event_type: " . $event_type);

// Map status
webhook_log("=== STATUS MAPPING ===");
if ($status === 'ended') {
    $ended_reason = isset($message['endedReason']) ? $message['endedReason'] : (isset($call['endedReason']) ? $call['endedReason'] : '');
    webhook_log("Status is 'ended'");
    webhook_log("message.endedReason: " . (isset($message['endedReason']) ? $message['endedReason'] : 'NULL'));
    webhook_log("call.endedReason: " . (isset($call['endedReason']) ? $call['endedReason'] : 'NULL'));
    webhook_log("Final endedReason: " . ($ended_reason ?: 'NULL'));
    
    if (!empty($ended_reason)) {
        $has_error = (stripos($ended_reason, 'error') !== false || stripos($ended_reason, 'failed') !== false);
        webhook_log("endedReason contains error/failed: " . ($has_error ? 'YES' : 'NO'));
        
        if ($has_error) {
            $status = 'error';
            webhook_log("✓ Mapped status from 'ended' to 'error'");
        } else {
            $status = 'completed';
            webhook_log("✓ Mapped status from 'ended' to 'completed'");
        }
    } else {
        $status = 'completed';
        webhook_log("✓ No endedReason, mapped status from 'ended' to 'completed'");
    }
} else {
    webhook_log("Status is not 'ended', remains: " . ($status ?: 'NULL'));
}
webhook_log("Final mapped status: " . ($status ?: 'NULL'));

// Extract additional data - transcript
$transcript = null;
if (isset($call['transcript'])) {
    $transcript = $call['transcript'];
    webhook_log("Found transcript in call.transcript");
} elseif (isset($call['artifact']['transcript'])) {
    $transcript = $call['artifact']['transcript'];
    webhook_log("Found transcript in call.artifact.transcript");
} elseif (isset($artifact['transcript'])) {
    $transcript = $artifact['transcript'];
    webhook_log("Found transcript in message.artifact.transcript");
} elseif (isset($artifact['messagesOpenAIFormatted']) && is_array($artifact['messagesOpenAIFormatted'])) {
    // Format messages from OpenAI formatted array
    webhook_log("Found messagesOpenAIFormatted array, formatting transcript");
    $transcript_lines = [];
    foreach ($artifact['messagesOpenAIFormatted'] as $msg) {
        if (isset($msg['role']) && isset($msg['content'])) {
            $role = ucfirst($msg['role']);
            // Map roles to readable names
            if ($role === 'Assistant') {
                $role = 'Agent';
            } elseif ($role === 'User') {
                $role = 'Customer';
            }
            $transcript_lines[] = $role . ': ' . $msg['content'];
        }
    }
    if (!empty($transcript_lines)) {
        $transcript = implode("\n\n", $transcript_lines);
        webhook_log("Formatted transcript from messagesOpenAIFormatted: " . strlen($transcript) . " characters");
    }
} elseif (isset($artifact['messages']) && is_array($artifact['messages'])) {
    // Format messages from detailed messages array
    webhook_log("Found messages array, formatting transcript");
    $transcript_lines = [];
    foreach ($artifact['messages'] as $msg) {
        if (isset($msg['role']) && isset($msg['message'])) {
            $role = ucfirst($msg['role']);
            // Map roles to readable names
            if ($role === 'Bot') {
                $role = 'Agent';
            } elseif ($role === 'User') {
                $role = 'Customer';
            } elseif ($role === 'System') {
                // Skip system messages in transcript
                continue;
            }
            $transcript_lines[] = $role . ': ' . $msg['message'];
        }
    }
    if (!empty($transcript_lines)) {
        $transcript = implode("\n\n", $transcript_lines);
        webhook_log("Formatted transcript from messages: " . strlen($transcript) . " characters");
    }
}

if ($transcript) {
    webhook_log("✓ Transcript extracted successfully: " . strlen($transcript) . " characters");
} else {
    webhook_log("⚠ No transcript found in payload");
}

$recording_url = null;
// Check multiple locations for recording URL (prioritize mono combined, then stereo, then others)
if (isset($artifact['recording']['mono']['combinedUrl'])) {
    $recording_url = $artifact['recording']['mono']['combinedUrl'];
    webhook_log("Found recording in artifact.recording.mono.combinedUrl");
} elseif (isset($artifact['recordingUrl'])) {
    $recording_url = $artifact['recordingUrl'];
    webhook_log("Found recording in artifact.recordingUrl");
} elseif (isset($artifact['recording']['stereoUrl'])) {
    $recording_url = $artifact['recording']['stereoUrl'];
    webhook_log("Found recording in artifact.recording.stereoUrl");
} elseif (isset($artifact['stereoRecordingUrl'])) {
    $recording_url = $artifact['stereoRecordingUrl'];
    webhook_log("Found recording in artifact.stereoRecordingUrl");
} elseif (isset($call['recordingUrl'])) {
    $recording_url = $call['recordingUrl'];
    webhook_log("Found recording in call.recordingUrl");
} elseif (isset($call['artifact']['recording']['url'])) {
    $recording_url = $call['artifact']['recording']['url'];
    webhook_log("Found recording in call.artifact.recording.url");
} elseif (isset($call['artifact']['recording']) && is_string($call['artifact']['recording'])) {
    $recording_url = $call['artifact']['recording'];
    webhook_log("Found recording in call.artifact.recording (string)");
}

if ($recording_url) {
    webhook_log("✓ Recording URL extracted: " . substr($recording_url, 0, 100) . "...");
} else {
    webhook_log("⚠ No recording URL found in payload");
}

$duration = null;
if (isset($artifact['durationSeconds'])) {
    $duration = intval($artifact['durationSeconds']);
    webhook_log("Found duration in artifact.durationSeconds: " . $duration);
} elseif (isset($artifact['duration'])) {
    $duration = intval($artifact['duration']);
    webhook_log("Found duration in artifact.duration: " . $duration);
} elseif (isset($call['duration'])) {
    $duration = intval($call['duration']);
    webhook_log("Found duration in call.duration: " . $duration);
} elseif (isset($message['duration'])) {
    $duration = intval($message['duration']);
    webhook_log("Found duration in message.duration: " . $duration);
}

$ended_reason = null;
if (isset($message['endedReason'])) {
    $ended_reason = $message['endedReason'];
} elseif (isset($call['endedReason'])) {
    $ended_reason = $call['endedReason'];
}

// Update or create call record
if (!empty($external_id)) {
    webhook_log("Processing call with external_id: " . $external_id . ", lead_id: " . $lead_id);
    
    // Check if record exists by external_id
    $check_sql = "SELECT id, lead_id, status FROM " . $db_config['dbprefix'] . "vapi_calls WHERE external_id = ?";
    webhook_log("Checking for existing record by external_id: " . $check_sql);
    
    $stmt = $mysqli->prepare($check_sql);
    if (!$stmt) {
        webhook_log("ERROR: Prepare failed for check - " . $mysqli->error);
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $mysqli->error]);
        $mysqli->close();
        exit;
    }
    
    $stmt->bind_param("s", $external_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    
    // If not found by external_id, try to find by lead_id ONLY if:
    // 1. The call is in 'requested' status (just initiated, waiting for first webhook)
    // 2. AND the call doesn't have an external_id yet (or it's null/empty)
    // This prevents updating random calls during testing
    if (!$existing && !empty($lead_id)) {
        webhook_log("Record not found by external_id, trying to find by lead_id: " . $lead_id);
        // Only match if status is 'requested' AND external_id is null/empty (new call waiting for first webhook)
        $check_sql2 = "SELECT id, lead_id, status, external_id FROM " . $db_config['dbprefix'] . "vapi_calls 
            WHERE lead_id = ? AND status = 'requested' AND (external_id IS NULL OR external_id = '') 
            ORDER BY created_at DESC LIMIT 1";
        $stmt2 = $mysqli->prepare($check_sql2);
        if ($stmt2) {
            $stmt2->bind_param("i", $lead_id);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $existing = $result2->fetch_assoc();
        }
        
        if ($existing) {
            webhook_log("Found record by lead_id (requested status, no external_id). ID: " . $existing['id'] . ", Current status: " . ($existing['status'] ?: 'NULL'));
            // Update the external_id to match what Vapi is sending
            $update_ext_sql = "UPDATE " . $db_config['dbprefix'] . "vapi_calls SET external_id = ? WHERE id = ?";
            $stmt_ext = $mysqli->prepare($update_ext_sql);
            if ($stmt_ext) {
                $stmt_ext->bind_param("si", $external_id, $existing['id']);
                if ($stmt_ext->execute()) {
                    webhook_log("Updated external_id for record ID: " . $existing['id'] . " from NULL to '" . $external_id . "'");
                } else {
                    webhook_log("ERROR: Failed to update external_id: " . $stmt_ext->error);
                }
            }
        } else {
            webhook_log("No record found by lead_id either");
        }
    }
    
    if ($existing) {
        webhook_log("Found existing record ID: " . $existing['id'] . ", Current status: " . ($existing['status'] ?: 'NULL'));
    } else {
        webhook_log("No existing record found, will create new");
    }
    
    $now = date('Y-m-d H:i:s');
    $payload_json = json_encode($data);
    
    if ($existing) {
        // Update existing record
        webhook_log("=== PREPARING UPDATE ===");
        $update_fields = [];
        $update_values = [];
        $types = '';
        
        // ALWAYS update status if we have one (even if it's the same)
        if ($status !== null && $status !== '') {
            $update_fields[] = "status = ?";
            $update_values[] = $status;
            $types .= "s";
            webhook_log("Will update status to: " . $status);
        } else {
            webhook_log("WARNING: Status is null or empty, will NOT update status field");
        }
        
        $update_fields[] = "response_payload = ?";
        $update_values[] = $payload_json;
        $types .= "s";
        
        $update_fields[] = "updated_at = ?";
        $update_values[] = $now;
        $types .= "s";
        
        if ($transcript !== null) {
            $update_fields[] = "transcript = ?";
            $update_values[] = $transcript;
            $types .= "s";
        }
        
        if ($recording_url !== null) {
            $update_fields[] = "recording_url = ?";
            $update_values[] = $recording_url;
            $types .= "s";
        }
        
        if ($duration !== null) {
            $update_fields[] = "duration_seconds = ?";
            $update_values[] = $duration;
            $types .= "i";
        }
        
        if ($ended_reason !== null) {
            $update_fields[] = "ended_reason = ?";
            $update_values[] = $ended_reason;
            $types .= "s";
        }
        
        // Use record ID for WHERE clause (more reliable than external_id)
        $update_sql = "UPDATE " . $db_config['dbprefix'] . "vapi_calls SET " . implode(", ", $update_fields) . " WHERE id = ?";
        $update_values[] = $existing['id'];
        $types .= "i";
        
        webhook_log("=== EXECUTING UPDATE ===");
        webhook_log("SQL: " . $update_sql);
        webhook_log("Update fields count: " . count($update_fields));
        webhook_log("Update values: " . json_encode($update_values));
        webhook_log("Types string: " . $types);
        webhook_log("Record ID to update: " . $existing['id']);
        
        $stmt = $mysqli->prepare($update_sql);
        if (!$stmt) {
            webhook_log("ERROR: Prepare failed for update - " . $mysqli->error);
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Database update error: ' . $mysqli->error]);
            $mysqli->close();
            exit;
        }
        
        $stmt->bind_param($types, ...$update_values);
        if (!$stmt->execute()) {
            webhook_log("ERROR: Execute failed for update - " . $stmt->error);
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Update failed: ' . $stmt->error]);
            $mysqli->close();
            exit;
        }
        
        $rows_affected = $stmt->affected_rows;
        if ($rows_affected > 0) {
            webhook_log("Record updated successfully. Rows affected: " . $rows_affected . ", New status: " . ($status ?: 'unchanged'));
        } else {
            webhook_log("WARNING: Update executed but no rows affected! Record ID: " . $existing['id']);
        }
        
        // Verify the update
        $verify_sql = "SELECT id, status, updated_at, external_id FROM " . $db_config['dbprefix'] . "vapi_calls WHERE id = ?";
        $stmt_verify = $mysqli->prepare($verify_sql);
        if ($stmt_verify) {
            $stmt_verify->bind_param("i", $existing['id']);
            $stmt_verify->execute();
            $verify_result = $stmt_verify->get_result();
            $verified = $verify_result->fetch_assoc();
            if ($verified) {
                webhook_log("VERIFIED UPDATE - ID: " . $verified['id'] . ", Status: " . $verified['status'] . ", External ID: " . ($verified['external_id'] ?: 'NULL') . ", Updated: " . $verified['updated_at']);
            } else {
                webhook_log("WARNING: Could not verify update - record not found after update!");
            }
        }
        
        $call_id = $existing['id'];
    } else {
        // Create new record
        webhook_log("Creating new call record");
        $insert_sql = "INSERT INTO " . $db_config['dbprefix'] . "vapi_calls 
            (lead_id, external_id, status, response_payload, transcript, recording_url, duration_seconds, ended_reason, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $mysqli->prepare($insert_sql);
        if (!$stmt) {
            webhook_log("ERROR: Prepare failed for insert - " . $mysqli->error);
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Database insert error: ' . $mysqli->error]);
            $mysqli->close();
            exit;
        }
        
        $status_value = $status ?: 'received';
        // Type string: i(lead_id) s(external_id) s(status) s(payload) s(transcript) s(recording) i(duration) s(ended_reason) s(created_at) s(updated_at) = 10 chars
        // Parameters: lead_id(i), external_id(s), status(s), payload(s), transcript(s), recording(s), duration(i), ended_reason(s), created_at(s), updated_at(s)
        // Construct type string programmatically to ensure exactly 10 characters: i + 5s + i + 3s
        $insert_types = "i" . str_repeat("s", 5) . "i" . str_repeat("s", 3); // = "isssssiss" (10 chars)
        $stmt->bind_param($insert_types, $lead_id, $external_id, $status_value, $payload_json, $transcript, $recording_url, $duration, $ended_reason, $now, $now);
        
        if (!$stmt->execute()) {
            webhook_log("ERROR: Execute failed for insert - " . $stmt->error);
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Insert failed: ' . $stmt->error]);
            $mysqli->close();
            exit;
        }
        
        $call_id = $mysqli->insert_id;
        webhook_log("New record created with ID: " . $call_id);
    }
    
    // Log event
    if ($call_id) {
        $stmt = $mysqli->prepare("INSERT INTO " . $db_config['dbprefix'] . "vapi_call_events 
            (vapi_call_id, lead_id, external_id, event_type, event_payload, created_at) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $event_payload = $payload_json;
        $stmt->bind_param("iissss", $call_id, $lead_id, $external_id, $event_type, $event_payload, $now);
        if (!$stmt->execute()) {
            webhook_log("ERROR: Failed to insert event - " . $stmt->error);
        } else {
            webhook_log("Event logged successfully for call ID: " . $call_id);
        }
    }
    
    // Update campaign lead status if this call is part of a campaign
    if ($lead_id && $call_id) {
        webhook_log("Checking for campaign leads with lead_id: $lead_id and call_id: $call_id");
        
        $campaign_lead_sql = "SELECT campaign_id, lead_id FROM " . $db_config['dbprefix'] . "vapi_campaign_leads WHERE lead_id = ? AND (call_id = ? OR call_id IS NULL)";
        $campaign_lead_stmt = $mysqli->prepare($campaign_lead_sql);
        if ($campaign_lead_stmt) {
            $campaign_lead_stmt->bind_param("ii", $lead_id, $call_id);
            $campaign_lead_stmt->execute();
            $campaign_lead_result = $campaign_lead_stmt->get_result();
            
            if ($campaign_lead_result && $campaign_lead_result->num_rows > 0) {
                while ($campaign_lead = $campaign_lead_result->fetch_assoc()) {
                    $campaign_id = $campaign_lead['campaign_id'];
                    $campaign_lead_id = $campaign_lead['lead_id'];
                    
                    // Determine status based on call status
                    $campaign_lead_status = 'pending';
                    if ($status === 'completed') {
                        $campaign_lead_status = 'completed';
                    } elseif ($status === 'error' || $status === 'failed') {
                        $campaign_lead_status = 'failed';
                    } elseif ($status === 'initiated' || $status === 'queued' || $status === 'ringing') {
                        $campaign_lead_status = 'initiated';
                    }
                    
                    webhook_log("Updating campaign lead: campaign_id=$campaign_id, lead_id=$campaign_lead_id, status=$campaign_lead_status, call_id=$call_id");
                    
                    // Update campaign lead
                    $update_campaign_lead_sql = "UPDATE " . $db_config['dbprefix'] . "vapi_campaign_leads SET status = ?, call_id = ?, called_at = NOW() WHERE campaign_id = ? AND lead_id = ?";
                    $update_campaign_lead_stmt = $mysqli->prepare($update_campaign_lead_sql);
                    if ($update_campaign_lead_stmt) {
                        $update_campaign_lead_stmt->bind_param("siii", $campaign_lead_status, $call_id, $campaign_id, $campaign_lead_id);
                        $update_campaign_lead_stmt->execute();
                        $update_campaign_lead_stmt->close();
                        
                        // Update campaign statistics
                        webhook_log("Updating campaign statistics for campaign_id: $campaign_id");
                        
                        // Get counts
                        $count_sql = "SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                            SUM(CASE WHEN status = 'initiated' THEN 1 ELSE 0 END) as initiated,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
                            FROM " . $db_config['dbprefix'] . "vapi_campaign_leads WHERE campaign_id = ?";
                        $count_stmt = $mysqli->prepare($count_sql);
                        if ($count_stmt) {
                            $count_stmt->bind_param("i", $campaign_id);
                            $count_stmt->execute();
                            $count_result = $count_stmt->get_result();
                            if ($count_result && $count_row = $count_result->fetch_assoc()) {
                                $update_campaign_sql = "UPDATE " . $db_config['dbprefix'] . "vapi_campaigns SET 
                                    total_leads = ?,
                                    calls_completed = ?,
                                    calls_failed = ?,
                                    calls_initiated = ?,
                                    calls_pending = ?,
                                    updated_at = NOW()
                                    WHERE id = ?";
                                $update_campaign_stmt = $mysqli->prepare($update_campaign_sql);
                                if ($update_campaign_stmt) {
                                    $update_campaign_stmt->bind_param("iiiiii", 
                                        $count_row['total'],
                                        $count_row['completed'],
                                        $count_row['failed'],
                                        $count_row['initiated'],
                                        $count_row['pending'],
                                        $campaign_id
                                    );
                                    $update_campaign_stmt->execute();
                                    $update_campaign_stmt->close();
                                    webhook_log("Campaign statistics updated successfully");
                                }
                            }
                            $count_stmt->close();
                        }
                    }
                }
                $campaign_lead_stmt->close();
            } else {
                webhook_log("No campaign leads found for lead_id: $lead_id");
            }
        }
    }
    
    // Add note to lead if call completed/ended/error
    if ($lead_id && ($event_type === 'end-of-call-report' || $event_type === 'status-update' || $status === 'completed' || $status === 'error')) {
        $note_content = "Vapi.ai Call Event: " . ucfirst(str_replace('-', ' ', $event_type)) . "\n\n";
        $note_content .= "Status: " . ucfirst($status) . "\n";
        
        if ($duration) {
            $note_content .= "Duration: " . $duration . " seconds\n";
        }
        
        if ($ended_reason) {
            $note_content .= "Ended Reason: " . $ended_reason . "\n";
        }
        
        if ($transcript) {
            $note_content .= "\nTranscript:\n" . $transcript . "\n";
        }
        
        if ($recording_url) {
            $note_content .= "\nRecording: " . $recording_url . "\n";
        }
        
        $note_content .= "\n(Full details available in Vapi Integration call logs)";
        
        // Get current user ID (default to 0 for system)
        $added_from = 0;
        
        // Use correct column names: rel_id and rel_type (not relation and relation_type)
        $stmt = $mysqli->prepare("INSERT INTO " . $db_config['dbprefix'] . "notes 
            (description, dateadded, addedfrom, rel_id, rel_type) 
            VALUES (?, ?, ?, ?, 'lead')");
        $now_note = date('Y-m-d H:i:s');
        $stmt->bind_param("ssii", $note_content, $now_note, $added_from, $lead_id);
        if (!$stmt->execute()) {
            webhook_log("ERROR: Failed to insert note - " . $stmt->error);
        } else {
            $note_id = $mysqli->insert_id;
            webhook_log("Note added successfully to lead. Note ID: " . $note_id);
        }
    }
}

$mysqli->close();

webhook_log("=== WEBHOOK COMPLETED SUCCESSFULLY ===");

http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['success' => true, 'processed' => true]);
exit;

