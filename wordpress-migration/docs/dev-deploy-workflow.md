# Recreating the Shopify Code-Push Flow on WordPress

**Goal:** Reproduce the Shopify CLI workflow — *local dev server → edit code with AI (Claude Code /
Codex) → commit to GitHub → one-command push to live* — on WordPress + WooCommerce.

**Verified:** WordPress.com plan gating + GitHub Deployments confirmed against official
WordPress.com support docs, June 2026.

---

## The Shopify flow, mapped 1:1 to WordPress

| Shopify step | WordPress equivalent |
|---|---|
| `shopify theme dev` (local preview vs live data) | **WordPress Studio** (free local app by Automattic) or `wp-env` / Local / DDEV — or the `docker-compose.yml` already in this kit |
| Edit Liquid with Claude Code / Codex | Edit theme/block/plugin **PHP/JS/CSS** with the same AI tools (identical loop) |
| `git commit` → GitHub | Identical — same repo, same commits |
| `shopify theme push --live` | **GitHub Deployments** (push triggers deploy) and/or **WP-CLI over SSH** (`wp theme activate`, etc.) |

**GitHub Deployments mechanics (the push-to-live piece):**
- Connect repo via the WordPress.com GitHub app; pick repos to authorize.
- **Simple** deploy = copy repo files to a destination folder. **Advanced** = `wpcom.yml` build
  workflow (Composer install, tests, control which files ship).
- **Automatic** = deploy on every push to a branch (recommended for *staging*).
- **Manual** = trigger from dashboard (recommended for *production*).
- Best-practice pattern: push → auto-deploy to **staging** → review → **manual promote to prod**.
  (Closer to a real release process than Shopify's straight-to-live `push --live`, and you can still
  do straight-to-live with auto-deploy if you want.)

---

## First, the thing that confuses everyone: ".com" vs ".org"

"WordPress" is **two different things sharing a name**, and the plan question only applies to one:

- **WordPress.com** = a *hosting company* (Automattic). The Personal/Premium/Business/Commerce plans
  are theirs. **Buy a plan and they are your host — their servers run your site.** SSH/SFTP here =
  a key to the box you're already renting *from them*, which is why they can gate it behind a tier.
- **WordPress.org** = the *free open-source software*. You install it on **any** server you rent
  (DigitalOcean, Hostinger, etc.). There, **SSH comes from your host automatically** — it's your box,
  nobody charges you for it as a "feature" — and the plan tiers below **do not exist**.

So the gating below applies **only if you let Automattic host you**. If you self-host (which is what
every competitor in [hosting-research.md](hosting-research.md) does), there is no plan tier and no SSH
gate — you SSH into *your* server, and the loop just works. What WordPress.com Business actually sells
is **managed hosting** (they run patching/backups/security/scaling); SSH is bundled into that tier.
Self-hosting trades that fee for being your own sysadmin (or paying a managed host — WP Engine /
Kinsta / Cloudways / SiteGround — to do it).

## If you let WordPress.com host you, the floor = **Business.**

The flow needs **SFTP/SSH + WP-CLI + GitHub Deployments + staging + custom code**. Here is where each
unlocks (verified against WordPress.com support docs, June 2026):

| Plan | Plugins | Upload theme files | SFTP/SSH · WP-CLI · custom code | Staging | **GitHub Deployments** | WooCommerce |
|---|---|---|---|---|---|---|
| Personal | ✅ (since Apr 2026) | ✅ | ❌ | ❌ | ❌ | install plugin only |
| Premium | ✅ | ✅ | ❌ | ❌ | ❌ | install plugin only |
| **Business** (~$25/mo, billed yearly) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ (install Woo yourself, free) |
| Commerce (~$45/mo, billed yearly) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ **bundled** + premium extensions |

**Answer to "what's the floor plan":**
- **Business is the minimum** that reproduces the full flow. Personal/Premium can now install plugins
  and upload theme zips via the admin UI, but they give **no SFTP/SSH, no WP-CLI, no GitHub
  Deployments, no staging** — so the AI/git/push-to-live loop is impossible below Business.
- **Commerce is not required for the workflow** — it's the same dev tooling as Business. Commerce only
  adds the *bundled* WooCommerce + premium store extensions + commerce features. WooCommerce core is a
  free plugin, and Business allows plugins, so you can run a full store on Business.
