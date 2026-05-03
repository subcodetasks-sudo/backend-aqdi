<?php

use App\Http\Controllers\FirebaseController;
use App\Http\Controllers\Website\AuthController;
use App\Http\Controllers\Website\ContractController;
use App\Http\Controllers\Website\ContractRealEstate;
use App\Http\Controllers\Website\CouponController;
use App\Http\Controllers\Website\GeneralController;
use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\website\LastCompelete;
use App\Http\Controllers\Website\PaymentController;
use App\Http\Controllers\Website\PropertyController;
use App\Http\Controllers\Website\RealEstateController;
use App\Http\Controllers\website\UnCompeleteController;
use App\Http\Controllers\Website\UnitEstateController;
use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Artisan;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get("sitemap.xml" , function () {
return \Illuminate\Support\Facades\Redirect::to('sitemap.xml');
 });


Route::get('/db', function () {
    // Cache the routes
    Artisan::call('route:cache');

    // Optionally clear route cache
    // Artisan::call('route:clear');
    
    // Optionally clear view cache
    // Artisan::call('view:clear');
    
    // Optionally clear application cache (if needed)
    // Artisan::call('cache:clear');
    
    return 'Success';
});


 
Route::get('greeting/{locale}', function ($locale) {

    if (!in_array($locale, ['en', 'ar'])) {
        abort(400);
    }

    localeSession($locale);

    return redirect()->back();
});

    Route::name('website.')->middleware('setLocale')->group(function() {

    Route::get('/ads', [HomeController::class, 'landing'])->name('landing');

    Route::get('/', [HomeController::class, 'home'])->name('home');
    Route::get('/qa', [HomeController::class, 'qa'])->name('qa');
    Route::get('/about-us', [HomeController::class, 'aboutUs'])->name('aboutUs');
    Route::get('/terms', [HomeController::class, 'terms'])->name('terms');
    Route::get('/social/media', [HomeController::class, 'media'])->name('media');
    Route::get('/overview', [HomeController::class, 'overview'])->name('overview');
    Route::get('/videos', [HomeController::class, 'videos']);
    Route::get('/blogs', [HomeController::class, 'blog'])->name('blog');
    Route::get('/blog/{slug}', [HomeController::class, 'singelblog'])->name('singelblog');

    // authentication pages
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'PostLogin'])->name('login.post');

    Route::get('/signup', [AuthController::class, 'signup'])->name('signup');
    Route::post('/PostSignup', [AuthController::class, 'PostSignup'])->name('PostSignup');

    Route::get('/send_code', [AuthController::class, 'sendcode'])->name('Sendcode');
    Route::post('/send_code', [AuthController::class, 'postSendCode'])->name('postSendCode');



    
    Route::get('/forget_password', [AuthController::class, 'forgotPassword'])->name('forgotPassword');
    Route::post('/forget_password', [AuthController::class, 'postForget'])->name('PostForget');
    Route::get('/new_password', [AuthController::class, 'newPassword'])->name('newPassword');
    
    Route::post('/reset_password', [AuthController::class, 'sendNewPassword'])->name('ResetPassword');

    

    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/verification', [AuthController::class, 'verification'])->name('verification');
    Route::post('/verification', [AuthController::class, 'sendVerification'])->name('sendVerification');
    Route::post('/resend-verification', [AuthController::class, 'resend'])->name('resendVerification');


 


  });

/************** Contract steps  **************/

 Route::group(['middleware' => ['LoginWebsite:web'],'controller' => ContractController::class], function () {
    // Route::get('contract/create/{uuid}', 'contractCreate')->name('contract.create');
    // Route::post('/contract/type/{uuid}', 'submitContractType')->name('submit.contract_type');
    
    Route::get('check/create', 'checkContract')->name('check.create');

    Route::get('contract/choose', 'chooseReal')->name('contract.choose');



    Route::get('contract/new', 'ContractNew')->name('contract.new');
    Route::post('/contract/new',  'ContractNew')->name('submit.new');
   
   
    Route::get('/work-paper/{uuid}',  'paperwork')->name('paperwork');
    Route::get('/pricing/{uuid}',  'pricing')->name('pricing');
    //step1
    Route::get('/contract/step1/{uuid}', 'Step1')->name('step1');
 
    Route::post('/contract/step1/{uuid}', 'submitStep1')->name('submit.step1');
    //step2
    Route::get('/contract/step2/{uuid}',  'PageStep2')->name('step2');
    Route::post('/contract/step2/{uuid}', 'submitStep2')->name('submit.step2');
    //step3
    Route::get('/contract/step3/{uuid}',  'contract_step3')->name('contract.step3');
    Route::post('/contract/step3/{uuid}',  'submitStep3')->name('submit.step3');
    //step4
    Route::get('/contract/step4/{uuid}',  'contract_step4')->name('contract.step4');
    Route::post('/contract/step4/{uuid}',  'submitStep4')->name('submit.step4');

    Route::get('/contract/step5/{uuid}',  'contract_step5')->name('contract.step5');
    Route::post('/contract/step5/{uuid}',  'submitStep5')->name('submit.step5');

    Route::get('/contract/step6/{uuid}',  'contract_step6')->name('contract.step6');
    Route::post('/contract/step6/{uuid}',  'submitStep6')->name('submit.step6');

    Route::get('financial_statements/{uuid}','Financial')->name('Financial');
 
    
    
    Route::get('/fetch-bank-info','Financial')->name('fetch-bank-info');
    Route::get('/get-cities',  'getCities');
    Route::get('/get-cities-tenant',  'getCitiesTenant');

   
    Route::get('/choose/unit/{uuid}',  'submitUnit')->name('submitUnit');
  


});

