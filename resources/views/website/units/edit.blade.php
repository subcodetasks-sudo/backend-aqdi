<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>تعديل الوحدة</title>
    <link rel="icon" href="{{ asset('website/asset/images/logo.png') }}" type="image/svg+xml" />
    <link rel="stylesheet" href="{{ asset('website/asset/css/style.css') }}" />
    <!-- FontAwesome CDN Link -->
    <script src="https://kit.fontawesome.com/5ef60b71ad.js" crossorigin="anonymous"></script>
</head>

<body>
    <section class="real-state-form">
        <h1 class="heading1-state">تعديل بيانات الوحدة المؤجرة</h1>
        <p class="description-state">قم بتحديث بياناتك بشكل صحيح</p>
        <form class="form-content-state" action="{{ route('unit.update', ['id' => $unitReal->id]) }}" method="POST">
            @csrf
            @method('PUT') <!-- Handle PUT request for updating -->
            
            <div class="row-state">
                <div class="form-group-state">
                    <label for="unit-name" class="label-state">نوع الوحده</label>
                    <select id="unit-name" class="select-state" name="unit_type_id">
                        <option value="" disabled hidden>نوع الوحده</option>
                        @foreach ($unitType as $item)
                            <option value="{{ $item->id }}" {{ $unitReal->unit_type_id == $item->id ? 'selected' : '' }}>
                                {{ $item->name_trans }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group-state">
                    <label for="unit-usage" class="label-state">استخدام الوحده</label>
                    <select id="unit-usage" class="select-state" name="unit_usage_id">
                        @foreach ($unitUsage as $item)
                            <option value="{{ $item->id }}" {{ $unitReal->unit_usage_id == $item->id ? 'selected' : '' }}>
                                {{ $item->name_ar }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row-state">
                <div class="form-group-state">
                    <label for="unit-number" class="label-state">رقم الوحده</label>
                    <select id="unit-number" class="select-state" name="unit_number">
                        @for ($i = 1; $i <= 10; $i++)
                            <option value="{{ $i }}" {{ $unitReal->unit_number == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="form-group-state">
                    <label for="floor-number" class="label-state">رقم الطابق</label>
                    <select id="floor-number" class="select-state" name="floor_number">
                        @for ($i = 1; $i <= 10; $i++)
                            <option value="{{ $i }}" {{ $unitReal->floor_number == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <div class="form-group-state">
                <label for="unit-area" class="label-state">مساحة الوحدة الاجمالي تقريبا</label>
                <input type="text" id="unit-area" class="input-state" name="unit_area" value="{{ $unitReal->unit_area }}" placeholder="مساحة الوحدة الاجمالي تقريبا">
            </div>

            <div class="form-group-state apartment-dependent-none" style="{{ $unitReal->contract_type == 'apartment' ? 'display: none' : '' }}">
                <label for="subleasing" class="label-state">التأجير من الباطن</label>
                <select id="subleasing" class="select-state" name="sub_delay">
                    <option value="1" {{ $unitReal->sub_delay == 1 ? 'selected' : '' }}>نعم</option>
                    <option value="0" {{ $unitReal->sub_delay == 0 ? 'selected' : '' }}>لا</option>
                </select>
            </div>

            @if($Real->contract_type == 'housing')
                <div class="row-state apartment-dependent" style="{{ $unitReal->contract_type == 'apartment' ? 'display: flex' : 'display: none' }}">
                    <div class="form-group-state">
                        <label for="rooms-number" class="label-state">عدد الغرف</label>
                        <select id="rooms-number" class="select-state" name="tootal_rooms">
                            @for ($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ $unitReal->tootal_rooms == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group-state">
                        <label for="halls-number" class="label-state">عدد الصالات</label>
                        <select id="halls-number" class="select-state" name="The_number_of_halls">
                            @for ($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ $unitReal->The_number_of_halls == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="row-state apartment-dependent" style="{{ $unitReal->contract_type == 'apartment' ? 'display: flex' : 'display: none' }}">
                    <div class="form-group-state">
                        <label for="kitchens-number" class="label-state">عدد المطابخ</label>
                        <select id="kitchens-number" class="select-state" name="The_number_of_kitchens">
                            @for ($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ $unitReal->The_number_of_kitchens == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group-state">
                        <label for="bathrooms-number" class="label-state">عدد دورات المياه</label>
                        <select id="bathrooms-number" class="select-state" name="The_number_of_toilets">
                            @for ($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ $unitReal->The_number_of_toilets == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
            @endif

            <div class="row-state">
                <div class="form-group-state">
                    <label for="window-ac" class="label-state">مكيف شباك</label>
                    <select id="window-ac" class="select-state" name="window_ac">
                        @for ($i = 1; $i <= 10; $i++)
                            <option value="{{ $i }}" {{ $unitReal->window_ac == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="form-group-state">
                    <label for="split-ac" class="label-state">مكيف سبليت</label>
                    <select id="split-ac" class="select-state" name="split_ac">
                        @for ($i = 1; $i <= 10; $i++)
                            <option value="{{ $i }}" {{ $unitReal->split_ac == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <div class="row-state">
                <div class="form-group-state">
                    <label for="electricity-meter" class="label-state">رقم عداد الكهرباء (اختياري)</label>
                    <input id="electricity-meter" class="input-state" placeholder="رقم عداد الكهرباء (اختياري)" name="electricity_meter_number" value="{{ $unitReal->electricity_meter_number }}">
                </div>
                <div class="form-group-state">
                    <label for="water-meter" class="label-state">رقم عداد المياه (اختياري)</label>
                    <input id="water-meter" class="input-state" placeholder="رقم عداد المياه (اختياري)" name="water_meter_number" value="{{ $unitReal->water_meter_number }}">
                </div>
            </div>

            <center>
                <button type="submit" class="next-button-state">حفظ</button>
            </center>
        </form>
    </section>
</body>

</html>
