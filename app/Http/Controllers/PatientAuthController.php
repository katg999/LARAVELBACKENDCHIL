<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\PatientLoginCode;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Patient login by phone number and one-time code, giving access to their own
 * records (visit notes, prescriptions, lab results). Nothing is revealed about
 * whether a phone number is registered.
 */
class PatientAuthController extends Controller
{
    private const CODE_TTL_MIN = 10;
    private const MAX_ATTEMPTS = 5;

    public function __construct(private SmsService $sms)
    {
    }

    public function showLogin()
    {
        return view('patient.login');
    }

    public function requestCode(Request $request)
    {
        $data = $request->validate(['phone' => 'required|string|max:20']);
        $phone = $this->sms->normalizeUgandanNumber($data['phone']);

        $generic = redirect()->route('patient.login')
            ->with('status', 'If that number is registered, we sent a 6-digit code by SMS.')
            ->with('phone', $data['phone']);

        if (!$phone) {
            return $generic;
        }

        // At most 3 codes per number per hour, and 10 per IP per hour
        $numberKey = 'patient-code:' . $phone;
        $ipKey = 'patient-code-ip:' . $request->ip();
        if (RateLimiter::tooManyAttempts($numberKey, 3) || RateLimiter::tooManyAttempts($ipKey, 10)) {
            return $generic;
        }
        RateLimiter::hit($numberKey, 3600);
        RateLimiter::hit($ipKey, 3600);

        if ($this->patientsFor($phone)->isNotEmpty()) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            PatientLoginCode::where('phone', $phone)->whereNull('used_at')->delete();
            PatientLoginCode::create([
                'phone' => $phone,
                'code_hash' => $this->hash($code),
                'expires_at' => now()->addMinutes(self::CODE_TTL_MIN),
            ]);
            $this->sms->send($phone, "Your Easemed code is {$code}. It expires in " . self::CODE_TTL_MIN . ' minutes. Do not share it.');
        }

        return $generic;
    }

    public function verify(Request $request)
    {
        $data = $request->validate(['phone' => 'required|string|max:20', 'code' => 'required|digits:6']);
        $phone = $this->sms->normalizeUgandanNumber($data['phone']);

        $fail = redirect()->route('patient.login')->withErrors(['code' => 'That code is not valid or has expired.'])
            ->with('phone', $data['phone']);

        $record = $phone
            ? PatientLoginCode::where('phone', $phone)->whereNull('used_at')->where('expires_at', '>', now())->latest()->first()
            : null;

        if (!$record || $record->attempts >= self::MAX_ATTEMPTS) {
            return $fail;
        }

        $record->increment('attempts');

        if (!hash_equals($record->code_hash, $this->hash($data['code']))) {
            return $fail;
        }

        $record->update(['used_at' => now()]);
        $ids = $this->patientsFor($phone)->pluck('id')->all();

        $request->session()->regenerate();
        $request->session()->put('patient_auth', ['phone' => $phone, 'patient_ids' => $ids]);

        foreach ($ids as $id) {
            AuditLog::record(['type' => 'patient', 'id' => $id], 'patient.login');
        }

        return redirect()->route('patient.records');
    }

    public function records(Request $request)
    {
        $auth = $request->session()->get('patient_auth');
        if (!$auth) {
            return redirect()->route('patient.login');
        }

        $patients = Patient::whereIn('id', $auth['patient_ids'])
            ->with([
                'medicalHistories' => fn ($q) => $q->with('doctor')->latest('recorded_date'),
                'prescriptions.items',
                'labTests',
                'appointments' => fn ($q) => $q->with('doctor')->orderByDesc('appointment_time')->limit(20),
                'policies.insurer',
            ])->get();

        foreach ($patients as $p) {
            AuditLog::record(['type' => 'patient', 'id' => $p->id], 'patient.records.viewed', $p);
        }

        return view('patient.records', ['patients' => $patients, 'insurers' => \App\Models\Insurer::where('active', true)->orderBy('name')->get(['id', 'name'])]);
    }

    public function logout(Request $request)
    {
        $request->session()->forget('patient_auth');
        $request->session()->regenerateToken();

        return redirect()->route('patient.login')->with('status', 'You are signed out.');
    }

    private function patientsFor(string $phone)
    {
        // Match either the patient's own number or a parent's number, in any local format.
        $local = '0' . substr($phone, 4);          // +256772123456 -> 0772123456
        $plain = substr($phone, 1);                // 256772123456

        return Patient::query()
            ->whereIn('contact_number', [$phone, $plain, $local])
            ->orWhereIn('parent_contact', [$phone, $plain, $local])
            ->get();
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}
