<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherCode extends Model
{
    //

    protected $fillable = [ 'code', 'expiration_date', 'used' ];

    public function specialOffer(){
        return $this->belongsTo(SpecialOffer::class, 'special_offer_id');
    }

    public function recipient(){
        return $this->belongsTo(Recipient::class, 'recipient_id');
    }

}
