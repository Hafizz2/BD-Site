<?php

namespace App\Services\Finance;

use App\Enums\DueStatus;
use App\Enums\PaymentStatus;
use App\Models\Donation;
use App\Models\Due;
use App\Models\Expense;
use App\Models\Payment;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Finance report for a date range (approved payments by paid_at).
 */
class FinanceReport
{
    public function __construct(public CarbonInterface $from, public CarbonInterface $to) {}

    private function approved()
    {
        return Payment::query()->where('payments.status', PaymentStatus::Approved->value)
            ->whereBetween('payments.paid_at', [$this->from->copy()->startOfDay(), $this->to->copy()->endOfDay()]);
    }

    public function summary(): array
    {
        $collected = (float) $this->approved()->sum('amount');
        $donations = (float) Donation::whereBetween('received_at', [$this->from->toDateString(), $this->to->toDateString()])->sum('amount');
        $expenses = (float) Expense::whereBetween('spent_at', [$this->from->toDateString(), $this->to->toDateString()])->sum('amount');
        $outstanding = (float) Due::whereIn('status', DueStatus::outstanding())->selectRaw('COALESCE(SUM(amount - paid_amount),0) as s')->value('s');

        return [
            'collected' => $collected,
            'donations' => $donations,
            'income' => $collected + $donations,
            'expenses' => $expenses,
            'net' => $collected + $donations - $expenses,
            'outstanding' => $outstanding,
            'payments_count' => $this->approved()->count(),
            'pending_count' => Payment::where('status', PaymentStatus::PendingReview->value)->count(),
        ];
    }

    /** @return Collection<int,object{label:string,total:float,count:int}> */
    public function byMethod(): Collection
    {
        return $this->approved()->select('method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('method')->orderByDesc('total')->get()
            ->map(fn ($r) => (object) ['label' => $r->method?->getLabel() ?? (string) $r->method, 'total' => (float) $r->total, 'count' => (int) $r->cnt]);
    }

    /** Totals per program/batch (via the due's fee plan batch; member contributions grouped separately). */
    public function byBatch(): Collection
    {
        return $this->approved()
            ->leftJoin('dues', 'dues.id', '=', 'payments.due_id')
            ->leftJoin('fee_plans', 'fee_plans.id', '=', 'dues.fee_plan_id')
            ->leftJoin('batches', 'batches.id', '=', 'fee_plans.batch_id')
            ->leftJoin('programs', 'programs.id', '=', 'batches.program_id')
            ->select(
                DB::raw("COALESCE(programs.name, CASE WHEN payments.payable_type = 'member' THEN 'Member contributions' ELSE 'Other / general' END) as program"),
                DB::raw("COALESCE(batches.name, '—') as batch"),
                DB::raw('SUM(payments.amount) as total'),
                DB::raw('COUNT(*) as cnt'),
            )
            ->groupBy('program', 'batch')->orderBy('program')->get()
            ->map(fn ($r) => (object) ['program' => $r->program, 'batch' => $r->batch, 'total' => (float) $r->total, 'count' => (int) $r->cnt]);
    }

    public function expensesByCategory(): Collection
    {
        return Expense::whereBetween('spent_at', [$this->from->toDateString(), $this->to->toDateString()])
            ->select('category', DB::raw('SUM(amount) as total'))->groupBy('category')->orderByDesc('total')->get()
            ->map(fn ($r) => (object) ['label' => $r->category?->getLabel() ?? (string) $r->category, 'total' => (float) $r->total]);
    }

    /** People who still owe money (all time). */
    public function unpaid(int $limit = 500): Collection
    {
        return Due::with(['payable', 'feePlan'])->whereIn('status', DueStatus::outstanding())
            ->orderBy('due_date')->limit($limit)->get()
            ->groupBy(fn (Due $d) => $d->payable_type.':'.$d->payable_id)
            ->map(fn (Collection $dues) => (object) [
                'name' => $dues->first()->payable?->full_name ?? '—',
                'phone' => $dues->first()->payable?->phone,
                'type' => $dues->first()->payable_type,
                'items' => $dues->map(fn ($d) => $d->label)->implode(', '),
                'balance' => (float) $dues->sum(fn ($d) => (float) $d->balance()),
                'oldest' => $dues->min('due_date'),
                'overdue' => $dues->contains(fn ($d) => $d->status === DueStatus::Overdue),
            ])->sortByDesc('balance')->values();
    }
}
