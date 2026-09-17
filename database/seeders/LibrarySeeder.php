<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Seeder;

class LibrarySeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            ['code' => 'LIT', 'name' => 'Literatura brasileira', 'description' => 'Romances, contos e histórias da nossa literatura.'],
            ['code' => 'FIC', 'name' => 'Ficção e fantasia', 'description' => 'Mundos imaginários e aventuras para descobrir.'],
            ['code' => 'TEC', 'name' => 'Tecnologia', 'description' => 'Conhecimento para aprender e criar com tecnologia.'],
            ['code' => 'HIS', 'name' => 'História e sociedade', 'description' => 'Leituras sobre o passado e a vida em sociedade.'],
        ])->mapWithKeys(fn ($data) => [$data['code'] => Category::updateOrCreate(['code' => $data['code']], $data)]);

        // Dados fictícios de acervo para demonstração; não são metadados bibliográficos reais.
        $titles = [
            ['Entre Rios e Memórias', 'Ana Ribeiro', 'LIT'],
            ['O Jardim das Palavras', 'Bruno Almeida', 'LIT'],
            ['Cartas da Vila', 'Clara Santos', 'LIT'],
            ['Retratos de um Verão', 'Daniel Costa', 'LIT'],
            ['A Cidade das Estrelas', 'Elisa Rocha', 'FIC'],
            ['O Mapa dos Ventos', 'Felipe Moura', 'FIC'],
            ['Além da Última Ponte', 'Gabriela Lima', 'FIC'],
            ['O Guardião da Lua', 'Henrique Dias', 'FIC'],
            ['Primeiros Passos com PHP', 'Isabela Martins', 'TEC'],
            ['Construindo Aplicações Web', 'João Ferreira', 'TEC'],
            ['Banco de Dados na Prática', 'Larissa Gomes', 'TEC'],
            ['Programação e Criatividade', 'Marcos Souza', 'TEC'],
            ['Memórias da Nossa Cidade', 'Natália Barbosa', 'HIS'],
            ['Caminhos da Sociedade', 'Otávio Pereira', 'HIS'],
            ['Histórias do Cotidiano', 'Paula Nunes', 'HIS'],
            ['Cultura e Comunidade', 'Rafael Oliveira', 'HIS'],
        ];
        foreach ($titles as $index => [$title, $author, $category]) {
            Book::updateOrCreate(['code' => 'LIV-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT)], [
                'title' => $title,
                'author' => $author,
                'publication_year' => 2010 + $index % 15,
                'publisher' => 'Editora Exemplo',
                'category_id' => $categories[$category]->id,
                'status' => $index % 7 === 0 ? 'indisponivel' : ($index % 5 === 0 ? 'emprestado' : 'disponivel'),
                'synopsis' => 'Exemplar fictício para demonstração do sistema de biblioteca.',
            ]);
        }
    }
}
