<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item_sn_references extends Model
{
    protected $table = 'item_sn_references';
    protected $fillable = [
        'item_id',
        'serial_number',
        'is_used'
    ];
    protected $primaryKey = 'id';
    protected $keyType = 'int';

    protected $casts = [
        'is_used' => 'boolean',
        'item_id' => 'int',
        'serial_number' => 'string'
    ];

    /**
     * @return BelongsTo<Item, $this>
     */
    public function Item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }
}
