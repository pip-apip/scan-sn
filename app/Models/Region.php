<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    /** @use HasFactory<\Database\Factories\RegionFactory> */
    use HasFactory;

    protected $fillable = [
        'name_region',
        'category_region',
        'project_id'
    ];

    // @phpstan-ignore missingType.return
    public function mappingLists()
    {
        return $this->hasMany(Mapping_lists::class, 'region_id');
    }
}
