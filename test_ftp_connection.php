<?php

$sftpConfigPath = __DIR__ . '/.vscode/sftp.json';

if (!file_exists($sftpConfigPath)) {
    die("Error: .vscode/sftp.json not found.\n");
}

$config = json_decode(file_get_contents($sftpConfigPath), true);

if (!$config) {
    die("Error: Could not parse .vscode/sftp.json.\n");
}

echo "Attempting to connect to {$config['host']}...\n";

$conn_id = ftp_connect($config['host'], $config['port'] ?? 21);

if (!$conn_id) {
    die("Error: Could not connect to {$config['host']}.\n");
}

echo "Connected. Attempting to login as {$config['username']}...\n";

if (@ftp_login($conn_id, $config['username'], $config['password'])) {
    echo "Login successful!\n";
    
    // Turn passive mode on
    ftp_pasv($conn_id, true);

    echo "Current directory: " . ftp_pwd($conn_id) . "\n";
    
    echo "Listing files in current directory:\n";
    $files = ftp_nlist($conn_id, ".");
    
    if ($files === false) {
        echo "Error: Could not list files.\n";
    } else {
        foreach ($files as $file) {
            echo "- $file\n";
        }
    }
    
    // Attempt to go up a directory
    echo "\nAttempting to go up a directory (cd ..)...\n";
    if (ftp_chdir($conn_id, "..")) {
        echo "Successfully changed to parent directory.\n";
        echo "Current directory: " . ftp_pwd($conn_id) . "\n";
        echo "Listing files in parent directory:\n";
        $files = ftp_nlist($conn_id, ".");
        
        // List detailed file info
        echo "Detailed listing (rawlist):\n";
        $raw_files = ftp_rawlist($conn_id, ".");
        if ($raw_files) {
            foreach ($raw_files as $file) {
                echo "$file\n";
            }
        } else {
            echo "Could not get raw listing.\n";
        }

        // Attempt to write a UNIQUE verification file
        $timestamp = time();
        $test_file = "verify_{$timestamp}.txt";
        $fp = fopen('php://temp', 'r+');
        fwrite($fp, "Verification Code: $timestamp");
        rewind($fp);
        
        if (ftp_fput($conn_id, $test_file, $fp, FTP_ASCII)) {
            echo "\nSuccessfully uploaded $test_file\n";
            echo "PLEASE CHECK THIS URL IN BROWSER: http://educags-os.online/$test_file\n";
            
            // Don't delete it immediately so we can check it
            // ftp_delete($conn_id, $test_file); 
        } else {
            echo "Error: Could not write to FTP root.\n";
        }

    } else {
        echo "Could not change to parent directory. We might be chrooted.\n";
    }

} else {
    die("Error: Login failed.\n");
}

ftp_close($conn_id);
echo "\nConnection closed.\n";
