@props(['title' => 'Visão geral'])
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ $title }} · Bibliotequinha da Rhay</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="library-body">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><span><span class="brand-name">Bibliotequinha</span><span class="brand-owner">da Rhay</span><span class="brand-caption">SISTEMA DE BIBLIOTECA</span></span></a>
        <div class="nav-label">ESPAÇO DE LEITURA</div>
        <nav aria-label="Navegação principal">
            <a class="{{ request()->routeIs('loans.*') ? 'selected' : '' }}" href="{{ route('loans.index') }}">Empréstimos</a>
            <a class="{{ request()->routeIs('dashboard') ? 'selected' : '' }}" href="{{ route('dashboard') }}"><span aria-hidden="true">▦</span> Visão geral</a>
            <a class="{{ request()->routeIs('books.*') ? 'selected' : '' }}" href="{{ route('books.index') }}"><span aria-hidden="true">▤</span> Livros</a>
            <a class="{{ request()->routeIs('categories.*') ? 'selected' : '' }}" href="{{ route('categories.index') }}"><span aria-hidden="true">◎</span> Categorias</a>
        </nav>
        <div class="sidebar-note"><span class="dot"></span> Histórias que conectam.<p>Organize o acervo.<br>Abra novas possibilidades.</p></div>
        <div class="account"><span class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><div><a href="{{ route('profile.edit') }}">{{ auth()->user()->name }}</a><small>{{ ['admin' => 'Administrador', 'bibliotecario' => 'Bibliotecário', 'consulta' => 'Consulta'][auth()->user()->role] ?? 'Consulta' }}</small></div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout" title="Sair" aria-label="Sair">↪</button></form>
        </div>
    </aside>
    <div class="workspace">
        <header class="topbar"><span>Biblioteca <span class="breadcrumb">/ &nbsp; {{ $title }}</span></span><span class="today">{{ now()->format('d/m/Y') }}</span></header>
        <main id="main">
            @if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert error" role="alert"><strong>Confira os dados informados.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            {{ $slot }}
        </main>
        <footer>Bibliotequinha da Rhay · Sistema de Biblioteca</footer>
    </div>
</body>
</html>
