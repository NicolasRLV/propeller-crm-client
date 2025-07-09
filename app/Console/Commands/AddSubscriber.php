<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class AddSubscriber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:add-subscriber
        {--email= : Subscriber email (required)}
        {--first-name= : Subscriber first name (optional)}
        {--last-name= : Subscriber last name (optional)}
        {--dob= : Date of birth (YYYY-MM-DD, required, must be at least 18 years old)}
        {--marketing-consent= : Marketing consent (true/false, required)}
        {--lists=* : List IDs (optional, comma-separated)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new subscriber in the Propeller CRM';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Prepare data from command options
        $data = [
            'emailAddress' => $this->option('email'),
            'marketingConsent' => filter_var($this->option('marketing-consent'), FILTER_VALIDATE_BOOLEAN),
            'firstName' => $this->option('first-name'),
            'lastName' => $this->option('last-name'),
            'dateOfBirth' => $this->option('dob'),
            'lists' => is_array($this->option('lists')) ? $this->option('lists') : ($this->option('lists') ? explode(',', $this->option('lists')) : []), // Handle multiple list IDs, converting comma-separated string to array if necessary
        ];

        // Define validation rules for the subscriber data
        $validator = Validator::make($data, [
            'emailAddress' => 'required|email|max:255',
            'marketingConsent' => 'required|boolean',
            'firstName' => 'nullable|string|max:255',
            'lastName' => 'nullable|string|max:255',
            'dateOfBirth' => 'required|date_format:Y-m-d|before_or_equal:' . now()->subYears(18)->format('Y-m-d'), // Date of birth must be in Y-m-d format and at least 18
            'lists' => 'nullable|array',
            'lists.*' => 'string', // Each item in the lists array must be a string
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->error("Error: {$field}: {$message}");
                }
            }
            return 1;
        }

        // If marketing consent is false, ensure no lists are sent to the API
        if (!$data['marketingConsent']) {
            $data['lists'] = [];
        }

        try {
            // Send POST request to the CRM API with token authentication
            $response = Http::withToken(config('services.crm_api.token'))
                ->post(config('services.crm_api.url') . '/api/subscriber', array_filter($data));

            if ($response->successful()) {
                $subscriber = $response->json()['subscriber'];
                $name = trim("{$subscriber['firstName']} {$subscriber['lastName']}") ?: 'Subscriber';
                $this->info("{$name} created successfully. ID: {$subscriber['id']}");
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
