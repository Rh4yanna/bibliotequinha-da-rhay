<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\User;
use App\Services\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PostgresLoanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'pgsql') { $this->markTestSkipped('Requer PostgreSQL isolado; ver README.md.'); }
    }

    private function book(string $code = 'SQL-1'): Book
    {
        $category = Category::firstOrCreate(['code' => 'SQL'], ['name' => 'Banco de dados']);
        return Book::create(['title' => 'Livro SQL', 'author' => 'Autor Teste', 'code' => $code, 'category_id' => $category->id, 'publication_year' => 2020, 'status' => 'disponivel']);
    }

    public function test_web_loan_and_return_execute_procedures_functions_and_triggers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reader = User::factory()->create(['role' => 'consulta']);
        $book = $this->book();
        $this->actingAs($admin)->get(route('loans.create'))->assertOk();
        $this->post(route('loans.store'), ['user_id' => $reader->id, 'book_id' => $book->id, 'due_at' => now()->addDays(14)->toDateString()])->assertSessionHasNoErrors()->assertRedirect();
        $loan = Loan::firstOrFail();
        $this->assertSame(1, app(LoanService::class)->quantidadeAtivos($reader->id));
        $this->assertSame('emprestado', $book->fresh()->status);
        $this->assertDatabaseHas('loan_audits', ['loan_id' => $loan->id, 'operation' => 'INSERT']);
        $this->patch(route('loans.update', $loan))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNotNull($loan->fresh()->returned_at);
        $this->assertSame('disponivel', $book->fresh()->status);
        $this->assertDatabaseHas('loan_audits', ['loan_id' => $loan->id, 'operation' => 'UPDATE']);
        $this->assertSame(0, app(LoanService::class)->quantidadeAtivos($reader->id));
        $this->patch(route('loans.update', $loan))->assertSessionHasErrors('loan');
    }

    public function test_duplicate_loan_limit_and_history_protection(): void
    {
        $user = User::factory()->create();
        $service = app(LoanService::class);
        $book = $this->book();
        $service->registrar($user->id, $book->id, now()->addDay()->toDateString());
        try { $service->registrar($user->id, $book->id, now()->addDay()->toDateString()); $this->fail('Duplicidade aceita'); } catch (ValidationException) {}
        foreach (['SQL-2', 'SQL-3'] as $code) { $service->registrar($user->id, $this->book($code)->id, now()->addDay()->toDateString()); }
        $this->assertSame(3, $service->quantidadeAtivos($user->id));
        $this->expectException(ValidationException::class);
        $service->registrar($user->id, $this->book('SQL-4')->id, now()->addDay()->toDateString());
    }

    public function test_reader_sees_only_own_loans_and_cannot_return_or_create(): void
    {
        $reader = User::factory()->create(['role' => 'consulta']);
        $other = User::factory()->create();
        $loan = app(LoanService::class)->registrar($other->id, $this->book()->id, now()->addDay()->toDateString());
        $this->actingAs($reader)->get(route('loans.index'))->assertOk()->assertDontSee('SQL-1');
        $this->get(route('loans.show', $loan))->assertForbidden();
        $this->post(route('loans.store'), [])->assertForbidden();
        $this->patch(route('loans.update', $loan))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->delete(route('books.destroy', $loan->book))->assertSessionHasErrors('book');
        $this->put(route('books.update', $loan->book), array_merge($loan->book->toArray(), ['status' => 'disponivel']))->assertSessionHasErrors('status');
    }

    public function test_direct_sql_insert_is_validated_and_audited(): void
    {
        $user = User::factory()->create();
        $book = $this->book();
        DB::table('loans')->insert(['user_id' => $user->id, 'book_id' => $book->id, 'borrowed_at' => now(), 'due_at' => now()->addDay()]);
        $this->assertDatabaseCount('loan_audits', 1);
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('loans')->insert(['user_id' => $user->id, 'book_id' => $book->id, 'borrowed_at' => now(), 'due_at' => now()->addDay()]);
    }
}
