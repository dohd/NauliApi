<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use TransactionRelationship;

    /**
     * 
     */
    protected $table = 'transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [];

    /**
     * Dates
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * Guarded fields of model
     * @var array
     */
    protected $guarded = [
        'id'
    ];


    /**
     * model life cycle event listeners
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('owner_id', function ($builder) {
            if (isset(auth()->user()->owner_id))
            $builder->where('owner_id', auth()->user()->owner_id);
        });
    }

    /**
     * Accessor attributes
     */
    public function getDepositAttribute($value) {
        return +$value;
    }
    public function getCashoutAttribute($value) {
        return +$value;
    }
    public function getFeeAttribute($value) {
        return +$value;
    }
    public function getBalanceAttribute($value) {
        return +$value;
    }
    public function getNetBalanceAttribute($value) {
        return +$value;
    }
}
