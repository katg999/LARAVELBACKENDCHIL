<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Conference;
use App\Models\ConferenceParticipant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ConferenceController extends Controller
{
    /**
     * Create a conference room for an appointment
     */
    public function create(Request $request, Appointment $appointment): JsonResponse
    {
        // Check if user can create conference for this appointment
        if (!$this->canManageConference($appointment)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to create conference for this appointment'
            ], 403);
        }

        // Check if conference already exists
        if ($appointment->conference) {
            return response()->json([
                'success' => false,
                'message' => 'Conference already exists for this appointment'
            ], 400);
        }

        try {
            $conference = Conference::createForAppointment($appointment);

            // Add participants
            $this->addParticipantsToConference($conference, $appointment);

            return response()->json([
                'success' => true,
                'conference' => $conference,
                'message' => 'Conference created successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create conference: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create conference'
            ], 500);
        }
    }

    /**
     * Join a conference room
     */
    public function join(Request $request, Conference $conference)
    {
        // Check if user can join this conference
        if (!$conference->canJoin(Auth::user())) {
            abort(403, 'You are not authorized to join this conference');
        }

        // Update participant status
        $participant = ConferenceParticipant::where('conference_id', $conference->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($participant) {
            $participant->join();
        }

        // If this is the host starting the conference
        if ($conference->isHost(Auth::user()) && !$conference->isActive()) {
            $conference->start();
        }

        // Generate Agora token (we'll implement this later)
        $token = $this->generateAgoraToken($conference, Auth::id());

        return view('conferences.room', compact('conference', 'token'));
    }

    /**
     * Leave a conference room
     */
    public function leave(Request $request, Conference $conference): JsonResponse
    {
        $participant = ConferenceParticipant::where('conference_id', $conference->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($participant) {
            $participant->leave();

            // If host is leaving, end the conference
            if ($participant->isHost()) {
                $conference->end();
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * End a conference (host only)
     */
    public function end(Request $request, Conference $conference): JsonResponse
    {
        if (!$conference->isHost(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'Only the host can end the conference'
            ], 403);
        }

        $conference->end();

        return response()->json(['success' => true]);
    }

    /**
     * Kick a participant from conference (host only)
     */
    public function kickParticipant(Request $request, Conference $conference, ConferenceParticipant $participant): JsonResponse
    {
        if (!$conference->isHost(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'Only the host can kick participants'
            ], 403);
        }

        if ($participant->conference_id !== $conference->id) {
            return response()->json([
                'success' => false,
                'message' => 'Participant not in this conference'
            ], 400);
        }

        $participant->kick();

        return response()->json(['success' => true]);
    }

    /**
     * Get conference status
     */
    public function status(Request $request, Conference $conference): JsonResponse
    {
        if (!$conference->canJoin(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'conference' => [
                'id' => $conference->id,
                'status' => $conference->status,
                'room_name' => $conference->room_name,
                'started_at' => $conference->started_at,
                'participants' => $conference->participants->map(function ($participant) {
                    return [
                        'id' => $participant->id,
                        'user_id' => $participant->user_id,
                        'name' => $participant->user->name,
                        'role' => $participant->role,
                        'status' => $participant->status,
                        'joined_at' => $participant->joined_at,
                    ];
                }),
            ]
        ]);
    }

    /**
     * Check if user can manage conference for appointment
     */
    private function canManageConference(Appointment $appointment): bool
    {
        $user = Auth::user();

        // Doctor can manage their own appointments
        if ($appointment->doctor_id === $user->id) {
            return true;
        }

        // School admin can manage school appointments
        if ($user->is_admin && $appointment->school_id === $user->school_id) {
            return true;
        }

        // Health facility admin can manage facility appointments
        if ($user->is_admin && $appointment->health_facility_id === $user->health_facility_id) {
            return true;
        }

        return false;
    }

    /**
     * Add participants to conference
     */
    private function addParticipantsToConference(Conference $conference, Appointment $appointment): void
    {
        // Add doctor as host
        ConferenceParticipant::create([
            'conference_id' => $conference->id,
            'user_id' => $appointment->doctor_id,
            'role' => 'host',
            'status' => 'invited',
        ]);

        // Add patient as participant (if exists)
        if ($appointment->patient) {
            ConferenceParticipant::create([
                'conference_id' => $conference->id,
                'user_id' => $appointment->patient_id,
                'role' => 'participant',
                'status' => 'invited',
            ]);
        }
    }

    /**
     * Generate Agora token for user (placeholder - will implement with Agora SDK)
     */
    private function generateAgoraToken(Conference $conference, int $userId): string
    {
        // TODO: Implement Agora token generation
        // For now, return a placeholder
        return 'placeholder_token_' . $conference->id . '_' . $userId;
    }
}
