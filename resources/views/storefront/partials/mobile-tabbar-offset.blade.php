{{--
    Reserves room for a theme's fixed mobile bottom tab bar so it never covers the end of the page.
    $height = the tab bar's height in px. Skipped while Laravel Debugbar is active: it already pads
    the <body> by its own toolbar height (stacking on top would double the gap in dev).
--}}
@if (!(app()->bound('debugbar') && app('debugbar')->isEnabled()))
<style>
  @media (max-width: 1023.98px) {
    body { padding-bottom: {{ (int) $height }}px; }
  }
</style>
@endif
