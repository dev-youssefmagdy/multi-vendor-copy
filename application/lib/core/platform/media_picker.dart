import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';

class PickedFile {
  const PickedFile(this.path, this.name);
  final String path, name;
}

abstract class MediaPicker {
  Future<List<PickedFile>> pickPhotos();
}

class ImagePickerMediaPicker implements MediaPicker {
  @override
  Future<List<PickedFile>> pickPhotos() async {
    final files = await ImagePicker().pickMultiImage(limit: 5);
    return [for (final f in files) PickedFile(f.path, f.name)];
  }
}

final mediaPickerProvider =
    Provider<MediaPicker>((_) => ImagePickerMediaPicker());
