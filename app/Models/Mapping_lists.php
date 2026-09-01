<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mapping_lists extends Model
{
    protected $table = 'mapping_lists';
    protected $fillable = [
        'name',
        'project_id',
        'region_id',
        'item_id',
        'serial_number',
        'image_path',
        'user_id'
    ];
    protected $primaryKey = 'id';
    protected $keyType = 'int';

    /**
     * @return HasMany<Item_sn_references, $this>
     */
    public function Item_sn_references(): HasMany
    {
        return $this->hasMany(Item_sn_references::class, 'item_id', 'id');
    }
}
