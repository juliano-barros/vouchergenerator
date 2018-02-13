<?php
/**
 * Created by PhpStorm.
 * User: julia
 * Date: 2018-02-13
 * Time: 1:04 AM
 */

use App\Models\SpecialOffer;
use App\Models\VoucherCode;
use App\Models\Recipient;
use Carbon\Carbon;

class VoucherTest extends TestCase
{

    use \Laravel\Lumen\Testing\DatabaseTransactions, \Laravel\Lumen\Testing\DatabaseMigrations;

    /**
     * Testing generate vouchers without recipient
     *
     * @throws Exception
     */
    public function test_generatevouchers_without_recipient(){

        // Creating special offer
        $specialOffer = factory(SpecialOffer::class)->create();

        // Generating a date
        $date = Carbon::now()->addDays(rand(2,10));

        // Generating voucher
        $this->post('/api/voucher/generate', array("specialOffer" => $specialOffer->id, "expirationDate" => $date->format('Y-m-d') ) )
             ->seeStatusCode(200)
             ->assertEquals(0, VoucherCode::count(), 'Should be generate 0 vouchers');

    }

    /**
     * Testing generate vouchers with recipient
     *
     * @throws Exception
     */
    public function test_generatevouchers_with_recipient(){

        // Creating a special offer
        $specialOffer = factory(SpecialOffer::class)->create();

        // Creating recipients
        factory(Recipient::class, 5)->create();

        // Generating a date
        $date = Carbon::now()->addDays(rand(2,10));

        // Generating voucher
        $this->post('/api/voucher/generate', array("specialOffer" => $specialOffer->id, "expirationDate" => $date->format('Y-m-d') ) )
             ->seeStatusCode(200)
             ->assertEquals(5, VoucherCode::count(), 'Should be generate 5 vouchers');

    }


    /**
     * Testing using all vouchers genereted
     *
     * @throws Exception
     */
    public function test_use_all_vouchers(){

        // Creating a special offer
        $specialOffer = factory(SpecialOffer::class)->create();

        // Creating recipients
        $recipients = factory(Recipient::class, 5)->create();

        // Generating a date
        $date = Carbon::now()->addDays(rand(2,10));

        // Generating voucher
        $this->post('/api/voucher/generate', array("specialOffer" => $specialOffer->id, "expirationDate" => $date->format('Y-m-d') ) )
             ->seeStatusCode(200);


        // Loop to use vouchers
        foreach ( $recipients as $recipient){

            // Getting vouchers free for this recipient
            $response = $this->get('/api/voucher/' . $recipient->email )
                             ->seeStatusCode(200)->response;

            $responseJson = json_decode($response->getContent())[0];

            // Verifying if it is returning offername and code voucher
            self::assertTrue( $responseJson->offerName !== "", 'Shouldn\'t be empty');
            self::assertTrue( $responseJson->code !== "", 'Shouldn\'t be empty');

            // Verifying if it is json
            self::assertJson($response->getContent());

            // Getting first code,
            $code = $responseJson->code;

            // Using this voucher
            $this->patch('/api/voucher/use', array("email" => $recipient->email, "voucher" => $code ) )->response;

            // Should return 200
            self::seeStatusCode(200);

        }

        // With 5 recipients should generate 5 vouchers
        self::assertEquals(5, VoucherCode::count(), 'Should be generate 5 vouchers');

    }

    /**
     * Testing return code trying to use same code more than once
     *
     * @throws Exception
     */
    public function test_voucher_alreadyinuse(){

        // Creating a special offer
        $specialOffer = factory(SpecialOffer::class)->create();

        // Creating recipients
        $recipients = factory(Recipient::class, 5)->create();

        // Generating a date
        $date = Carbon::now()->addDays(rand(2,10));

        // Generating voucher
        $this->post('/api/voucher/generate', array("specialOffer" => $specialOffer->id, "expirationDate" => $date->format('Y-m-d') ) )
             ->seeStatusCode(200);


        foreach ( $recipients as $recipient){


            // Getting vouchers free for this recipient
            $response = $this->get('/api/voucher/' . $recipient->email )->seeStatusCode(200)->response->getContent();

            // Verifying if it is json
            self::assertJson($response);

            // Getting first code,
            $code = json_decode($response)[0]->code;

            // Using this voucher
            $response = $this->patch('/api/voucher/use', array("email" => $recipient->email, "voucher" => $code ) )
                             ->seeStatusCode(200)
                             ->response->getContent();


            // Verifying if it is json
            self::assertJson($response);

            // Getting first code,
            $percent = floatval(json_decode($response)->percent);

            // Checking if returned a valid percent
            self::assertTrue($percent > 0, $response);

            // Trying to get same code again
            $this->patch('/api/voucher/use', array("email" => $recipient->email, "voucher" => $code ) )
                 ->seeStatusCode(410);

        }

        // With 5 recipients should generate 5 vouchers
        self::assertEquals(5, VoucherCode::count(), 'Should be generate 5 vouchers');
    }

    /**
     * Cheking trying use code with recipient inexistent
     *
     * @throws Exception
     */
    public function test_vouchernotfound(){

        // Creating a special offer
        $specialOffer = factory(SpecialOffer::class)->create();

        // Creating recipients
        $recipients = factory(Recipient::class, 5)->create();

        // Generating a date
        $date = Carbon::now()->addDays(rand(2,10));

        // Generating voucher
        $this->post('/api/voucher/generate', array("specialOffer" => $specialOffer->id, "expirationDate" => $date->format('Y-m-d') ) )
             ->seeStatusCode(200);

        foreach ( $recipients as $recipient){


            // Getting vouchers free for this recipient, however it would get empty
            $response = $this->get('/api/voucher/' . $recipient->email .'notfound' )->seeStatusCode(200)->response->getContent();

            // Should be returned empty array string
            self::assertEquals('[]', $response);

        }

    }

