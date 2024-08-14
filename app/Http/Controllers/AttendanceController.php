<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\SMSContact;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function fetchAndPersist(Request $request)
    {
        try{
            $data = $request->validate([
                'id_number' => 'required|string',
                'month' => 'required|integer|min:1|max:12',
                'day' => 'required|integer|min:1|max:31',
                'year' => 'required|integer|min:1900|max:' . date('Y'),
                'is_tardy' => 'required|boolean',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The given data was invalid.',
                    'errors' => $e->validator->errors(),
                ]
            ], 422);
        }

        // Fetch the student record from SMSContact
        $student = SMSContact::where('id_number', $data['id_number'])->first();

        if (!$student) {
            return response()->json([
                'status' => 'success',  // Keep status as success
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Student not found.',
                ]
            ], 200);  // Use 200 status code instead of 404
        }

        // Persist the data to the Attendance table
        Attendance::create([
            'id_number' => $data['id_number'],
            'month' => $data['month'],
            'day' => $data['day'],
            'year' => $data['year'],
            'is_tardy' => $data['is_tardy'],
        ]);

        // Return the student record as the payload
        return response()->json([
            'status' => 'success',
            'data' => $student,
            'message' => 'Attendance created successfully.'
        ], 201);
    }
}
