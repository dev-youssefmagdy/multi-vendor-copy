import java.util.Base64
import java.util.Properties

plugins {
    id("com.android.application")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
}

// ===== White-label tenants (see application/tenants/README.md, docs/mobile/WHITE_LABEL_BUILDS.md) =====
// One product flavor per enabled tenant in application/tenants/index.json (dimension "tenant").
// Full validation lives in tool/tenant_builder (`validate`); here we only re-check what Gradle itself
// interpolates, so a hand-edited manifest can never inject anything into the build.
data class TenantFlavor(
    val id: String,
    val type: String,
    val displayName: String,
    val applicationId: String,
    val urlScheme: String,
    val appLinkHost: String,
    val hasGeneratedIcon: Boolean,
)

val tenantsDir: File = rootProject.file("../tenants")
val tenantIdPattern = Regex("^[a-z][a-z0-9]{2,30}$")
val applicationIdPattern = Regex("^[a-zA-Z][a-zA-Z0-9_]*(\\.[a-zA-Z][a-zA-Z0-9_]*)+$")
val schemePattern = Regex("^[a-z][a-z0-9+.-]{1,40}$")
val hostPattern = Regex("^(\\*\\.)?([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\\.)+[a-z][a-z0-9-]{0,61}[a-z0-9]$")

fun tenantError(msg: String): Nothing =
    throw GradleException("tenants: $msg (run `dart run tool/tenant_builder/bin/tenant_builder.dart validate --all`)")

@Suppress("UNCHECKED_CAST")
fun readJson(f: File): Map<String, Any?> {
    if (!f.isFile) tenantError("missing ${f.path}")
    return groovy.json.JsonSlurper().parse(f) as Map<String, Any?>
}

@Suppress("UNCHECKED_CAST")
fun loadTenantFlavors(): List<TenantFlavor> {
    val index = readJson(File(tenantsDir, "index.json"))
    val platformDomain = index["platformDomain"] as? String ?: tenantError("index.json: platformDomain missing")
    if (!hostPattern.matches(platformDomain)) tenantError("index.json: invalid platformDomain '$platformDomain'")
    val entries = index["tenants"] as? List<Map<String, Any?>> ?: tenantError("index.json: tenants missing")
    return entries.filter { it["enabled"] == true }.mapNotNull { e ->
        val id = e["id"] as? String ?: tenantError("index.json: tenant without id")
        if (!tenantIdPattern.matches(id)) tenantError("invalid tenant id '$id'")
        val t = readJson(File(tenantsDir, "$id/tenant.json"))
        if (t["id"] != id) tenantError("$id/tenant.json: id does not match folder")
        val android = t["android"] as? Map<String, Any?> ?: tenantError("$id: android section missing")
        if (android["enabled"] != true) return@mapNotNull null
        val type = t["type"] as? String ?: tenantError("$id: type missing")
        val name = (t["displayName"] as? String)?.trim().orEmpty()
        if (name.isEmpty() || name.length > 30 || name.any { it < ' ' || it in "<>&\"\\" }) {
            tenantError("$id: invalid displayName")
        }
        val appId = android["applicationId"] as? String ?: ""
        if (!applicationIdPattern.matches(appId)) tenantError("$id: invalid android.applicationId '$appId'")
        val scheme = android["urlScheme"] as? String ?: ""
        if (!schemePattern.matches(scheme)) tenantError("$id: invalid android.urlScheme '$scheme'")
        val linkHost = when (type) {
            "platform" -> "*.$platformDomain"
            "store" -> (t["store"] as? Map<String, Any?>)?.get("host") as? String ?: ""
            else -> tenantError("$id: unknown type '$type'")
        }
        if (!hostPattern.matches(linkHost)) tenantError("$id: invalid store.host '$linkHost'")
        val branding = t["branding"] as? Map<String, Any?> ?: emptyMap()
        TenantFlavor(id, type, name, appId, scheme, linkHost, branding["appIcon"] != null)
    }.also { list ->
        if (list.isEmpty()) tenantError("no enabled Android tenant in index.json")
        list.groupBy { it.applicationId }.filter { it.value.size > 1 }.keys.firstOrNull()?.let {
            tenantError("duplicate android.applicationId '$it'")
        }
    }
}

val tenantFlavors = loadTenantFlavors()

// ===== Release signing (never the debug key) =====
// Per tenant, first match wins:
//  1. env ANDROID_SIGNING_TENANT=<id> + ANDROID_KEYSTORE_PATH, ANDROID_KEYSTORE_PASSWORD, ANDROID_KEY_ALIAS,
//     ANDROID_KEY_PASSWORD (CI decodes ANDROID_KEYSTORE_BASE64 into ANDROID_KEYSTORE_PATH). The tenant guard
//     stops one tenant's upload key from signing another tenant's app.
//  2. android/keystores/<id>.properties (gitignored): storeFile, storePassword, keyAlias, keyPassword.
//  3. platform tenant only: android/key.properties (Flutter convention, gitignored).
// Without credentials, release tasks of that flavor fail; debug/profile builds still use the debug key.
data class SigningSecrets(val storeFile: File, val storePassword: String, val keyAlias: String, val keyPassword: String)

fun signingFromProperties(f: File): SigningSecrets? {
    if (!f.isFile) return null
    val p = Properties().apply { f.inputStream().use { load(it) } }
    val store = p.getProperty("storeFile") ?: return null
    val storeFile = File(store).let { if (it.isAbsolute) it else File(f.parentFile, store) }
    return SigningSecrets(
        storeFile,
        p.getProperty("storePassword") ?: return null,
        p.getProperty("keyAlias") ?: return null,
        p.getProperty("keyPassword") ?: return null,
    )
}

