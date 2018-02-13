<?php
/**
 * Created by PhpStorm.
 * User: julia
 * Date: 2018-02-12
 * Time: 9:08 PM
 */

namespace App\Http\Controllers;


use App\Models\SpecialOffer;
use App\Repositories\VoucherRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class VoucherController extends Controller
{

    private $repository;

    /**
     * VoucherController constructor.
     * @param VoucherRepository $repository
     */
    public function __construct( VoucherRepository $repository )
    {
        $this->repository = $repository;
    }


    public function voucherGenerate(Request $request){

        $specialOffer = SpecialOffer::findOrFail($request->specialOffer);
        $dateExpiration = Carbon::createFromFormat('Y-m-d', $request->dateExpiration);
        $this->repository->voucherGenerate($specialOffer, $dateExpiration);

        return response(["status" => "ok"]);

    }

    public function voucherUse( Request $request ){

        if ( ! isset($request->email) || $request->email === ""  ){
            return response(["error"=>"Invalid Request"], 403);
        }

        if ( ! isset($request->voucher) || $request->voucher === ""  ){
            return response(["error"=>"Invalid Request"], 403);
        }

        return $this->repository->useVoucher($request->email, $request->voucher);
    }

    public function voucherRecipient(Request $request, $email){

        return response($this->repository->voucherRecipient($email)->get(), 200);

    }

}