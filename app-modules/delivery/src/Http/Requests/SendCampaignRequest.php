<?php

namespace Domains\Delivery\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'campaign_id' => ['required', 'uuid', 'exists:delivery_campaigns,id'],
        ];
    }
}
