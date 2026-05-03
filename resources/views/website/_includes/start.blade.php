<!-- start contract type section  -->
<section class="typeContract">
    <div class="container">
        <div class="textHead mt-5">
            <h3 class="mb-4">{{ __('website.documented_contract') }}</h3>
            <p>{!! __('website.conclude_your_approved_contract_yourself') !!}</p>
        </div>
        <h3 class="mt-5 text-center fw-bold">{{ __('website.contract_type') }}</h3>
        <!-- form submit -->
        <form action="" method="POST" data-aos="fade-up" data-aos-duration="3000">
            <div class="row mt-5">
                <div class="col-12 col-md-6 text-center text-md-end ">
                    <div class="custom-radio pe-0 pe-md-5">
                        <!-- input radio -->
                        <input type="radio" name="contract" id="residential_contract">
                        <!-- label input -->
                        <label for="residential_contract" class="py-1 px-4">
                            <img src="{{ asset('website/asset/image/data/skny.png') }}" alt="residential_contract"
                                class="img-fluid pt-2">
                            <p class="text-center pt-3">{{ __('website.housing_contract') }}</p>
                        </label>
                    </div>
                </div>
                <div class="col-12 col-md-6 text-center text-md-start mt-5 mt-md-0">
                    <div class="custom-radio ps-0 ps-lg-5">
                        <!-- input radio -->
                        <input type="radio" name="contract" id="commercial_contract">
                        <!-- label input -->
                        <label for="commercial_contract" class="py-2 px-5">
                            <img src="{{ asset('website/asset/image/data/tgary.png') }}" alt="commercial_contract"
                                class="img-fluid pt-2">
                            <p class="text-center pt-2">{{ __('website.commercial_contract') }}</p>
                        </label>
                    </div>
                </div>
                <div class="col-8 col-sm-6 col-md-5 col-lg-3 m-auto mt-5">
                    <button type="submit" id="submit">{{ __('website.continue') }}</button>
                </div>
            </div>
        </form>
    </div>
</section>
<!-- end contract type login -->
