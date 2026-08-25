<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GalleryIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', Rule::in(['created_at', 'name', 'updated_at'])],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function validatedSearch(): ?string
    {
        /** @var string|null $search */
        $search = $this->validated('search');

        return $search;
    }

    public function validatedSort(): string
    {
        /** @var string $sort */
        $sort = $this->validated('sort', 'created_at');

        return $sort;
    }

    /**
     * @return 'asc'|'desc'
     */
    public function validatedDirection(): string
    {
        /** @var 'asc'|'desc' $direction */
        $direction = $this->validated('direction', 'desc');

        return $direction;
    }

    public function validatedPerPage(): int
    {
        /** @var int|string $perPage */
        $perPage = $this->validated('per_page', 15);

        return (int) $perPage;
    }
}
