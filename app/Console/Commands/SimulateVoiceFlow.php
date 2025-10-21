<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\HealthFacility;
use App\Models\School;
use Carbon\Carbon;

class SimulateVoiceFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'voice:simulate {--flow=appointment : The type of voice flow to simulate (appointment, consultation, emergency)} {--interactive : Run in interactive mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate voice interaction flows for healthcare system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $flow = $this->option('flow');
        $interactive = $this->option('interactive');

        switch ($flow) {
            case 'appointment':
                $this->simulateAppointmentBooking($interactive);
                break;
            case 'consultation':
                $this->simulateConsultationFlow($interactive);
                break;
            case 'emergency':
                $this->simulateEmergencyResponse($interactive);
                break;
            default:
                $this->error("Unknown flow type: {$flow}");
                $this->info("Available flows: appointment, consultation, emergency");
                return 1;
        }

        return 0;
    }

    /**
     * Simulate appointment booking voice flow
     */
    private function simulateAppointmentBooking($interactive = false)
    {
        $this->info("🎤 Starting Appointment Booking Voice Flow Simulation");
        $this->line("─" . str_repeat("─", 60));

        // Step 1: Welcome and identify caller
        $this->voiceOutput("Hello! Welcome to CHIL Healthcare System. How can I help you today?");
        $this->voiceOutput("Are you calling as a patient, school representative, or health facility staff?");

        if ($interactive) {
            $callerType = $this->choice('Select caller type:', ['patient', 'school', 'health_facility'], 0);
        } else {
            $callerType = 'patient'; // Default for simulation
            $this->voiceInput("Patient");
        }

        // Step 2: Authentication/Identification
        switch ($callerType) {
            case 'patient':
                $this->handlePatientFlow($interactive);
                break;
            case 'school':
                $this->handleSchoolFlow($interactive);
                break;
            case 'health_facility':
                $this->handleHealthFacilityFlow($interactive);
                break;
        }
    }

    /**
     * Handle patient appointment booking flow
     */
    private function handlePatientFlow($interactive = false)
    {
        $this->voiceOutput("Thank you. I'll help you book an appointment.");
        $this->voiceOutput("First, could you please provide your patient ID or phone number for verification?");

        // Simulate patient lookup
        $patients = Patient::take(3)->get();
        if ($patients->isEmpty()) {
            $this->voiceOutput("I apologize, but I need to set up some sample data first. Please run the seeders.");
            return;
        }

        $patient = $patients->first();
        $this->voiceInput("Patient ID: {$patient->patient_id}");

        $this->voiceOutput("Thank you. I found your record: {$patient->name}.");
        $this->voiceOutput("What type of appointment would you like to book?");

        $appointmentTypes = ['General Consultation', 'Follow-up Visit', 'Emergency Consultation', 'Lab Test'];
        if ($interactive) {
            $appointmentType = $this->choice('Select appointment type:', $appointmentTypes, 0);
        } else {
            $appointmentType = $appointmentTypes[0];
            $this->voiceInput($appointmentType);
        }

        // Find available doctors
        $doctors = Doctor::where('is_online', true)->take(3)->get();
        if ($doctors->isEmpty()) {
            $this->voiceOutput("I apologize, but no doctors are currently available. Please try again later.");
            return;
        }

        $this->voiceOutput("Here are our available doctors:");
        foreach ($doctors as $index => $doctor) {
        $this->voiceOutput(($index + 1) . ". Dr. {$doctor->name} - " . ($doctor->specialization ?? 'General Practice'));
        }

        if ($interactive) {
            $doctorChoice = $this->choice('Select a doctor:', $doctors->pluck('name')->toArray(), 0);
            $selectedDoctor = $doctors->where('name', $doctorChoice)->first();
        } else {
            $selectedDoctor = $doctors->first();
            $this->voiceInput("Dr. {$selectedDoctor->name}");
        }

        // Check availability
        $this->voiceOutput("Let me check Dr. {$selectedDoctor->name}'s availability.");
        $availableSlots = $this->getAvailableSlots($selectedDoctor);

        if (empty($availableSlots)) {
            $this->voiceOutput("I'm sorry, Dr. {$selectedDoctor->name} has no available slots today. Would you like to:");
            $options = ['Try another doctor', 'Schedule for tomorrow', 'Call back later'];
            if ($interactive) {
                $choice = $this->choice('Select option:', $options, 0);
            } else {
                $choice = $options[0];
                $this->voiceInput($choice);
            }
            return;
        }

        $this->voiceOutput("Available time slots:");
        foreach ($availableSlots as $index => $slot) {
            $this->voiceOutput(($index + 1) . ". {$slot->format('l, F j, Y g:i A')}");
        }

        if ($interactive) {
            $slotChoice = $this->choice('Select a time slot:', array_map(function($slot) {
                return $slot->format('l, F j, Y g:i A');
            }, $availableSlots), 0);
            $selectedSlot = $availableSlots[array_search($slotChoice, array_map(function($slot) {
                return $slot->format('l, F j, Y g:i A');
            }, $availableSlots))];
        } else {
            $selectedSlot = $availableSlots[0];
            $this->voiceInput($selectedSlot->format('l, F j, Y g:i A'));
        }

        // Confirm appointment
        $this->voiceOutput("Perfect! I'll book your {$appointmentType} with Dr. {$selectedDoctor->name} on {$selectedSlot->format('l, F j, Y \\a\\t g:i A')}.");

        if ($interactive) {
            if (!$this->confirm('Do you want to confirm this appointment?', true)) {
                $this->voiceOutput("Appointment booking cancelled. Thank you for using CHIL Healthcare System.");
                return;
            }
        } else {
            $this->voiceInput("Yes, confirm");
        }

        // Create appointment
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $selectedDoctor->id,
            'appointment_date' => $selectedSlot,
            'status' => 'scheduled',
            'notes' => "Booked via voice system - {$appointmentType}",
            'duration_id' => 1, // Default duration
        ]);

        $this->voiceOutput("✅ Appointment confirmed!");
        $this->voiceOutput("Appointment ID: {$appointment->id}");
        $this->voiceOutput("Date & Time: {$selectedSlot->format('l, F j, Y \\a\\t g:i A')}");
        $this->voiceOutput("Doctor: Dr. {$selectedDoctor->name}");
        $this->voiceOutput("A confirmation SMS has been sent to your registered phone number.");
        $this->voiceOutput("Thank you for using CHIL Healthcare System. Goodbye!");
    }

    /**
     * Handle school appointment booking flow
     */
    private function handleSchoolFlow($interactive = false)
    {
        $this->voiceOutput("Thank you for calling from a school. I'll help you book appointments for your students.");

        $schools = School::take(3)->get();
        if ($schools->isEmpty()) {
            $this->voiceOutput("I apologize, but I need to set up some sample school data first.");
            return;
        }

        $school = $schools->first();
        $this->voiceOutput("I can see you're calling from {$school->name}. Is that correct?");

        if ($interactive) {
            if (!$this->confirm('Is this the correct school?', true)) {
                $this->voiceOutput("Please contact your school administrator for the correct contact information.");
                return;
            }
        } else {
            $this->voiceInput("Yes");
        }

        $this->voiceOutput("Great! How many appointments would you like to book today?");

        if ($interactive) {
            $count = $this->ask('Number of appointments:', 1);
        } else {
            $count = 1;
            $this->voiceInput("One appointment");
        }

        $this->voiceOutput("I'll help you book {$count} appointment(s). Let's start with the first one.");
        $this->handlePatientFlow($interactive); // Reuse patient flow logic
    }

    /**
     * Handle health facility appointment booking flow
     */
    private function handleHealthFacilityFlow($interactive = false)
    {
        $this->voiceOutput("Thank you for calling from a health facility. I'll help you manage appointments.");

        $facilities = HealthFacility::take(3)->get();
        if ($facilities->isEmpty()) {
            $this->voiceOutput("I apologize, but I need to set up some sample health facility data first.");
            return;
        }

        $facility = $facilities->first();
        $this->voiceOutput("I can see you're calling from {$facility->name}. Is that correct?");

        if ($interactive) {
            if (!$this->confirm('Is this the correct facility?', true)) {
                $this->voiceOutput("Please contact your facility administrator.");
                return;
            }
        } else {
            $this->voiceInput("Yes");
        }

        $this->voiceOutput("What would you like to do?");
        $options = ['Book new appointment', 'Check existing appointments', 'Cancel appointment'];

        if ($interactive) {
            $action = $this->choice('Select action:', $options, 0);
        } else {
            $action = $options[0];
            $this->voiceInput($action);
        }

        switch ($action) {
            case 'Book new appointment':
                $this->handlePatientFlow($interactive);
                break;
            case 'Check existing appointments':
                $this->checkExistingAppointments($facility);
                break;
            case 'Cancel appointment':
                $this->cancelAppointment($facility, $interactive);
                break;
        }
    }

    /**
     * Simulate consultation flow
     */
    private function simulateConsultationFlow($interactive = false)
    {
        $this->info("🎤 Starting Consultation Voice Flow Simulation");
        $this->line("─" . str_repeat("─", 60));

        $this->voiceOutput("Welcome to CHIL Telemedicine Consultation Service.");
        $this->voiceOutput("Please hold while I connect you to your doctor.");

        // Simulate connection delay
        $this->voiceOutput("...connecting...");
        sleep(1);

        $doctors = Doctor::where('is_online', true)->take(1)->get();
        if ($doctors->isEmpty()) {
            $this->voiceOutput("I'm sorry, no doctors are currently available for consultation. Please try again later.");
            return;
        }

        $doctor = $doctors->first();
        $this->voiceOutput("Hello, this is Dr. {$doctor->name}. How can I help you today?");

        $symptoms = ['Fever', 'Headache', 'Cough', 'Sore throat', 'Fatigue', 'Other'];
        if ($interactive) {
            $symptom = $this->choice('Select your main symptom:', $symptoms, 0);
        } else {
            $symptom = $symptoms[0];
            $this->voiceInput($symptom);
        }

        $this->voiceOutput("I understand you're experiencing {$symptom}. Can you tell me more about when this started and how severe it is?");

        if ($interactive) {
            $description = $this->ask('Describe your symptoms:');
        } else {
            $description = "Started yesterday, mild to moderate";
            $this->voiceInput($description);
        }

        $this->voiceOutput("Thank you for that information. Based on what you've described, I recommend the following:");

        // Simulate diagnosis and recommendations
        $recommendations = [
            "Rest and hydrate well",
            "Take over-the-counter medication for symptom relief",
            "Monitor your temperature",
            "Contact us if symptoms worsen",
            "Schedule a follow-up appointment if needed"
        ];

        foreach ($recommendations as $rec) {
            $this->voiceOutput("• {$rec}");
        }

        $this->voiceOutput("Do you have any questions about these recommendations?");

        if ($interactive) {
            if ($this->confirm('Do you have questions?', false)) {
                $question = $this->ask('What is your question?');
                $this->voiceOutput("That's a good question. {$question} - I recommend consulting with your primary care physician for personalized advice.");
            }
        } else {
            $this->voiceInput("No questions");
        }

        $this->voiceOutput("Thank you for using CHIL Telemedicine. Take care and feel better soon!");
    }

    /**
     * Simulate emergency response flow
     */
    private function simulateEmergencyResponse($interactive = false)
    {
        $this->info("🚨 Starting Emergency Response Voice Flow Simulation");
        $this->line("─" . str_repeat("─", 60));

        $this->voiceOutput("EMERGENCY: CHIL Healthcare Emergency Response Line");
        $this->voiceOutput("If this is a life-threatening emergency, please hang up and dial your local emergency services immediately.");

        $this->voiceOutput("What type of emergency are you experiencing?");

        $emergencies = [
            'Medical Emergency (chest pain, difficulty breathing, severe bleeding)',
            'Mental Health Crisis',
            'Medication Emergency',
            'Facility Emergency',
            'Other'
        ];

        if ($interactive) {
            $emergencyType = $this->choice('Select emergency type:', $emergencies, 0);
        } else {
            $emergencyType = $emergencies[0];
            $this->voiceInput($emergencyType);
        }

        $this->voiceOutput("I understand this is a {$emergencyType}. Please provide your location and current situation briefly.");

        if ($interactive) {
            $location = $this->ask('Your location:');
            $situation = $this->ask('Describe the situation:');
        } else {
            $location = "Downtown Health Facility";
            $situation = "Patient experiencing chest pain";
            $this->voiceInput("Location: {$location}");
            $this->voiceInput("Situation: {$situation}");
        }

        $this->voiceOutput("Thank you. I'm dispatching emergency services to {$location}.");
        $this->voiceOutput("Please stay on the line. Help is on the way.");

        // Simulate emergency response
        $this->voiceOutput("Emergency services have been notified. ETA: 5-7 minutes.");
        $this->voiceOutput("Please follow these instructions while waiting:");

        $instructions = [
            "Stay calm and try to remain still",
            "If possible, have someone stay with you",
            "Keep the line open for further instructions",
            "If you have any medications, inform the responding team"
        ];

        foreach ($instructions as $instruction) {
            $this->voiceOutput("• {$instruction}");
        }

        $this->voiceOutput("Emergency response team is en route. Please stay on the line until they arrive.");
    }

    /**
     * Check existing appointments for a health facility
     */
    private function checkExistingAppointments($facility)
    {
        $appointments = Appointment::where('health_facility_id', $facility->id)
            ->with(['patient', 'doctor'])
            ->latest()
            ->take(5)
            ->get();

        if ($appointments->isEmpty()) {
            $this->voiceOutput("You have no upcoming appointments scheduled.");
            return;
        }

        $this->voiceOutput("Here are your upcoming appointments:");
        foreach ($appointments as $appointment) {
            $this->voiceOutput("• {$appointment->appointment_date->format('M j, Y g:i A')} - {$appointment->patient->name} with Dr. {$appointment->doctor->name}");
        }
    }

    /**
     * Cancel appointment for a health facility
     */
    private function cancelAppointment($facility, $interactive = false)
    {
        $appointments = Appointment::where('health_facility_id', $facility->id)
            ->where('status', 'scheduled')
            ->with(['patient', 'doctor'])
            ->latest()
            ->take(5)
            ->get();

        if ($appointments->isEmpty()) {
            $this->voiceOutput("You have no cancellable appointments.");
            return;
        }

        $this->voiceOutput("Which appointment would you like to cancel?");
        $options = $appointments->map(function($apt) {
            return "{$apt->appointment_date->format('M j, Y g:i A')} - {$apt->patient->name}";
        })->toArray();

        if ($interactive) {
            $choice = $this->choice('Select appointment to cancel:', $options, 0);
            $selectedAppointment = $appointments[array_search($choice, $options)];
        } else {
            $selectedAppointment = $appointments->first();
            $this->voiceInput($options[0]);
        }

        if ($interactive) {
            if (!$this->confirm('Are you sure you want to cancel this appointment?', false)) {
                $this->voiceOutput("Cancellation aborted.");
                return;
            }
        } else {
            $this->voiceInput("Yes, cancel");
        }

        $selectedAppointment->update(['status' => 'cancelled']);
        $this->voiceOutput("Appointment cancelled successfully. A confirmation has been sent.");
    }

    /**
     * Get available time slots for a doctor
     */
    private function getAvailableSlots($doctor)
    {
        // Simulate available slots for the next 7 days
        $slots = [];
        $startDate = Carbon::now()->addDay();

        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->copy()->addDays($i);

            // Skip weekends if doctor doesn't work weekends
            if ($date->isWeekend()) continue;

            // Add some time slots
            $slots = array_merge($slots, [
                $date->copy()->setTime(9, 0),
                $date->copy()->setTime(10, 30),
                $date->copy()->setTime(14, 0),
                $date->copy()->setTime(15, 30),
            ]);
        }

        return array_slice($slots, 0, 8); // Return first 8 available slots
    }

    /**
     * Output voice system message
     */
    private function voiceOutput($message)
    {
        $this->line("🎤 <fg=green>{$message}</>");
    }

    /**
     * Output user voice input
     */
    private function voiceInput($input)
    {
        $this->line("👤 <fg=blue>{$input}</>");
    }
}