{{-- Header/Footer partial for elora theme --}}
{{-- Edit this file directly to change header/footer markup --}}
{{-- Tenant-specific data via tenant() helper only --}}
<!-- ===== FOOTER ===== -->
<style>
  @media (max-width: 639px) {
    body footer, body[data-page="index"] footer { padding-bottom: 104px !important; }
  }
</style>
<x-elora.footer variant="v1" :storeName="$storeName" :socialLinks="$socialLinks ?? null" :footerCopyright="$footerCopyright ?? null" />
@vite(['resources/css/elora/footer.css', 'resources/js/elora/footer.js'])
