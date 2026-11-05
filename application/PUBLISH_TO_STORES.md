# إنشاء متجر جديد
tenant-builder create --tenant store_alpha

# فحص الإعدادات
tenant-builder validate --tenant store_alpha

# توليد إعدادات Android وiOS
tenant-builder generate --tenant store_alpha

# بناء Android AAB
tenant-builder build \
  --tenant store_alpha \
  --platform android \
  --artifact aab \
  --mode release

# بناء تطبيقات كل المتاجر
tenant-builder build \
  --all \
  --platform android \
  --artifact aab \
  --mode release


------------------------------------------

