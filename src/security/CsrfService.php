<?php declare(strict_types=1);

namespace src\security;

use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @package src\security
 */
final class CsrfService
{
    /**
     * @param CsrfTokenManagerInterface $manager
     * @param string $defaultTokenId
     */
    public function __construct(
        private CsrfTokenManagerInterface $manager,
        private string $defaultTokenId = 'default',
    ) {}

    /**
     * @return string
     */
    public function defaultTokenId(): string
    {
        return $this->defaultTokenId;
    }

    /**
     * @param string|null $tokenId
     * @return string
     */
    public function generateToken(?string $tokenId = null): string
    {
        return $this->manager
            ->getToken($this->resolveTokenId($tokenId))
            ->getValue();
    }

    /**
     * @param string|null $value
     * @param string|null $tokenId
     * @return bool
     */
    public function isTokenValid(?string $value, ?string $tokenId = null): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return $this->manager->isTokenValid(
            new CsrfToken($this->resolveTokenId($tokenId), $value),
        );
    }

    /**
     * @param string|null $tokenId
     * @return string
     */
    private function resolveTokenId(?string $tokenId): string
    {
        if ($tokenId !== null && trim($tokenId) !== '') {
            return $tokenId;
        }

        return $this->defaultTokenId;
    }
}