    /**
     * Testing using all voucher from another recipient
     * This method also test if the code continue available
     *
     * @throws Exception
     */
    public function test_use_voucher_another_recipient(){

        // Creating a special offer
        $specialOffer = factory(SpecialOffer::class)->create();

        // Creating recipients
        $recipients = factory(Recipient::class, 2)->create();

        // Generating a date
        $date = Carbon::now()->addDays(rand(2,10));

        // Generating voucher
        $this->post('/api/voucher/generate', [
                                                    "specialOffer" => $specialOffer->id,
                                                    "expirationDate" => $date->format('Y-m-d')
                                                  ] )
             ->seeStatusCode(200)
             ->assertEquals(2, VoucherCode::count(), 'Should be generate 2 vouchers');


        // Getting voucher free for the first
        $response = $this->get('/api/voucher/' . $recipients[0]->email )
                         ->seeStatusCode(200)->response->getContent();

        // Verifying if it is json
        self::assertJson($response);

        // Getting first code,
        $code = json_decode($response)[0]->code;

        // Using the voucher from first recipient to second
        $this->patch('/api/voucher/use', array("email" => $recipients[1]->email, "voucher" => $code ) )->seeStatusCode(403);


        // Checking if codes continue available for users, because it didn't use before

        // Getting voucher free for the first
        $response = $this->get('/api/voucher/' . $recipients[0]->email )->seeStatusCode(200)->response->getContent();


        // Verifying if it is json
        self::assertJson($response);

        // Getting first code,
        $code = json_decode($response);

        // Should be 1 code
        self::assertEquals( 1, count($code), 'Should be 1 code');

        // Getting voucher free for the first
        $response = $this->get('/api/voucher/' . $recipients[1]->email )->seeStatusCode(200)->response->getContent();

        // Verifying if it is json
        self::assertJson($response);

        // Getting first code,
        $code = json_decode($response);

        // Should be 1 code
        self::assertEquals( 1, count($code), 'Should be 1 code');

    }

    /**
     * Testing more than one voucher generate, using one of them and checking if it is really used
     *
     * @throws Exception
     */
    public function test_more_than_one_generate(){

        // Creating a special offer
        $specialOffer = factory(SpecialOffer::class)->create();

        // Creating recipients
        $recipients = factory(Recipient::class, 2)->create();

        // Generating a date
        $date = Carbon::now()->addDays(rand(2,10));

        // Generating voucher
        $this->post('/api/voucher/generate', array("specialOffer" => $specialOffer->id, "expirationDate" => $date->format('Y-m-d') ) )
             ->seeStatusCode(200)
             ->assertEquals(2, VoucherCode::count(), 'Should be generate 2 vouchers');

        // Creating another special offer
        $specialOffer = factory(SpecialOffer::class)->create();

        // Generating a date
        $date = Carbon::now()->addDays(rand(2,10));

        // Generating voucher
        $this->post('/api/voucher/generate', array("specialOffer" => $specialOffer->id, "expirationDate" => $date->format('Y-m-d') ) )
             ->seeStatusCode(200)
             ->assertEquals(4, VoucherCode::count(), 'Should be generate 4 vouchers');

        foreach ($recipients as $recipient){

            // Getting vouchers free for this recipient
            $response = $this->get('/api/voucher/' . $recipient->email )->seeStatusCode(200)->response->getContent();

            // Verifying if it is json
            self::assertJson($response);

            // Getting all codes available,
            $code = json_decode($response);

            self::assertEquals( 2,count($code), 'Each recipient should be taken 2 codes available');

            // Using this voucher
            $this->patch('/api/voucher/use', array("email" => $recipient->email, "voucher" => $code[0]->code ) )->seeStatusCode(200);

            // Getting vouchers free for this recipient
            $response = $this->get('/api/voucher/' . $recipient->email )->seeStatusCode(200)->response->getContent();

            // Verifying if it is json
            self::assertJson($response);

            // Getting first code available,
            $code = json_decode($response);

            //Now this recipient has only one voucher available
            self::assertEquals( 1,count($code), 'Each recipient should be taken 1 codes available');

        }

    }

    /**
     * Verifying if the voucher is expired
     *
     * @throws Exception
     */
    public function test_voucher_expired(){

        // Creating a special offer
        $specialOffer = factory(SpecialOffer::class)->create();

        // Creating recipients
        $recipients = factory(Recipient::class, 2)->create();

        // Generating a date
        $date = Carbon::now()->addDays(rand(-10,-2));

        // Generating voucher
        $this->post('/api/voucher/generate', array("specialOffer" => $specialOffer->id, "expirationDate" => $date->format('Y-m-d') ) );

        foreach ($recipients as $recipient){

            // Getting vouchers free for this recipient
            $response = $this->get('/api/voucher/' . $recipient->email )->seeStatusCode(200)->response->getContent();

            // Verifying if it is json
            self::assertJson($response);

            // Getting all codes available,
            $code = json_decode($response);

            // Using this voucher
            $this->patch('/api/voucher/use', array("email" => $recipient->email, "voucher" => $code[0]->code ) )->seeStatusCode(409);

        }
    }

}