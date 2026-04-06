<?php

namespace Domains\Community\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'post_id' => ['required', 'uuid', 'exists:publishing_posts,id'],
            'content' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'uuid', 'exists:community_comments,id'],
        ];
    }
}