- **Pick Commerce only if** you want Woo + premium extensions out of the box and commerce-grade
  features bundled; otherwise **Business** is the cost floor that delivers the exact flow.

### Business + free WooCommerce vs the Commerce plan
WooCommerce is **one free plugin and identical on both plans** — WordPress.com states *none of the core
WooCommerce functionality is restricted on either plan*. Commerce = Business + Woo pre-installed +
**~25 bundled premium extensions** (Subscriptions, Bookings, Product Add-Ons, AutomateWoo, advanced
shipping, etc.; ~$1,500/yr value) + auto-updates + streamlined store admin. You are **not missing
WooCommerce** by choosing Business — you install the same free plugin and get the same store
(products/cart/checkout/payments, 0% transaction fees).

**Break-even:** Commerce is ~$240/yr more than Business. Worth it only if you'd otherwise buy >$240/yr
of those premium extensions (i.e. you need subscriptions, bookings, complex product configurators).
For a standard catalog (volume discounts, free-shipping threshold, optional shipment protection — see
[open-decisions.md](open-decisions.md)), **Business + free WooCommerce is enough**; buy extensions à la
carte only when a concrete need appears. If self-hosted, this whole question is moot — install free Woo,
buy only the extensions you need.

---

## The honest operator caveat: WordPress.com vs self-hosted

The plan question above answers the WordPress.com path. But note what the hosting research found
([hosting-research.md](hosting-research.md)): **no competitor in the sample is on WordPress.com.** They
run **self-hosted WordPress.org** on Hostinger / SiteGround / WP Engine + Cloudflare.

On self-hosted, this exact flow is **even more native and unrestricted**:
- Any host with SSH + git, or managed hosts (**WP Engine, Kinsta, Cloudways, SiteGround**) that ship
  built-in **git deploy + one-click staging→production push** — which is the *closest* analog to
  `shopify theme push --live`.
- Full control of the stack, no platform AUP gate on what you sell.

**AUP risk:** WordPress.com Business/Commerce runs under Automattic's Acceptable Use Policy, same
class of exposure as the managed hosts — a research-peptide store could draw scrutiny. Don't treat
"it's banned" as fact (competitors prove mainstream hosts *do* serve this niche), but **verify before
committing and keep a portable backup** so you can move hosts fast.

**Recommendation:**
- Want the simplest managed path *inside* the WordPress.com ecosystem → **Business** plan
  (Commerce if you want Woo bundled). GitHub Deployments + WP-CLI = your push-to-live.
- Want what competitors actually run + cheaper + zero platform AUP gate → **self-hosted on a managed
  host with git + staging** (WP Engine / Kinsta / Cloudways / SiteGround) + Cloudflare. Same AI→git→
  deploy loop, using the host's git/staging or GitHub Actions + SSH/rsync.

---

## "Most Shopify-like" spectrum

"Shopify-like" = all-in-one, one vendor/bill, never touch the server. The more managed a path is, the
more Shopify-like it feels — **and the more it re-imports Shopify's platform/AUP (deplatforming) risk.**
Those two pull in opposite directions:

```
Shopify  →  WP.com Commerce  →  WP.com Business  →  Managed WP host        →  Self-managed VPS
(today)     (most turnkey WP)   (+ full dev loop)    (WP Engine/Kinsta/Cloudways)  (DigitalOcean)
└──── more managed · more lock-in / AUP risk ────┘   └──── more control · you own + can move ────┘
```

- **WP.com Business + free WooCommerce = the most Shopify-like setup in WordPress** (one managed roof:
  hosting, SSL, backups, updates, support, payments, *and* the SSH/WP-CLI/GitHub-Deployments loop).
  Only gaps vs Shopify: you manage plugin updates yourself, and you inherit Automattic's AUP.
- **For a peptide store the sweet spot is one notch right:** a **managed WP host** (WP Engine / Kinsta /
  Cloudways) on self-hosted WooCommerce — keeps the managed, near-all-in-one feel + the git/staging
  deploy loop, but **you own the site and can move hosts in a day** if a provider gets cold feet. That
  portability is the one thing no fully-managed platform (Shopify or WP.com) can give you, and in this
  vertical it is the highest-value property. Competitors all sit on the right half for this reason.

---

*Verified 2026-06-15 against WordPress.com support + developer docs. Plan features/pricing are
point-in-time.*
