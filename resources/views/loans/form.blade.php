<x-library-layout title="Registrar empréstimo">
    <h1>Registrar empréstimo</h1><p>Um exemplar por empréstimo, até três ativos por pessoa.</p>
    <form method="POST" action="{{ route('loans.store') }}" class="panel form-panel">@csrf
        <div class="form-grid">
        <div class="field"><label for="user_id">Leitor</label><select id="user_id" name="user_id" required>@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} · {{ $user->email }}</option>@endforeach</select></div>
        <div class="field"><label for="book_id">Exemplar disponível</label><select id="book_id" name="book_id" required><option value="">Selecione um exemplar</option>@foreach($books as $book)<option value="{{ $book->id }}" @selected(old('book_id') == $book->id)>{{ $book->title }} · {{ $book->code }}</option>@endforeach</select></div>
        <div class="field"><label for="due_at">Prazo de devolução</label><input id="due_at" type="date" name="due_at" min="{{ now()->toDateString() }}" value="{{ old('due_at', now()->addDays(14)->toDateString()) }}" required></div>
        </div>
        @if($books->isEmpty())<p class="alert error">Nenhum exemplar disponível para empréstimo.</p>@endif
        <div class="form-actions"><a class="btn" href="{{ route('loans.index') }}">Cancelar</a><button class="btn primary" @disabled($books->isEmpty())>Registrar</button></div>
    </form>
</x-library-layout>
