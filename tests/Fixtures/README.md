# Synthetic media fixtures

`legacy.doc` contains invented interview text with UTF-8 characters, timestamp strings and two paragraphs, followed by repeated synthetic text (antiword rejects very small Word text streams). It was generated from a minimal DOCX using LibreOffice's MS Word 97 filter. No archival transcript was used. LibreOffice is not a runtime dependency.

`recording.mp4` is a silent 16×16 black MPEG-4 video, 1.25 seconds at 8 fps, generated with FFmpeg:

```sh
ffmpeg -f lavfi -i color=c=black:s=16x16:r=8 -t 1.25 -c:v mpeg4 -an recording.mp4
```

DOCX and PCM WAV fixtures are generated inside the tests. Real DOC/FFprobe integration tests require the corresponding server binaries; the video test uses this committed file and does not require FFmpeg during the test run.
