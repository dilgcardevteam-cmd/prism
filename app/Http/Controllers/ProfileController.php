<?php

namespace App\Http\Controllers;

use App\Support\InputSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the user's profile page.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        $user = Auth::user();
        
        // Position list based on agency
        $positions = [
            'DILG' => [
                'Engineer II',
                'Engineer III',
                'Unit Chief',
                'Assistant Unit Chief',
                'Financial Analyst II',
                'Financial Analyst III',
                'Project Evaluation Officer II',
                'Project Evaluation Officer III',
                'Information Systems Analyst III'
            ],
            'LGU' => [
                'Municipal Engineer I',
                'Municipal Engineer II',
                'Municipal Engineer III',
                'Planning Officer II',
                'Planning Officer III'
            ]
        ];
        
        return view('profile.show', compact('user', 'positions'));
    }

    /**
     * Update the user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // Validate the incoming request
        $validated = $request->validate([
            'fname' => ['required', 'string', 'max:255'],
            'lname' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'mobileno' => ['required', 'string', 'digits:11'],
        ]);

        $validated = InputSanitizer::sanitizeTextFields($validated, ['fname', 'lname', 'position']);
        $validated['mobileno'] = preg_replace('/\D+/', '', (string) $validated['mobileno']);

        // Update user profile
        $user->update($validated);

        return redirect()->route('profile.show')->with('success', 'Profile updated successfully!');
    }
}
