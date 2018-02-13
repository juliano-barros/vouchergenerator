
# Voucher Generator

This is an micro-service app to generate voucher codes for recipients and offers already stored.

## About project. <br>

It is made in: <br>
Lumen PHP Framework (5.6) <br>
Mysql <br>
Deployed on Heroku: https://vouchergenerator.herokuapp.com/api/ <br>
PHPUnit <br>

## Configuration test (Postman). <br>

You can make your tests through Postman, on project you can find postmanexamples directory  <br>
[Postmanexample directory](https://github.com/juliano-barros/vouchergenerator/tree/master/postmanexamples) <br>
Inside of this directory you will find 2 files: <br>
[VoucherGenerator.postman_collection.json](https://github.com/juliano-barros/vouchergenerator/blob/master/postmanexamples/VoucherGenerator.postman_collection.json) this file you must import on your postman on import collection. <br>
[VoucherGenerator.postman_environment.json](https://github.com/juliano-barros/vouchergenerator/blob/master/postmanexamples/VoucherGenerator.postman_environment.json) this second file you must import on your enviroment options. <br> <br>
The enviroment is set up for my Heroku deployment, so you can use them to reach out my micro-service app.

## Example to call outside of postman. <br>

There are 3 APIs to keep vouchers <br>

### First API <br>
url/api/voucher/generate <br> <br>
Must be called as POST verb <br>
This API you need pass offer id (specialOffer) and expiration date (expirationDate) formmated (yyyy-mm-dd) as json on body:<br>
Example: 
```javascript
{ 
      "specialOffer": "1", 
      "expirationDate" : "2018-02-13" 
 } 
```
It will return a status ok if it is finished without error <br>
Return: <br>
```javascript
{ 
      "status": "ok"
} 
```

### Second API <br>
url/api/voucher/use <br> <br>
Must be called as PATCH verb <br>
This API you need pass email(email) and voucher code(code) <br> 
Example: 
```javascript
{ 
       "email" : "recipientemail@dom.com", 
       "code":"ab12ad542adc23ca34d" 
}
```
It will return a percent of voucher <br>
Return: 
```javascript
{
      "percent":11 <br>
} 
```

### Third API <br>
url/api/voucher/{email} <br> <br>
Must be called as GET verb <br>
This API you need to pass email on URL to get all valid vouchers for this email. <br>

Example: url/api/voucher/recipient@gmail.com <br><br>
It will return all vouchers that belongs to e-mail with offer name <br>
Return : 
```javascript
[ 
   {
      "offerName": "Offer name",<br>
      "code": "03fd34dsr2344565dfds5y4drw67es58"<br>
   }
]
 ```

## Database:

List all data stored on Heroku Database

### Special offers stored

ID|Name   | Percent
--|-------|--------:
1|Black friday| 15.00%
11|Valentines day|20.00%


### Recipients stored

ID| Name      | E-mail
--|----------|----------
1|recipient1|recipient1@gmail.com
11|recipient2|recipient2@gmail.com
21|recipient3|recipient3@gmail.com
31|recipient4|recipient4@gmail.com




# Lumen PHP Framework

[![Build Status](https://travis-ci.org/laravel/lumen-framework.svg)](https://travis-ci.org/laravel/lumen-framework)
[![Total Downloads](https://poser.pugx.org/laravel/lumen-framework/d/total.svg)](https://packagist.org/packages/laravel/lumen-framework)
[![Latest Stable Version](https://poser.pugx.org/laravel/lumen-framework/v/stable.svg)](https://packagist.org/packages/laravel/lumen-framework)
[![Latest Unstable Version](https://poser.pugx.org/laravel/lumen-framework/v/unstable.svg)](https://packagist.org/packages/laravel/lumen-framework)
[![License](https://poser.pugx.org/laravel/lumen-framework/license.svg)](https://packagist.org/packages/laravel/lumen-framework)

Laravel Lumen is a stunningly fast PHP micro-framework for building web applications with expressive, elegant syntax. We believe development must be an enjoyable, creative experience to be truly fulfilling. Lumen attempts to take the pain out of development by easing common tasks used in the majority of web projects, such as routing, database abstraction, queueing, and caching.

## Official Documentation

Documentation for the framework can be found on the [Lumen website](http://lumen.laravel.com/docs).

## Security Vulnerabilities

If you discover a security vulnerability within Lumen, please send an e-mail to Taylor Otwell at taylor@laravel.com. All security vulnerabilities will be promptly addressed.

## License

The Lumen framework is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
