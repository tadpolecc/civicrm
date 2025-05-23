<?php
declare(strict_types=1);
namespace TYPO3\PharStreamWrapper\Interceptor;

/*
 * This file is part of the TYPO3 project.
 *
 * It is free software; you can redistribute it and/or modify it under the terms
 * of the MIT License (MIT). For the full copyright and license information,
 * please read the LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\PharStreamWrapper\Assertable;
use TYPO3\PharStreamWrapper\Exception;
use TYPO3\PharStreamWrapper\Manager;
use TYPO3\PharStreamWrapper\Phar\DeserializationException;
use TYPO3\PharStreamWrapper\Phar\Reader;

/**
 * @internal Experimental implementation of checking against serialized objects in Phar meta-data
 * @internal This functionality has not been 100% pentested...
 */
class PharMetaDataInterceptor implements Assertable
{
    /**
     * Determines whether the according Phar archive contains
     * (potential insecure) serialized objects.
     *
     * @throws Exception
     */
    public function assert(string $path, string $command): bool
    {
        if ($this->baseFileDoesNotHaveMetaDataIssues($path)) {
            return true;
        }
        throw new Exception(
            sprintf(
                'Problematic meta-data in "%s"',
                $path
            ),
            1539632368
        );
    }

    private function baseFileDoesNotHaveMetaDataIssues(string $path): bool
    {
        $invocation = Manager::instance()->resolve($path);
        if ($invocation === null) {
            return false;
        }
        // directly return in case invocation was checked before
        if ($invocation->getVariable(self::class) === true) {
            return true;
        }
        // otherwise analyze meta-data
        try {
            $reader = new Reader($invocation->getBaseName());
            $reader->resolveContainer()->getManifest()->deserializeMetaData();
            $invocation->setVariable(self::class, true);
        } catch (DeserializationException $exception) {
            return false;
        }
        return true;
    }
}
