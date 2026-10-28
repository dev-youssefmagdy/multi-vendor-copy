import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:application/app/providers.dart';
import '../support/mocks.dart';

void main() {
  testWidgets('login: invalid credentials show the server error and stay on screen', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    await s.push(t, '/login');
    await t.enterText(find.byKey(const Key('login_email')), 'sam@example.com');
    await t.enterText(find.byKey(const Key('login_password')), 'wrong-pass');
    await t.tap(find.byKey(const Key('login_submit')));
    await t.pumpAndSettle();
    expect(find.text('Invalid email or password.'), findsOneWidget);
    expect(await s.c.read(authSessionProvider.future), isFalse);
  });

  testWidgets('login validates the email locally and success redirects to `from`', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    await s.push(t, '/login?from=${Uri.encodeComponent('/orders')}');
    await t.tap(find.byKey(const Key('login_submit')));
    await t.pumpAndSettle();
    expect(s.authRepo.calls, isEmpty);
    await t.enterText(find.byKey(const Key('login_email')), 'sam@example.com');
    await t.enterText(find.byKey(const Key('login_password')), 'secret123');
    await t.tap(find.byKey(const Key('login_submit')));
    await t.pumpAndSettle();
    expect(await s.c.read(authSessionProvider.future), isTrue);
    expect(find.text('My orders'), findsWidgets);
    expect(find.byKey(const Key('order_ord-1')), findsOneWidget);
  });

  testWidgets('register: needs name/email/password + confirmation; server field errors shown', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    await s.push(t, '/register');
    await t.enterText(find.byKey(const Key('reg_name')), 'Sam');
    await t.enterText(find.byKey(const Key('reg_email')), 'taken@example.com');
    await t.enterText(find.byKey(const Key('reg_password')), 'secret123');
    await t.enterText(find.byKey(const Key('reg_confirm')), 'different');
    await t.tap(find.byKey(const Key('register_submit')));
    await t.pumpAndSettle();
    expect(find.text('Passwords do not match'), findsOneWidget);
    expect(s.authRepo.calls, isEmpty);
    await t.enterText(find.byKey(const Key('reg_confirm')), 'secret123');
    await t.tap(find.byKey(const Key('register_submit')));
    await t.pumpAndSettle();
    expect(s.authRepo.calls, ['register:taken@example.com']);
    expect(find.text('The email has already been taken.'), findsOneWidget);
    await t.enterText(find.byKey(const Key('reg_email')), 'new@example.com');
    await t.tap(find.byKey(const Key('register_submit')));
    await t.pumpAndSettle();
    expect(await s.c.read(authSessionProvider.future), isTrue);
  });

  testWidgets('forgot password: request code then reset', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    await s.push(t, '/forgot-password');
    await t.enterText(find.byKey(const Key('forgot_email')), 'sam@example.com');
    await t.tap(find.byKey(const Key('forgot_submit')));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('forgot_sent')), findsOneWidget);
    await t.enterText(find.byKey(const Key('reset_token')), 'abc123');
    await t.enterText(find.byKey(const Key('reset_password')), 'newpass123');
    await t.enterText(find.byKey(const Key('reset_confirm')), 'newpass123');
    await t.tap(find.byKey(const Key('reset_submit')));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('login_submit')), findsOneWidget);
  });

  testWidgets('protected screens prompt a signed-out user to sign in', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    for (final r in ['/orders', '/checkout', '/account/addresses', '/favorites', '/returns']) {
      await s.go(t, r);
      expect(find.text('Sign in required'), findsOneWidget, reason: r);
    }
  });

  testWidgets('account hub: signed in shows profile + sign out clears the session', (t) async {
    final s = await Shop.create(signedIn: true);
    await s.launch(t);
    await s.go(t, '/account');
    expect(find.text('Sam Buyer'), findsOneWidget);
    await t.tap(find.byKey(const Key('sign_out')));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('account_sign_in')), findsOneWidget);
    expect(await s.c.read(authSessionProvider.future), isFalse);
  });

  testWidgets('profile edit + wrong current password error', (t) async {
    final s = await Shop.create(signedIn: true);
    await s.launch(t);
    await s.push(t, '/account/profile');
    await t.enterText(find.byKey(const Key('p_name')), 'Samira');
    await t.tap(find.byKey(const Key('p_save')));
    await t.pumpAndSettle();
    expect(s.account.profileData['full_name'], 'Samira');
    await t.ensureVisible(find.byKey(const Key('pw_save')));
    await t.enterText(find.byKey(const Key('pw_current')), 'nope');
    await t.enterText(find.byKey(const Key('pw_new')), 'newpass123');
    await t.enterText(find.byKey(const Key('pw_confirm')), 'newpass123');
    await t.tap(find.byKey(const Key('pw_save')));
    await t.pumpAndSettle();
    expect(find.text('Current password is incorrect.'), findsOneWidget);
  });

  testWidgets('addresses: list, add via form, delete', (t) async {
    final s = await Shop.create(signedIn: true);
    await s.launch(t);
    await s.push(t, '/account/addresses');
    expect(find.byKey(const Key('addr_3')), findsOneWidget);
    await t.tap(find.byKey(const Key('add_address')));
    await t.pumpAndSettle();
    await t.enterText(find.byKey(const Key('addr_full_name')), 'Home');
    await t.enterText(find.byKey(const Key('addr_address_line_1')), '2 Nile');
    await t.enterText(find.byKey(const Key('addr_city')), 'Giza');
    await t.tap(find.byKey(const Key('addr_save')));
    await t.pumpAndSettle();
    expect(s.account.saved.single['city'], 'Giza');
    expect(find.byKey(const Key('addr_99')), findsOneWidget);
  });

  testWidgets('favorites: signed-out heart goes to login; signed-in toggles and lists', (t) async {
    final s = await Shop.create(signedIn: true);
    await s.launch(t);
    await s.push(t, '/category/shoes');
    await t.tap(find.byKey(const Key('fav_2')));
    await t.pumpAndSettle();
    expect(s.account.favs, {'2'});
    await s.go(t, '/favorites');
    expect(find.byKey(const Key('favorites_grid')), findsOneWidget);
    expect(find.byKey(const Key('product_p2')), findsOneWidget);
  });

  testWidgets('favorites heart for a guest opens login', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    await s.push(t, '/category/shoes');
    await t.tap(find.byKey(const Key('fav_2')));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('login_submit')), findsOneWidget);
  });
}
