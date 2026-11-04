import React from 'react';
import { AsYouType, getCountries, getCountryCallingCode, parsePhoneNumberFromString } from 'libphonenumber-js/max';
import { FormField } from './FormField';
import { Input } from './Input';

const names = new Intl.DisplayNames(['en'], { type: 'region' });
const countries = getCountries().sort((a, b) => names.of(a).localeCompare(names.of(b)));

export function PhoneInput({ country, onCountryChange, value, onChange, error }) {
    function formatPhone(input) {
        const international = input.trim().startsWith('+') ? parsePhoneNumberFromString(input) : null;
        const nationalNumber = international?.country === country
            ? international.nationalNumber
            : input.replace(/\D/g, '');

        onChange(new AsYouType(country).input(nationalNumber));
    }

    return <>
        <FormField name="phone_country" label="Country for phone number">
            <select className="ui-input" id="phone_country" autoComplete="country" value={country} onChange={(event) => onCountryChange(event.target.value)}>
                <option value="">Select your country</option>
                {countries.map((code) => <option key={code} value={code}>{names.of(code)} (+{getCountryCallingCode(code)})</option>)}
            </select>
        </FormField>
        <FormField name="phone_number" label="Phone number" error={error} hint={country ? 'Enter the number without the country code.' : 'Choose a country to enter your number.'}>
            <div className="ui-phone-input">
                {country && <span className="ui-phone-prefix">+{getCountryCallingCode(country)}</span>}
                <Input id="phone_number" type="tel" inputMode="tel" autoComplete="tel-national" disabled={!country} aria-invalid={Boolean(error)} value={value} onChange={(event) => formatPhone(event.target.value)} />
            </div>
        </FormField>
    </>;
}
