Role: Mobile Release Engineer. Read 00_MASTER_PLAN.md §7. Configure Android (applicationId, intent-filters autoVerify for platform
wildcard domain + documented custom-domain path, scheme fallback), iOS (Associated Domains entitlement, Runner bundle id, AASA), backend
well-known routes verification (curl evidence from tests), web landing fallback (Blade page on tenant domain: "Open in app / Install",
preserves path via ?store=), manual store-code recovery, optional Play Install Referrer. Write docs/mobile/DEEPLINKS.md with DNS,
signing SHA-256, Team ID, custom-domain scalability analysis. Never claim verification without evidence.
