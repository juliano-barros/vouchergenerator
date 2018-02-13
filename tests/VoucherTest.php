<?php
/**
 * Created by PhpStorm.
 * User: julia
 * Date: 2018-02-13
 * Time: 1:04 AM
 */

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
        $specialOffer = factory(App\Models\SpecialOffer::class)->create();

        // Generating voucher
        $response = $this->post('/api/vouchergenerator', array("specialOffer" => $specialOffer->id, "dateExpiration" => "2018-02-25" ) )->response;

        // With or without recipient should be returnd status 200
        self::assertTrue($response->getStatusCode() === 200, 'Status code from vouchergenerator should be 200');

        // Without recipient should be returned any voucher
        self::assertTrue(\App\Models\VoucherCode::count() === 0, 'Should be generate 0 vouchers');

    }

    /**
     * Testing generate vouchers with recipient
     *
     * @throws Exception
     */
    public function test_generatevouchers_with_recipient(){

        // Creating a special offer
        $specialOffer = factory(App\Models\SpecialOffer::class)->create();

        // Creating recipients
        factory(App\Models\Recipient::class, 5)->create();

        // Generating vouchers
        $response = $this->post('/api/vouchergenerator', array("specialOffer" => $specialOffer->id, "dateExpiration" => "2018-02-25" ) )->response;

        // After generate vouchers should be returned status code 200
        self::assertTrue($response->getStatusCode() === 200, 'Status code from vouchergenerator should be 200');

        // With 5 recipients should generate 5 vouchers
        self::assertTrue(\App\Models\VoucherCode::count() === 5, 'Should be generate 5 vouchers');

    }


    /**
     * Testing using all vouchers genereted
     *
     * @throws Exception
     */
    public function test_use_all_vouchers(){

        // Creating a special offer
        $specialOffer = factory(App\Models\SpecialOffer::class)->create();

        // Creating recipients
        $recipients = factory(App\Models\Recipient::class, 5)->create();

        // Generating vouchers
        $response = $this->post('/api/vouchergenerator', array("specialOffer" => $specialOffer->id, "dateExpiration" => "2018-02-25" ) )->response;

        self::assertTrue($response->getStatusCode() === 200, 'Status code from vouchergenerator should be 200');

        // Loop to use vouchers
        foreach ( $recipients as $recipient){

            // Getting vouchers free for this recipient
            $response = $this->get('/api/voucher/' . $recipient->email )->response;

            // Should be returned 200
            self::seeStatusCode(200);

            // Verifying if it is returning offername and code voucher
            self::assertTrue( json_decode($response->getContent())[0]->offerName !== "", 'Shouldn\'t be empty');
            self::assertTrue( json_decode($response->getContent())[0]->code !== "", 'Shouldn\'t be empty');

            // Verifying if it is json
            self::assertJson($response->getContent());

            // Getting first code,
            $code = json_decode($response->getContent())[0]->code;

            // Using this voucher
            $this->post('/api/voucheruse', array("email" => $recipient->email, "voucher" => $code ) )->response;

            // Should return 200
            self::seeStatusCode(200);

        }

        // With 5 recipients should generate 5 vouchers
        self::assertTrue(\App\Models\VoucherCode::count() === 5, 'Should be generate 5 vouchers');

    }

    /**
     * Testing return code trying to use same code more than once
     *
     * @throws Exception
     */
    public function test_voucher_alreadyinuse(){

        // Creating a special offer
        $specialOffer = factory(App\Models\SpecialOffer::class)->create();

        // Creating recipients
        $recipients = factory(App\Models\Recipient::class, 5)->create();

        // Generating vouchers
        $response = $this->post('/api/vouchergenerator', array("specialOffer" => $specialOffer->id, "dateExpiration" => "2018-02-25" ) )->response;

        // Should return true
        self::assertTrue($response->getStatusCode() === 200, 'Status code from vouchergenerator should be 200');

        foreach ( $recipients as $recipient){


            // Getting vouchers free for this recipient
            $response = $this->get('/api/voucher/' . $recipient->email )->response;

            // Should be returned 200
            self::seeStatusCode(200);

            // Verifying if it is json
            self::assertJson($response->getContent());

            // Getting first code,
            $code = json_decode($response->getContent())[0]->code;

            // Using this voucher
            $response = $this->post('/api/voucheruse', array("email" => $recipient->email, "voucher" => $code ) )->response;

            // Should return 200
            self::seeStatusCode(200);

            // Verifying if it is json
            self::assertJson($response->getContent());

            // Getting first code,
            $percent = floatval(json_decode($response->getContent())->percent);

            // Checking if returned a valid percent
            self::assertTrue($percent > 0, $response->getContent());

            // Trying to get same code again
            $this->post('/api/voucheruse', array("email" => $recipient->email, "voucher" => $code ) )->response;

            // Should return 410 because it is already in use
            self::seeStatusCode(410);

        }

        // With 5 recipients should generate 5 vouchers
        self::assertTrue(\App\Models\VoucherCode::count() === 5, 'Should be generate 5 vouchers');
    }

    /**
     * Cheking trying use code with recipient inexistent
     *
     * @throws Exception
     */
    public function test_vouchernotfound(){

        // Creating a special offer
        $specialOffer = factory(App\Models\SpecialOffer::class)->create();

        // Creating recipients
        $recipients = factory(App\Models\Recipient::class, 5)->create();

        // Generating vouchers
        $response = $this->post('/api/vouchergenerator', array("specialOffer" => $specialOffer->id, "dateExpiration" => "2018-02-25" ) )->response;

        self::assertTrue($response->getStatusCode() === 200, 'Status code from vouchergenerator should be 200');

        foreach ( $recipients as $recipient){


            // Getting vouchers free for this recipient, however it would get empty
            $response = $this->get('/api/voucher/' . $recipient->email .'notfound' )->response;

            // Should return 200
            self::seeStatusCode(200);

            // Should be returned empty array string
            self::assertTrue($response->getContent() === '[]');

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
        $specialOffer = factory(App\Models\SpecialOffer::class)->create();

        // Creating recipients
        $recipients = factory(App\Models\Recipient::class, 2)->create();

        // Generating vouchers
        $response = $this->post('/api/vouchergenerator', array("specialOffer" => $specialOffer->id, "dateExpiration" => "2018-02-25" ) )->response;

        // Should return 200
        self::assertTrue($response->getStatusCode() === 200, 'Status code from vouchergenerator should be 200');

        // With 2 recipients should generate 2 vouchers
        self::assertTrue(\App\Models\VoucherCode::count() === 2, 'Should be generate 2 vouchers');

        // Getting voucher free for the first
        $response = $this->get('/api/voucher/' . $recipients[0]->email )->response;

        // Should be returned 200
        self::seeStatusCode(200);

        // Verifying if it is json
        self::assertJson($response->getContent());

        // Getting first code,
        $code = json_decode($response->getContent())[0]->code;

        // Using the voucher from first recipient to second
        $this->post('/api/voucheruse', array("email" => $recipients[1]->email, "voucher" => $code ) )->response;

        // Should return 403
        self::seeStatusCode(403);

        // Checking if codes continue available for users, because it didn't use before

        // Getting voucher free for the first
        $response = $this->get('/api/voucher/' . $recipients[0]->email )->response;

        // Should be returned 200
        self::seeStatusCode(200);

        // Verifying if it is json
        self::assertJson($response->getContent());

        // Getting first code,
        $code = json_decode($response->getContent());

        // Should be 1 code
        self::assertTrue( count($code) === 1, 'Should be 1 code');

        // Getting voucher free for the first
        $response = $this->get('/api/voucher/' . $recipients[1]->email )->response;

        // Should be returned 200
        self::seeStatusCode(200);

        // Verifying if it is json
        self::assertJson($response->getContent());

        // Getting first code,
        $code = json_decode($response->getContent());

        // Should be 1 code
        self::assertTrue( count($code) === 1, 'Should be 1 code');

    }

    /**
     * Testing more than one voucher generate, using one of them and checking if it is really used
     *
     * @throws Exception
     */
    public function test_more_than_one_generate(){

        // Creating a special offer
        $specialOffer = factory(App\Models\SpecialOffer::class)->create();

        // Creating recipients
        $recipients = factory(App\Models\Recipient::class, 2)->create();

        // Generating vouchers
        $response = $this->post('/api/vouchergenerator', array("specialOffer" => $specialOffer->id, "dateExpiration" => "2018-02-25" ) )->response;

        // Should return 200
        self::assertTrue($response->getStatusCode() === 200, 'Status code from vouchergenerator should be 200');

        // With 2 recipients should generate 2 vouchers
        self::assertTrue(\App\Models\VoucherCode::count() === 2, 'Should be generate 2 vouchers');

        // Creating another special offer
        $specialOffer = factory(App\Models\SpecialOffer::class)->create();

        // Generating vouchers
        $response = $this->post('/api/vouchergenerator', array("specialOffer" => $specialOffer->id, "dateExpiration" => "2018-02-25" ) )->response;

        // Should return 200
        self::assertTrue($response->getStatusCode() === 200, 'Status code from vouchergenerator should be 200');

        // With 2 recipients should generate twice should be 4 vouchers
        self::assertTrue(\App\Models\VoucherCode::count() === 4, 'Should be generate 4 vouchers');

        foreach ($recipients as $recipient){

            // Getting vouchers free for this recipient
            $response = $this->get('/api/voucher/' . $recipient->email )->response;

            // Should be returned 200
            self::seeStatusCode(200);

            // Verifying if it is json
            self::assertJson($response->getContent());

            // Getting all codes available,
            $code = json_decode($response->getContent());

            self::assertTrue( count($code) == 2, 'Each recipient should be taken 2 codes available');

            // Using this voucher
            $response = $this->post('/api/voucheruse', array("email" => $recipient->email, "voucher" => $code[0]->code ) )->response;

            // Should return 200
            self::seeStatusCode(200);

            // Getting vouchers free for this recipient
            $response = $this->get('/api/voucher/' . $recipient->email )->response;

            // Should be returned 200
            self::seeStatusCode(200);

            // Verifying if it is json
            self::assertJson($response->getContent());

            // Getting first code available,
            $code = json_decode($response->getContent());

            //Now this recipient has only one voucher available
            self::assertTrue( count($code) == 1, 'Each recipient should be taken 1 codes available');

        }

    }

}