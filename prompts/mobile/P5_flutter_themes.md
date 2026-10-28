Role: Senior Flutter UI Engineer. Read 00_MASTER_PLAN.md and the matrix (§ variants & sections). For EVERY theme×variant in the matrix
(ecommet; elora v1-v6; souqify v1-v6) implement: tokens, header/footer/bottom-nav style, ordered home layout from bootstrap sections, and
one widget per section type (matrix §3) in lib/themes/<theme>/. Match the blade visual design (read blade/CSS of that variant to copy
layout, spacing, radii, typography, colors). Section registry must ignore unknown sections gracefully and honor tenant order/visibility.
Golden/widget tests: each variant renders with fixture bootstrap JSON; a test asserts registry covers 100% of matrix variants & sections.
Work one theme per sub-task to keep context small.
