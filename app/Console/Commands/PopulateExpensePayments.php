<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\ExpensePayment;
use Illuminate\Console\Command;

class PopulateExpensePayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expenses:populate-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate expense_payments table for existing expenses data assuming full payment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting population of expense_payments...');

        $expenses = Expense::doesntHave('payments')->get();

        $count = 0;
        foreach ($expenses as $expense) {
            // Create initial payment record
            ExpensePayment::create([
                'expense_id'     => $expense->id,
                'payment_date'   => $expense->transaction_date,
                'amount'         => $expense->amount,
                'payment_method' => $expense->payment_method ?? 'cash',
                'notes'          => 'Pelunasan awal (migrasi data eksisting)',
                'user_id'        => $expense->user_id,
            ]);

            // Ensure paid_amount and payment_status on expense model are fully paid
            $expense->update([
                'paid_amount'    => $expense->amount,
                'payment_status' => 'lunas',
            ]);

            $count++;
        }

        $this->info("Successfully populated expense_payments for {$count} expenses.");
        return 0;
    }
}
