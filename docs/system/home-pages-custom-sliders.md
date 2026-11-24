# Task: Build a Dynamic Spotlight Product Carousel with Progressive Card Scaling

Act as a Senior Frontend Engineer specializing in advanced JavaScript animations, interactive carousels, CSS transforms, and premium e-commerce UI experiences.

I need you to inspect my existing project and implement a highly polished, responsive product carousel with a dynamic spotlight/focus effect.

## 1. Core Concept

Build a horizontal, continuously interactive carousel where product cards dynamically change their width, height, scale, depth, and visual prominence based on their position.

This is NOT a standard carousel where every card has the same dimensions.

The main concept is:

- One product card is visually dominant and significantly larger than the others.
- Cards progressively become smaller as they move away from the active card.
- The active card should feel closer to the user, while surrounding cards appear slightly behind it, creating a sophisticated 3D depth / pyramid-like composition.
- When the user scrolls, drags, or navigates, the next card smoothly becomes the dominant card.
- All size and position transitions must happen dynamically and continuously, not just when the active index changes.

## 2. Required Layout Variations

Implement support for TWO different visual configurations.

### Variation A: Center Spotlight

The active product card is positioned in the center and is significantly larger than surrounding cards.

Visual behavior:

- The center card is the largest card.
- Cards immediately adjacent to it are smaller.
- Cards further away become progressively smaller.
- The composition should resemble a pyramid or depth-based visual hierarchy.

Example visual hierarchy:

```text
       Small    Medium    LARGE    Medium    Small
```

The active card should have:
- Maximum width and height.
- Higher visual prominence.
- Higher z-index.
- Optional subtle shadow and depth.
- Smooth scaling and positioning.

### Variation B: Left Spotlight

The active product card is positioned toward the left side of the carousel.

Visual behavior:

- The leftmost active card is significantly larger.
- Cards to its right progressively decrease in size.
- The farther a card is from the active card, the smaller and deeper it appears.
- The active card should visually dominate the section.

Example:

```text
LARGE    Medium    Small    Smaller    Tiny
```

The carousel must support both configurations through a simple configuration option.

## 3. Dynamic Interaction

This is the most important requirement.

The carousel must dynamically calculate the visual appearance of every card based on its distance from the active card.

When the user:

- Drags the carousel.
- Uses the mouse wheel or trackpad.
- Swipes on mobile.
- Clicks navigation arrows.
- Clicks a product card.

The cards should smoothly transition between their different sizes and positions.

For example:

When Card 3 is active:

```text
Small | Medium | LARGE | Medium | Small
```

After moving to Card 4:

```text
Small | Small | Medium | LARGE | Medium
```

The transition should feel natural and fluid, with no sudden jumps or layout shifts.

Use requestAnimationFrame or an efficient animation mechanism if necessary.

## 4. Technical Implementation

You may use Swiper.js if it is the best solution.

Evaluate the following options:

- Swiper.js with custom progress-based effects.
- JavaScript with CSS transforms.
- CSS 3D transforms using perspective, translateZ, and scale.
- A custom carousel implementation if Swiper cannot achieve the required behavior cleanly.

Preferred approach: Swiper.js with a custom effect or progress-based card transformations.

Important:
- Do not rely only on Swiper's built-in Coverflow effect.
- Implement custom per-card scaling and positioning logic.
- Use each slide's progress or its distance from the active slide to calculate its visual properties.
- Avoid unnecessary DOM reflows.
- Use GPU-friendly CSS transforms wherever possible.

## 5. Suggested Animation Logic

Calculate each card's distance from the active position:

```javascript
const distance = Math.abs(slideProgress);
```

Use this distance to dynamically calculate:

- Scale.
- Width and height appearance.
- Opacity.
- TranslateX.
- TranslateZ.
- Z-index.
- Optional blur.
- Shadow intensity.

The exact values should be configurable.

The animation must interpolate smoothly during dragging, not only after the slide transition finishes.

## 6. Responsive Design

The carousel must work perfectly on:

- Desktop.
- Laptop.
- Tablet.
- Mobile.

Responsive behavior:

- Desktop: Show the full depth composition with multiple surrounding cards.
- Tablet: Reduce the number of visible cards while preserving the spotlight effect.
- Mobile: Keep the active card prominent and allow natural touch/swipe interaction.
- Prevent horizontal page overflow.
- Ensure images maintain their aspect ratio.
- Avoid card clipping and unexpected layout shifts.

## 7. Configuration & Reusability

Build the carousel as a reusable component.

It should support configuration such as:

```javascript
{
    layout: 'center', // center | left
    autoplay: false,
    loop: true,
    speed: 700,
    spacing: 20,
    maxScale: 1.25,
    minScale: 0.65,
    perspective: 1200,
    enableMouseWheel: true,
    enableTouch: true
}
```

These are suggested options; adjust the implementation as needed.

It should be easy to reuse the component in different product sections with different datasets and visual configurations.

## 8. Design Quality

The final result should look like a premium modern e-commerce experience.

Pay attention to:

- Smooth transitions.
- Natural depth perception.
- Balanced spacing.
- Proper layering.
- High-quality shadows.
- Consistent image proportions.
- No visual flickering.
- No abrupt size changes.
- No unnecessary animations.

The active card must be immediately recognizable without making surrounding cards completely disappear.

## 9. Project Integration Requirements

Before writing any code:

1. Inspect the existing project structure.
2. Identify the frontend framework and current JavaScript setup.
3. Identify whether Swiper.js is already installed.
4. Inspect existing product sections and reusable components.
5. Understand the existing CSS architecture and design system.
6. Identify any existing carousel implementation that should be reused or improved.

Do not introduce unnecessary dependencies.

Do not break existing sections, styling, functionality, or responsive behavior.

Follow the existing project's coding conventions.

## 10. Implementation and Testing

Implement the actual working solution in the existing project.

Do not stop at a demo, mockup, explanation, or pseudocode.

Test the following scenarios:

- Center spotlight layout.
- Left spotlight layout.
- Dragging between cards.
- Mouse wheel interaction.
- Touch interaction.
- Active card synchronization.
- Loop behavior.
- Responsive behavior.
- Multiple carousel instances on the same page.

Verify that the active card changes smoothly and that all other cards adjust their dimensions and depth according to their relative positions.

## Final Deliverables

Provide:

1. The implemented reusable carousel component.
2. The JavaScript animation logic.
3. The required CSS.
4. Two working examples: Center Spotlight and Left Spotlight.
5. A summary of modified and created files.
6. Any required dependency installation commands.
7. Testing results and any remaining limitations.

**Important:** Prioritize the visual result and interaction quality over using a specific library. The goal is to achieve a genuinely dynamic, progressive-size spotlight carousel, not a conventional equal-sized slider or a basic Coverflow effect.
