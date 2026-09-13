{{--
    Option list shared by the PTR's From and To end-user pickers.
--}}
<optgroup label="End Users &amp; Office Heads">
    @foreach($others as $person)
        <option value="{{ $person->id }}" data-custodian="0">
            {{ $person->person_accnt }}{{ $person->office_name ? ' - '.$person->office_name : '' }}
        </option>
    @endforeach
</optgroup>

