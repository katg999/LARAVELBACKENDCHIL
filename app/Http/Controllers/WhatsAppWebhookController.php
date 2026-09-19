<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Services\SmsService;
use App\Services\WhatsAppSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/** Inbound WhatsApp: Meta's verification handshake, and a reply with the patient's visits link. */
class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $expected = config('services.whatsapp.verify_token');

        if ($expected && $request->query('hub_mode') === 'subscribe'
            && hash_equals((string) $expected, (string) $request->query('hub_verify_token'))) {
            return response((string) $request->query('hub_challenge'), 200)->header('Content-Type', 'text/plain');
        }

        abort(403);
    }

    public function receive(Request $request, WhatsAppSender $whatsapp, SmsService $sms)
    {
        $secret = config('services.whatsapp.app_secret');
        $expected = $secret ? 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret) : null;
        if (!$expected || !hash_equals($expected, (string) $request->header('X-Hub-Signature-256'))) {
            abort(403);
        }

        foreach ($request->input('entry.0.changes.0.value.messages', []) as $message) {
            $phone = $sms->normalizeUgandanNumber($message['from'] ?? null);
            $patient = $phone ? $this->patientFor($phone) : null;

            $whatsapp->send($phone, $patient
                ? 'Hello ' . $patient->name . '. Your visits, payments and medicine: '
                    . URL::temporarySignedRoute('patient.visits', now()->addHours(24), ['patient' => $patient->id])
                : 'This number is not registered with a clinic on Easemed. Please ask your clinic to add it.');
        }

        return response('ok', 200);
    }

    private function patientFor(string $phone): ?Patient
    {
        $local = '0' . substr($phone, 4);
        $plain = substr($phone, 1);

        return Patient::whereIn('contact_number', [$phone, $plain, $local])
            ->orWhereIn('parent_contact', [$phone, $plain, $local])->first();
    }
}
