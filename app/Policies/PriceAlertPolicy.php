<?php

namespace App\Policies;

use App\Models\PriceAlert;
use App\Models\User;

class PriceAlertPolicy
{
    public function view(User $user, PriceAlert $alert): bool
    {
        return $user->id === $alert->user_id;
    }

    public function delete(User $user, PriceAlert $alert): bool
    {
        return $user->id === $alert->user_id;
    }
}
