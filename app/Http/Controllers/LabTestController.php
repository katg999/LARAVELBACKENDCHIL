<?php

namespace App\Http\Controllers;

use App\Models\LabTest;
use Illuminate\Http\Request;

class LabTestController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'student_id' => 'required|exists:students,id',
            'test_type' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        $labTest = LabTest::create(array_merge($validated, [
            'status' => 'pending'
        ]));

        return response()->json([
            'success' => true,
            'lab_test' => $labTest
        ]);
    }

    public function index(School $school)
    {
        return $school->labTests()
            ->with('student')
            ->latest()
            ->get();
    }
}