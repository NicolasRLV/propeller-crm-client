<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GetLists extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:get-lists';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get all subscriber lists from the Propeller CRM';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            // Send GET request to the CRM API to fetch all lists with bearer token authentication
            $response = Http::withToken(config('services.crm_api.token'))
                ->get(config('services.crm_api.url') . '/api/lists');

            if ($response->successful()) {
                $lists = $response->json()['lists'];
                if (empty($lists)) {
                    $this->info('No lists found.');
                } else {
                    $this->info('Available Subscriber Lists:');
                    // Display each list's name and ID
                    foreach ($lists as $list) {
                        $this->info("  - Name: {$list['name']}, ID: {$list['id']}");
                    }
                }
            } else {
                // Handle API errors
                $jsonResponse = $response->json();
                if (is_null($jsonResponse)) {
                    logger()->error('API Error: Non-JSON response or empty body', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    $this->error("Error: The API returned an unexpected response (Status: {$response->status()}).");
                } else {
                    logger()->error('API Error Response:', ['response' => $jsonResponse]);
                    $this->error($this->formatError($jsonResponse));
                }
            }
        } catch (\Exception $e) {
            $this->error("Error: Failed to connect to the server: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    /**
     * Formats API error responses into simplified messages.
     *
     * @param array $error The error response from the API.
     * @return string The formatted error message.
     */
    protected function formatError($error)
    {
        $message = $error['message'] ?? 'An unknown error occurred.';
        $errorCode = $error['error'] ?? 'UNKNOWN';

        return match ($errorCode) {
            'VALIDATION_ERROR' => 'Error: ' . implode('; ', array_map(
                fn($field) => "{$field['field']}: {$field['message']}",
                $error['fields'] ?? []
            )),
            'UNAUTHORIZED' => 'Error: Invalid access token. Please verify your CRM_API_TOKEN.',
            'ACCESS_DENIED' => 'Error: Access denied. Please check your permissions.',
            'NOT_FOUND' => 'Error: Resource not found. Please verify your input.',
            default => "Error: {$message}",
        };
    }
}
