<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

if (! function_exists('isBrokenLink')) {
    function isBrokenLink(string $url): bool
    {
        $statusCode = (string) getRemoteStatus($url);

        if (str_starts_with($statusCode, '4') || str_starts_with($statusCode, '5') || $statusCode === '0') {
            return true;
        }

        return false;
    }
}

if (! function_exists('getRemoteStatus')) {
    function getRemoteStatus(string $url): int
    {
        return cache()->driver(config('seo.cache.driver'))->tags('seo')->rememberForever($url, function () use ($url) {
            $options = [
                'timeout' => 30,
                'return_transfer' => true,
                'follow_location' => true,
                'no_body' => true,
                'header' => true,
            ];

            if (app()->runningUnitTests()) {
                $options = [
                    ...$options,
                    'ssl_verifyhost' => false,
                    'ssl_verifypeer' => false,
                    'ssl_verifystatus' => false,
                ];
            }

            $domain = parse_url($url, PHP_URL_HOST);

            if (in_array($domain, array_keys(config('seo.resolve')))) {
                $port = str_contains($url, 'https://') ? 443 : 80;

                $ipAddress = config('seo.resolve')[$domain];

                if (! empty($ipAddress)) {
                    $options = [
                        ...$options,
                        'resolve' => ["{$domain}:{$port}:{$ipAddress}"],
                    ];
                }
            }

            $http = Http::withOptions([
                ...config('seo.http.options', []),
                ...$options,
            ])->withHeaders([
                ...config('seo.http.headers', []),
            ]);

            $response = $http->get($url);

            $statusCode = $response->status();

            return $statusCode;
        });
    }
}

if (! function_exists('http_build_headers')) {
    function http_build_headers(array $headers): array
    {
        return array_map(fn ($value, $header): string => $header.': '.$value, array_values($headers), array_keys($headers));
    }
}

if (! function_exists('getRemoteFileSize')) {
    function getRemoteFileSize(string $url): int
    {
        return cache()->driver(config('seo.cache.driver'))->tags('seo')->rememberForever($url.'.size', function () use ($url) {

            $options = [
                'timeout' => 30,
                'return_transfer' => true,
                'follow_location' => true,
                'no_body' => true,
                'header' => true,
            ];

            if (app()->runningUnitTests()) {
                $options = [
                    ...$options,
                    'ssl_verifyhost' => false,
                    'ssl_verifypeer' => false,
                    'ssl_verifystatus' => false,
                ];
            }

            $domain = parse_url($url, PHP_URL_HOST);

            if (in_array($domain, array_keys(config('seo.resolve')))) {
                $port = str_contains($url, 'https://') ? 443 : 80;

                $ipAddress = config('seo.resolve')[$domain];

                if (! empty($ipAddress)) {
                    $options = [
                        ...$options,
                        'resolve' => ["{$domain}:{$port}:{$ipAddress}"],
                    ];
                }
            }

            $http = Http::withOptions([
                ...config('seo.http.options', []),
                ...$options,
            ])->withHeaders([
                ...config('seo.http.headers', []),
            ]);

            $data = $http->get($url)->body();
            
            if (empty($data)) {
                return 0;
            }

            if (
                preg_match('/Content-Length: (\d+)/', $data, $matches) ||
                preg_match('/content-length: (\d+)/', $data, $matches)
            ) {
                $contentLength = (int) $matches[1];
            }

            if (! isset($contentLength)) {
                $contentLength = strlen(file_get_contents($url));
            }

            return $contentLength;
        });
    }
}

if (! function_exists('getCheckCount')) {
    function getCheckCount(): int
    {
        $checks = collect();

        collect(config('seo.check_paths', ['Vormkracht10\\Seo\\Checks' => __DIR__.'/Checks']))
            ->each(function ($path, $baseNamespace) use (&$checks) {
                if (app()->runningUnitTests()) {
                    $path = __DIR__.'/Checks';
                }

                $files = is_dir($path) ? (new Finder)->in($path)->files() : Arr::wrap($path);

                foreach ($files as $fileInfo) {
                    $checkClass = $baseNamespace.str_replace(
                        ['/', '.php'],
                        ['\\', ''],
                        Str::after(
                            is_string($fileInfo) ? $fileInfo : $fileInfo->getRealPath(),
                            realpath($path)
                        )
                    );

                    $checks->put($checkClass, null);
                }
            });

        $checks = $checks->except(config('seo.exclude_checks', []));

        if (empty(config('seo.checks')) || ! in_array('*', config('seo.checks'))) {
            $checks = $checks->only(config('seo.checks'));
        }

        return $checks->count();
    }
}

if (! function_exists('bytesToHumanReadable')) {
    function bytesToHumanReadable(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        /**
         * According to the International System of Units (SI), kilo prefix is specified as 1000 (103).
         * Based on this, 1 kilobyte is equal to 1000 bytes. So we use 1000 instead of 1024.
         */
        $i = (int) floor(log($bytes, 1000));

        return round($bytes / (1000 ** $i), 2).' '.$units[$i];
    }
}

if (! function_exists('addBaseIfRelativeUrl')) {
    function addBaseIfRelativeUrl(string $url, string|null $checkedUrl = null): string
    {
        if (! Str::startsWith($url, '/')) {
            return $url;
        }

        if ($checkedUrl) {
            $parsedUrl = parse_url($checkedUrl);

            if ($parsedUrl) {
                return $parsedUrl['scheme'].'://'.$parsedUrl['host'].'/'.ltrim($url, '/');
            }
        }

        if (config('app.url')) {
            return config('app.url').$url;
        }

        return $url;
    }
}
