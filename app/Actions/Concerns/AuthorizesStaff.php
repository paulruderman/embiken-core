<?php

namespace App\Actions\Concerns;

use App\Models\Staff;
use Illuminate\Support\Facades\Auth;

trait AuthorizesStaff
{
    public function authorize(): bool
    {
        $staff = Auth::guard('staff')->user();

        if (! $staff instanceof Staff) {
            return false;
        }

        if (tenant()?->isSuspended() && ! $staff->is_platform_manager) {
            return false;
        }

        return true;
    }
}
