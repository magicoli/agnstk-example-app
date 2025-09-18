<?php

namespace Agnstk\Http\Controllers\Auth;

use Agnstk\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller {
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;
    
    /**
     * Send a reset link to the given user with proper error handling.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request) {
        $this->validateEmail($request);

        try {
            // We will send the password reset link to this user. Once we have attempted
            // to send the link, we will examine the response then see the message we
            // need to show to the user. Finally, we'll send out a proper response.
            $response = $this->broker()->sendResetLink(
                $this->credentials($request)
            );

            return $response == Password::RESET_LINK_SENT
                        ? $this->sendResetLinkResponse($request, $response)
                        : $this->sendResetLinkFailedResponse($request, $response);
                        
        } catch (\Exception $e) {
            // Handle mail sending failures gracefully
            \Log::error('Password reset email failed: ' . $e->getMessage());
            
            // Return with error message instead of crashing
            $error_message = 'Unable to send password reset email. Please try again later or contact support.';
            
            // In testing environment, include debug information in the main error message
            if (app()->environment('testing', 'local')) {
                $error_message .= PHP_EOL . 'Debug info: ' . $e->getMessage();
            }
            
            return back()->withErrors([
                'email' => $error_message
            ]);
        }
    }
}