Route::group(['middleware' => ['LoginWebsite:web','setLocale'],'controller' => CouponController::class], function () {

    Route::get('coupon/{uuid}',  'index')->name('coupon');
    Route::post('getCoupon/{uuid}',  'getCoupon')->name('getCoupon');
    
});

// ContractRealEstate

Route::group(['middleware' => ['LoginWebsite:web','setLocale'],'controller' => ContractRealEstate::class], function () {
   
    Route::get('/real/unit','unitReal')->name('unit.real');
    Route::post('/contract/unit/{id}','createContractUnit')->name('contract.unit.real');

    
    Route::get('/real/units/{uuid}/{real_id}','unitRealContract')->name('real.units');
    
     Route::post('/submit/units/{uuid}/{real_id}/{id}','submitUnits')->name('submit.units');

    Route::get('unit/{uuid}/{real_id}','units')->name('unit.index');
    
    // Route::post('/submit/real/{uuid}/{id}/{real_id}',  'submitReal')->name('submit.real');

   
    Route::get('contract/create/real/{real_id}', 'contractCreate')->name('contract.create.real');
    Route::get('contract/type/{real_id}','submitContractType')->name('submit.contract_type');
    
    Route::get('/contract/real/pricing/{uuid}/{real_id}/{id}', 'pricing')->name('real.pricing');
    Route::get('/work-paper/real/{uuid}/{real_id}/{id}',  'paperwork')->name('real.paperwork');

   
    
    Route::get('/contract/real/step1/{uuid}/{real_id}/{id}', 'RealStep1')->name('real.step1');
    Route::post('/contract/real/step1/{uuid}/{real_id}/{id}', 'submitStep1')->name('real.submit.step1');

    Route::get('/contract/real/step2/{uuid}/{real_id}/{id}', 'PageStep2')->name('real.step2');
    Route::post('/contract/real/step2/{uuid}/{real_id}/{id}', 'submitStep2')->name('real.submit.step2');

     //step3
     Route::get('/contract/step3/real/{uuid}/{real_id}/{id}',  'contract_step3')->name('real.contract.step3');
     Route::post('/contract/step3/real/{uuid}/{real_id}/{id}',  'submitStep3')->name('real.submit.step3');

     Route::get('/contract/real/step4/{uuid}/{real_id}/{id}',  'contract_step4')->name('real.contract.step4');
     Route::post('/contract/real/step4/{uuid}/{real_id}/{id}',  'submitStep4')->name('real.submit.step4');
     
     Route::get('/contract/real/step5/{uuid}/{real_id}/{id}',  'contract_step5')->name('real.contract.step5');
     Route::post('/contract/real/step5/{uuid}/{real_id}/{id}',  'submitRealStep5')->name('real.submit.step5');

     Route::get('/contract/real/step6/{uuid}/{real_id}/{id}',  'contract_step6')->name('real.contract.step6');
     Route::post('/contract/real/step6/{uuid}/{real_id}/{id}',  'submitStep6')->name('real.submit.step6');
     
     Route::get('financial_statements/real/{uuid}/{real_id}/{id}','Financial')->name('real.Financial');
  
});



        Route::get('rating/{uuid}/{user_id}', [PaymentController::class, 'rating'])->name('rating');
        Route::post('rating/{uuid}/{user_id}', [PaymentController::class, 'PostRating'])->name('submit.rating');
        Route::post('updateCartByIPN/{uuid}', [PaymentController::class, 'updateCartByIPN'])->name('updateCartByIPN');
        Route::get('payment/{uuid}', [PaymentController::class, 'index'])->name('payment.index')->middleware('auth');
 


