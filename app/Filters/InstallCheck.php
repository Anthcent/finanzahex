<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class InstallCheck implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $path = trim($request->getUri()->getPath(), '/');
        if ($path === 'health' || $path === 'debug-check') {
            return;
        }

        $lockFile = WRITEPATH . 'installed.lock';

        if (!file_exists($lockFile)) {
            try {
                $db = \Config\Database::connect();
                // Check if connection is successful
                $db->connect(); 
            } catch (\Throwable $e) {
                // DB not ready or credentials wrong
                // Allow the request to proceed so standard CI4 DB error shows, or show custom error?
                // For now, let it fail naturally if DB is issue, or return helpful message.
                return Services::response()->setBody("Database connection failed. Please check .env configuration.");
            }

            try {
                $migrate = Services::migrations();
                
                // Run all migrations
                $migrate->latest();

                // Create lock file
                file_put_contents($lockFile, 'Installed on ' . date('Y-m-d H:i:s'));
                
                // Redirect to self to clear any post-migration state if needed, or just continue
                // But better to just continue the request.
                // Optionally show a "System Installed" message once?
                // For now, allow request to proceed silently after install.
                
            } catch (\Throwable $e) {
                return Services::response()->setBody("Installation failed: " . $e->getMessage());
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
