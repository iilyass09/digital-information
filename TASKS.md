# TASKS — Digital Information Board

> Gunakan file `PRD.md` sebagai source of truth.
>
> Setelah menyelesaikan task, ubah `[ ]` menjadi `[x]`.
>
> Jangan mengerjakan phase berikutnya sebelum phase sebelumnya stabil, kecuali ada dependency yang memang mengharuskan paralel.

---

# Phase 0 — Project Inspection

- [x] Inspect existing repository structure
- [x] Inspect current Laravel version
- [x] Inspect current PHP version
- [x] Inspect current Node/npm version
- [x] Inspect existing React/Vite setup
- [x] Inspect existing authentication
- [x] Inspect existing routes
- [x] Inspect existing database configuration
- [x] Confirm database is MySQL
- [x] Confirm no SQLite dependency is being used
- [x] Check existing package conventions before adding dependencies
- [x] Document relevant findings in `README.md`

### Phase 0 Done When

- [x] Existing architecture is understood
- [x] Technology conflicts are identified
- [x] Implementation approach is documented

---

# Phase 1 — Foundation

## Laravel

- [x] Configure Laravel application
- [x] Configure MySQL
- [x] Create `.env.example`
- [x] Verify database connection
- [x] Configure API routing
- [x] Configure web routing
- [x] Configure storage

## React + Vite

- [x] Verify React installation
- [x] Verify Vite build
- [x] Configure React entry point
- [x] Establish frontend folder structure
- [x] Establish reusable component structure
- [x] Establish API service structure

## Authentication

- [x] Implement admin authentication
- [x] Username-based admin login
- [x] Local admin credentials: username/password admin / admin
- [x] Implement `/admin/login`
- [x] Implement `/admin` protected route
- [x] Implement admin role middleware
- [x] Implement logout
- [x] Verify unauthenticated admin access is rejected

## Routing

- [x] `/` → Digital Board
- [x] `/admin/login` → Admin Login
- [x] `/admin` → Admin Dashboard
- [x] `/admin/promotions`
- [x] `/admin/achievements`
- [x] `/admin/live-hosts`
- [x] `/admin/birthdays`
- [x] `/admin/display-preview`

### Phase 1 Verification

- [x] Laravel starts successfully
- [x] React/Vite builds successfully
- [x] MySQL connection works
- [x] Admin can login
- [x] `/` does not require login
- [x] `/admin` requires admin authentication

---

# Phase 2 — Database

## Migrations

- [x] Update users table with role
- [x] Add username field for username-based login
- [x] Create promotions table
- [x] Create achievements table
- [x] Create live_channels table
- [x] Create live_hosts table
- [x] Create birthdays table

## Models

- [x] Create Promotion model
- [x] Create Achievement model
- [x] Create LiveChannel model
- [x] Create LiveHost model
- [x] Create Birthday model
- [x] Configure fillable/casts
- [x] Configure relationships

## Relationships

- [x] LiveChannel hasMany LiveHosts
- [x] LiveHost belongsTo LiveChannel

## Seeders

- [x] Create admin user
- [x] Seed 8 live channels
- [x] Seed sample promotions
- [x] Seed sample achievements
- [x] Seed sample live host schedules
- [x] Seed sample birthdays

## Database Verification

- [x] `php artisan migrate` succeeds
- [x] `php artisan db:seed` succeeds
- [x] All expected tables exist
- [x] MySQL is the active database
- [x] No SQLite configuration remains

---

# Phase 3 — Admin CMS

## Admin Layout

- [x] Create admin layout
- [x] Create sidebar/navigation
- [x] Create responsive mobile navigation
- [x] Create dashboard summary cards
- [x] Add logout action

## Dashboard

- [x] Active promotions count
- [x] Active achievements count
- [x] Today's host count
- [x] Today's birthday count

---

## Promotions

- [x] Promotion list page
- [x] Promotion create page
- [x] Promotion edit page
- [x] Promotion delete action
- [x] Promotion activate/deactivate
- [x] Banner upload
- [x] Image validation
- [x] Date validation
- [x] Sort order
- [x] Empty state
- [x] Success/error feedback

