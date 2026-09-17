<x-library-layout :title="$book->exists ? 'Editar livro' : 'Cadastrar livro'">
    <a class="text-link" href="{{ route('books.index') }}">← Voltar para livros</a>
    <div class="page-heading"><div><div class="eyebrow">ACERVO DA BIBLIOTECA</div><h1>{{ $book->exists ? 'Editar livro' : 'Novo livro' }}</h1><p>Preencha os dados da obra e identifique o exemplar.</p></div></div>
    <form class="panel form-panel" method="POST" action="{{ $book->exists ? route('books.update', $book) : route('books.store') }}">
        @csrf @if($book->exists) @method('PUT') @endif
        <h2>Informações do livro</h2><p class="muted">Os campos com * são obrigatórios. Cada cadastro representa um exemplar físico.</p>
        <div class="form-grid">
            <div class="field"><label for="title">Título *</label><input id="title" name="title" value="{{ old('title', $book->title) }}" required minlength="2" maxlength="200">@error('title')<small class="field-error">{{ $message }}</small>@enderror</div>
            <div class="field"><label for="author">Autor *</label><input id="author" name="author" value="{{ old('author', $book->author) }}" required minlength="3" maxlength="150">@error('author')<small class="field-error">{{ $message }}</small>@enderror</div>
            <div class="field"><label for="publication_year">Ano de publicação *</label><input id="publication_year" type="number" name="publication_year" value="{{ old('publication_year', $book->publication_year) }}" required min="1450" max="{{ now()->year }}">@error('publication_year')<small class="field-error">{{ $message }}</small>@enderror</div>
            <div class="field"><label for="publisher">Editora</label><input id="publisher" name="publisher" value="{{ old('publisher', $book->publisher) }}" maxlength="150"></div>
        </div>
        <h2 class="section-title">Organização do acervo</h2>
        <div class="form-grid">
            <div class="field"><label for="code">Código do exemplar *</label><input id="code" name="code" value="{{ old('code', $book->code) }}" required maxlength="20" placeholder="LIV-0001">@error('code')<small class="field-error">{{ $message }}</small>@enderror</div>
            <div class="field"><label for="category_id">Categoria *</label><select id="category_id" name="category_id" required><option value="">Selecione uma categoria</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $book->category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select>@error('category_id')<small class="field-error">{{ $message }}</small>@enderror</div>
            <div class="field"><label for="status">Situação *</label><select id="status" name="status" required>@foreach(App\Models\Book::STATUSES as $value => $label)<option value="{{ $value }}" @selected(old('status', $book->status ?? 'disponivel') === $value)>{{ $label }}</option>@endforeach</select></div>
        </div>
        <div class="field section-title"><label for="synopsis">Sinopse ou observações</label><textarea id="synopsis" name="synopsis" rows="4" maxlength="3000">{{ old('synopsis', $book->synopsis) }}</textarea></div>
        @if($categories->isEmpty())<p class="alert error">Um administrador precisa cadastrar uma categoria antes de cadastrar livros.</p>@endif
        <div class="form-actions"><a class="btn" href="{{ route('books.index') }}">Cancelar</a><button class="btn primary" @disabled($categories->isEmpty())>Salvar livro</button></div>
    </form>
</x-library-layout>
