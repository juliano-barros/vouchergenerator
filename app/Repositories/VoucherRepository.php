<?php
/**
 * Created by PhpStorm.
 * User: julia
 * Date: 2018-02-12
 * Time: 9:11 PM
 */

namespace App\Repositories;


use App\Models\Recipient;
use App\Models\SpecialOffer;
use App\Models\VoucherCode;
use Illuminate\Support\Carbon;

class VoucherRepository
{

    /**
     * @param SpecialOffer $specialOffer
     * @param DateTime $dateExpiration
     */
    public function voucherGenerate(SpecialOffer $specialOffer, Carbon $dateExpiration){

        $recipients = Recipient::all();
        foreach ($recipients as $recipient){
            $voucher = new VoucherCode();
            $voucher->recipient_id= $recipient->id;
            $voucher->special_offer_id = $specialOffer->id;
            $voucher->code = $this->getKey();
            $voucher->expiration_date = $dateExpiration;
            $voucher->save();
        }

    }

    /**
     * @return string
     */
    private function getKey(){

        $date  = Carbon::now();

        return md5($date->toDateTimeString() . $date->micro . rand(1,255));
    }


    /**
     * @param string $email
     * @param string $voucher
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function useVoucher(string $email, string $voucher){

        $voucherCode = VoucherCode::where('code', '=', $voucher)->first();

        if ( $voucherCode && ( $voucherCode->recipient->email === $email) ){
            if ( $voucherCode->used ) {
                return response(["error" => "voucher already in use"], 410);
            }else{
                $voucherCode->used = true;
                $voucherCode->save();
                return response(["percent" => $voucherCode->specialOffer->percentage], 200);

            }
        }else{
            return response(["error" => "Invalid voucher"], 403 );
        }
    }

    public function voucherRecipient(string $email){

        $voucherCode = VoucherCode::select("special_offers.name as offerName", "voucher_codes.code")->
                        leftJoin( 'recipients', 'recipients.id', '=', 'voucher_codes.recipient_id')->
                        leftJoin( 'special_offers', 'special_offers.id', '=', 'voucher_codes.special_offer_id')->
                        where('recipients.email', '=', $email)->where('voucher_codes.used', '<>', '1');

        return $voucherCode;

    }

}