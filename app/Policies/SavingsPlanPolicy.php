<?php

namespace App\Policies;

use App\Models\SavingsPlan;
use App\Models\User;

class SavingsPlanPolicy
{
    public function view(User $user, SavingsPlan $plan): bool
    {
        return $user->id === $plan->user_id;
    }

    public function delete(User $user, SavingsPlan $plan): bool
    {
        return $user->id === $plan->user_id
            && $plan->status !== SavingsPlan::STATUS_CANCELLED;
    }
}
