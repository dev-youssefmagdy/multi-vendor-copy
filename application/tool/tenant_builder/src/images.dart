import 'dart:io';
import 'dart:typed_data';

import 'package:crypto/crypto.dart';
import 'package:image/image.dart' as img;

/// Basic facts about a source PNG, for validation.
class PngInfo {
  PngInfo(this.width, this.height, this.hasAlphaChannel, this.hasTransparency);
  final int width;
  final int height;

  /// PNG stores an alpha channel (App Store rejects icons with one).
  final bool hasAlphaChannel;

  /// At least one pixel is not fully opaque.
  final bool hasTransparency;
}

const _pngMagic = [0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A];

bool isPng(Uint8List bytes) {
  if (bytes.length < 8) return false;
  for (var i = 0; i < 8; i++) {
    if (bytes[i] != _pngMagic[i]) return false;
  }
  return true;
}

/// Null when [file] is not a decodable PNG.
PngInfo? readPng(File file) {
  final bytes = file.readAsBytesSync();
  if (!isPng(bytes)) return null;
  final image = img.decodePng(bytes);
  if (image == null) return null;
  final alpha = image.hasAlpha;
  var transparent = false;
  if (alpha) {
    for (final px in image) {
      if (px.a < image.maxChannelValue) {
        transparent = true;
        break;
      }
    }
  }
  return PngInfo(image.width, image.height, alpha, transparent);
}

/// Square-resizes [src] to [size] and encodes it as PNG (deterministic).
Uint8List resizePng(img.Image src, int size) {
  final out = img.copyResize(
    src,
    width: size,
    height: size,
    interpolation: img.Interpolation.average,
  );
  return Uint8List.fromList(img.encodePng(out, level: 9));
}

img.Image decodePngFile(File f) {
  final image = img.decodePng(f.readAsBytesSync());
  if (image == null) throw FormatException('Not a PNG: ${f.path}');
  return image;
}

/// Hash of the decoded RGBA pixels (+ size), independent of PNG encoding /
/// aapt crunching. Used to recognise a tenant's icon inside a built artifact.
String pixelHash(img.Image image) {
  final rgba = image.convert(numChannels: 4, format: img.Format.uint8);
  final header = Uint8List.fromList('${rgba.width}x${rgba.height}:'.codeUnits);
  return sha256.convert([...header, ...rgba.toUint8List()]).toString();
}

String? pixelHashOfBytes(List<int> bytes) {
  final data = bytes is Uint8List ? bytes : Uint8List.fromList(bytes);
  if (!isPng(data)) return null;
  try {
    final image = img.decodePng(data);
    return image == null ? null : pixelHash(image);
  } catch (_) {
    return null;
  }
}
