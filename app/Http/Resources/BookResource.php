<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'title' => $this->title, 'author' => $this->author,
            'code' => $this->code, 'publication_year' => $this->publication_year,
            'publisher' => $this->publisher, 'category_id' => $this->category_id,
            'status' => $this->status, 'synopsis' => $this->synopsis,
            'category' => $this->whenLoaded('category', fn () => ['id' => $this->category->id, 'name' => $this->category->name]),
        ];
    }
}
