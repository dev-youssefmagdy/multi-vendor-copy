@props(['model', 'value' => '', 'height' => 400])

@php
    $editorId = 'tme-' . preg_replace('/[^a-z0-9]/', '-', strtolower($model)) . '-' . substr(md5(uniqid()), 0, 6);
@endphp

<div wire:ignore>
    <textarea id="{{ $editorId }}"></textarea>
</div>

<script>
(function () {
    var editorId = '{{ $editorId }}';
    var model    = '{{ $model }}';
    var initialValue = @js($value);

    function getLivewireComponent(el) {
        var node = el ? el.parentElement : null;
        while (node) {
            if (node.hasAttribute && node.hasAttribute('wire:id')) {
                return Livewire.find(node.getAttribute('wire:id'));
            }
            node = node.parentElement;
        }
        return null;
    }

    function initEditor() {
        if (typeof window.tinymce === 'undefined') {
            setTimeout(initEditor, 100);
            return;
        }

        if (tinymce.get(editorId)) {
            return;
        }

        tinymce.init({
            selector: '#' + editorId,
            height: {{ $height }},
            menubar: 'file edit view insert format tools table',
            plugins: 'lists link image media table wordcount code fullscreen',
            toolbar: 'undo redo | styles | fontfamily fontsize | bold italic underline strikethrough | link image media table | bullist numlist | code fullscreen',
            branding: false,
            promotion: false,
            setup: function (editor) {
                editor.on('init', function () {
                    editor.setContent(initialValue);
                });
                editor.on('change input', function () {
                    var component = getLivewireComponent(document.getElementById(editorId));
                    if (component) {
                        component.set(model, editor.getContent(), true);
                    }
                });
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEditor);
    } else {
        initEditor();
    }
})();
</script>

@pushOnce('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
@endPushOnce
