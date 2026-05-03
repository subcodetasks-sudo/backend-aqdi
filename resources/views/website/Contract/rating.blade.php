@extends('website._layout.app')
 
 @section('content')
        <!-- start login section  -->
        <section class="InstrumentData">
            <div class="container">
                <div class="row mt-5">
                    <!-- progress -->
                    <div class="col-11 col-md-8 m-auto">
                        <div class="progressStep4"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-11 col-md-6 col-lg-5 m-auto mt-5">
                        <img src="asset/image/data/Online Review-rafiki 1.png" class="img-fluid" alt="">
                    </div>
                </div>
                @php
                 $user_id = Auth::user()->id;
                @endphp

                <div class="row mt-4">
                    <div class="col m-auto">
                        <form action="{{ route('submit.rating', ['uuid' => $contract->uuid, 'user_id' => $user_id]) }}" method="POST">
                            @csrf
                            <div class="rating">
                                <input value="5" name="rating" id="star5" type="radio">
                                <label for="star5"></label>
                                <input value="4" name="rating" id="star4" type="radio">
                                <label for="star4"></label>
                                <input value="3" name="rating" id="star3" type="radio">
                                <label for="star3"></label>
                                <input value="2" name="rating" id="star2" type="radio">
                                <label for="star2"></label>
                                <input value="1" name="rating" id="star1" type="radio">
                                <label for="star1"></label>
                            </div>
                            <div class="row messageBox mt-4">
                                <div class="col-11 col-md-6 col-lg-5 m-auto">
                                    <textarea name="rating_note" id="message" placeholder="    {{__('website.message')}}"></textarea>
                                </div>
                            </div>
                            <div class="row messageBox2 mt-4">
                                <div class="col-11 col-md-6 col-lg-5 m-auto">
                                    <button type="submit">{{__('website.rating_now')}} </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
        <!-- end section login -->

    </main>
    <!-- end main -->



    <!-- Modal  clarification -->
    <!-- Modal -->
     
    <!-- aos Js library -->
    <script src="asset/js/aos.js"></script>
    <!-- Bootstrap JS -->
    <script src="asset/js/bootstrap.min.js"></script>
    <!-- Script JS -->
    <script src="asset/js/index.js"></script>
 @endsection