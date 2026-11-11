---
name: system-optimization
description: Use when investigating slow pages, high memory/CPU, database bottlenecks, timeouts, queues, caching, or scalability.
---

# System Optimization

1. Establish a baseline: endpoint/job, dataset size, response time, memory, query count, logs, and reproducible workload.
2. Profile/measure before optimizing; identify bottleneck (DB, PHP, network, rendering, queue, external service).
3. Inspect query patterns, indexes, N+1, unnecessary columns, repeated computation, serialization, and payload size.
4. Prefer targeted improvements: eager loading, appropriate indexes, pagination, caching with invalidation, chunking, queues, and reduced payloads.
5. Preserve correctness, authorization scope, and freshness requirements; do not cache user-specific/private data across users.
6. Avoid premature denormalization, broad cache changes, or infrastructure upgrades without evidence.
7. Benchmark before/after under comparable conditions and add regression tests where possible.
8. Report measurements, tradeoffs, invalidation/recovery, and limits of the test environment.
