@php
    $locationData = app(\App\Core\Location\CountryStateData::class);
    $countryOptions = $locationData->countries();
    $selectedCountry = (string) ($selectedCountry ?? '');
    $selectedIsKnown = in_array($selectedCountry, $countryOptions, true);
@endphp
<option value="">— Select country —</option>
@if($selectedCountry !== '' && ! $selectedIsKnown)
    <option value="{{ $selectedCountry }}" selected>{{ $selectedCountry }}</option>
@endif
@foreach($countryOptions as $countryCode => $countryLabel)
    <option
        value="{{ $countryLabel }}"
        data-country-code="{{ $countryCode }}"
        @selected($selectedCountry === $countryLabel)
    >
        {{ $countryLabel }}
    </option>
@endforeach
