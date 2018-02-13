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
     * Generating codes for all recipients
     *
     * @param SpecialOffer $specialOffer
     * @param Carbon $expirationDate
     */
    public function voucherGenerate(SpecialOffer $specialOffer, Carbon $expirationDate){

        // Getting all recipients
        $recipients = Recipient::all();

        // Generating all vouchers code
        foreach ($recipients as $recipient){
            $voucher = new VoucherCode();
            $voucher->recipient()->associate( $recipient );
            $voucher->specialOffer()->associate($specialOffer);
            $voucher->code = $this->getKey();
            $voucher->expiration_date = $expirationDate;
            $voucher->save();
        }

    }

    /**
     * Code generate
     *
     * @return string
     */
    private function getKey(){

        $date  = Carbon::now();

        return md5($date->toDateTimeString() . $date->micro . rand(1,255));
    }


    /**
     * Use an voucher for specific recipient
     *
     * @param string $email
     * @param string $voucher
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function useVoucher(string $email, string $voucher){

        $voucherCode = VoucherCode::where('code', '=', $voucher)->first();

        if ( $voucherCode && ( $voucherCode->recipient->email === $email) ){
            if ( $voucherCode->used ) {
                return response()->json(["error" => "voucher already in use"], 410);
            }else{
                $date = Carbon::now();
                if ( $date > $voucherCode->expiration_date){
                    return response()->json(["error" => "Voucher expired"], 409);
                }else{
                    $voucherCode->used = true;
                    $voucherCode->save();
                    return response()->json(["percent" => $voucherCode->specialOffer->percentage]);
                }

            }
        }else{
            return response( )->json(["error" => "Invalid voucher"], 403 );
        }
    }

    /**
     * Get all voucher codes valid for this email with its respective offer. (Valid it is when exists voucher for that email and it wasn't used yet)
     * @param string $email
     * @return mixed
     */
    public function voucherRecipient(string $email){

        $voucherCode = VoucherCode::select("special_offers.name as offerName", "voucher_codes.code")->
                        leftJoin( 'recipients', 'recipients.id', '=', 'voucher_codes.recipient_id')->
                        leftJoin( 'special_offers', 'special_offers.id', '=', 'voucher_codes.special_offer_id')->
                        where('recipients.email', '=', $email)->where('voucher_codes.used', '<>', '1');

        return $voucherCode;

    }

}