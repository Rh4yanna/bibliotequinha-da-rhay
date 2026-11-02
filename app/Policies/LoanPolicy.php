<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    public function viewAny(User $user): bool { return in_array($user->role, ['admin', 'bibliotecario', 'consulta'], true); }
    public function view(User $user, Loan $loan): bool { return $this->create($user) || ($user->role === 'consulta' && $loan->user_id === $user->id); }
    public function create(User $user): bool { return in_array($user->role, ['admin', 'bibliotecario'], true); }
    public function update(User $user, Loan $loan): bool { return $this->create($user); }
}
