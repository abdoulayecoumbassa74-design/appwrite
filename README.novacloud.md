# NovaCloud Foundation

NovaCloud extends this Appwrite repository with cloud-control-plane concerns while preserving all existing Appwrite modules and runtime entry points.

## Architecture

- `gateway/` contains edge routing, reverse-proxy, RBAC, rate-limit, logging, metrics, WebSocket, HTTP/2, and gRPC configuration.
- `services/` declares the service catalog for compute, VM, storage, Kubernetes, networking, database, monitoring, billing, AI, and marketplace domains.
- `internal/` contains shared internal policies, including tenant isolation rules.
- `proto/` contains gRPC contracts for NovaCloud services.
- `helm/`, `kubernetes/`, and `opentofu/` contain deployable infrastructure assets.
- `dashboard/` contains NovaCloud console navigation and authenticated user settings configuration, including the 2FA build contract.

## Build integration

Run `composer novacloud:validate` to validate the required NovaCloud architecture files and route declarations.

## User settings and 2FA

NovaCloud user settings are declared in `dashboard/config/user-settings.json`. The configuration maps profile, security, preferences, cloud defaults, and two-factor authentication controls to existing Appwrite account endpoints so the console can build 2FA without breaking authentication.
