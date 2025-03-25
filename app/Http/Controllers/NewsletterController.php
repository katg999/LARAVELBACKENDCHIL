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
        'email' => 'required|email|unique:newsletter_subscribers,email',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    DB::beginTransaction();

    try {
        $subscriber = NewsletterSubscriber::create([
            'email' => $request->email,
            'verification_token' => Str::random(60),
        ]);

        // Queue the email instead of sending synchronously
        dispatch(function () use ($subscriber) {
            Mail::to($subscriber->email)
               ->send(new VerifyNewsletterSubscription($subscriber));
        })->afterResponse(); // Don't wait for send to complete

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Subscription successful!'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Service temporarily unavailable'
        ], 503);
    }
}
}
