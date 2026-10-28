enum TenantStatus {
  active,
  suspended,
  notFound;

  static TenantStatus parse(Object? v) {
    switch ('$v'.toLowerCase()) {
      case 'active':
        return TenantStatus.active;
      case 'suspended':
        return TenantStatus.suspended;
      default:
        return TenantStatus.notFound;
    }
  }
}

class Tenant {
  const Tenant({
    required this.id,
    required this.domain,
    required this.name,
    this.logo,
    this.status = TenantStatus.active,
    this.apiBase,
  });

  final String id;
  final String domain;
  final String name;
  final String? logo;
  final TenantStatus status;

  /// Informational only; requests always use the platform API origin.
  final String? apiBase;

  factory Tenant.fromJson(Map<String, dynamic> j) => Tenant(
        id: '${j['tenant_id'] ?? j['id']}',
        domain: '${j['domain'] ?? ''}'.toLowerCase(),
        name: '${j['name'] ?? j['domain'] ?? ''}',
        logo: j['logo'] as String?,
        status: TenantStatus.parse(j['status'] ?? 'active'),
        apiBase: j['api_base'] as String?,
      );

  Map<String, dynamic> toJson() => {
        'tenant_id': id,
        'domain': domain,
        'name': name,
        'logo': logo,
        'status': status.name,
        'api_base': apiBase,
      };
}

/// Result of `GET /resolve`.
class ResolveResult {
  const ResolveResult(this.status, [this.tenant]);
  final TenantStatus status;
  final Tenant? tenant;

  factory ResolveResult.fromJson(Map<String, dynamic> j) {
    final status = TenantStatus.parse(j['status']);
    final hasId = (j['tenant_id'] ?? j['id']) != null;
    return ResolveResult(
        status, status != TenantStatus.notFound && hasId ? Tenant.fromJson(j) : null);
  }
}

class HomeSection {
  const HomeSection(
      {required this.key, this.widget, this.source, this.dataEndpoint});
  final String key;
  final String? widget;
  final String? source;
  final String? dataEndpoint;

  factory HomeSection.fromJson(Map<String, dynamic> j) => HomeSection(
        key: '${j['key']}',
        widget: j['widget'] as String?,
        source: j['source'] as String?,
        dataEndpoint: j['data_endpoint'] as String?,
      );
  Map<String, dynamic> toJson() => {
        'key': key,
        'widget': widget,
        'source': source,
        'data_endpoint': dataEndpoint,
      };
}

class LocaleInfo {
  const LocaleInfo(
      {required this.code, this.name, this.rtl = false, this.isDefault = false});
  final String code;
  final String? name;
  final bool rtl;
  final bool isDefault;
  factory LocaleInfo.fromJson(Map<String, dynamic> j) => LocaleInfo(
      code: '${j['code']}',
      name: j['name'] as String?,
      rtl: j['rtl'] == true,
      isDefault: j['default'] == true);
  Map<String, dynamic> toJson() =>
      {'code': code, 'name': name, 'rtl': rtl, 'default': isDefault};
}

class CurrencyInfo {
  const CurrencyInfo(
      {required this.code, this.symbol, this.rate = 1, this.isDefault = false});
  final String code;
  final String? symbol;
  final double rate;
  final bool isDefault;
  factory CurrencyInfo.fromJson(Map<String, dynamic> j) => CurrencyInfo(
      code: '${j['code']}',
      symbol: j['symbol'] as String?,
      rate: (j['rate'] as num?)?.toDouble() ?? 1,
      isDefault: j['default'] == true);
  Map<String, dynamic> toJson() => {
        'code': code,
        'symbol': symbol,
        'rate': rate,
        'default': isDefault
      };
}

/// Parsed `GET /bootstrap` `data` (real backend shape; tolerant of gaps).
class TenantConfig {
  const TenantConfig({
    this.tenantName,
    this.appName,
    this.themeSlug = 'default',
    this.variantKey = 'default',
    this.variantName,
    this.colors = const {},
    this.sections = const [],
    this.allowedSectionKeys = const {},
    this.logoUrl,
    this.splashImageUrl,
    this.socialLinks = const [],
    this.footer = const {},
    this.features = const {},
    this.settings = const {},
    this.locales = const [],
    this.currencies = const [],
    this.maintenance = false,
    this.updateRequired = false,
    this.minAppVersion,
    this.configVersion,
  });

  final String? tenantName;
  final String? appName;
  final String themeSlug;
  final String variantKey;
  final String? variantName;

