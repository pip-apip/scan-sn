<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $table = 'items';
    protected $fillable = [
        'name',
        'project_id'
    ];
    protected $primaryKey = 'id';
    protected $keyType = 'int';

    /**
     * @return HasMany<Mapping_lists, $this>
     */
    public function Mapping_lists(): HasMany
    {
        return $this->hasMany(Mapping_lists::class, 'item_id', 'id');
    }

    /**
     * @return HasMany<Item_sn_references, $this>
     */
    public function Item_sn_references(): HasMany
    {
        return $this->hasMany(Item_sn_references::class, 'item_id', 'id');
    }
}
