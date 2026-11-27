# Notes — Saved-search alerts

## Decisions

- **What you can save:** same filters as the listings page (type, max price, min bedrooms, area). Empty means “any”. You need at least one filter so “alert me to everything” isn’t accidental. Later I’d add min/max on price and bedrooms.
- **How you save:** “Save search” on the listings filter form — one place to set criteria.
- **No backfill:** saving a search doesn’t alert on homes already live. That would flood the inbox and users just seen those results.
- **When we match:** only when a listing goes live (created live, or status changes to live).
- **No duplicate spam:** one alert per person per listing. If two of your searches match the same home, you get one alert (tied to the earliest search).
- **What an alert is:** a row in the database, created straight  (no email/queue in this demo).
- **Auth:** the demo stub’s docs suggest `$request->user()` and `auth()->user()` are the same; only the request side is wired, so we use `$request->user()`.

## Queues (not built)

Matching runs in the request today. Next step:

1. dispatches a job when a listing goes live (instead of matching inline).
2. That job creates the alerts rows.
3. A follow-up job/notification sends email per new alert.
4. If there are many saved searches, split the work by user chunks so one go-live doesn’t become one huge job.

## Left out / wouldn’t hold up yet

- Email, mark alert as read, editing a search in place.
- Expanding filters i.e would add range for price and number of bedrooms.
- Pagination and limts on saved searches / alerts —  add if volumes grow.
- Matcher loads all searches in PHP — fine here; at scale push criteria into SQL and/or jobs.
- **Scaffold N+1:** listings index/show resolve `branch` per row in `ListingResource` with no eager load.

## AI

Used Cursor to explore the existing Laravel/Inertia patterns and explain the app. I asked it to draft a plan from `TASK.md`, reviewed and edited that plan, then asked it to implement. Once built, I reviewed the code and tested the work, fixing anything that fell short.
