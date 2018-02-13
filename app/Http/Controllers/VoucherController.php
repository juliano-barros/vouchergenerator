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

class VoucherController extends Controller{

    private $repository;

    /**
     * Getting a repository to work with
     *
     * VoucherController constructor.
     * @param VoucherRepository $repository
     */
    public function __construct( VoucherRepository $repository )
    {
        // Repository to persist data
        $this->repository = $repository;
    }


    /**
     * API to generate codes for all users
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function voucherGenerate(Request $request){

        $this->validate($request, [
            'specialOffer' => 'required',
            'expirationDate' => 'required'
        ]);

        $specialOffer = SpecialOffer::findOrFail($request->specialOffer);

        $expirationDate = Carbon::createFromFormat('Y-m-d', $request->expirationDate);

        $this->repository->voucherGenerate($specialOffer, $expirationDate);

        // If it is generated successfully, return status ok
        return response()->json(['status' => 'ok']);

    }

    /**
     * API to use a voucher code
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function voucherUse( Request $request ){

        // Validating request
        $this->validate($request, [
            'email' => 'required|email',
            'voucher' => 'required'
        ]);

        // Use voucher passed, repository already return a response
        return $this->repository->useVoucher($request->email, $request->voucher);
    }

    /**
     * Getting all vouchers for an email
     *
     * @param Request $request
     * @param $email
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function voucherRecipient(Request $request, $email){

        // Getting all voucher valids
        return response()->json($this->repository->voucherRecipient($email)->get());

    }

}