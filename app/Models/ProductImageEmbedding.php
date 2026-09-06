<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

// Image search v1 — expandable to vector DB (pgvector, Pinecone, etc.).
class ProductImageEmbedding extends Model
{
    use CentralConnection;

    protected $fillable = [
        'product_id',
        'variant_id',
        'source',
        'embedding',
        'dims',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
            'dims' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
