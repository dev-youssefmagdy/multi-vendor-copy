<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class OrderAttachment extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tenant_id',
        'order_number',
        'path',
        'original_name',
        'mime_type',
        'extension',
        'size_bytes',
        'uploaded_by_admin_id',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'uploaded_by_admin_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int) $this->size_bytes;
        if ($bytes < 1024)
            return $bytes . ' B';
        if ($bytes < 1048576)
            return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
