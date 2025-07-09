# Laravel Propeller CRM API Client

## Overview
A Laravel-based API client for the Propeller demo CRM API, implementing CLI commands (`crm:add-subscriber`, `crm:send-enquiry`) and a Vue.js frontend. Built with PHP 8.1+ and Laravel 10+, it provides a professional interface with clear feedback and robust error handling.

## Prerequisites
- PHP 8.1+
- Composer
- Laravel 10+
- Node.js and npm
- API bearer token for the Propeller CRM API

## Setup
1. Clone the repository and navigate to the project directory.
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```

3. Copy `.env.example` to `.env` and configure:
   ```
   CRM_API_URL=https://devtest-crm-api.standard.aws.prop.cm
   CRM_API_TOKEN=your-bearer-token-here
   ```

4. Compile frontend assets:
   ```bash
   npm run dev
   ```

5. Start the Laravel server:
   ```bash
   php artisan serve
