<?php
/**
 * MySQL Version Checker
 * Reads connection info from environment or .env.local and displays MySQL version
 */

// Path to .env.local file
$envFile = __DIR__ . '/../.env.local';
$configSource = null;

// Function to parse .env file
function parseEnvFile($filePath) {
    if (!file_exists($filePath)) {
        return null;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];

    foreach ($lines as $line) {
        // Skip comments and empty lines
        if (strpos(trim($line), '#') === 0 || trim($line) === '') {
            continue;
        }

        // Parse KEY=VALUE or KEY="VALUE"
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }

            $env[$key] = $value;
        }
    }

    return $env;
}

// Function to parse DATABASE_URL
function parseDatabaseUrl($url) {
    // Format: mysql://user:password@host:port/database?params
    if (!preg_match('/^mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/([^\?]+)/', $url, $matches)) {
        return null;
    }

    return [
        'user' => urldecode($matches[1]),
        'password' => urldecode($matches[2]),
        'host' => $matches[3],
        'port' => $matches[4],
        'database' => urldecode($matches[5])
    ];
}

// HTML header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySQL Version Checker</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 20px 0;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        .info-row {
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .info-value {
            color: #333;
            font-family: monospace;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>MySQL Version Checker</h1>

<?php
// First, check environment variables for DATABASE_URL
$databaseUrl = getenv('DATABASE_URL');

if ($databaseUrl !== false && !empty($databaseUrl)) {
    // Found in environment
    $configSource = 'Environment Variable';
} else {
    // Fall back to .env.local file
    $env = parseEnvFile($envFile);

    if (!$env) {
        echo '<div class="info-box error">';
        echo '<strong>Error:</strong> Could not read .env.local file at: ' . htmlspecialchars($envFile);
        echo '</div>';
        exit;
    }

    if (!isset($env['DATABASE_URL'])) {
        echo '<div class="info-box error">';
        echo '<strong>Error:</strong> DATABASE_URL not found in environment or .env.local file';
        echo '</div>';
        exit;
    }

    $databaseUrl = $env['DATABASE_URL'];
    $configSource = '.env.local file';
}

// Parse DATABASE_URL
$dbConfig = parseDatabaseUrl($databaseUrl);

if (!$dbConfig) {
    echo '<div class="info-box error">';
    echo '<strong>Error:</strong> Could not parse DATABASE_URL: ' . htmlspecialchars($databaseUrl);
    echo '</div>';
    exit;
}

// Display connection info
echo '<div class="info-box">';
echo '<h3>Connection Information</h3>';
echo '<div class="info-row"><span class="info-label">Source:</span><span class="info-value">' . htmlspecialchars($configSource) . '</span></div>';
echo '<div class="info-row"><span class="info-label">Host:</span><span class="info-value">' . htmlspecialchars($dbConfig['host']) . '</span></div>';
echo '<div class="info-row"><span class="info-label">Port:</span><span class="info-value">' . htmlspecialchars($dbConfig['port']) . '</span></div>';
echo '<div class="info-row"><span class="info-label">Database:</span><span class="info-value">' . htmlspecialchars($dbConfig['database']) . '</span></div>';
echo '<div class="info-row"><span class="info-label">User:</span><span class="info-value">' . htmlspecialchars($dbConfig['user']) . '</span></div>';
echo '<div class="info-row"><span class="info-label">Password:</span><span class="info-value">' . htmlspecialchars($dbConfig['password']) . '</span></div>';
echo '</div>';

// Connect to MySQL
$connectionSuccessful = false;
$connectionError = null;
$hostsToTry = [$dbConfig['host']];

// If host is localhost, also try 127.0.0.1 (and vice versa) as MySQL treats them differently
if ($dbConfig['host'] === 'localhost') {
    $hostsToTry[] = '127.0.0.1';
} elseif ($dbConfig['host'] === '127.0.0.1') {
    $hostsToTry[] = 'localhost';
}

$pdo = null;
$successfulHost = null;

foreach ($hostsToTry as $tryHost) {
    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $tryHost,
            $dbConfig['port'],
            $dbConfig['database']
        );

        $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $connectionSuccessful = true;
        $successfulHost = $tryHost;
        break; // Connection successful, stop trying
    } catch (PDOException $e) {
        $connectionError = $e->getMessage();
        // Continue to next host
    }
}

