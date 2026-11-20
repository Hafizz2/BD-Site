<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Enums\SubmittedVia;
use App\Filament\Resources\PaymentResource;
use App\Models\Due;
use App\Models\Member;
use App\Models\Student;
use App\Services\Finance\PaymentService;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * Payments are recorded here by the finance team (the bot no longer takes payments).
 * Approving notifies the payer on Telegram if they are linked.
 */
class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    public function form(Schema $schema): Schema
    {
        $schema = PaymentResource::form($schema);

        return $schema->components([
            ...$schema->getComponents(),
            Toggle::make('approve_now')->label('Approve immediately (I checked the money arrived)')
                ->visible(fn () => auth()->user()->can('approve_payment'))->default(true)->dehydrated(false)->columnSpanFull(),
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $payerClass = in_array($data['payable_type'], ['member', Member::class], true) ? Member::class : Student::class;
        $payer = $payerClass::findOrFail($data['payable_id']);
        $due = ! empty($data['due_id']) ? Due::find($data['due_id']) : null;

        $payment = app(PaymentService::class)->submit($payer, $due, $data, SubmittedVia::Admin, null, auth()->user());

        if (($this->data['approve_now'] ?? false) && auth()->user()->can('approve_payment')) {
            app(PaymentService::class)->approve($payment, auth()->user());
        }

        return $payment;
    }

    protected function getRedirectUrl(): string
    {
        return PaymentResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
