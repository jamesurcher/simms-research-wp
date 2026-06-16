# Where RUO Peptide Companies Actually Host (Empirical)

**Purpose:** Answer the open "Hosting environment" + "CDN/cache layer" launch decisions for the
Shopify → WooCommerce migration by looking at what real research-use-only peptide stores run on.

**Method:** Not blog advice. Live recon on ~18 active peptide stores (June 2026) — DNS A/NS/MX/TXT
records, HTTP response headers, SPF leaks, non-proxied subdomains, and `/wp-json/` namespace probes.
IPs mapped to hosting orgs via WHOIS. Where a site sits behind Cloudflare, the origin host is
inferred from the leaks that bypass it (mail subdomains, SPF `include:`/`ip4:`, vendor headers).

---

## The distilled answer

Competitors run a **two-layer** setup. "Which server" has two parts:

1. **Edge / front door → Cloudflare. Near-universal (~15 of 18).**
   It's the public A-record on almost every store. It is the DNS + CDN + WAF + DDoS + caching layer.
   It is **not** the hosting — it proxies and hides the real server. Free tier is enough for most.

2. **Origin / the actual "server" → managed WordPress or cPanel/LiteSpeed hosting.**
   Almost nobody in this space hand-rolls a bare DIY VPS. They buy **managed WP hosting** (which is a
   managed VPS/cloud underneath — the host runs the OS, PHP, MySQL, patching, backups) and point
   Cloudflare at it. The application layer is **WordPress + WooCommerce**, confirmed by `wc/*` REST
   namespaces on every store that didn't lock down `/wp-json`.

**So: the typical stack is `Cloudflare (edge) → managed-WP host (origin) → WordPress + WooCommerce`,
often with a page-cache plugin (NitroPack / LiteSpeed Cache) on top.**

---

## Named origin hosts found (with evidence)

| Store | Edge | Origin host (the "server") | How we know |
|---|---|---|---|
| Biotech Peptides | Cloudflare | **WP Engine** (managed WP, premium) | `x-powered-by: WP Engine` + `wpe/cache-plugin` namespace |
| Paradigm Peptides | Cloudflare | **SiteGround** (managed/cloud WP) | SPF leaks `ns1.us194.siteground.us` + `spf.securedserverspace.com` |
| Swiss Chems | none (direct) | **Hostinger** (LiteSpeed cloud) | `platform: hostinger`, `server: hcdn`, MX `mx1.hostinger.com` |
| PureRawz | Cloudflare | **InMotion Hosting** (VPS/shared) | SPF `ip4:198.46.87.213` → InMotion Hosting ASN |
| Pinnacle Peptides | none | **Namecheap** hosting | A-record `162.255.119.201` → Namecheap ASN |
| Particle Peptides | none | **Websupport.sk** (EU host) | A-record + NS `ns1.websupport.sk` |
| Sports Tech Labs | Cloudflare | masked WP origin | `/wp-json/` + NitroPack; MX on Microsoft 365 |
| Behemoth Labz | Cloudflare | masked WP origin | WooCommerce `/wp-json/` + NitroPack |
| Loti Labs | Cloudflare | masked WP origin | WooCommerce `/wp-json/` |
| Polaris Peptides | Cloudflare | masked WP origin | WooCommerce `/wp-json/` |
| Chemyo | Cloudflare | masked WP origin | WooCommerce `/wp-json/` |
| Core Peptides / Modern Aminos | Cloudflare | masked (WP, hardened) | `/wp-json/` blocked; share `_spf.safewebservices.com` |
| Peptide Sciences | Cloudflare | masked (VPS) | `mail.` subdomain on a non-CF VPS range |
| Limitless Life | Cloudflare | **BigCommerce (SaaS)** | `cdn11.bigcommerce.com` stencil theme — *not* self-hosted |

### Patterns worth noting
- **WooCommerce is the default**, not a guess: `wc/v3` + `wc/store` namespaces confirmed on Biotech,
  Chemyo, Behemoth, Loti, Polaris. The lone SaaS holdout in the sample is Limitless (BigCommerce).
- **Several stores share the exact same `wc/pos` plugin + wp-json fingerprint** → a common agency /
  build template is circulating in this niche.
- **Page-cache layer is standard**: NitroPack (Biotech, Sports Tech, Behemoth) or LiteSpeed Cache
  (Hostinger-based sites). This sits between Cloudflare and the origin.
- **Mainstream managed hosts do host peptide stores.** The "general advice" that WP Engine /
  SiteGround ban research-chemical merchants is not borne out — Biotech runs on WP Engine and
  Paradigm on SiteGround right now. Risk is real but it is not a hard wall. (The exposure is the AUP,
  not the technology — keep a portable backup so you can move hosts fast if a host gets cold feet.)

---

## What this means for Simms

- A **raw, self-managed VPS is the minority choice** here. You *can* run one (DigitalOcean/Vultr/
  Hetzner + your own LEMP stack), but you'd own all the ops the managed hosts handle for you.
- The evidence-backed default for a store your size: **managed WordPress host + Cloudflare in front +
  a cache plugin.** That matches what the cleaner-run competitors actually do.
- Concrete options in order of "what competitors run":
  - **Hostinger** — cheapest, LiteSpeed, used by Swiss Chems (and likely others). Good value entry.
  - **SiteGround** — mid, managed, proven in-niche (Paradigm).
  - **WP Engine** — premium managed, proven in-niche (Biotech). Strictest AUP of the three.
  - **Self-managed VPS** (Hetzner/Vultr/DigitalOcean) — only if you want full control + lowest unit
    cost and are willing to own patching/backups/security. Pair with RunCloud/GridPane/SpinupWP to
    get most of the "managed" convenience on top of a raw VPS.
- **Put Cloudflare in front regardless of host.** It's the one universal in this space — DNS cutover,
  WAF, DDoS, and origin-hiding all in one, free tier sufficient.

---

*Recon date: 2026-06-15. Findings are point-in-time; hosts can change. Origins behind Cloudflare are
inferred from leaks, not direct observation — treat "masked" rows as WordPress-confirmed but
host-unconfirmed.*
