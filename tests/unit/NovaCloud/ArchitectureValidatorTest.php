<?php

namespace Tests\Unit\NovaCloud;

use Appwrite\NovaCloud\ArchitectureValidator;
use PHPUnit\Framework\TestCase;

final class ArchitectureValidatorTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/novacloud-validator-' . bin2hex(random_bytes(8));
        mkdir($this->root, recursive: true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->root);
    }

    public function testValidArchitecturePasses(): void
    {
        $this->writeFile('gateway/config/routes.json', json_encode([
            'routes' => [
                ['path' => '/api/v1/auth'],
                ['path' => '/api/v1/compute'],
            ],
            'transport' => [
                'http2' => true,
                'grpc' => true,
            ],
        ], JSON_THROW_ON_ERROR));
        $this->writeFile('gateway/config/rbac.json', '{}');

        $validator = new ArchitectureValidator(
            root: $this->root,
            requiredFiles: [
                'gateway/config/routes.json',
                'gateway/config/rbac.json',
            ],
            expectedRoutes: [
                '/api/v1/auth',
                '/api/v1/compute',
            ],
            expectedTransportCapabilities: [
                'http2',
                'grpc',
            ],
        );

        self::assertTrue($validator->isValid());
        self::assertSame([], $validator->failures());
    }

    public function testMissingFilesRoutesAndTransportAreReported(): void
    {
        $this->writeFile('gateway/config/routes.json', json_encode([
            'routes' => [
                ['path' => '/api/v1/auth'],
            ],
            'transport' => [
                'http2' => true,
            ],
        ], JSON_THROW_ON_ERROR));

        $validator = new ArchitectureValidator(
            root: $this->root,
            requiredFiles: [
                'gateway/config/routes.json',
                'services/catalog.yaml',
            ],
            expectedRoutes: [
                '/api/v1/auth',
                '/api/v1/compute',
            ],
            expectedTransportCapabilities: [
                'http2',
                'grpc',
            ],
        );

        self::assertSame([
            'file:services/catalog.yaml',
            'route:/api/v1/compute',
            'transport:grpc',
        ], $validator->failures());
        self::assertFalse($validator->isValid());
    }

    public function testInvalidRoutesJsonIsReported(): void
    {
        $this->writeFile('gateway/config/routes.json', '{invalid');

        $validator = new ArchitectureValidator(
            root: $this->root,
            requiredFiles: [
                'gateway/config/routes.json',
            ],
            expectedRoutes: [
                '/api/v1/auth',
            ],
            expectedTransportCapabilities: [
                'http2',
            ],
        );

        self::assertSame(['json:gateway/config/routes.json'], $validator->failures());
    }


    public function testUserSettingsAndTwoFactorRequirementsAreReported(): void
    {
        $this->writeFile('gateway/config/routes.json', json_encode([
            'routes' => [],
            'transport' => [],
        ], JSON_THROW_ON_ERROR));
        $this->writeFile('dashboard/config/user-settings.json', json_encode([
            'userSettings' => [
                'sections' => [
                    ['id' => 'profile'],
                ],
            ],
            'twoFactorBuildParameters' => [
                'supportedFactors' => ['totp'],
                'requiredEndpoints' => ['PATCH /v1/account/mfa'],
                'securityControls' => [
                    'requireFreshSessionForEnrollment' => true,
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $validator = new ArchitectureValidator(
            root: $this->root,
            requiredFiles: [
                'gateway/config/routes.json',
                'dashboard/config/user-settings.json',
            ],
            expectedRoutes: [],
            expectedTransportCapabilities: [],
        );

        self::assertContains('userSettings.section:twoFactorAuthentication', $validator->failures());
        self::assertContains('twoFactor.factor:recoveryCode', $validator->failures());
        self::assertContains('twoFactor.endpoint:POST /v1/account/mfa/challenges', $validator->failures());
        self::assertContains('twoFactor.securityControl:requireVerifiedFactorBeforeEnable', $validator->failures());
    }


    public function testUserSettingsPageMustExposeSectionsAndMfaEndpoints(): void
    {
        $this->writeFile('gateway/config/routes.json', json_encode([
            'routes' => [],
            'transport' => [],
        ], JSON_THROW_ON_ERROR));
        $this->writeFile('dashboard/pages/settings/user.html', '<section id="profile"></section> PATCH /v1/account/mfa');

        $validator = new ArchitectureValidator(
            root: $this->root,
            requiredFiles: [
                'gateway/config/routes.json',
                'dashboard/pages/settings/user.html',
            ],
            expectedRoutes: [],
            expectedTransportCapabilities: [],
        );

        self::assertContains('userSettings.pageSection:twoFactorAuthentication', $validator->failures());
        self::assertContains('userSettings.pageEndpoint:POST /v1/account/mfa/authenticators/totp', $validator->failures());
    }

    private function writeFile(string $path, string $contents): void
    {
        $file = $this->root . DIRECTORY_SEPARATOR . $path;
        $directory = dirname($file);

        if (!is_dir($directory)) {
            mkdir($directory, recursive: true);
        }

        file_put_contents($file, $contents);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($directory);
    }
}
