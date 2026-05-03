@extends('website.Contract.layout.app')
    
  @section('title', 'المالك')
    
    @section('content')
    <h3 class="heading3-state">رحلتك الاجارية اصبحت أسهل</h3>
    <h1 class="heading1-state">البيانات المالية</h1>
    <p class="description-state">قم بتعبئة بياناتك بشكل صحيح</p>
    
    <div class="prices-container">
    
        <div class="price-line">
            <p class="label"> ضريبه </p>
            <p>
                <span>
                    @if(isset($totalPriceDetails['details']['Residential_contract_tax']))
                        {{ $totalPriceDetails['details']['Residential_contract_tax'] }}
                    @elseif(isset($totalPriceDetails['details']['Value_added_tax']))
                        {{ $totalPriceDetails['details']['Value_added_tax'] }}
                    @else
                        {{ 'لم يتم أضافة اي رسوم خدمات' }}
                    @endif
                </span> ريال سعودي
            </p>
        </div>
    
        <div class="price-line">
            <p class="label"> رسوم التطبيق </p>
            <p>
                <span>
                    {{ $settings->application_fees ?? '0' }}
                </span> ريال سعودي
            </p>
        </div>
    
        <div class="price-line">
            <p class="label">مدة العقد {{ $contractPeriod['period'] ?? 'لم يتم تحديد مدة العقد' }}</p>
            <p>
                <span>
                    {{ $contractPeriod['price'] ?? 'لم يتم تحديد مدة' }}
                </span> ريال سعودي
            </p>
        </div>
    
        @foreach ($Pricing as $Service)
        <div class="price-line">
            <p class="label">{{ $Service->name_trans }}</p>
            <p><span class="price-line-number">{{ $Service->price }}</span> {{ __('website.currencySar') }}</p>
        </div>
        @endforeach
    
        <div class="price-line">
            @if($contract->contract_type == 'commerical')
                <p class="label"> ضريبة العقد التجاري </p>
                <p>
                    <span>
                        {{ $settings->commercial_tax ?? '0' }}
                    </span> ريال سعودي
                </p>
            @else
                <p class="label"> ضريبة العقد السكني </p>
                <p>
                    <span>
                        {{ $settings->housing_tax ?? '0' }}
                    </span> ريال سعودي
                </p>
            @endif
        </div>

    
        <!-- Pricing with or without coupon -->
        @if (!$contractCouponUsage)
        <div class="price-line total">
            <p class="label total">الاجمالي</p>
            <p id="totalPriceHolder">
                <span id="totalPrice" class="price-line-number">{{ $totalPriceDetails['total_price'] }}</span> ريال سعودي
            </p>
        </div>
        @else
        <!-- Display the original price with a strike-through -->
        <div class="price-line total">
            <p class="label total">الاجمالي قبل الخصم</p>
            <p id="totalPriceHolder" style="text-decoration: line-through;">
                <span id="totalPrice" class="price-line-number">{{ $totalPriceDetails['total_price'] }}</span> ريال سعودي
            </p>
        </div>
    
        <!-- Display the discount amount -->
        <div class="price-line discount total">
            <p class="label discount">الخصم</p>
            <p>
                <span class="price-line-number">{{ $discountedPrice }}</span> ريال سعودي
            </p>
        </div>
    
        <!-- Display the price after applying the coupon -->
        <div class="price-line price-after-discount total">
            <p class="label price-after-discount">السعر بعد الخصم</p>
            <p>
                <span class="price-line-number">{{ $priceAfterCoupon }}</span> ريال سعودي
            </p>
        </div>
        @endif
    </div>
    
    @error('contract_ownership')
    <div class="error-message">{{ $message }}</div>
    @enderror
    
    <!-- Coupon Form -->
    @error('code_coupon')
    <div class="error-message">{{ $message }}</div>
    @enderror
    
    @if(!$contractCouponUsage)
    <form class="form-content-state" action="{{ route('getCoupon', $contract->uuid) }}" method="POST" id="couponForm">
        @csrf
        <div class="form-group-state invoice">
            <label for="discount-coupon" class="label-state">هل لديك كوبون خصم ؟</label>
            <input id="discount-coupon" class="input-state" type="text" name="code_coupon"
                   required placeholder="ادخل كوبون الخصم الآن..." />
            <button id="applyCoupon" type="submit">تطبيق</button>
        </div>
    </form>
    @endif
    @if($contract->real_units_id == null)
    <!-- Property Form -->
    <form class="form-content-state" action="{{ route('property.create', ['uuid' => $contract->uuid]) }}" method="POST">
        @csrf
        <div class="form-group-state invoice">
            <label for="property-name" class="label-state">
                هل تود حفظ بيانات العقار - حتى تستطيع انشاء العقود مستقبلا بسرعة وسهولة
            </label>
            <input id="property-name" class="input-state" type="text" name="name_real_estate"
                   required placeholder="اكتب اسم وصفي للعقار مثال (عمارة حي النزهة)" />
            <button id="saveProperty" type="submit">حفظ</button>
        </div>
    </form>
    @endif
    
          <!-- Final Form -->
    <form class="form-content-state" id="finalForm">
        <div class="buttons-state">
            <!--<a type="button" href="#" class="back-button-state">عودة</a>-->
            <button type="submit" class="next-button-state">اتمام عملية الدفع 
                <img src="{{ asset('website/asset/images/white-left-arrow.svg') }}" alt="Next" />
            </button>
        </div>
    </form>
    
    <a class="help-text-state" href="https://wa.me/+966597500014">
        واجهتك مشكلة ؟كلمنا على واتساب
        <img src="{{ asset('website/asset/images/whatsapp-icon.svg') }}" alt="" />
    </a>
    <!-- Modal Part -->
    <!-- Order Modal -->
    <div class="overlay-order" id="overlay" style="display: none;"></div>
    <div class="order-modal-order" id="orderModal" style="display: none;">
        <div class="logo-holder-order">
            <img src="{{ asset('website/asset/images/logo.svg') }}" alt="" class="logo-order" />
        </div>
        <img src="{{ asset('website/asset/images/success-modal-icon.svg') }}" alt="" class="success-icon-order" />
        <h2>طلبكم رقم {{ $contract->uuid }}</h2>
        <p>قيد التنفيذ وسيعمل فريقنا على اتمامه وفي حال إتمام الطلب ستصلكم رسالة من 
            <img src="{{ asset('website/asset/images/ejar-icon.svg') }}" alt="" class="ejar-icon-order" />
            للتواصل واتس اب
        </p>
        
        <a class="whatsapp-order" href="https://wa.me/+966597500014">+966597500014 
            <img src="{{ asset('website/asset/images/whatsapp-icon.svg') }}" alt="" />
        </a>
         <p>
            {{ $settings->working_hours ?? '' }}
        </p>
        
        <a href="{{ route('payment.index', [$contract->uuid]) }}" class="confirm-order">اتمام الدفع 
            <img src="{{ asset('website/asset/images/white-left-arrow.svg') }}" alt="" />
        </a>
    </div>
    
    <script>
        // Get references to the form and modal elements
        const finalForm = document.getElementById('finalForm');
        const orderModal = document.getElementById('orderModal');
        const overlay = document.getElementById('overlay');
    
        // Add event listener to form submission
        finalForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent form submission for demonstration
    
            // Show the modal
            orderModal.style.display = 'block';
            overlay.style.display = 'block';
    
            // Optionally, you can add additional logic to handle actual form submission via AJAX or other means
        });
    
        // Close modal when overlay is clicked
        overlay.addEventListener('click', function() {
            orderModal.style.display = 'none';
            overlay.style.display = 'none';
        });
    </script>
    
@endsection
        <style>
            .price-line-number {
                font-weight: bold;
            }
            
            .price-line-number[style*="line-through"] {
                color: #a0a0a0;
            }
            
            .discount.total .price-line-number {
                color: #d9534f;
            }
            
            .final-price.total .price-line-number {
                color: #5cb85c;
            }
        
        </style>