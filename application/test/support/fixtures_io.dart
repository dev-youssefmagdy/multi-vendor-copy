import 'dart:convert';
import 'dart:io';

Map<String, dynamic> fx(String n) =>
    jsonDecode(File('test/fixtures/$n.json').readAsStringSync())
        as Map<String, dynamic>;