  /// Normalised tokens: primary, secondary, bg_main, text_primary, ...
  final Map<String, String> colors;

  /// Ordered, visible, already filtered by the server.
  final List<HomeSection> sections;

  /// theme slug -> variant key -> allowed section keys.
  final Map<String, Map<String, List<String>>> allowedSectionKeys;
  final String? logoUrl;
  final String? splashImageUrl;
  final List<Map<String, dynamic>> socialLinks;
  final Map<String, dynamic> footer;
  final Map<String, dynamic> features;
  final Map<String, dynamic> settings;
  final List<LocaleInfo> locales;
  final List<CurrencyInfo> currencies;
  final bool maintenance;
  final bool updateRequired;
  final String? minAppVersion;
  final String? configVersion;

  String? get displayName => appName ?? tenantName;
  bool feature(String k, {bool fallback = false}) =>
      features[k] is bool ? features[k] as bool : fallback;

  static Map<String, dynamic> _map(Object? v) =>
      v is Map ? Map<String, dynamic>.from(v) : <String, dynamic>{};
  static List<Map<String, dynamic>> _maps(Object? v) =>
      [for (final e in (v as List? ?? const [])) if (e is Map) Map<String, dynamic>.from(e)];

  factory TenantConfig.fromJson(Map<String, dynamic> j) {
    final settings = _map(j['settings']);
    final theme = _map(j['theme']);
    final status = _map(j['status']);
    final branding = _map(j['branding']);
    final logo = branding['logo'];
    final splashUrl = branding['splash_image_url'];
    final brandName = branding['app_name'];
    final allowed = <String, Map<String, List<String>>>{};
    _map(j['allowed_section_keys']).forEach((slug, variants) {
      allowed[slug] = {
        for (final e in _map(variants).entries)
          e.key: [for (final k in (e.value as List? ?? const [])) '$k']
      };
    });
    final appName = (settings['app_name'] is String && (settings['app_name'] as String).isNotEmpty)
        ? settings['app_name']
        : brandName;
    return TenantConfig(
      tenantName: _map(j['tenant'])['name'] as String?,
      appName: appName is String && appName.isNotEmpty ? appName : null,
      themeSlug: '${theme['slug'] ?? 'default'}',
      variantKey: '${theme['variant_key'] ?? 'default'}',
      variantName: theme['variant_name'] as String?,
      colors: {
        for (final e in _map(theme['colors']).entries) e.key: '${e.value}'
      },
      sections: _maps(j['sections']).map(HomeSection.fromJson).toList(),
      allowedSectionKeys: allowed,
      logoUrl: logo is Map
          ? (logo['image_url'] ?? logo['url']) as String?
          : (logo is String ? logo : null),
      splashImageUrl: splashUrl is String && splashUrl.isNotEmpty ? splashUrl : null,
      socialLinks: _maps(j['social_links']),
      footer: _map(j['footer']),
      features: _map(j['features']),
      settings: settings,
      locales: _maps(j['locales']).map(LocaleInfo.fromJson).toList(),
      currencies: _maps(j['currencies']).map(CurrencyInfo.fromJson).toList(),
      maintenance: status['maintenance'] == true,
      updateRequired: status['update_required'] == true,
      minAppVersion: status['min_app_version']?.toString(),
      configVersion: j['config_version']?.toString(),
    );
  }

  Map<String, dynamic> toJson() => {
        'tenant': {'name': tenantName},
        'settings': {...settings, 'app_name': ?appName},
        'features': features,
        'theme': {
          'slug': themeSlug,
          'variant_key': variantKey,
          'variant_name': variantName,
          'colors': colors,
        },
        'sections': [for (final s in sections) s.toJson()],
        'allowed_section_keys': allowedSectionKeys,
        'branding': {
          'logo': {'image_url': logoUrl},
          'splash_image_url': splashImageUrl,
          'app_name': appName,
        },
        'social_links': socialLinks,
        'footer': footer,
        'locales': [for (final l in locales) l.toJson()],
        'currencies': [for (final c in currencies) c.toJson()],
        'status': {
          'maintenance': maintenance,
          'update_required': updateRequired,
          'min_app_version': minAppVersion,
        },
        'config_version': configVersion,
      };
}

class BootstrapResult {
  const BootstrapResult({this.config, this.etag, this.notModified = false});
  final TenantConfig? config;
  final String? etag;
  final bool notModified;
}
