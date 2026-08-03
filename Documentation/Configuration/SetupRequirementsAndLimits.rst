..  include:: /Includes.rst.txt

..  _setup-requirements-and-limits:

===================================
Setup requirements & limits
===================================

A single reference for operational topics that affect whether - and how
reliably - frontend editing works in a given setup. Several :ref:`FAQ <faq>`
entries link here instead of repeating this content.

Multi-domain setups
======================

Frontend editing needs an active TYPO3 backend session in the browser that
is viewing the frontend. Whether that session is available depends on
domain/cookie configuration:

**Session cookie reach (``cookieDomain``)**

The backend session cookie is only sent to the domain(s) it was issued for.
If the frontend and backend live on different (sub)domains, set
`cookieDomain <https://docs.typo3.org/m/typo3/reference-coreapi/13.4/en-us/Configuration/Typo3ConfVars/SYS.html#confval-globals-typo3-conf-vars-sys-cookiedomain>`__
so the cookie is shared across them.

**SameSite**

A restrictive `cookieSameSite <https://docs.typo3.org/m/typo3/reference-coreapi/13.4/en-us/Configuration/Typo3ConfVars/SYS.html#confval-globals-typo3-conf-vars-sys-cookiesamesite>`__
setting can also prevent the backend session cookie from being sent on the
frontend request, with the same symptom as a cookie domain mismatch: the
Edit Menu never appears, with no error visible in the frontend.

**Cross-domain backend login**

If sharing a cookie domain is not an option (fully separate domains, not
subdomains of one another), use the
`multisite_belogin <https://extensions.typo3.org/extension/multisite_belogin>`__
extension, which provides backend login support across multiple domains
without a shared cookie domain.

**``returnUrl`` behavior**

The ``returnUrl`` passed to backend edit routes is validated against the
current request's host and the site's configured base hosts (including
language bases); a foreign host is rejected rather than followed. In a
cross-domain setup (frontend and backend on different domains), this can
mean a ``returnUrl`` that legitimately points at the frontend domain gets
treated as foreign to the backend request. If a strict referer header is
also masking the real frontend host - see the :ref:`FAQ <faq>` entry on
this - enable :ref:`forceReturnUrlGeneration <extconf-forceReturnUrlGeneration>`
to generate the return URL from pid/language instead of trusting the
request's own host.

External caches (Varnish, CDN)
==================================

After a hide or delete action, the extension clears TYPO3's own page cache
for the affected page - that part happens automatically. It has no way to
purge an external cache layer (Varnish, a CDN) sitting in front of TYPO3,
since that requires cache-tag/ban integration specific to that layer. If
such a cache is in front of your site, the redirect after hide/delete may
briefly show a stale version of the page until that external cache's own
TTL or invalidation logic catches up.

Anchor pattern requirement (and the headless/SPA non-goal)
================================================================

Matching a content element in the frontend HTML to its backend record
requires either the ``id="c{uid}"`` anchor pattern or the
``data-frontend-edit="{table}:{uid}"`` attribute to be present in the
rendered markup - see :ref:`How it works <how-it-works>` for both patterns
and how to add them to a custom template.

Headless/SPA frontends are an explicit non-goal: the script that performs
this matching is injected server-side into TYPO3's own rendered HTML (via a
PSR-15 middleware). It never runs for a frontend that TYPO3 itself does not
render HTML for - there is no client-side integration path for a
JSON-API/decoupled frontend.

Preview links (``ADMCMD_prev``)
====================================

A backend preview link (:literal:`ADMCMD_prev=...`) lets someone view a
page - e.g. an unpublished draft - without a full backend login. No frontend
editing UI is shown for such a viewer: there is no active backend user
session behind an ``ADMCMD_prev`` link, and the extension's access checks
require one. This is expected behavior, not a bug - a preview link is
meant for reviewing content, not editing it.
