<x-library-layout title="Detalhes do empréstimo">
    <h1>Empréstimo #{{ $loan->id }}</h1>
    <p>Livro: {{ $loan->book->title }} · {{ $loan->book->code }}</p><p>Leitor: {{ $loan->user->name }}</p>
    <p>Retirada: {{ $loan->borrowed_at->format('d/m/Y H:i') }}</p><p>Prazo: {{ $loan->due_at->format('d/m/Y') }}</p>
    <p>Devolução: {{ $loan->returned_at?->format('d/m/Y H:i') ?? 'Pendente' }}</p>
    @can('update', $loan)@if(!$loan->returned_at)<form method="POST" action="{{ route('loans.update', $loan) }}" onsubmit="return confirm('Confirmar devolução deste exemplar?')">@csrf @method('PATCH')<button class="btn primary">Registrar devolução</button></form>@endif@endcan
    <a href="{{ route('loans.index') }}">Voltar aos empréstimos</a>
</x-library-layout>
