<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasBilingualName;
use App\Traits\AutoCastTypes;

class Account extends Model
{
    use HasBilingualName, AutoCastTypes;

    protected $fillable = ['branch_id', 'parent_id', 'name', 'name_ar', 'name_en', 'code', 'type'];

    protected $casts = [
        'id' => 'integer',
        'branch_id' => 'integer',
        'parent_id' => 'integer',
        'type' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($account) {
            if (empty($account->code)) {
                $account->code = $account->generateCode();
            }
        });
    }

    /**
     * Generate account code automatically
     */
    public function generateCode()
    {
        if ($this->parent_id) {
            // Child account: Parent-XXX format
            $parent = Account::find($this->parent_id);
            $siblingCount = Account::where('parent_id', $this->parent_id)->count();
            $childNumber = str_pad($siblingCount + 1, 3, '0', STR_PAD_LEFT);
            return $parent->code . '-' . $childNumber;
        } else {
            // Root account: XXX format
            $rootCount = Account::whereNull('parent_id')->count();
            return str_pad($rootCount + 1, 3, '0', STR_PAD_LEFT);
        }
    }

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function getTypeNameAttribute()
    {
        return [
            1 => 'Asset',
            2 => 'Liability',
            3 => 'Equity',
            4 => 'Income',
            5 => 'Expense'
        ][$this->type] ?? 'Unknown';
    }
}
