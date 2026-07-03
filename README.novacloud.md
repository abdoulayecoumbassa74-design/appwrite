# NovaCloud Foundation

NovaCloud extends this Appwrite repository with cloud-control-plane concerns while preserving all existing Appwrite modules and runtime entry points.

## Architecture

- `gateway/` contains edge routing, reverse-proxy, RBAC, rate-limit, logging, metrics, WebSocket, HTTP/2, and gRPC configuration.
- `services/` declares the service catalog for compute, VM, storage, Kubernetes, networking, database, monitoring, billing, AI, and marketplace domains.
- `internal/` contains shared internal policies, including tenant isolation rules.
- `proto/` contains gRPC contracts for NovaCloud services.
- `helm/`, `kubernetes/`, and `opentofu/` contain deployable infrastructure assets.
- `dashboard/` contains NovaCloud console navigation configuration that preserves Appwrite authentication.

## Build integration

Run `composer novacloud:validate` to validate the required NovaCloud architecture files and route declarations.
