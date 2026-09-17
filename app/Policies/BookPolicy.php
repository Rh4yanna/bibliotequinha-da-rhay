<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'bibliotecario', 'consulta'], true);
    }

    public function view(User $user, Book $book): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'bibliotecario'], true);
    }

    public function update(User $user, Book $book): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Book $book): bool
    {
        return $user->role === 'admin';
    }
}
