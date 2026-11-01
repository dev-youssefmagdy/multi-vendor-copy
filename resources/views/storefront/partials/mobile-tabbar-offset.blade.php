{{--
    Reserves room for a theme's fixed mobile bottom tab bar so it never covers the end of the page.
    $height = the tab bar's height in px.
--}}
<style>
  @media (max-width: 1023.98px) {
    body { padding-bottom: {{ (int) $height }}px; }
  }
</style>
