<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $current_cycle = Carbon::now()->startOfMonth();

        $is_paid = $this->payments()
            ->where('billing_cycle', $current_cycle->toDateString())
            ->exists();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'amount' => (float) $this->amount,
            'due_day' => $this->due_day,
            'due_date_this_month' => Carbon::now()->setDay($this->due_day)->toDateString(),
            'is_paid_this_month' => $is_paid,
            'created_at' => $this->created_at,
        ];
    }
}
