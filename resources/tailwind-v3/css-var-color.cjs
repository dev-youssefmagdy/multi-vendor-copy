// Tailwind v3 color backed by a CSS custom property, with the original value as fallback so the
// design is unchanged until a tenant overrides the variable (Appearance > Colors).
// `<alpha-value>` keeps opacity modifiers (bg-primary/50) working.
module.exports = (name, fallback) => `color-mix(in srgb, var(${name}, ${fallback}) calc(<alpha-value> * 100%), transparent)`;
