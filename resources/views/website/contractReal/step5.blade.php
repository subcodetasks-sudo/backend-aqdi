@extends('website.Contract.layout.app')
@section('title', 'الوجدة')
@section('content')

<h3 class="heading3-state">رحلتك الاجارية اصبحت أسهل</h3>
<h1 class="heading1-state">بيانات الوحدة المؤجره</h1>
<p class="description-state">قم بتعبئة بياناتك بشكل صحيح</p>

<form method="POST" action="{{ route('real.submit.step5', ['uuid' => $contract->uuid, 'real_id' => $real->id,$unit->id]) }}" class="form-content-state" id="owner-form">
    @csrf

    <div class="row-state">
        <div class="form-group-state">
            <label for="unit-type" class="label-state">نوع الوحدة</label>
            <select id="unit-type" class="select-state" required name="unit_type_id">
                <option value="" disabled selected>نوع الوحدة</option>
                @foreach ($unitType as $item)
                    <option value="{{ $item->id }}" @if(old('unit_type_id', $unit->unit_type_id) == $item->id) selected @endif>{{ $item->name_trans }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group-state">
            <label for="unit-usage" class="label-state">استخدام الوحدة</label>
            <select id="unit-usage" class="select-state" required name="unit_usage_id">
                <option value="" disabled selected>استخدام الوحدة</option>
                @foreach ($unitUsage as $item)
                    <option value="{{ $item->id }}" @if(old('unit_usage_id', $unit->unit_usage_id) == $item->id) selected @endif>{{ $item->name_ar }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row-state">
        <div class="form-group-state">
            <label for="unit-number" class="label-state">رقم الوحدة</label>
                 <select id="unit-number" class="select-state" required name="unit_number">
                <option value="" disabled selected>رقم الوحدة  </option>
                <!--<option value="0">لا يوجد</option>-->
                @for ($i = 1; $i <= 50; $i++)
                    <option value="{{ $i }}" {{ old('unit_number',$unit->unit_number) == $i ? 'selected' : '' }}>{{ $i }}</option>
                @endfor
            </select>        
            </div>
        <div class="form-group-state">
            <label for="floor-number" class="label-state">رقم الطابق</label>
            <select id="floor-number" class="select-state" required name="floor_number">
                <option value="" disabled selected>رقم الطابق</option>
                @for ($i = 1; $i <= 10; $i++)
                    <option value="{{ $i }}" @if(old('floor_number', $unit->floor_number) == $i) selected @endif>{{ $i }}</option>
                @endfor
            </select>
        </div>
    </div>

    <div class="form-group-state">
        <label for="unit-area" class="label-state">مساحة الوحدة الإجمالية تقريبا</label>
        <input id="unit-area" class="input-state" value="{{ old('unit_area', $unit->unit_area) }}" placeholder="مساحة الوحدة الإجمالية تقريبا" required name="unit_area">
    </div>
    @if($contract->contract_type=='housing')

    <div class="row-state">
        <div class="form-group-state">
            <label for="rooms-number" class="label-state">عدد الغرف</label>
            <select id="rooms-number" class="select-state"  name="tootal_rooms">
                <option value="0" @if(old('tootal_rooms', $contract->tootal_rooms) == 0) selected @endif>لا يوجد</option>

                <option value="" disabled selected>عدد الغرف</option>
                @for ($i = 1; $i <= 10; $i++)
                    <option value="{{ $i }}" @if(old('tootal_rooms', $unit->tootal_rooms) == $i) selected @endif>{{ $i }}</option>
                @endfor
            </select>
        </div>
        <div class="form-group-state">
            <label for="halls-number" class="label-state">عدد الصالات</label>
            <select id="halls-number" class="select-state"  name="The_number_of_halls">
            <option value="0" @if(old('The_number_of_halls', $contract->The_number_of_halls) == 0) selected @endif>لا يوجد</option>

                <option value="" disabled selected>عدد الصالات</option>
                @for ($i = 1; $i <= 10; $i++)
                    <option value="{{ $i }}" @if(old('The_number_of_halls', $unit->The_number_of_halls) == $i) selected @endif>{{ $i }}</option>
                @endfor
            </select>
        </div>
    </div>

    <div class="row-state">
        <div class="form-group-state">
            <label for="kitchens-number" class="label-state">عدد المطابخ</label>
            <select id="kitchens-number" class="select-state"  name="The_number_of_kitchens">
            <option value="0" @if(old('The_number_of_kitchens', $contract->The_number_of_kitchens) == 0) selected @endif>لا يوجد</option>
                <option value="" disabled selected>عدد المطابخ</option>
                @for ($i = 1; $i <= 10; $i++)
                     <option value="{{ $i }}" @if(old('The_number_of_kitchens', $unit->The_number_of_kitchens) == $i) selected @endif>{{ $i }}</option>
                
                    @endfor
            </select>
        </div>
        <div class="form-group-state">
            <label for="bathrooms-number" class="label-state">عدد دورات المياه</label>
            <select id="bathrooms-number" class="select-state"  name="The_number_of_toilets">
            <option value="0" @if(old('The_number_of_toilets', $contract->The_number_of_toilets) == 0) selected @endif>لا يوجد</option>
                <option value="" disabled selected>عدد دورات المياه</option>
                @for ($i = 1; $i <= 10; $i++)
                    <option value="{{ $i }}" 
                    <option value="{{ $i }}" @if(old('The_number_of_toilets', $unit->The_number_of_toilets) == $i) selected @endif>{{ $i }}</option>

                      
                        {{ $i }}
                    </option>
                @endfor
            </select>
        </div>
        
    </div>
    
@endif
    <div class="row-state">
        <div class="form-group-state">
            <label for="window-ac" class="label-state">مكيف شباك</label>
            <select id="window-ac" class="select-state" required name="window_ac">
                <option value="" disabled selected>اختر عدد مكيفات الشباك</option>
                <option value="0" @if(old('window_ac', $unit->window_ac) == 0) selected @endif>لا يوجد</option>
                @for ($i = 1; $i <= 10; $i++)
                    <option value="{{ $i }}" @if(old('window_ac', $unit->window_ac) == $i) selected @endif>{{ $i }}</option>
                @endfor
            </select>
        </div>
        <div class="form-group-state">
            <label for="split-ac" class="label-state">مكيف سبليت</label>
            <select id="split-ac" class="select-state" required name="split_ac">
                <option value="" disabled selected>اختر عدد مكيفات السبليت</option>
                <option value="0" @if(old('split_ac', $unit->split_ac) == 0) selected @endif>لا يوجد</option>
                @for ($i = 1; $i <= 10; $i++)
                    <option value="{{ $i }}" @if(old('split_ac', $unit->split_ac) == $i) selected @endif>{{ $i }}</option>
                @endfor
            </select>
        </div>
    </div>

    <div class="row-state">
        <div class="form-group-state">
            <label for="electricity-meter" class="label-state">رقم عداد الكهرباء (اختياري)</label>
            <input id="electricity-meter" placeholder="رقم عداد الكهرباء (اختياري)" value="{{ old('electricity_meter_number', $unit->electricity_meter_number) }}" class="input-state" name="electricity_meter_number">
        </div>
        <div class="form-group-state">
            <label for="water-meter" class="label-state">رقم عداد المياه (اختياري)</label>
            <input id="water-meter" placeholder="رقم عداد المياه (اختياري)" value="{{ old('water_meter_number', $unit->water_meter_number) }}" class="input-state" name="water_meter_number">
        </div>
    </div>

    <div class="buttons-state">
        <a href="{{ url()->previous() }}" class="back-button-state">عودة</a>
        <button type="submit" class="next-button-state">
            التالي
            <img src="{{ asset('website/asset/images/white-left-arrow.svg') }}" alt="Next">
        </button>
    </div>
</form>

<a class="help-text-state" href="https://wa.me/+966597500014">
    واجهتك مشكلة ؟كلمنا على واتساب
    <img src="{{ asset('website/asset/images/whatsapp-icon.svg') }}" alt="">
</a>

@endsection
