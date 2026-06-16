<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ExpensesType extends Model
{
    protected $fillable = ['name', 'description', 'is_active'];
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function financialTransactions()
    {
        return $this->hasMany(FinancialTransaction::class, 'expenses_id');
    }
}
