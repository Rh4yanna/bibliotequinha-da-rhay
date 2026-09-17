<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LibraryTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function data(array $overrides = []): array
    {
        $category = Category::firstOrCreate(['code' => 'LIT'], ['name' => 'Literatura', 'description' => 'Romances e contos']);

        return array_merge(['title' => 'O Jardim das Palavras', 'author' => 'Ana Ribeiro', 'code' => 'LIV-0001', 'publication_year' => 2020, 'publisher' => 'Editora Exemplo', 'category_id' => $category->id, 'status' => 'disponivel', 'synopsis' => 'Um livro fictício.'], $overrides);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (['dashboard', 'books.index', 'books.create', 'categories.index', 'categories.create'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_all_roles_can_read_the_catalog_and_book_details(): void
    {
        $book = Book::create($this->data());
        foreach (['admin', 'bibliotecario', 'consulta'] as $role) {
            $this->actingAs($this->user($role));
            $this->get(route('dashboard'))->assertOk()->assertSee($book->title);
            $this->get(route('books.index'))->assertOk()->assertSee($book->author);
            $this->get(route('books.show', $book))->assertOk()->assertSee('Literatura')->assertSee('2020');
            $this->get(route('categories.index'))->assertOk()->assertSee('LIT');
        }
    }

    public function test_admin_can_complete_book_crud_and_normalizes_inventory_code(): void
    {
        $this->actingAs($this->user('admin'));
        $data = $this->data(['code' => ' liv-0001 ']);
        $this->get(route('books.create'))->assertOk();
        $this->post(route('books.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $book = Book::firstOrFail();
        $this->assertSame('LIV-0001', $book->code);
        $this->get(route('books.edit', $book))->assertOk();
        $this->put(route('books.update', $book), array_merge($data, ['title' => 'Novo título']))->assertSessionHasNoErrors()->assertRedirect(route('books.show', $book));
        $this->assertDatabaseHas('books', ['title' => 'Novo título']);
        $this->delete(route('books.destroy', $book))->assertRedirect(route('books.index'));
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function test_librarian_can_write_but_cannot_delete_books_or_manage_categories(): void
    {
        $this->actingAs($this->user('bibliotecario'));
        $data = $this->data();
        $this->post(route('books.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $book = Book::firstOrFail();
        $this->put(route('books.update', $book), array_merge($data, ['status' => 'emprestado']))->assertSessionHasNoErrors()->assertRedirect();
        $this->delete(route('books.destroy', $book))->assertForbidden();
        $this->get(route('books.show', $book))->assertDontSee('Excluir livro');
        $this->get(route('categories.create'))->assertForbidden();
        $this->get(route('categories.edit', $book->category))->assertForbidden();
        $this->post(route('categories.store'), [])->assertForbidden();
        $this->put(route('categories.update', $book->category), [])->assertForbidden();
        $this->delete(route('categories.destroy', $book->category))->assertForbidden();
        $this->assertDatabaseHas('books', ['id' => $book->id, 'status' => 'emprestado']);
    }

    public function test_consulta_cannot_write_even_by_direct_request(): void
    {
        $data = $this->data();
        $book = Book::create($data);
        $this->actingAs($this->user('consulta'));
        $this->get(route('books.create'))->assertForbidden();
        $this->get(route('books.edit', $book))->assertForbidden();
        $this->post(route('books.store'), $data)->assertForbidden();
        $this->put(route('books.update', $book), $data)->assertForbidden();
        $this->delete(route('books.destroy', $book))->assertForbidden();
        $this->get(route('categories.create'))->assertForbidden();
        $this->post(route('categories.store'), [])->assertForbidden();
        $this->put(route('categories.update', $book->category), [])->assertForbidden();
        $this->delete(route('categories.destroy', $book->category))->assertForbidden();
        $this->get(route('books.index'))->assertDontSee('Cadastrar livro');
    }

    public function test_validation_rejects_invalid_data_and_duplicate_codes(): void
    {
        $this->actingAs($this->user('bibliotecario'));
        $data = $this->data();
        Book::create($data);
        $this->post(route('books.store'), $data)->assertSessionHasErrors(['code']);
        $this->post(route('books.store'), $this->data(['title' => 'A', 'author' => 'B', 'publication_year' => now()->year + 1, 'category_id' => 999, 'status' => 'invalido', 'synopsis' => str_repeat('a', 3001)]))->assertSessionHasErrors(['title', 'author', 'publication_year', 'category_id', 'status', 'synopsis']);
        $this->assertDatabaseCount('books', 1);
    }

    public function test_multiple_copies_of_a_title_are_allowed_with_different_codes(): void
    {
        $this->actingAs($this->user('bibliotecario'));
        Book::create($this->data());
        $this->post(route('books.store'), $this->data(['code' => 'LIV-0002']))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('books', 2);
    }

    public function test_search_by_title_author_or_code_and_combined_filters(): void
    {
        $book = Book::create($this->data());
        Book::create($this->data(['title' => 'Outro livro', 'author' => 'Bruno Costa', 'code' => 'LIV-0002', 'status' => 'emprestado']));
        $this->actingAs($this->user('consulta'));
        foreach (['JARDIM', 'ANA RIBEIRO', 'liv-0001'] as $search) {
            $this->get(route('books.index', ['search' => $search, 'category_id' => $book->category_id, 'status' => 'disponivel']))->assertOk()->assertSee($book->title)->assertDontSee('Outro livro');
        }
        $this->get(route('books.index', ['search' => 'inexistente']))->assertOk()->assertSee('Nenhum livro encontrado');
    }

    public function test_admin_manages_categories_but_cannot_delete_one_with_books(): void
    {
        $this->actingAs($this->user('admin'));
        $this->get(route('categories.create'))->assertOk();
        $this->post(route('categories.store'), ['name' => 'Tecnologia', 'code' => 'tec', 'description' => 'Livros técnicos'])->assertSessionHasNoErrors()->assertRedirect();
        $category = Category::firstOrFail();
        $this->assertSame('TEC', $category->code);
        $this->get(route('categories.edit', $category))->assertOk();
        $this->put(route('categories.update', $category), ['name' => 'Tecnologia e Ciência', 'code' => 'TEC', 'description' => null])->assertSessionHasNoErrors()->assertRedirect();
        $this->post(route('categories.store'), ['name' => 'A', 'code' => 'TEC', 'description' => str_repeat('x', 1001)])->assertSessionHasErrors(['name', 'code', 'description']);
        $book = Book::create($this->data(['category_id' => $category->id]));
        $this->delete(route('categories.destroy', $category))->assertSessionHasErrors('category');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $book->delete();
        $this->delete(route('categories.destroy', $category))->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_public_registration_and_profile_cannot_elevate_role(): void
    {
        $this->post(route('register'), ['name' => 'Nova Conta', 'email' => 'nova@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'role' => 'admin'])->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));
        $user = User::where('email', 'nova@example.test')->firstOrFail();
        $this->assertSame('consulta', $user->role);
        $this->actingAs($user)->patch(route('profile.update'), ['name' => 'Novo Nome', 'email' => $user->email, 'role' => 'bibliotecario'])->assertSessionHasNoErrors();
        $this->assertSame('consulta', $user->fresh()->role);
    }

    public function test_seeders_create_library_demo_data_and_can_run_again(): void
    {
        $this->seed();
        $this->seed();
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('categories', 4);
        $this->assertDatabaseCount('books', 16);
        foreach (['admin', 'bibliotecario', 'consulta'] as $role) {
            $this->assertDatabaseHas('users', ['email' => $role.'@biblioteca.test', 'role' => $role]);
        }
    }

    public function test_role_migration_preserves_existing_accounts_and_is_reversible(): void
    {
        $migration = require database_path('migrations/2026_09_13_000002_update_library_roles.php');
        $user = $this->user('bibliotecario');
        $password = $user->password;
        $migration->down();
        $this->assertSame('secretaria', $user->fresh()->role);
        $migration->up();
        $this->assertSame('bibliotecario', $user->fresh()->role);
        $this->assertSame($password, $user->fresh()->password);
        $this->assertSame($user->email, $user->fresh()->email);
    }

    public function test_library_migration_keeps_previous_academic_records_intact(): void
    {
        $course = DB::table('courses')->insertGetId(['name' => 'Curso anterior', 'code' => 'LEGADO', 'semesters' => 4]);
        $student = DB::table('students')->insertGetId(['course_id' => $course, 'name' => 'Registro anterior', 'email' => 'legado@example.test', 'registration' => 'LEG-1', 'birth_date' => '2001-01-01', 'status' => 'ativo']);
        $migration = require database_path('migrations/2026_09_13_000001_create_library_tables.php');
        $migration->down();
        $migration->up();
        $this->assertDatabaseHas('students', ['id' => $student, 'name' => 'Registro anterior']);
        $this->assertDatabaseHas('courses', ['id' => $course]);
    }
}
