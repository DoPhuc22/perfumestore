<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Product extends Model
{
    use HasFactory;
public $timestamps = false;
    protected $fillable = ['product_name', 'quantity', 'price', 'description', 'image', 'category_id', 'season_id', 'size_id', 'gender_id', 'brand_id'];
    protected $table = 'products';
    public function scopeFilter($query, array $filters): void
    {
        if ($filters['search'] ?? false) {
            $query->where('product_name', 'like', '%' . request('search') . '%')
                ->orWhere('description', 'like', '%' . request('search') . '%');
        }
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function gender()
    {
        return $this->belongsTo(Gender::class);
    }

    public function size()
    {
        return $this->belongsTo(Size::class);
    }
}
