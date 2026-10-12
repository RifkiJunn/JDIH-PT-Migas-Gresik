<?php

namespace App\Models;

use App\Enums\JenisRelasi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRelation extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['jenis' => JenisRelasi::class];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function related(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'related_document_id');
    }
}
