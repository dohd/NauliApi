<?php

namespace App\Models;

trait TransactionRelationship
{
    public function deposit()
    {
        return $this->belongsTo(Deposit::class);
    }

    public function cashout()
    {
        return $this->belongsTo(Cashout::class);
    }
}