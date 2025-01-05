<?php
// server.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'setup_virtual_domain':
                setupVirtualDomain($data['data']);
                break;
            case 'restart_services':
                restartServices();
                break;
            case 'stop_services':
                stopServices();
                break;
            case 'check_services_status':
                checkServicesStatus();
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No action specified']);
    }
}

function restartServices()
{
    // Restart Apache and MySQL, capturing the exit status codes
    $apacheStatus = null;
    $mysqlStatus = null;

    // Execute the restart commands and capture the exit status codes
    exec('sudo systemctl restart apache2', $apacheOutput, $apacheStatus);
    exec('sudo systemctl restart mysql', $mysqlOutput, $mysqlStatus);

    // Check if both services restarted successfully
    if ($apacheStatus === 0 && $mysqlStatus === 0) {
        echo json_encode(['success' => true, 'message' => 'Apache and MySQL have been restarted successfully.']);
    } else {
        // If either service fails to restart, return an error message
        echo json_encode(['success' => false, 'message' => 'Failed to restart Apache or MySQL.']);
    }
}



function stopServices()
{
    exec('sudo systemctl stop apache2');
    exec('sudo systemctl stop mysql');
    echo json_encode(['success' => true]);
}

function setupVirtualDomain($data)
{
    $domain = escapeshellarg($data['domain']);
    $docRoot = escapeshellarg($data['doc_root']);

    // Check if the domain already exists
    $existing = shell_exec("grep '$domain' /etc/hosts");
    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'Domain already exists in /etc/hosts']);
        return;
    }

    // Add domain to /etc/hosts
    shell_exec("echo '127.0.0.1 $domain' | sudo tee -a /etc/hosts");

    // Create document root
    shell_exec("sudo mkdir -p $docRoot");
    shell_exec("echo '<?php phpinfo(); ?>' | sudo tee $docRoot/index.php");

    // Create virtual host config
    $vhostConfig = "
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    ServerName $domain
    DocumentRoot $docRoot

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>";

    file_put_contents("/etc/apache2/sites-available/$domain.conf", $vhostConfig);

    // Enable site and reload Apache
    shell_exec("sudo a2ensite $domain.conf");
    shell_exec('sudo systemctl reload apache2');

    echo json_encode(['success' => true]);
}

function checkServicesStatus()
{
    // Check Apache and MySQL status using systemctl
    $apacheStatus = shell_exec('systemctl is-active apache2');
    $mysqlStatus = shell_exec('systemctl is-active mysql');

    // Format the status as 'active' or 'inactive'
    $apacheStatus = trim($apacheStatus) === 'active' ? 'Apache is running' : 'Apache is not running';
    $mysqlStatus = trim($mysqlStatus) === 'active' ? 'MySQL is running' : 'MySQL is not running';

    // Return the status as JSON
    echo json_encode([
        'apache_status' => $apacheStatus,
        'mysql_status' => $mysqlStatus,
    ]);
}
