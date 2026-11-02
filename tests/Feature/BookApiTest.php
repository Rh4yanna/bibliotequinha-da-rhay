<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    private function token(string $role = 'admin'): string
    {
        $user = User::factory()->create(['role' => $role]);
        $token = bin2hex(random_bytes(32));
        DB::table('api_tokens')->insert(['user_id' => $user->id, 'name' => 'Teste', 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addHour()]);
        return $token;
    }

    private function data(): array
    {
        $category = Category::create(['code' => 'API', 'name' => 'Literatura']);
        return ['title' => 'Livro da API', 'author' => 'Autor Exemplo', 'code' => 'API-001', 'publication_year' => 2020, 'category_id' => $category->id, 'status' => 'disponivel'];
    }

    public function test_get_returns_paginated_json(): void
    {
        $book = Book::create($this->data());
        $this->withToken($this->token('consulta'))->getJson('/api/livros')->assertOk()->assertJsonPath('data.0.id', $book->id)->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_admin_can_complete_api_crud(): void
    {
        $this->withToken($this->token());
        $response = $this->postJson('/api/livros', $this->data())->assertCreated();
        $id = $response->json('data.id');
        $this->getJson("/api/livros/$id")->assertOk()->assertJsonPath('data.title', 'Livro da API');
        $this->patchJson("/api/livros/$id", ['title' => 'Livro atualizado'])->assertOk()->assertJsonPath('data.title', 'Livro atualizado');
        $this->deleteJson("/api/livros/$id")->assertNoContent();
        $this->getJson("/api/livros/$id")->assertNotFound();
    }

    public function test_auth_validation_and_permissions_are_enforced(): void
    {
        $this->get('/api/livros')->assertUnauthorized()->assertHeader('Content-Type', 'application/json');
        $this->withToken($this->token('consulta'))->postJson('/api/livros', [])->assertForbidden();
        $this->withToken($this->token())->postJson('/api/livros', [])->assertUnprocessable()->assertJsonValidationErrors(['title', 'author', 'category_id']);
        DB::table('api_tokens')->update(['expires_at' => now()->subMinute()]);
        $this->getJson('/api/livros')->assertUnauthorized();
    }
}
