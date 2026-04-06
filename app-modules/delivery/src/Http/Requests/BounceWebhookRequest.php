<?php

namespace Domains\Delivery\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BounceWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'workspace_id' => ['required', 'uuid', 'exists:identity_workspaces,id'],
            'campaign_id' => ['nullable', 'uuid', 'exists:delivery_campaigns,id'],
            'email' => ['required', 'email', 'max:255'],
            'bounce_type' => ['required', 'in:hard,soft,complaint'],
            'code' => ['nullable', 'string', 'max:100'],
            'reason' => ['nullable', 'string'],
        ];
    }
}
