import 'package:flutter/widgets.dart';

/// Lightweight localisation: English source strings are the keys; other
/// languages override through [_ar]. Unknown keys / languages fall back to
/// the English text, so a missing translation never breaks a screen.
extension TrX on BuildContext {
  String tr(String en, [Map<String, String> args = const {}]) {
    final code = Localizations.maybeLocaleOf(this)?.languageCode ?? 'en';
    return translate(code, en, args);
  }
}

String translate(String lang, String en, [Map<String, String> args = const {}]) {
  var s = (lang == 'ar' ? _ar[en] : null) ?? en;
  args.forEach((k, v) => s = s.replaceAll(':$k', v));
  return s;
}

const _ar = <String, String>{
  'Home': 'الرئيسية',
  'Categories': 'الأقسام',
  'Cart': 'السلة',
  'Favorites': 'المفضلة',
  'Account': 'حسابي',
  'Search': 'بحث',
  'Search products': 'ابحث عن المنتجات',
  'Add to cart': 'أضف إلى السلة',
  'Buy now': 'اشتر الآن',
  'Checkout': 'إتمام الشراء',
  'Place order': 'تأكيد الطلب',
  'Sign in': 'تسجيل الدخول',
  'Sign out': 'تسجيل الخروج',
  'Create account': 'إنشاء حساب',
  'Email': 'البريد الإلكتروني',
  'Password': 'كلمة المرور',
  'Confirm password': 'تأكيد كلمة المرور',
  'Name': 'الاسم',
  'Phone': 'الهاتف',
  'Forgot password?': 'نسيت كلمة المرور؟',
  'Retry': 'إعادة المحاولة',
  'Loading...': 'جار التحميل...',
  'Something went wrong': 'حدث خطأ ما',
  'No internet connection': 'لا يوجد اتصال بالإنترنت',
  'Your cart is empty': 'سلتك فارغة',
  'Continue shopping': 'متابعة التسوق',
  'Subtotal': 'المجموع الفرعي',
  'Shipping': 'الشحن',
  'Tax': 'الضريبة',
  'Discount': 'الخصم',
  'Total': 'الإجمالي',
  'Coupon code': 'رمز القسيمة',
  'Apply': 'تطبيق',
  'Remove': 'إزالة',
  'Orders': 'الطلبات',
  'My orders': 'طلباتي',
  'Order': 'الطلب',
  'Returns': 'المرتجعات',
  'Addresses': 'العناوين',
  'Profile': 'الملف الشخصي',
  'Language': 'اللغة',
  'Currency': 'العملة',
  'Country': 'الدولة',
  'Preferences': 'التفضيلات',
  'Best selling': 'الأكثر مبيعاً',
  'New in': 'وصل حديثاً',
  'Top rated': 'الأعلى تقييماً',
  'Offers': 'العروض',
  'Sort': 'ترتيب',
  'Filters': 'تصفية',
  'Reviews': 'التقييمات',
  'Related products': 'منتجات مشابهة',
  'Description': 'الوصف',
  'Out of stock': 'نفد المخزون',
  'Page not found': 'الصفحة غير موجودة',
  'Go home': 'العودة للرئيسية',
  'Payment': 'الدفع',
  'Cash on delivery': 'الدفع عند الاستلام',
  'Payment pending': 'الدفع قيد الانتظار',
  'Payment successful': 'تم الدفع بنجاح',
  'Payment failed': 'فشل الدفع',
  'Cancel order': 'إلغاء الطلب',
  'Reorder': 'إعادة الطلب',
  'Track order': 'تتبع الطلب',
  'Invoice': 'الفاتورة',
  'Request a return': 'طلب إرجاع',
  'Save': 'حفظ',
  'Cancel': 'إلغاء',
  'Delete': 'حذف',
  'Edit': 'تعديل',
  'Add address': 'إضافة عنوان',
  'Default': 'افتراضي',
  'No favorites yet': 'لا توجد مفضلات بعد',
  'No results': 'لا توجد نتائج',
  'Sign in required': 'يلزم تسجيل الدخول',
  'Prices have changed': 'تغيرت الأسعار',
  'Continue': 'متابعة',
  'Review order': 'مراجعة الطلب',
  'Shipping address': 'عنوان الشحن',
  'Payment method': 'طريقة الدفع',
};
