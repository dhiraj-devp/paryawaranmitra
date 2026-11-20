# UI design system

The interface is a quiet office tool. Forest green marks the active item, the primary button, and focus. Surfaces are white cards on a light grey canvas. Motion, gradients, and decoration are not part of the system.

## Colour

Defined in the `@theme` block of `resources/views/partials/assets.blade.php`.

| Token | Value | Use |
| --- | --- | --- |
| forest-700 | `#1B4332` | Primary button, active navigation, focus ring, Livewire progress bar |
| forest-800 | `#143528` | Primary hover |
| forest-900 | `#0D241C` | Login panel |
| forest-50 / 100 | `#F2F7F4` / `#E0EEE6` | Quiet highlights |
| canvas | `#F4F6F5` | Page background |
| white | `#FFFFFF` | Cards, sidebar, header |
| line | `#E4E7E6` | Borders |
| ink | `#1A1D1B` | Primary text |
| muted | `#5C6560` | Secondary text |

Status colours stay flat: green for active or verified, amber for pending, red for inactive or failed, grey for later or draft. Badges use the `x-ui.badge` component.

## Type

- English: Inter, weights 400–700, self-hosted in `public/fonts`.
- Marathi: Noto Sans Devanagari, weights 400–700, applied when `html[lang="mr"]`.
- Page titles are `text-xl` or `text-2xl`, font weight 600.
- Labels are `text-sm`. Helper text uses the muted colour.
- Dates use `d-m-Y H:i`.

## Layout

- Desktop: fixed-width sidebar (`18rem`) and a sticky header. Collapsed width is `4.5rem`, icons only.
- Mobile: the sidebar is off-canvas. The checkbox `#nav-open` opens it. A backdrop label closes it. The header wraps search onto a second row.
- The sidebar is forest-900. The current item is forest-700. Unavailable items stay lighter so they read as later.
- Content uses the full width beside the sidebar, with the same spacing scale (`p-4`, `p-6`, `gap-4`, `gap-6`).
- Cards are `x-ui.card`: white, 1px line border, small radius, no shadow heavier than a hairline.

## Components

All live in `resources/views/components`.

| Component | Purpose |
| --- | --- |
| `x-ui.button` | Primary, secondary, and quiet actions. Renders a link or a button. |
| `x-ui.input` | Label, control, and error for text fields. |
| `x-ui.card` | Surface for a section. |
| `x-ui.badge` | Status. |
| `x-ui.alert` | Error and notice banners. |
| `x-ui.empty-state` | A short title and explanation when a list is empty. |
| `x-ui.table` | Header slot and body. Tables scroll horizontally on small screens. |
| `x-ui.page-header` | Title, description, and optional actions. |
| `x-ui.icon` | Stroke icons used by navigation and the header. |
| `x-nav.sidebar` | Groups and items from `NavigationBuilder`. |
| `x-nav.header` | Search, Quick Create, language, user, logout. |
| `x-nav.language-switcher` | POST form. Works without JavaScript. |

Layouts are `resources/views/layouts/guest.blade.php` for sign-in and `resources/views/layouts/app.blade.php` for the shell. Both accept a slot and a `@yield('content')` section.

## Forms and tables

- One column on small screens, two columns only when the fields are short and related.
- Required fields are marked in the label.
- Validation errors sit under the field and are summarised at the top when Livewire reports them.
- Tables show an empty state rather than a blank grid.
- Destructive actions are not drawn as primary buttons. This phase has no delete buttons.

## Search and Quick Create

Search is a GET form. Fewer than two characters asks for a longer query. Results are grouped by source. Quick Create is a disclosure. With an empty action list it states that nothing can be created yet.

## Accessibility

- Controls have visible labels.
- Focus uses the forest ring.
- The sidebar collapse control and language select have accessible names.
- Colour is not the only status signal. Badges include text.
- The mobile drawer can be closed from the backdrop and does not depend on Alpine.

## Pages that exist

Home portal chooser, admin login, staff login, both dashboards, clients and industry units, services, fee schedules, enquiries, work orders, and quotations in both portals, admin users, admin settings, admin audit log, both search pages, the customer holding page, and errors 403, 404, and 419.
