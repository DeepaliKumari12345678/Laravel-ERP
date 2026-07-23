<?php

namespace App\Core\Location;

use Illuminate\Support\Facades\Cache;
use Sokil\IsoCodes\IsoCodesFactory;
use Sokil\IsoCodes\TranslationDriver\DummyDriver;

class CountryStateData
{
    /**
     * @return array<string, string> ISO alpha-2 code => country name
     */
    public function countries(): array
    {
        return Cache::remember('erp.iso.countries', now()->addDay(), function () {
            $countries = [];

            foreach ($this->factory()->getCountries() as $country) {
                $countries[$country->getAlpha2()] = $country->getName();
            }

            asort($countries, SORT_NATURAL | SORT_FLAG_CASE);

            return $countries;
        });
    }

    /**
     * @return array<string, string> ISO subdivision code => state/province name
     */
    public function states(string $countryCode): array
    {
        $countryCode = strtoupper(trim($countryCode));

        if (! isset($this->countries()[$countryCode])) {
            return [];
        }

        return Cache::remember('erp.iso.states.'.$countryCode, now()->addDay(), function () use ($countryCode) {
            $states = [];
            $database = $this->factory()->getSubdivisions(IsoCodesFactory::OPTIMISATION_IO);

            foreach ($database->getAllByCountryCode($countryCode) as $state) {
                $states[$state->getCode()] = $state->getName();
            }

            asort($states, SORT_NATURAL | SORT_FLAG_CASE);

            return $states;
        });
    }

    public function countryCode(?string $countryName): ?string
    {
        $countryName = trim((string) $countryName);

        if ($countryName === '') {
            return null;
        }

        $code = array_search($countryName, $this->countries(), true);

        return $code === false ? null : $code;
    }

    public function isValidCountry(?string $countryName): bool
    {
        return $this->countryCode($countryName) !== null;
    }

    public function isValidState(?string $countryName, ?string $stateName): bool
    {
        $stateName = trim((string) $stateName);

        if ($stateName === '') {
            return true;
        }

        $countryCode = $this->countryCode($countryName);

        if ($countryCode === null) {
            return false;
        }

        $states = $this->states($countryCode);

        return $states === [] || in_array($stateName, $states, true);
    }

    /**
     * @return array<int, string|\Closure>
     */
    public function countryRules(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:100',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === null || $value === '') {
                    return;
                }

                if (! $this->isValidCountry((string) $value)) {
                    $fail('Please select a valid country.');
                }
            },
        ];
    }

    /**
     * @return array<int, string|\Closure>
     */
    public function stateRules(string $countryField = 'country'): array
    {
        return [
            'nullable',
            'string',
            'max:100',
            function (string $attribute, mixed $value, \Closure $fail) use ($countryField): void {
                if ($value === null || $value === '') {
                    return;
                }

                $country = request()->input($countryField);

                if (! $this->isValidState(is_string($country) ? $country : null, (string) $value)) {
                    $fail('Please select a valid state / province for the chosen country.');
                }
            },
        ];
    }

    protected function factory(): IsoCodesFactory
    {
        return new IsoCodesFactory(null, new DummyDriver);
    }
}
