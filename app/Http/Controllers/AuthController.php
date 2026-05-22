<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Mail\EmailVerificationMail;
use App\Mail\ForgotPasswordMail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:30',
            'email' => 'required|string|email|max:100|unique:users',
            'born_date' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female',
            'password' => 'required|string|min:8|max:32',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'born_date' => $request->born_date,
            'gender' => $request->gender,
            'password' => Hash::make($request->password),
        ]);

        // Generate and store OTP
        $otp = sprintf("%06d", mt_rand(100000, 999999));
        Cache::put("email_otp_{$user->id}", $otp, 900); // 15 minutes

        // Send Email
        try {
            Mail::to($user->email)->send(new EmailVerificationMail($user, $otp));
        } catch (\Exception $e) {
            Log::error("Failed to send verification email to {$user->email}: " . $e->getMessage());
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => (int)$user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => null,
                'born_date' => $user->born_date,
                'gender' => $user->gender,
            ]
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6'
        ]);

        $user = $request->user();
        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email is already verified', 'user' => $user]);
        }

        $cachedOtp = Cache::get("email_otp_{$user->id}");

        if (!$cachedOtp || $cachedOtp !== $request->otp) {
            return response()->json(['message' => 'Invalid or expired verification code'], 422);
        }

        $user->email_verified_at = now();
        $user->save();

        Cache::forget("email_otp_{$user->id}");

        return response()->json([
            'message' => 'Email verified successfully',
            'user' => $user
        ]);
    }

    public function resendVerification(Request $request)
    {
        $user = $request->user();
        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email is already verified'], 400);
        }

        $otp = sprintf("%06d", mt_rand(100000, 999999));
        Cache::put("email_otp_{$user->id}", $otp, 900);

        try {
            Mail::to($user->email)->send(new EmailVerificationMail($user, $otp));
        } catch (\Exception $e) {
            Log::error("Failed to send verification email to {$user->email}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to send verification code'], 500);
        }

        return response()->json(['message' => 'Verification code resent successfully']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            // Return success anyway for security
            return response()->json(['message' => 'If the email exists, a password reset code has been sent.']);
        }

        $otp = sprintf("%06d", mt_rand(100000, 999999));
        Cache::put("password_reset_otp_{$user->id}", $otp, 900);

        try {
            Mail::to($user->email)->send(new ForgotPasswordMail($user, $otp));
        } catch (\Exception $e) {
            Log::error("Failed to send password reset email to {$user->email}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to send reset code'], 500);
        }

        return response()->json(['message' => 'If the email exists, a password reset code has been sent.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8|max:32'
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $cachedOtp = Cache::get("password_reset_otp_{$user->id}");

        if (!$cachedOtp || $cachedOtp !== $request->otp) {
            return response()->json(['message' => 'Invalid or expired reset code'], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        Cache::forget("password_reset_otp_{$user->id}");
        $user->tokens()->delete();

        return response()->json(['message' => 'Password reset successfully']);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|max:32'
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully']);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:100',
            'password' => 'required|string|max:32',
        ]);

        \Illuminate\Support\Facades\Log::info("Login attempt for: " . $request->email);
        $user = User::where('email', $request->email)->first();

        if (!$user) {
             \Illuminate\Support\Facades\Log::warning("User not found: " . $request->email);
             return response()->json(['message' => 'User not found in database'], 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            \Illuminate\Support\Facades\Log::warning("Password mismatch for: " . $request->email);
            return response()->json(['message' => 'Incorrect password'], 401);
        }

        // Enforce single active session per user by deleting all previous access tokens
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;
        \Illuminate\Support\Facades\Log::info("Login successful for: " . $request->email);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
