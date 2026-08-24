@php
    $attribution = app(\App\Services\Marketing\AttributionService::class)->resolve(request());
    $values = $attribution->toArray();
@endphp
@foreach (\App\Support\Marketing\UtmAttribution::FIELD_NAMES as $field)
    <input type="hidden" name="{{ $field }}" value="{{ old($field, $values[$field] ?? request($field)) }}">
@endforeach
