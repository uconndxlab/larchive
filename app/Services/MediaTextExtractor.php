<?php

namespace App\Services;

use App\Models\Media;
use App\Transcripts\TranscriptSourceFactory;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

class MediaTextExtractor
{
    public function transcript(Media $media): array
    {
        $disk = TranscriptStorage::diskFor($media->path);
        $extension = strtolower(pathinfo($media->filename, PATHINFO_EXTENSION));
        // Trust the document extension/MIME before legacy format=txt values.
        $format = match (true) {
            $extension === 'docx', $media->mime_type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            $extension === 'doc', $media->mime_type === 'application/msword' => 'doc',
            $extension === 'pdf', $media->mime_type === 'application/pdf' => 'pdf',
            default => $media->format ?: $extension,
        };

        if (in_array($format, ['docx', 'doc'])) {
            $text = $this->withLocalFile($disk, $media->path, function ($path) use ($format) {
                if ($format === 'docx') {
                    return $this->docx($path);
                }
                $process = new Process([config('media.extraction.antiword', 'antiword'), '-m', 'UTF-8.txt', '-w', '0', $path]);
                $process->setTimeout(60);
                $process->mustRun();

                return $process->getOutput();
            });

            return ['text' => $this->utf8($text), 'segments' => []];
        }

        if (! $disk->exists($media->path)) {
            throw new RuntimeException('Transcript file is missing.');
        }
        $source = TranscriptSourceFactory::create($media);
        if (! $source) {
            throw new RuntimeException('Unsupported transcript format.');
        }
        $segments = $source->segments();
        // The existing plain-text parser estimates ends for caption playback.
        // Those estimates must never be exposed as archival transcript timestamps.
        if ($source->format() === 'txt') {
            foreach ($segments as &$segment) {
                $segment['end'] = null;
            }
            unset($segment);
        }
        $text = $source->format() === 'ohms'
            ? collect($segments)->pluck('text')->implode("\n")
            : $disk->get($media->path);

        return [
            'text' => $this->utf8($text),
            'segments' => json_decode(json_encode($segments, JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR), true),
        ];
    }

    public function duration(Media $media, ?string $diskName = null): float
    {
        $selectedDisk = $diskName ?? config('media.disk', 'public');
        $path = $selectedDisk === 'public' ? preg_replace('#^public/#', '', $media->path) : $media->path;
        $disk = Storage::disk($selectedDisk);
        // Incoming attachments and older uploads use public storage even when
        // the newer media uploader is configured for an archive/object disk.
        if ($diskName === null && $selectedDisk !== 'public' && ! $disk->exists($path)) {
            $disk = Storage::disk('public');
            $path = preg_replace('#^public/#', '', $media->path);
        }

        return $this->withLocalFile($disk, $path, function ($path) {
            $process = new Process([
                config('media.extraction.ffprobe', 'ffprobe'), '-v', 'error',
                '-protocol_whitelist', 'file', '-show_entries', 'format=duration:stream=codec_type', '-of', 'json', $path,
            ]);
            $process->setTimeout(120);
            $process->mustRun();
            $result = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
            $duration = $result['format']['duration'] ?? null;
            $hasRecording = collect($result['streams'] ?? [])->contains(fn ($stream) => in_array($stream['codec_type'] ?? null, ['audio', 'video']));
            if (! $hasRecording || ! is_numeric($duration) || ! is_finite((float) $duration) || (float) $duration <= 0) {
                throw new RuntimeException('No valid recording duration found.');
            }

            return (float) $duration;
        });
    }

    public function withLocalFile(Filesystem $disk, string $path, callable $callback): mixed
    {
        if (! $disk->exists($path)) {
            throw new RuntimeException('Source file is missing.');
        }
        // Local recordings can be very large: probe in place rather than copying.
        if (method_exists($disk, 'getAdapter') && $disk->getAdapter() instanceof \League\Flysystem\Local\LocalFilesystemAdapter) {
            return $callback($disk->path($path));
        }
        $temporary = tempnam(sys_get_temp_dir(), 'larchive_extract_');
        if ($temporary === false) {
            throw new RuntimeException('Cannot create private temporary file.');
        }
        $input = $output = null;
        try {
            $input = $disk->readStream($path);
            $output = fopen($temporary, 'wb');
            if (! is_resource($input) || ! is_resource($output) || ($copied = stream_copy_to_stream($input, $output)) === false
                || $copied !== $disk->size($path)) {
                throw new RuntimeException('Cannot read source file.');
            }
            fclose($output);
            $output = null;

            return $callback($temporary);
        } finally {
            if (is_resource($input)) {
                fclose($input);
            }
            if (is_resource($output)) {
                fclose($output);
            }
            unlink($temporary);
        }
    }

    private function docx(string $path): string
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Malformed DOCX archive.');
        }
        try {
            $stat = $zip->statName('word/document.xml');
            // Bound decompression before reading untrusted XML.
            if (! $stat || $stat['size'] > 20 * 1024 * 1024) {
                throw new RuntimeException('Missing or oversized DOCX document body.');
            }
            $xml = $zip->getFromName('word/document.xml');
        } finally {
            $zip->close();
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument;
            if (! $xml || ! $document->loadXML($xml, LIBXML_NONET) || $document->doctype) {
                throw new RuntimeException('Malformed DOCX document body.');
            }
            $xpath = new DOMXPath($document);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $body = $xpath->query('/w:document/w:body')->item(0);
            if (! $body) {
                throw new RuntimeException('Missing DOCX document body.');
            }

            return trim($this->bodyText($body));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function bodyText(DOMNode $node): string
    {
        // Only visible body text. Never read properties, comments, deleted revisions,
        // field instructions, headers/footers, embedded objects, or author attributes.
        if ($node->namespaceURI === 'http://schemas.openxmlformats.org/wordprocessingml/2006/main') {
            if (in_array($node->localName, ['del', 'moveFrom', 'instrText', 'object', 'drawing', 'pPr', 'rPr'])) {
                return '';
            }
            if ($node->localName === 'r') {
                foreach ($node->childNodes as $properties) {
                    if ($properties->localName === 'rPr') {
                        foreach ($properties->childNodes as $property) {
                            if (in_array($property->localName, ['vanish', 'webHidden'])
                                && ! in_array($property->attributes?->getNamedItemNS($property->namespaceURI, 'val')?->nodeValue, ['0', 'false', 'off'])) {
                                return '';
                            }
                        }
                    }
                }
            }
            if ($node->localName === 't') {
                return $node->textContent;
            }
            if (in_array($node->localName, ['br', 'cr'])) {
                return "\n";
            }
            if ($node->localName === 'tab') {
                return "\t";
            }
        }
        $text = '';
        foreach ($node->childNodes as $child) {
            $text .= $this->bodyText($child);
        }
        if ($node->namespaceURI === 'http://schemas.openxmlformats.org/wordprocessingml/2006/main') {
            $text .= match ($node->localName) {
                'p', 'tr' => "\n",
                'tc' => "\t",
                default => '',
            };
        }

        return $text;
    }

    private function utf8(string $text): string
    {
        return str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], json_decode(json_encode($text, JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR), true));
    }
}
