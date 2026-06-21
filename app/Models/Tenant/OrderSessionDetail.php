<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class OrderSessionDetail extends Model
{
    protected $table = 'order_session_details';

    protected $fillable = [
        'play_session_id',
        'device_id',
        'catalog_item_id',
        'price',
        'quantity',
        'total',
        'status',
        'order_by',
        'user_id',
        'notes',
    ];

    public function playSession()
    {
        return $this->belongsTo(PlaySessions::class);
    }

    public function device()
    {
        return $this->belongsTo(Devices::class);
    }

    public function catalogItem()
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
