<?php

declare(strict_types=1);

namespace Embiken\BikeFleet\Services;

use Illuminate\Validation\ValidationException;
use ZipArchive;

class CatalogFleetImportBundle
{
    private const array CSV_FILES = ['categories', 'models', 'variants', 'bikes'];

    private const int MAX_ARCHIVE_BYTES = 100 * 1024 * 1024;

    private const int MAX_ARCHIVE_ENTRIES = 1000;

    private const int MAX_EXPANDED_BYTES = 150 * 1024 * 1024;

    private const int MAX_CSV_BYTES = 10 * 1024 * 1024;

    private const int MAX_IMAGE_BYTES = 10 * 1024 * 1024;

    /**
     * @return array{
     *     files: array{categories: string, models: string, variants: string, bikes: string},
     *     photos: array<string, string>
     * }
     */
    public function read(string $archivePath): array
    {
        $archiveSize = filesize($archivePath);

        if ($archiveSize === false || $archiveSize > self::MAX_ARCHIVE_BYTES) {
            throw ValidationException::withMessages([
                'bundle' => 'The ZIP must be no larger than 100 MB.',
            ]);
        }

        $archive = new ZipArchive;
        $opened = $archive->open($archivePath);

        if ($opened !== true) {
            throw ValidationException::withMessages([
                'bundle' => 'The catalog bundle is not a readable ZIP archive.',
            ]);
        }

        try {
            if ($archive->numFiles > self::MAX_ARCHIVE_ENTRIES) {
                throw ValidationException::withMessages([
                    'bundle' => 'The ZIP contains too many files.',
                ]);
            }

            $entries = $this->entryNames($archive);
            $prefix = $this->sharedTopLevelDirectory($entries);
            $files = [];
            $photos = [];
            $expandedBytes = 0;

            foreach ($entries as $index => $entryName) {
                $name = substr($entryName, strlen($prefix));

                if ($name === '' || str_ends_with($name, '/')) {
                    continue;
                }

                if ($this->isArchiveMetadata($name)) {
                    continue;
                }

                $entry = $archive->statIndex($index);
                $entrySize = $entry['size'] ?? null;

                if (! is_int($entrySize) || $entrySize < 0) {
                    throw ValidationException::withMessages([
                        'bundle' => 'The ZIP contains an unreadable file entry.',
                    ]);
                }

                $expandedBytes += $entrySize;

                if ($expandedBytes > self::MAX_EXPANDED_BYTES) {
                    throw ValidationException::withMessages([
                        'bundle' => 'The uncompressed ZIP contents exceed 150 MB.',
                    ]);
                }

                $content = $archive->getFromIndex($index);

                if (! is_string($content) || strlen($content) !== $entrySize) {
                    throw ValidationException::withMessages([
                        'bundle' => 'A file in the ZIP could not be read completely.',
                    ]);
                }

                if (in_array($name, array_map(static fn (string $file): string => "{$file}.csv", self::CSV_FILES), true)) {
                    if ($entrySize > self::MAX_CSV_BYTES) {
                        throw ValidationException::withMessages([
                            'bundle' => "{$name} must be no larger than 10 MB.",
                        ]);
                    }

                    $key = pathinfo($name, PATHINFO_FILENAME);

                    if (array_key_exists($key, $files)) {
                        throw ValidationException::withMessages([
                            'bundle' => "The ZIP contains more than one {$name} file.",
                        ]);
                    }

                    $files[$key] = $content;

                    continue;
                }

                if (! str_starts_with($name, 'images/')) {
                    throw ValidationException::withMessages([
                        'bundle' => "Unexpected ZIP entry: {$name}.",
                    ]);
                }

                $this->validatePhoto($name, $content);

                if (array_key_exists($name, $photos)) {
                    throw ValidationException::withMessages([
                        'bundle' => "The ZIP contains the image {$name} more than once.",
                    ]);
                }

                $photos[$name] = $content;
            }

            foreach (self::CSV_FILES as $file) {
                if (! array_key_exists($file, $files)) {
                    throw ValidationException::withMessages([
                        'bundle' => "The ZIP is missing {$file}.csv.",
                    ]);
                }
            }

            /** @var array{categories: string, models: string, variants: string, bikes: string} $files */
            return ['files' => $files, 'photos' => $photos];
        } finally {
            $archive->close();
        }
    }

    public function validatePhoto(string $path, string $contents): void
    {
        if (! $this->isValidPhotoReference($path)) {
            throw ValidationException::withMessages([
                'photo_file' => 'Photo references must point to a PNG, JPEG, or WebP file directly inside images/.',
            ]);
        }

        if (strlen($contents) > self::MAX_IMAGE_BYTES) {
            throw ValidationException::withMessages([
                'photo_file' => "{$path} exceeds the 10 MB image limit.",
            ]);
        }

        $image = getimagesizefromstring($contents);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);

        if (! is_array($image) || ! is_string($mime)) {
            throw ValidationException::withMessages([
                'photo_file' => "{$path} is not a readable image.",
            ]);
        }

        $allowedMimeTypes = [
            'image/png' => ['png'],
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/webp' => ['webp'],
        ];
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $width = $image[0];
        $height = $image[1];

        if (! in_array($extension, $allowedMimeTypes[$mime] ?? [], true)) {
            throw ValidationException::withMessages([
                'photo_file' => "{$path} must use a file extension that matches its image format.",
            ]);
        }

        if ($width < 1 || $height < 1 || $width > 8000 || $height > 8000 || $width * $height > 40_000_000) {
            throw ValidationException::withMessages([
                'photo_file' => "{$path} has dimensions outside the supported range.",
            ]);
        }
    }

    public function isValidPhotoReference(string $path): bool
    {
        return preg_match('/\Aimages\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:png|jpe?g|webp)\z/i', $path) === 1;
    }

    /** @return array<int, string> */
    private function entryNames(ZipArchive $archive): array
    {
        $entries = [];
        $seen = [];

        for ($index = 0; $index < $archive->numFiles; $index++) {
            $name = $archive->getNameIndex($index);

            if (! is_string($name)
                || str_starts_with($name, '/')
                || str_contains($name, '\\')
                || in_array('..', explode('/', $name), true)
                || str_contains($name, "\0")) {
                throw ValidationException::withMessages([
                    'bundle' => 'The ZIP contains an unsafe file path.',
                ]);
            }

            if (isset($seen[$name])) {
                throw ValidationException::withMessages([
                    'bundle' => "The ZIP contains the entry {$name} more than once.",
                ]);
            }

            $seen[$name] = true;
            $entries[$index] = $name;
        }

        return $entries;
    }

    /** @param array<int, string> $entries */
    private function sharedTopLevelDirectory(array $entries): string
    {
        $entries = array_filter($entries, fn (string $entry): bool => ! $this->isArchiveMetadata($entry));
        $rootFiles = array_filter($entries, static fn (string $entry): bool => ! str_ends_with($entry, '/') && ! str_contains($entry, '/'));

        if ($rootFiles !== []) {
            return '';
        }

        $topLevelNames = [];

        foreach ($entries as $entry) {
            $topLevelNames[explode('/', $entry, 2)[0]] = true;
        }

        if (count($topLevelNames) !== 1) {
            return '';
        }

        $topLevel = (string) array_key_first($topLevelNames);

        return $topLevel.'/';
    }

    private function isArchiveMetadata(string $name): bool
    {
        return $name === '.DS_Store'
            || str_starts_with($name, '__MACOSX/')
            || str_starts_with($name, '._');
    }
}