---

## Achievements

- [x] Achievement list page
- [x] Achievement create page
- [x] Achievement edit page
- [x] Achievement delete action
- [x] Achievement activate/deactivate
- [x] Employee name field
- [x] Division field
- [x] Achievement title
- [x] Description
- [x] Image upload
- [x] Achievement date
- [x] Sort order
- [x] Empty state
- [x] Success/error feedback

---

## Live Host

- [x] Live host list page
- [x] Date filter
- [x] Channel filter
- [x] Create schedule
- [x] Edit schedule
- [x] Delete schedule
- [x] Select live channel
- [x] Host name
- [x] Date
- [x] Start time
- [x] End time
- [x] Active/inactive
- [x] Sort order
- [x] Empty state
- [x] Success/error feedback

### Verify 8 Channels

- [x] Johen PUBG
- [x] Johen MLBB
- [x] Johen Roblox
- [x] Johen Valorant
- [x] Johen Free Fire
- [x] Johen E-Football
- [x] Johen FC Mobile
- [x] Monkey PUBG

---

## Birthday

- [x] Birthday list page
- [x] Create birthday
- [x] Edit birthday
- [x] Delete birthday
- [x] Employee name
- [x] Division
- [x] Birth date
- [x] Image upload
- [x] Active/inactive
- [x] Empty state
- [x] Success/error feedback

---

# Phase 4 — API

## Admin API

- [x] Promotion CRUD API
- [x] Achievement CRUD API
- [x] Live host CRUD API
- [x] Birthday CRUD API
- [x] Admin API authentication
- [x] Admin API authorization

## Public Display API

- [x] Create `GET /api/display`
- [x] Return active promotions
- [x] Apply promotion date filtering
- [x] Return active achievements
- [x] Return today's live host schedules
- [x] Return today's birthdays
- [x] Return consistent JSON structure
- [x] Return only data needed by display
- [x] Handle empty datasets

Expected structure:

```json
{
  "success": true,
  "data": {
    "promotions": [],
    "achievements": [],
    "live_hosts": [],
    "birthdays": []
  }
}
```

## API Verification

- [x] Test public endpoint without login
- [x] Test admin endpoint without authentication
- [x] Test admin endpoint with non-admin user
- [x] Test valid admin request
- [x] Test empty data
- [x] Test invalid data
- [x] Test image upload
- [x] Test date filtering

---

# Phase 5 — Digital Display

## Base Layout

- [x] Create `/` Digital Board
- [x] Full viewport
- [x] Hide scrollbar
- [x] Prevent horizontal overflow
- [x] Prevent vertical overflow
- [x] No normal website navbar
- [x] No normal website footer
- [x] Responsive viewport
- [x] TV-friendly typography

---

## Display Data

- [x] Fetch `/api/display`
- [x] Store display data in React state
- [x] Handle loading state
- [x] Handle API error
- [x] Handle empty state
- [x] Handle image error

---

## Slide System

- [x] Create slide state
- [x] Create 5-second rotation
- [x] Clean up interval on unmount
- [x] Loop continuously
- [x] Skip empty categories
- [x] Prevent unnecessary re-rendering

Expected flow:

```text
Promotions
↓ 5s
Achievements
↓ 5s
Live Hosts
↓ 5s
Birthdays
↓ 5s
Promotions
...
```

---

## Promotion Slide

- [x] Full-screen banner presentation
- [x] Image fit correctly
- [x] No distortion
- [x] Multiple promotion support
- [x] Internal promotion carousel if needed
- [x] Smooth transition

---

## Achievement Slide

- [x] Large employee image
- [x] Employee name
- [x] Achievement title
- [x] Division
- [x] Description
- [x] Support multiple achievements
- [x] Readable from distance

---

## Live Host Slide

