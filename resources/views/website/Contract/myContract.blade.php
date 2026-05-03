@extends('website.auth.app')

@section('title', 'طلباتي')

@section('content')
    <!-- Breadcrumb Navigation -->
    <nav class="breadcrumb">
        <a href="{{ route('website.home') }}">الرئيسيه</a>
        <a href="{{ route('myContract') }}">طلباتي</a>
    </nav>

    <section class="orders">
        <!-- Search Inputs -->
        <form action="{{ route('search') }}" method="POST">
            @csrf
            <div class="search-inputs">
                <div class="search-box">
                    <label for="owner_id">البحث من خلال رقم هوية المالك *</label>
                    <div class="input-container">
                        <input
                            type="text"
                            id="owner_id"
                            name="owner_id"
                            value="{{ old('owner_id', request('owner_id')) }}"
                            placeholder="البحث من خلال رقم هوية المالك"
                        />
                        <button type="submit" class="search-button">
                            <i class="fa-solid fa-magnifying-glass search-icon"></i>
                        </button>
                    </div>
                </div>

                <div class="search-box">
                    <label for="tenant_id">البحث من خلال رقم هوية المستأجر *</label>
                    <div class="input-container">
                        <input
                            type="text"
                            id="tenant_id"
                            name="tenant_id"
                            value="{{ old('tenant_id', request('tenant_id')) }}"
                            placeholder="البحث من خلال رقم هوية المستأجر"
                        />
                        <button type="submit" class="search-button">
                            <i class="fa-solid fa-magnifying-glass search-icon"></i>
                        </button>
                    </div>
                </div>

                <div class="search-box">
                    <label for="request_number">البحث برقم الطلب *</label>
                    <div class="input-container">
                        <input
                            type="text"
                            id="request_number"
                            name="request_number"
                            value="{{ old('request_number', request('request_number')) }}"
                            placeholder="البحث برقم الطلب"
                        />
                        <button type="submit" class="search-button">
                            <i class="fa-solid fa-magnifying-glass search-icon"></i>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <h2 class="requests-heading">جميـــــع الطلبات</h2>

        <!-- Contract Boxes -->
        <div class="contract-boxes">
            @foreach ($myContracts as $MyContract)
                <div class="contract-box-order {{ $MyContract->is_completed ? 'completed' : 'uncompleted' }}">
                    <h4>عقد ايجار: <span>{{ $MyContract->uuid }}#</span></h4>
                    <div class="contract-header">
                        <div>
                            <p>رقم هوية المالك: <span>{{ $MyContract->property_owner_id_num }}</span></p>
                            <p>رقم هوية المستأجر: <span>{{ $MyContract->id_num_of_property_owner_agent }}</span></p>
                        </div>
                        <div class="contract-time">
                            <img src="{{ asset('website/asset/images/contract-time-icon.svg') }}" alt="Contract Time Icon" />
                            <span>{{ $MyContract->created_at->format('Y-m-d') }}</span>
                            <span>{{ $MyContract->created_at->format('H:i:s') }}</span>
                        </div>
                    </div>

                <div class="contract-footer">
                <p class="contract-state {{ $MyContract->is_completed ? 'completed' : 'uncompleted' }}">
                    حالة العقد: <span>{{ $MyContract->is_completed ? 'مكتمل' : 'غير مكتمل' }}</span>
                </p>
            
                @if ($MyContract->is_completed)
                    @if ($MyContract->file == null)
                     <a href="#"  class="complete-payment-btn" download target="_blank">
                        <img src="{{ asset('website/asset/images/contract-dollar-icon.svg') }}" alt="Contract Dollar Icon" />

                        العقد قيد الأرسال 
                        </a>

                    @else
                        @foreach ($files as $file)
                            @if ($file['contract_uuid'] == $MyContract->uuid)
                                <a href="{{ asset(str_replace('public/', '', $file['file'])) }}"
                                   class="complete-payment-btn" 
                                   download 
                                   target="_blank">
                                    <img src="{{ asset('website/asset/images/contract-dollar-icon.svg') }}" alt="Contract Dollar Icon" />
                                    اضغط لتنزيل العقد تم الدفع بنجاح
                                </a>
                            @endif
                        @endforeach
                    @endif
                @else
                    <a href="{{ route('CheckContract', ['uuid' => $MyContract->uuid]) }}" class="complete-payment-btn">
                        <img src="{{ asset('website/asset/images/contract-dollar-icon.svg') }}" alt="Contract Dollar Icon" />
                        اتمام دفع قيمة العقد
                    </a>
                @endif
            </div>

                </div>
            @endforeach
        </div>
    </section>

    <script src="{{ asset('website/asset/js/script.js') }}"></script>
@endsection

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha384-k6RqeWeci5ZR/Lv4MR0sA0FfDOMarh4VZnfN2dxlVb8W8N3jEtZB0sQY02yztHSS" crossorigin="anonymous">
