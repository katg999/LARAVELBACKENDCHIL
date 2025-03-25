<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NewsletterSubscriber;
use App\Mail\VerifyNewsletterSubscription;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255|unique:newsletter_subscribers,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $verificationToken = Str::random(60);

        $subscriber = NewsletterSubscriber::create([
            'email' => $request->email,
            'verification_token' => $verificationToken,
        ]);

        try {
            Mail::to($request->email)->send(new VerifyNewsletterSubscription($subscriber));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification email. Please try again later.'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you for subscribing! Please check your email to verify your subscription.',
        ]);
    }

    public function verify($token)
    {
        $subscriber = NewsletterSubscriber::where('verification_token', $token)->first();

        if (!$subscriber) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification token.'
            ], 404);
        }

        if ($subscriber->verified_at) {
            return response()->json([
                'success' => true,
                'message' => 'Email already verified.'
            ]);
        }

        $subscriber->update([
            'verified_at' => now(),
            'is_active' => true,
            'verification_token' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email successfully verified. Thank you for subscribing to our newsletter!'
        ]);
    }
}