- [x] Display 8 channels
- [x] Display today's schedule
- [x] Display up to 4 hosts per channel
- [x] Display host name
- [x] Display start/end time
- [x] Responsive grid
- [x] Readable at 1920×1080
- [x] Readable at 1280×720

---

## Birthday Slide

- [x] Display today's birthdays
- [x] Employee photo
- [x] Employee name
- [x] Division
- [x] Support multiple birthdays
- [x] Skip category if no birthdays

---

# Phase 6 — Data Refresh & Fullscreen

## Data Polling

- [x] Implement 30–60 second polling
- [x] Do not poll every 5 seconds
- [x] Clean up polling interval
- [x] Replace stale data correctly
- [x] Handle polling failure gracefully

## Fullscreen

- [x] Add fullscreen action
- [x] Use Fullscreen API
- [x] Hide controls when fullscreen
- [x] Verify display works without fullscreen
- [x] Verify display works in fullscreen

---

# Phase 7 — Visual Polish

## Display

- [x] Establish typography hierarchy
- [x] Improve spacing
- [x] Improve contrast
- [x] Improve card hierarchy
- [x] Add smooth transitions
- [x] Avoid excessive animation
- [x] Ensure readability from distance
- [x] Ensure no content is clipped

## Responsive

Test:

- [x] 1280×720
- [x] 1366×768
- [x] 1920×1080
- [x] 3840×2160
- [x] Desktop browser
- [x] Laptop browser
- [ ] TV/browser environment

---

# Phase 8 — Error Handling

- [x] API failure fallback
- [x] Automatic retry
- [x] Image fallback
- [x] Empty promotion handling
- [x] Empty achievement handling
- [x] Empty host handling
- [x] Empty birthday handling
- [x] Empty-all-data handling
- [x] Admin form validation
- [x] Upload validation
- [x] Server-side validation
- [x] User-friendly error messages

---

# Phase 9 — Performance & Long-Running Test

- [x] Check React memory usage
- [x] Check timer cleanup
- [x] Check polling cleanup
- [x] Check unnecessary API requests
- [ ] Check unnecessary image requests
- [ ] Check unnecessary re-renders
- [x] Check browser console for errors
- [ ] Run display continuously for extended period
- [x] Verify slides continue rotating
- [x] Verify data polling continues
- [x] Verify no progressive UI degradation

---

# Phase 10 — Final QA

## Admin

- [x] Login
- [x] Logout
- [x] Dashboard
- [x] Promotion CRUD
- [x] Achievement CRUD
- [x] Live Host CRUD
- [x] Birthday CRUD
- [x] Image upload
- [x] Validation
- [x] Authorization

## Public Display

- [x] Opens directly from `/`
- [x] No login
- [x] Fullscreen-ready
- [x] 5-second rotation
- [x] Correct data
- [x] Today's data
- [x] Empty categories skipped
- [x] API refresh works
- [x] Error handling works
- [x] No scrollbar
- [x] No overflow
- [x] TV responsive
- [x] Desktop responsive

## Database

- [x] MySQL verified
- [x] Migrations clean
- [x] Seeders clean
- [x] No SQLite
- [x] `.env.example` correct

## Build

- [x] Production build succeeds
- [x] Laravel tests pass where available
- [x] Frontend build succeeds
- [x] No critical console errors
- [x] No critical PHP errors

---

# Phase 11 — Documentation

- [x] Update README installation instructions
- [x] Document MySQL setup
- [x] Document `.env`
- [x] Document migration
- [x] Document seeding
- [x] Document storage link
- [x] Document development commands
- [x] Document production build
- [x] Document admin login
- [x] Document display URL
- [x] Document important architecture decisions

---

# Final Definition of Done

- [ ] All PRD acceptance criteria satisfied
- [ ] All critical tasks completed
- [x] MySQL confirmed
- [x] Admin CMS works
- [x] Public display works without login
- [x] Display rotates every 5 seconds
- [x] Display refreshes data automatically
- [x] Display handles empty/error states
- [x] Display is responsive for TV
- [ ] Display can run for long periods
- [x] Production build succeeds
- [x] README is up to date
