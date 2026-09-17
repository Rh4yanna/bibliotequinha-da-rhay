<x-library-layout title="Livros">
    <div class="page-heading"><div><div class="eyebrow">ACERVO DA BIBLIOTECA</div><h1>Livros</h1><p>Encontre sua próxima leitura e explore o acervo.</p></div>@can('create', App\Models\Book::class)<a class="btn primary" href="{{ route('books.create') }}">+ Cadastrar livro</a>@endcan</div>
    <section class="panel">
        <form class="filters" method="GET" action="{{ route('books.index') }}">
            <div class="field search-field"><label for="search">Buscar livro</label><input id="search" name="search" placeholder="Título, autor ou código" value="{{ request('search') }}" maxlength="100"></div>
            <div class="field"><label for="category_id">Categoria</label><select id="category_id" name="category_id"><option value="">Todas as categorias</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
            <div class="field"><label for="status">Situação</label><select id="status" name="status"><option value="">Todas as situações</option>@foreach(App\Models\Book::STATUSES as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
            <button class="btn primary">Filtrar</button><a class="text-link" href="{{ route('books.index') }}">Limpar</a>
        </form>
        <div class="panel-heading"><h2>Acervo de livros <span class="count">{{ $books->total() }}</span></h2></div>
        <div class="table-scroll"><table><thead><tr><th>Livro / Autor</th><th>Código</th><th>Categoria</th><th>Situação</th><th><span class="sr-only">Ações</span></th></tr></thead><tbody>
        @forelse($books as $book)
            <tr><td><div class="person"><span class="avatar pale">{{ mb_substr($book->title, 0, 1) }}</span><div><strong>{{ $book->title }}</strong><small>{{ $book->author }}</small></div></div></td><td class="mono">{{ $book->code }}</td><td>{{ $book->category->name }}</td><td><span class="badge {{ $book->status }}">{{ App\Models\Book::STATUSES[$book->status] }}</span></td><td><a class="text-link" href="{{ route('books.show', $book) }}">Ver livro →</a></td></tr>
        @empty<tr><td colspan="5" class="empty">Nenhum livro encontrado. Ajuste os filtros ou cadastre o primeiro exemplar.</td></tr>@endforelse
        </tbody></table></div><div class="pagination">{{ $books->links() }}</div>
    </section>
</x-library-layout>
