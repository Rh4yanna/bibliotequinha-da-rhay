<x-library-layout :title="$category->exists ? 'Editar categoria' : 'Cadastrar categoria'">
    <a class="text-link" href="{{ route('categories.index') }}">← Voltar para categorias</a>
    <div class="page-heading"><div><div class="eyebrow">ORGANIZAÇÃO DO ACERVO</div><h1>{{ $category->exists ? 'Editar categoria' : 'Nova categoria' }}</h1><p>Classifique os livros para facilitar novas descobertas.</p></div></div>
    <form class="panel form-panel" method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}">@csrf @if($category->exists) @method('PUT') @endif
        <h2>Informações da categoria</h2><p class="muted">Os campos com * são obrigatórios.</p><div class="form-grid">
            <div class="field"><label for="name">Nome *</label><input id="name" name="name" value="{{ old('name', $category->name) }}" required minlength="3" maxlength="100"></div>
            <div class="field"><label for="code">Código *</label><input id="code" name="code" value="{{ old('code', $category->code) }}" required maxlength="20" placeholder="LIT"></div>
        </div><div class="field section-title"><label for="description">Descrição</label><textarea id="description" name="description" rows="4" maxlength="1000">{{ old('description', $category->description) }}</textarea></div><div class="form-actions"><a class="btn" href="{{ route('categories.index') }}">Cancelar</a><button class="btn primary">Salvar categoria</button></div>
    </form>
</x-library-layout>
