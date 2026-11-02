<x-library-layout title="Empréstimos">
    <h1>Empréstimos</h1>
    <p>Você possui {{ $activeCount }} empréstimo(s) ativo(s). Limite: 3 por pessoa.</p>
    @can('create', App\Models\Loan::class)<p><a class="btn primary" href="{{ route('loans.create') }}">Registrar empréstimo</a></p>@endcan
    <div class="table-wrap"><table><thead><tr><th>Exemplar</th><th>Leitor</th><th>Prazo</th><th>Situação</th><th>Detalhes</th></tr></thead><tbody>
    @forelse($loans as $loan)
        <tr><td>{{ $loan->book->title }} · {{ $loan->book->code }}</td><td>{{ $loan->user->name }}</td><td>{{ $loan->due_at->format('d/m/Y') }}</td><td>{{ $loan->returned_at ? 'Devolvido' : ($loan->due_at->endOfDay()->isPast() ? 'Atrasado' : 'Ativo') }}</td><td><a href="{{ route('loans.show', $loan) }}">Ver empréstimo</a></td></tr>
    @empty<tr><td colspan="5">Nenhum empréstimo registrado.</td></tr>@endforelse
    </tbody></table></div>{{ $loans->links() }}
</x-library-layout>
