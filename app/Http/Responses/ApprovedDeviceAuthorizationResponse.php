<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\RedirectsToCurrentTeam;
use Laravel\Passport\Contracts\ApprovedDeviceAuthorizationResponse as ApprovedDeviceAuthorizationResponseContract;
use Symfony\Component\HttpFoundation\Response;

class ApprovedDeviceAuthorizationResponse implements ApprovedDeviceAuthorizationResponseContract
{
    use RedirectsToCurrentTeam;

    /**
     * After approving a CLI device login, send the user to their dashboard
     * (instead of Passport's default redirect back to the device-code page).
     */
    public function toResponse($request): Response
    {
        return redirect($this->redirectPathForCurrentTeam($request, '/dashboard'));
    }
}
