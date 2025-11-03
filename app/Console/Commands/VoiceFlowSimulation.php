<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use App\Models\Doctor;
use App\Models\HealthFacility;

class VoiceFlowSimulation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'voiceflow:simulate
                            {--type= : User type (school, doctor, health-facility)}
                            {--email= : Email address to test}
                            {--action= : Action to perform (login, register)}
                            {--auto-verify : Automatically verify OTP after sending (login only)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate VoiceFlow authentication process (login/register) for testing purposes';

    /**
     * Base URL for API calls
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        // Priority 1: Use explicit environment variable if set
        if ($url = getenv('API_BASE_URL')) {
            $this->baseUrl = $url;
        }
        // Priority 2: Detect if running inside Docker container
        elseif (file_exists('/.dockerenv') || getenv('WEBROOT') === '/var/www/html/public') {
            // Running inside the Docker container - use internal port
            $this->baseUrl = 'http://localhost:80';
        }
        // Priority 3: Default to local development server
        else {
            $this->baseUrl = 'http://localhost:8000';
        }
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎤 VoiceFlow Login Simulation');
        $this->info('==============================');
        $this->info("📍 Using base URL: {$this->baseUrl}");
        $this->info('');

        // Check if server is running
        if (!$this->isServerRunning()) {
            $this->error('❌ Laravel server is not running on ' . $this->baseUrl);
            $suggestion = (getenv('WEBROOT') === '/var/www/html/public' || file_exists('/.dockerenv'))
                ? '💡 Make sure the Docker container web server is running'
                : '💡 Start the server with: php artisan serve --host=0.0.0.0 --port=8000';
            $this->info($suggestion);
            return 1;
        }

        $this->info('✅ Server is running');

        // Ask if user wants to register or login
        $action = $this->option('action') ?: $this->choice(
            'What would you like to do?',
            ['login', 'register'],
            0
        );

        if ($action === 'register') {
            return $this->handleRegistration();
        }

        // Continue with login flow
        return $this->handleLogin();
    }

    /**
     * Handle login simulation
     */
    protected function handleLogin()
    {
        // Get user type
        $userType = $this->option('type') ?: $this->choice(
            'Select user type to simulate:',
            ['school', 'doctor', 'health-facility'],
            0
        );

        // Get email
        $email = $this->option('email');
        
        if (!$email) {
            // Check if we can prompt (TTY available)
            if (!posix_isatty(STDIN)) {
                $this->error("❌ Email address is required");
                $this->info('');
                $this->error('⚠️  Non-interactive terminal detected!');
                $this->info('');
                $this->info('To run interactively in Docker, use the -it flags:');
                $this->info('  docker exec -it laravel_app php artisan voiceflow:simulate');
                $this->info('');
                $this->info('Or use docker-compose (recommended):');
                $this->info('  docker-compose exec app php artisan voiceflow:simulate');
                $this->info('');
                $this->info('Or provide the email option:');
                $this->info("  docker exec laravel_app php artisan voiceflow:simulate --type={$userType} --email=test@example.com --action=login");
                return 1;
            }
            
            $email = $this->ask('Enter email address:');
        }

        // Trim and check if email is empty or null
        $email = trim($email ?? '');
        
        if (empty($email)) {
            $this->error("❌ Email address is required");
            return 1;
        }

        if (!$this->validateEmail($email, $userType)) {
            $this->error("❌ Email '{$email}' is not associated with any {$userType}");
            return 1;
        }

        $this->info("📧 Testing {$userType} login for: {$email}");

        // Send OTP
        $this->info('📤 Sending OTP...');
        $otpResponse = $this->sendOtp($email);

        if (!$otpResponse['success']) {
            $this->error('❌ Failed to send OTP: ' . ($otpResponse['message'] ?? 'Unknown error'));
            return 1;
        }

        $this->info('✅ OTP sent successfully!');

        // Get OTP from database for testing
        $otpRecord = DB::table('otps')->where('email', $email)->first();

        if (!$otpRecord) {
            $this->error('❌ Could not retrieve OTP from database');
            return 1;
        }

        $otp = $otpRecord->code;
        $this->info("🔐 OTP Code: {$otp}");

        // Auto-verify if requested
        if ($this->option('auto-verify')) {
            $this->info('🔍 Auto-verifying OTP...');
            $verifyResponse = $this->verifyOtp($email, $otp, $userType);

            if ($verifyResponse['success']) {
                $this->info('✅ OTP verified successfully!');
                if (isset($verifyResponse['login_url'])) {
                    $this->info('🔗 Login URL: ' . $verifyResponse['login_url']);
                } else {
                    $this->info('🏠 Dashboard URL: N/A');
                }
            } else {
                $this->error('❌ OTP verification failed: ' . ($verifyResponse['message'] ?? 'Unknown error'));
            }
        } else {
            // Manual verification
            $this->info('🔍 To verify the OTP manually, you can:');
            $this->info('   1. Use --auto-verify flag next time');
            $this->info('   2. Or verify manually by calling the API:');
            $endpoint = match($userType) {
                'school' => '/api/voiceflow/verify-otp',
                'doctor' => '/api/voiceflow/verify-doctor-otp',
                'health-facility' => '/api/voiceflow/verify-health-facility-otp',
                default => '/api/voiceflow/verify-otp'
            };
            $this->info("      curl -X POST {$this->baseUrl}{$endpoint} -d 'email={$email}&otp={$otp}'");
            
            $enteredOtp = $this->ask('Enter OTP code to verify (or press Enter to skip):');
            
            if (!empty($enteredOtp)) {
                $verifyResponse = $this->verifyOtp($email, $enteredOtp, $userType);

                if ($verifyResponse['success']) {
                    $this->info('✅ OTP verified successfully!');
                    if (isset($verifyResponse['login_url'])) {
                        $this->info('🔗 Login URL: ' . $verifyResponse['login_url']);
                    } else {
                        $this->info('🏠 Dashboard URL: N/A');
                    }
                } else {
                    $this->error('❌ OTP verification failed: ' . ($verifyResponse['message'] ?? 'Unknown error'));
                }
            } else {
                $this->info('⏭️  OTP verification skipped.');
            }
        }

        $this->info('🎉 VoiceFlow login simulation completed!');
        return 0;
    }

    /**
     * Handle registration simulation
     */
    protected function handleRegistration()
    {
        $this->info('📝 Registration Simulation');
        $this->info('========================');

        // Get user type
        $userType = $this->choice(
            'Select user type to register:',
            ['school', 'doctor', 'health-facility'],
            0
        );

        $this->info("Registering as: {$userType}");

        // Collect registration data based on user type
        $registrationData = $this->collectRegistrationData($userType);

        // Simulate registration API call
        $this->info('📤 Sending registration request...');
        $registerResponse = $this->registerUser($userType, $registrationData);

        if (isset($registerResponse['success']) && $registerResponse['success'] === false) {
            $this->error('❌ Registration failed: ' . ($registerResponse['message'] ?? 'Unknown error'));
            return 1;
        }

        $this->info('✅ Registration successful!');
        if (isset($registerResponse['message'])) {
            $this->info('📧 ' . $registerResponse['message']);
        }

        $this->info('🎉 VoiceFlow registration simulation completed!');
        return 0;
    }

    /**
     * Collect registration data for the user type
     */
    protected function collectRegistrationData(string $userType): array
    {
        $data = [];

        switch ($userType) {
            case 'school':
                $data['name'] = $this->ask('School name:');
                $data['email'] = $this->ask('School email:');
                $data['contact'] = $this->ask('School contact:');
                break;

            case 'doctor':
                $data['name'] = $this->ask('Doctor name:');
                $data['email'] = $this->ask('Doctor email:');
                $data['contact'] = $this->ask('Doctor contact:');
                $data['specialization'] = $this->ask('Specialization:');
                break;

            case 'health-facility':
                $data['name'] = $this->ask('Health facility name:');
                $data['email'] = $this->ask('Health facility email:');
                $data['contact'] = $this->ask('Health facility contact:');
                break;
        }

        return $data;
    }

    /**
     * Register user via API
     */
    protected function registerUser(string $userType, array $data): array
    {
        try {
            $endpoint = match($userType) {
                'school' => '/api/register-school',
                'doctor' => '/api/register-doctor',
                'health-facility' => '/api/register-health-facility',
                default => '/api/register-school'
            };

            $response = Http::timeout(10)->post($this->baseUrl . $endpoint, $data);

            if ($response->successful()) {
                $json = $response->json();
                return $json ?: ['success' => false, 'message' => 'Invalid JSON response'];
            } else {
                return [
                    'success' => false,
                    'message' => 'HTTP ' . $response->status() . ': ' . $response->body()
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Request failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if Laravel server is running
     */
    protected function isServerRunning(): bool
    {
        try {
            // Try the root endpoint - accept redirects as indication server is running
            $response = Http::timeout(5)->get($this->baseUrl);
            return $response->status() >= 200 && $response->status() < 400;
        } catch (\Exception $e) {
            // Try alternative health check
            try {
                $response = Http::timeout(5)->get($this->baseUrl . '/api/health');
                return $response->status() >= 200 && $response->status() < 400;
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * Validate email exists for the given user type
     */
    protected function validateEmail(string $email, string $userType): bool
    {
        switch ($userType) {
            case 'school':
                return School::where('email', $email)->exists();
            case 'doctor':
                return Doctor::where('email', $email)->exists();
            case 'health-facility':
                return HealthFacility::where('email', $email)->exists();
            default:
                return false;
        }
    }

    /**
     * Send OTP via API
     */
    protected function sendOtp(string $email): array
    {
        try {
            $response = Http::timeout(10)->post($this->baseUrl . '/api/voiceflow/send-login-otp', [
                'email' => $email
            ]);

            return $response->json();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Request failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify OTP via API
     */
    protected function verifyOtp(string $email, string $otp, string $userType): array
    {
        try {
            $endpoint = match($userType) {
                'school' => '/api/voiceflow/verify-otp',
                'doctor' => '/api/voiceflow/verify-doctor-otp',
                'health-facility' => '/api/voiceflow/verify-health-facility-otp',
                default => '/api/voiceflow/verify-otp'
            };

            $response = Http::timeout(10)->post($this->baseUrl . $endpoint, [
                'email' => $email,
                'otp' => $otp
            ]);

            if ($response->successful()) {
                $json = $response->json();
                if (!$json) {
                    return ['success' => false, 'message' => 'Invalid JSON response'];
                }
                // Debug: log the response
                $this->info("Debug: Response for {$userType}: " . json_encode($json));
                return $json;
            } else {
                $this->error("Debug: HTTP error for {$userType}: " . $response->status() . ' - ' . $response->body());
                return [
                    'success' => false,
                    'message' => 'HTTP ' . $response->status() . ': ' . $response->body()
                ];
            }
        } catch (\Exception $e) {
            $this->error("Debug: Exception for {$userType}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Request failed: ' . $e->getMessage()
            ];
        }
    }
}