fun signingFor(id: String): SigningSecrets? {
    val env = System.getenv()
    if (env["ANDROID_SIGNING_TENANT"] == id) {
        val path = env["ANDROID_KEYSTORE_PATH"]
        val sp = env["ANDROID_KEYSTORE_PASSWORD"]
        val alias = env["ANDROID_KEY_ALIAS"]
        val kp = env["ANDROID_KEY_PASSWORD"]
        if (!path.isNullOrEmpty() && !sp.isNullOrEmpty() && !alias.isNullOrEmpty() && !kp.isNullOrEmpty()) {
            return SigningSecrets(File(path), sp, alias, kp)
        }
    }
    signingFromProperties(rootProject.file("keystores/$id.properties"))?.let { return it }
    if (id == "platform") signingFromProperties(rootProject.file("key.properties"))?.let { return it }
    return null
}

val tenantSigning: Map<String, SigningSecrets?> = tenantFlavors.associate { it.id to signingFor(it.id) }

android {
    namespace = "com.example.application" // source package only (MainActivity.kt); NOT the store id
    compileSdk = flutter.compileSdkVersion
    ndkVersion = flutter.ndkVersion

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    buildFeatures {
        resValues = true
    }

    defaultConfig {
        // Identity comes from the tenant flavor below; these are not used by any flavored variant.
        applicationId = tenantFlavors.first().applicationId
        resValue("string", "app_name", "Store App")
        // You can update the following values to match your application needs.
        // For more information, see: https://flutter.dev/to/review-gradle-config.
        minSdk = flutter.minSdkVersion
        targetSdk = flutter.targetSdkVersion
        // Uses the version code from pubspec.yaml. When using split APKs, 1000 * ABI_VERSION
        // is added automatically by Flutter. (https://developer.android.com/studio/build/configure-apk-splits#configure-APK-versions)
        // You can force using the value of versionCode by specifying the `-P force-version-code-ignoring-abi=true`
        // flag during build.
        versionCode = flutter.versionCode
        versionName = flutter.versionName
    }

    signingConfigs {
        tenantSigning.forEach { (id, s) ->
            if (s != null) {
                create("tenant_$id") {
                    storeFile = s.storeFile
                    storePassword = s.storePassword
                    keyAlias = s.keyAlias
                    keyPassword = s.keyPassword
                }
            }
        }
    }

    flavorDimensions += "tenant"
    productFlavors {
        tenantFlavors.forEach { t ->
            create(t.id) {
                dimension = "tenant"
                applicationId = t.applicationId
                resValue("string", "app_name", t.displayName.replace("'", "\\'"))
                manifestPlaceholders["appLinkHost"] = t.appLinkHost
                manifestPlaceholders["urlScheme"] = t.urlScheme
                // Used by release only: the debug/profile build types keep their own debug signing config.
                if (tenantSigning[t.id] != null) signingConfig = signingConfigs.getByName("tenant_${t.id}")
            }
        }
    }

    buildTypes {
        release {
            // Deliberately no signingConfig here (it would override the tenant's). Never the debug key.
            signingConfig = null
        }
    }
}

// Fail fast for release builds without the tenant's own upload key, and for tenant builds whose Dart side
// was not bound to the same tenant (`--dart-define-from-file=build/tenant_config/<id>.json`).
fun dartDefines(): Map<String, String> {
    val raw = project.findProperty("dart-defines") as? String ?: return emptyMap()
    return raw.split(",").filter { it.isNotBlank() }.mapNotNull {
        val kv = runCatching { String(Base64.getDecoder().decode(it)) }.getOrNull() ?: return@mapNotNull null
        val i = kv.indexOf('=')
        if (i <= 0) null else kv.substring(0, i) to kv.substring(i + 1)
    }.toMap()
}

tenantFlavors.forEach { t ->
    val cap = t.id.replaceFirstChar { it.uppercase() }
    tasks.matching { it.name.startsWith("pre$cap") && it.name.endsWith("Build") }.configureEach {
        val isRelease = name == "pre${cap}ReleaseBuild"
        val signed = tenantSigning[t.id] != null
        val iconDir = file("src/${t.id}/res/mipmap-mdpi")
        doFirst {
            if (isRelease && !signed) {
                throw GradleException(
                    "No release signing for tenant '${t.id}'. Set ANDROID_SIGNING_TENANT=${t.id} with " +
                        "ANDROID_KEYSTORE_PATH/ANDROID_KEYSTORE_PASSWORD/ANDROID_KEY_ALIAS/ANDROID_KEY_PASSWORD, or create " +
                        "android/keystores/${t.id}.properties. Release builds are never signed with the debug key.",
                )
            }
            if (t.hasGeneratedIcon && !File(iconDir, "ic_launcher.png").isFile) {
                throw GradleException(
                    "Tenant '${t.id}' icons are not generated. Run: dart run tool/tenant_builder/bin/tenant_builder.dart generate --tenant ${t.id}",
                )
            }
            if (t.type == "store") {
                val d = dartDefines()
                if (d["TENANT_ID"] != t.id || d["TENANT_HOST"].isNullOrEmpty()) {
                    throw GradleException(
                        "Flavor '${t.id}' must be built with --dart-define-from-file=build/tenant_config/${t.id}.json " +
                            "(use tenant_builder run/build). Found TENANT_ID='${d["TENANT_ID"] ?: ""}'.",
                    )
                }
            }
        }
    }
}

kotlin {
    compilerOptions {
        jvmTarget = org.jetbrains.kotlin.gradle.dsl.JvmTarget.JVM_17
    }
}

flutter {
    source = "../.."
}
