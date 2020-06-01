<?php

declare(strict_types=1);

namespace Chiron\Container;

/**
 * Classes implemented this interface will be treated as singleton (will only be constructed once in the container).
 */
// TODO : class à renommer BindSingletonInterface pour que cela soit plus simple à comprendre en lisant son nom. ou éventuellement SharedServiceInterface
interface SingletonInterface
{
}
