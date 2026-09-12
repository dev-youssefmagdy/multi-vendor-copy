<div class="translation-row-field" data-translation-key="{{ $row['key'] }}">
    <textarea class="field-control" rows="2" data-translation-value @if($row['locked']) disabled @endif>{{ $row['value'] }}</textarea>
    <button type="button" class="btn btn-sm btn-secondary" data-translation-save @if($row['locked']) disabled @endif>Save</button>
</div>
