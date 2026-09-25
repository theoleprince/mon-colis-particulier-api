<?php

namespace App\Modules\Delivery\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Photo or video of the parcel taken by the sender. type: image | video.
 */
class DeliveryMedia extends Model
{
    protected $fillable = ['type', 'path'];
}
