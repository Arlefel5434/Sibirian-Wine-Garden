<?php

namespace App\Services;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class ContentCache
{
    public function rememberProducts(string $variant, Closure $resolver, bool $enabled = true): array
    {
        return $this->remember('products', $variant, $resolver, $enabled);
    }

    public function rememberPosts(string $variant, Closure $resolver, bool $enabled = true): array
    {
        return $this->remember('posts', $variant, $resolver, $enabled);
    }

    public function rememberHome(string $variant, Closure $resolver): array
    {
        if (! config('content-cache.enabled')) {
            return $resolver();
        }

        $productVersion = $this->version('products');
        $postVersion = $this->version('posts');

        if ($productVersion === null || $postVersion === null) {
            return $resolver();
        }

        $version = $productVersion.'-'.$postVersion;

        return $this->rememberVersioned('home', $version, $variant, $resolver);
    }

    public function invalidateProducts(): bool
    {
        return $this->replaceVersion('products');
    }

    public function invalidatePosts(): bool
    {
        return $this->replaceVersion('posts');
    }

    public function fingerprint(array $context): string
    {
        return hash('sha256', json_encode($context, JSON_THROW_ON_ERROR));
    }

    private function remember(string $namespace, string $variant, Closure $resolver, bool $enabled): array
    {
        if (! config('content-cache.enabled') || ! $enabled) {
            return $resolver();
        }

        $version = $this->version($namespace);

        if ($version === null) {
            return $resolver();
        }

        return $this->rememberVersioned($namespace, $version, $variant, $resolver);
    }

    private function rememberVersioned(
        string $namespace,
        string $version,
        string $variant,
        Closure $resolver,
    ): array {
        $key = implode(':', [
            'content',
            'v'.config('content-cache.schema'),
            $namespace,
            $version,
            app()->getLocale(),
            $variant,
        ]);

        try {
            $cached = Cache::get($key);
        } catch (Throwable $exception) {
            report($exception);

            return $resolver();
        }

        if (is_array($cached)) {
            return $cached;
        }

        return $this->resolveWithLock($key, $resolver);
    }

    private function resolveWithLock(string $key, Closure $resolver): array
    {
        $resolverException = null;
        $resolve = function () use ($key, $resolver, &$resolverException): array {
            try {
                $value = $this->normalize($resolver());
            } catch (Throwable $exception) {
                $resolverException = $exception;

                throw $exception;
            }

            $this->storeValue($key, $value);

            return $value;
        };

        try {
            return Cache::lock(
                'content:lock:'.hash('sha256', $key),
                max(1, (int) config('content-cache.lock_seconds')),
            )->block(
                max(1, (int) config('content-cache.lock_wait_seconds')),
                function () use ($key, $resolve): array {
                    try {
                        $cached = Cache::get($key);
                    } catch (Throwable $exception) {
                        report($exception);

                        return $resolve();
                    }

                    return is_array($cached) ? $cached : $resolve();
                },
            );
        } catch (Throwable $exception) {
            if ($resolverException === $exception) {
                throw $exception;
            }

            if (! $exception instanceof LockTimeoutException) {
                report($exception);
            }

            return $this->resolveAfterLockFailure($key, $resolver);
        }
    }

    private function resolveAfterLockFailure(string $key, Closure $resolver): array
    {
        try {
            $cached = Cache::get($key);
        } catch (Throwable $exception) {
            report($exception);

            return $this->normalize($resolver());
        }

        return is_array($cached) ? $cached : $this->normalize($resolver());
    }

    private function storeValue(string $key, array $value): void
    {
        try {
            Cache::put($key, $value, config('content-cache.ttl'));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function version(string $namespace): ?string
    {
        try {
            return Cache::rememberForever(
                "content:version:{$namespace}",
                fn () => (string) Str::uuid(),
            );
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function replaceVersion(string $namespace): bool
    {
        try {
            return Cache::forever("content:version:{$namespace}", (string) Str::uuid());
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    private function normalize(array $value): array
    {
        return json_decode(json_encode($value, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }
}
