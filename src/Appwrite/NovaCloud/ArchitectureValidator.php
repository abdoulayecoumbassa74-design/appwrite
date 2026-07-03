<?php

namespace Appwrite\NovaCloud;

final class ArchitectureValidator
{
    /**
     * @param list<string> $requiredFiles
     * @param list<string> $expectedRoutes
     * @param list<string> $expectedTransportCapabilities
     */
    public function __construct(
        private readonly string $root,
        private readonly array $requiredFiles = self::REQUIRED_FILES,
        private readonly array $expectedRoutes = self::EXPECTED_ROUTES,
        private readonly array $expectedTransportCapabilities = self::EXPECTED_TRANSPORT_CAPABILITIES,
    ) {
    }

    private const REQUIRED_FILES = [
        'gateway/api-gateway/Caddyfile',
        'gateway/config/routes.json',
        'gateway/config/rbac.json',
        'gateway/config/rate-limits.yaml',
        'gateway/edge-auth/policy.rego',
        'gateway/load-balancer/traefik-dynamic.yaml',
        'services/catalog.yaml',
        'internal/tenancy/policy.rego',
        'proto/novacloud/compute.proto',
        'proto/novacloud/vm.proto',
        'helm/novacloud/Chart.yaml',
        'helm/novacloud/values.yaml',
        'helm/novacloud/templates/api-gateway.yaml',
        'kubernetes/base/api-gateway.yaml',
        'opentofu/main.tf',
        'opentofu/providers.tf',
        'dashboard/config/navigation.json',
        'dashboard/config/user-settings.json',
        'README.novacloud.md',
    ];

    private const EXPECTED_ROUTES = [
        '/api/v1/auth',
        '/api/v1/compute',
        '/api/v1/vm',
        '/api/v1/network',
        '/api/v1/storage',
        '/api/v1/kubernetes',
        '/api/v1/database',
    ];

    private const EXPECTED_TRANSPORT_CAPABILITIES = [
        'http2',
        'websocket',
        'grpc',
        'requestLogging',
    ];

    private const EXPECTED_USER_SETTINGS_SECTIONS = [
        'profile',
        'security',
        'twoFactorAuthentication',
        'preferences',
        'cloudDefaults',
    ];

    private const EXPECTED_TWO_FACTOR_ENDPOINTS = [
        'PATCH /v1/account/mfa',
        'POST /v1/account/mfa/authenticators/totp',
        'PUT /v1/account/mfa/authenticators/totp',
        'DELETE /v1/account/mfa/authenticators/totp',
        'POST /v1/account/mfa/challenges',
        'PUT /v1/account/mfa/challenges',
        'GET /v1/account/mfa/recovery-codes',
        'PATCH /v1/account/mfa/recovery-codes',
    ];

    private const EXPECTED_TWO_FACTOR_FACTORS = [
        'totp',
        'email',
        'phone',
        'recoveryCode',
    ];

    /**
     * @return list<string>
     */
    public function failures(): array
    {
        $failures = [];

        foreach ($this->requiredFiles as $file) {
            if (!is_file($this->root . DIRECTORY_SEPARATOR . $file)) {
                $failures[] = 'file:' . $file;
            }
        }

        $routesFile = $this->root . '/gateway/config/routes.json';
        if (!is_file($routesFile)) {
            return $failures;
        }

        $routes = json_decode((string) file_get_contents($routesFile), true);
        if (!is_array($routes)) {
            $failures[] = 'json:gateway/config/routes.json';

            return $failures;
        }

        $actualRoutes = array_column($routes['routes'] ?? [], 'path');
        foreach ($this->expectedRoutes as $route) {
            if (!in_array($route, $actualRoutes, true)) {
                $failures[] = 'route:' . $route;
            }
        }

        $transport = $routes['transport'] ?? [];
        foreach ($this->expectedTransportCapabilities as $capability) {
            if (($transport[$capability] ?? false) !== true) {
                $failures[] = 'transport:' . $capability;
            }
        }

        return array_merge($failures, $this->userSettingsFailures());
    }

    /**
     * @return list<string>
     */
    private function userSettingsFailures(): array
    {
        $failures = [];
        $settingsFile = $this->root . '/dashboard/config/user-settings.json';

        if (!is_file($settingsFile)) {
            return $failures;
        }

        $settings = json_decode((string) file_get_contents($settingsFile), true);
        if (!is_array($settings)) {
            return ['json:dashboard/config/user-settings.json'];
        }

        $sections = array_column($settings['userSettings']['sections'] ?? [], 'id');
        foreach (self::EXPECTED_USER_SETTINGS_SECTIONS as $section) {
            if (!in_array($section, $sections, true)) {
                $failures[] = 'userSettings.section:' . $section;
            }
        }

        $twoFactor = $settings['twoFactorBuildParameters'] ?? [];
        $factors = $twoFactor['supportedFactors'] ?? [];
        foreach (self::EXPECTED_TWO_FACTOR_FACTORS as $factor) {
            if (!in_array($factor, $factors, true)) {
                $failures[] = 'twoFactor.factor:' . $factor;
            }
        }

        $endpoints = $twoFactor['requiredEndpoints'] ?? [];
        foreach (self::EXPECTED_TWO_FACTOR_ENDPOINTS as $endpoint) {
            if (!in_array($endpoint, $endpoints, true)) {
                $failures[] = 'twoFactor.endpoint:' . $endpoint;
            }
        }

        foreach ([
            'requireFreshSessionForEnrollment',
            'requireVerifiedFactorBeforeEnable',
            'recoveryCodesAreOneTimeUse',
            'showRecoveryCodesOnce',
        ] as $control) {
            if (($twoFactor['securityControls'][$control] ?? false) !== true) {
                $failures[] = 'twoFactor.securityControl:' . $control;
            }
        }

        return $failures;
    }

    public function isValid(): bool
    {
        return $this->failures() === [];
    }
}
