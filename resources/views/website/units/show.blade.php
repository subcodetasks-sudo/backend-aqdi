@extends('website._layout.app')

@section('active-link-terms', 'active')

@section('content')
    <!-- start main section  -->
    <section class="orderNow mb-0 mb-md-5">
        <div class="container mt-0 mt-lg-5 pt-0 pt-lg-5">
            <div class="text-center mb-4">
                <a href="{{ url('edit/unit', ['id' => $item->id]) }}">
                    <button class="btn btn-secondary btn-update-profile">تعديل الوحده</button>
                </a>
            </div>

            <div class="row justify-content-center">
                <div class="col-8" data-aos="flip-left" data-aos-easing="ease-out-cubic" data-aos-duration="2000">
                    <div class="card p-3 card-small">
                        <div class="card-body">
                            <div class="cardbg"></div>
                            <div class="cardContent d-flex flex-wrap justify-content-between">
                                <ul class="list-unstyled mr-5">
                                    <li data-aos="fade-up" class="mb-3">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <p>{{ __('website.unit_area') }}: {{ $item->unit_area ?? __('website.not_available') }}</p>
                                    </li>
                                    <li data-aos="fade-up" class="mb-3">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <p>{{ __('website.tootal_rooms') }}: {{ $item->total_rooms ?? __('website.not_available') }}</p>
                                    </li>
                                    <li data-aos="fade-up" class="mb-3">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <p>{{ __('website.electricity_meter_number') }}: {{ $item->electricity_meter_number ?? __('website.not_available') }}</p>
                                    </li>
                                    <li data-aos="fade-up" class="mb-3">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <p>{{ __('website.number_of_unit_air_conditioners') }}: {{ $item->number_of_unit_air_conditioners ?? __('website.not_available') }}</p>
                                    </li>
                                    <li data-aos="fade-up" class="mb-3">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <p>{{ __('website.The_number_of_the_toilet') }}: {{ $item->number_of_toilets ?? __('website.not_available') }}</p>
                                    </li>
                                    <li data-aos="fade-up" class="mb-3">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <p>{{ __('website.The_number_of_kitchens') }}: {{ $item->number_of_kitchens ?? __('website.not_available') }}</p>
                                    </li>
                                </ul>

                                <ul class="list-unstyled">
                                    <li data-aos="fade-up" class="mb-3">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <p>{{ __('website.The_number_of_halls') }}: {{ $item->number_of_halls ?? __('website.not_available') }}</p>
                                    </li>
                                    <li data-aos="fade-up" class="mb-3">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <p>{{ __('website.step3.unit_usage') }}: {{ $item->unitUsage ? $item->unitUsage->name_ar : __('website.not_available') }}</p>
                                    </li>
                                    <li data-aos="fade-up" class="mb-3">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <p>{{ __('website.step3.unit_type') }}: {{ $item->unitType ? $item->unitType->name_ar : __('website.not_available') }}</p>
                                    </li>
                                    <li data-aos="fade-up" class="mb-3">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <p>{{ __('website.floor_number') }}: {{ $item->floor_number ?? __('website.not_available') }}</p>
                                    </li>
                                    <li data-aos="fade-up" class="mb-3">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <p>{{ __('website.Number_parking_spaces') }}: {{ $item->number_parking_spaces ?? __('website.not_available') }}</p>
                                    </li>
                                    <li data-aos="fade-up" class="mb-3">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <p>{{ __('website.created_at') }}: {{ $item->created_at->format('Y-m-d') ?? __('website.not_available') }}</p>
                                    </li>
                                    <li data-aos="fade-up" class="mb-3">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <p>{{ __('website.number_of_unit_air_conditioners') }}: {{ $item->number_of_unit_air_conditioners ?? __('website.not_available') }}</p>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('styles')
    <!-- Add any additional styles here -->
@endsection