/************** Contract GeneralController [un-compelete , profile ,...]  **************/


    Route::group(['middleware' => ['LoginWebsite','setLocale']], function () {
    
    //get UnCompeleted Contract
    Route::get('contract/LastStep/{MyContract}', [GeneralController::class, 'LastStep'])->name('UnCompleted');
    Route::get('contract/last', [GeneralController::class, 'LastContract'])->name('last.contract.UnCompleted');
    //End UnCompeleted Contract
     //CheckContract
    Route::get('contract/CheckContract/{uuid}', [GeneralController::class, 'CheckContract'])->name('CheckContract');


       //get UnCompeleted Contract real 
       Route::get('contract/LastStep/{MyContract}/{uuid}/{real_id}/{unit_id}', [GeneralController::class, 'LastStep'])->name('UnCompleted.real');
       Route::get('real/contract/last/real', [GeneralController::class, 'LastContract'])->name('last.contract.UnCompleted.real');
       //End UnCompeleted Contract real

    Route::get('/download', [GeneralController::class, 'getDownload']);


    Route::get('profile/', [GeneralController::class, 'profile'])->name('profile');
    Route::put('/profile/update/{id}', [GeneralController::class, 'updateProfile'])->name('update.profile');
    Route::get('myContract', [GeneralController::class, 'myContract'])->name('myContract');
    Route::get('myContracttest', [GeneralController::class, 'myContracttest'])->name('myContracttest');
    Route::get('Contract/Files/{uuid}', [GeneralController::class, 'ContractFile'])->name('ContractFile');
    Route::post('Remove',[GeneralController::class,'RemoveProfile'])->name('RemoveProfile');

    Route::post('search',[GeneralController::class,'Search'])->name('search');
    Route::post('update/profile/photo',[GeneralController::class,'updateProfileImage'])->name('update.profile.photo');

/************** Real Estate **************/


    Route::group(['middleware' => ['LoginWebsite:web','setLocale'],'controller' => RealEstateController::class], function () {
       
        Route::get('/real-estate',  'index')->name('realEstate');
         Route::post('/new/realEstate',  'NewRealEstate')->name('create.new.realEstate');
         Route::get('/step1/realEstate/{id}',  'createStepOneReal')->name('create.step1.realEstate');
         Route::post('step1/realEstate/{id}',  'storeStepOne')->name('storeStepOne.realEstate');
         Route::get('/step2/realEstate/{id}',  'stepTwo')->name('step2.realEstate');
         Route::post('/step2/realEstate/{id}',  'stepTwoStore')->name('create.step2.realEstate');
         
         Route::get('/step3/realEstate/{id}',  'stepThree')->name('createStep3.realEstate');
         Route::post('/step3/realEstate/{id}',  'stepThreeStore')->name('storeStep3.realEstate');

         Route::get('/end/realEstate/{id}',  'endForm')->name('endForm.realEstate');
         Route::get('show/realEstate/{id}',  'show')->name('realestate.show');
         
         Route::get('edit/realEstate/step1/{id}',  'editStepOne')->name('realestate.step1.edit');
         Route::get('edit/realEstate/step2/{id}',  'editStepThree')->name('realestate.step2.edit');
         Route::get('edit/realEstate/step3/{id}',  'editStepTwo')->name('realestate.step3.edit');
         
         Route::post('edit/realEstate/step1/{id}',  'updateStepOne')->name('realestate.step1.update');
         Route::post('edit/realEstate/step2/{id}',  'updateStepTwo')->name('realestate.step2.update');
         Route::post('edit/realEstate/step3/{id}',  'updateStepThree')->name('realestate.step3.update');


         Route::post('delete/realEstate/{id}',  'delete')->name('realestate.delete');

        
    });
   
    Route::group(['middleware' => ['LoginWebsite:web','setLocale'],'controller' => UnitEstateController::class], function () {

        Route::get('/add/Unit/{id}',  'createUnit')->name('create.realUnit');
        Route::post('/add/Unit/{id}',  'storeUnit')->name('store.Unit');
        Route::get('/unit/{id}/','unit')->name('unit');
        Route::get('show/unit/{id}',  'show')->name('unit.show');
        Route::get('edit/unit/{id}',  'edit')->name('unit.edit');
        Route::put('edit/unit/{id}',  'updateStepOne')->name('unit.update');
        Route::post('delete/unit/{id}',  'destroy')->name('unit.delete');
        
     });
     Route::post('property/create/{uuid}',  [PropertyController::class,'property'])->name('property.create');

// });
});

   