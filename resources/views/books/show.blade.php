<x-library-layout title="Detalhes do livro">
    <a class="text-link" href="{{ route('books.index') }}">← Voltar para livros</a>
    <div class="page-heading"><div><div class="eyebrow">FICHA DO EXEMPLAR</div><h1>{{ $book->title }}</h1><p>{{ $book->author }} · Código {{ $book->code }}</p></div>@can('update', $book)<a class="btn primary" href="{{ route('books.edit', $book) }}">Editar livro</a>@endcan</div>
    <section class="panel form-panel"><h2>Informações da obra e do exemplar</h2><dl class="detail-grid">
        <div><dt>Autor</dt><dd>{{ $book->author }}</dd></div><div><dt>Editora</dt><dd>{{ $book->publisher ?: 'Não informada' }}</dd></div>
        <div><dt>Ano de publicação</dt><dd>{{ $book->publication_year }}</dd></div><div><dt>Situação</dt><dd><span class="badge {{ $book->status }}">{{ App\Models\Book::STATUSES[$book->status] }}</span></dd></div>
        <div><dt>Categoria</dt><dd><a class="text-link" href="{{ route('books.index', ['category_id' => $book->category_id]) }}">{{ $book->category->name }}</a></dd></div><div><dt>Cadastrado em</dt><dd>{{ $book->created_at->format('d/m/Y') }}</dd></div>
    </dl>@if($book->synopsis)<div class="section-title"><h2>Sinopse e observações</h2><p class="synopsis">{{ $book->synopsis }}</p></div>@endif</section>
    @can('delete', $book)<section class="danger-zone"><div><h2>Excluir exemplar</h2><p>A exclusão remove permanentemente o cadastro deste exemplar.</p></div><form method="POST" action="{{ route('books.destroy', $book) }}" onsubmit="return confirm('Excluir este exemplar permanentemente?')">@csrf @method('DELETE')<button class="btn danger">Excluir livro</button></form></section>@endcan
</x-library-layout>
