<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoanRequest;
use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use App\Services\LoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LoanController extends Controller
{
    public function index(Request $request, LoanService $service)
    {
        Gate::authorize('viewAny', Loan::class);
        return view('loans.index', [
            'loans' => Loan::with(['book', 'user'])->when($request->user()->role === 'consulta', fn ($q) => $q->where('user_id', $request->user()->id))->latest('id')->paginate(10),
            'activeCount' => $service->quantidadeAtivos($request->user()->id),
        ]);
    }

    public function create()
    {
        Gate::authorize('create', Loan::class);
        return view('loans.form', ['books' => Book::where('status', 'disponivel')->whereDoesntHave('loans', fn ($q) => $q->whereNull('returned_at'))->orderBy('title')->get(), 'users' => User::orderBy('name')->get()]);
    }

    public function store(LoanRequest $request, LoanService $service)
    {
        $data = $request->validated();
        $loan = $service->registrar($data['user_id'], $data['book_id'], $data['due_at']);
        return to_route('loans.show', $loan)->with('success', 'Empréstimo registrado.');
    }

    public function show(Loan $loan)
    {
        Gate::authorize('view', $loan);
        return view('loans.show', ['loan' => $loan->load(['book', 'user'])]);
    }

    public function update(Loan $loan, LoanService $service)
    {
        Gate::authorize('update', $loan);
        $service->devolver($loan);
        return to_route('loans.show', $loan)->with('success', 'Devolução registrada.');
    }
}