if ($connectionSuccessful) {
    echo '<div class="info-box success">';
    echo '<strong>✓ Connection successful!</strong>';
    if ($successfulHost !== $dbConfig['host']) {
        echo '<br><small>Note: Connected using <code>' . htmlspecialchars($successfulHost) . '</code> instead of <code>' . htmlspecialchars($dbConfig['host']) . '</code></small>';
        echo '<br><small>MySQL treats localhost and 127.0.0.1 as different hosts with different permissions.</small>';
    }
    echo '</div>';

    // Get MySQL version using multiple methods
    echo '<div class="info-box success">';
    echo '<h3>MySQL Version Information</h3>';

    // Method 1: SELECT VERSION()
    $stmt = $pdo->query('SELECT VERSION() as version');
    $result = $stmt->fetch();
    echo '<div class="info-row"><span class="info-label">Version (VERSION()):</span><span class="info-value">' . htmlspecialchars($result['version']) . '</span></div>';

    // Method 2: SHOW VARIABLES LIKE 'version'
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'version'");
    $result = $stmt->fetch();
    echo '<div class="info-row"><span class="info-label">Version (SHOW VARIABLES):</span><span class="info-value">' . htmlspecialchars($result['Value']) . '</span></div>';

    // Method 3: Get server info from PDO
    echo '<div class="info-row"><span class="info-label">Server Info (PDO):</span><span class="info-value">' . htmlspecialchars($pdo->getAttribute(PDO::ATTR_SERVER_INFO)) . '</span></div>';

    // Additional version details
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'version_comment'");
    $result = $stmt->fetch();
    if ($result) {
        echo '<div class="info-row"><span class="info-label">Version Comment:</span><span class="info-value">' . htmlspecialchars($result['Value']) . '</span></div>';
    }

    $stmt = $pdo->query("SHOW VARIABLES LIKE 'version_compile%'");
    while ($row = $stmt->fetch()) {
        echo '<div class="info-row"><span class="info-label">' . htmlspecialchars($row['Variable_name']) . ':</span><span class="info-value">' . htmlspecialchars($row['Value']) . '</span></div>';
    }

    echo '</div>';

    // Close connection
    $pdo = null;

} else {
    // All connection attempts failed
    echo '<div class="info-box error">';
    echo '<h3>Connection Failed</h3>';
    echo '<div class="info-row"><span class="info-label">Attempted hosts:</span><span class="info-value">' . htmlspecialchars(implode(', ', $hostsToTry)) . '</span></div>';
    echo '<strong>Error:</strong> ' . htmlspecialchars($connectionError);
    echo '<br><br>';
    echo '<strong>Possible causes:</strong><ul style="margin: 10px 0; padding-left: 20px;">';
    echo '<li>MySQL user permissions are host-specific. The user may be granted access from a different hostname.</li>';
    echo '<li>Check: <code>SELECT user, host FROM mysql.user WHERE user=\'' . htmlspecialchars($dbConfig['user']) . '\';</code></li>';
    echo '<li>The Symfony application may connect via a different method (Unix socket vs TCP/IP).</li>';
    echo '<li>The password may contain special characters that need URL encoding in DATABASE_URL.</li>';
    echo '</ul>';
    echo '</div>';
}
?>

        <div class="info-box">
            <small>
                <strong>Note:</strong> This page reads configuration from environment variables first,
                falling back to <code>.env.local</code> if not set. Should only be used in development environments.
            </small>
        </div>
    </div>
</body>
</html>
