<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialOffer extends Model
{
    //
    protected $fillable = [ 'name', 'percentage' ];

    public function voucherCodes(){
        return $this->hasMany(VoucherCode::class, 'special_offer_id');
    }

}
