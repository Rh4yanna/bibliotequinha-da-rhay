<?php

return [
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser um texto.',
    'email' => 'Informe um endereço de e-mail válido.',
    'unique' => 'Este valor de :attribute já está cadastrado.',
    'exists' => 'O valor selecionado para :attribute não existe.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'in' => 'O valor selecionado para :attribute é inválido.',
    'date' => 'O campo :attribute deve ser uma data válida.',
    'date_format' => 'O campo :attribute deve estar no formato :format.',
    'before' => 'O campo :attribute deve ser uma data anterior a :date.',
    'after' => 'O campo :attribute deve ser uma data posterior a :date.',
    'alpha_dash' => 'O campo :attribute aceita apenas letras, números, hífens e sublinhados.',
    'regex' => 'O formato do campo :attribute é inválido.',
    'confirmed' => 'A confirmação de :attribute não confere.',
    'current_password' => 'A senha atual está incorreta.',
    'lowercase' => 'O campo :attribute deve estar em letras minúsculas.',
    'min' => ['string' => 'O campo :attribute deve ter pelo menos :min caracteres.', 'numeric' => 'O campo :attribute deve ser no mínimo :min.'],
    'max' => ['string' => 'O campo :attribute deve ter no máximo :max caracteres.', 'numeric' => 'O campo :attribute deve ser no máximo :max.'],
    'between' => ['numeric' => 'O campo :attribute deve estar entre :min e :max.'],
    'attributes' => ['name' => 'nome', 'email' => 'e-mail', 'password' => 'senha', 'current_password' => 'senha atual'],
];
